<?php

namespace app\servicios;

use Smalot\PdfParser\Document;
use Smalot\PdfParser\Element\ElementName;
use Smalot\PdfParser\Encoding;
use Smalot\PdfParser\Font;
use Smalot\PdfParser\Header;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Lee el texto de una boleta en PDF y lo entrega al extractor de la empresa que corresponda.
 *
 * Para sumar una empresa nueva alcanza con crear su extractor y agregarlo en extractores().
 */
class LectorBoletas
{
    /** @return ExtractorBoleta[] */
    private static function extractores(): array
    {
        return [
            new ExtractorEpe(),
            new ExtractorLitoralGas(),
            new ExtractorAssa(),
        ];
    }

    /**
     * Devuelve los datos de la boleta o null si no se pudo leer / la empresa no se reconoce.
     *
     * Resultado: ['empresa' => ..., 'servicio' => ..., 'cantidad' => ..., 'importe' => ..., 'fecha' => ...,
     *             'vencimientos' => [['fecha' => ..., 'importe' => ...], ...]]  (Etapa 23)
     */
    public static function leer(string $rutaPdf): ?array
    {
        if (!class_exists(Parser::class)) {
            throw new \RuntimeException('Falta instalar la librería smalot/pdfparser (composer require smalot/pdfparser).');
        }

        try {
            $documento = (new Parser())->parseFile($rutaPdf);
            self::corregirCodificaciones($documento);
            $texto = $documento->getText();
        } catch (Throwable $e) {
            // PDF dañado, protegido o que no es un PDF de texto
            return null;
        }

        foreach (self::extractores() as $extractor) {
            if ($extractor->reconoce($texto)) {
                return [
                    'empresa' => $extractor->empresa(),
                    'servicio' => $extractor->servicio(),
                ] + $extractor->extraer($texto);
            }
        }

        return null;
    }

    /**
     * Arregla las fuentes cuya codificación smalot/pdfparser no sabe interpretar.
     *
     * Cuando la boleta se abre en el navegador y se guarda con "Imprimir → Guardar como PDF"
     * (Firefox, Chrome), las fuentes quedan con una codificación propia: un diccionario
     * /Encoding con /Differences (qué letra es cada código) pero sin /BaseEncoding.
     * smalot ignora ese caso y devuelve los códigos crudos, así que el texto sale ilegible.
     *
     * Acá se reemplaza cada una de esas fuentes por una copia cuya codificación sí entiende:
     * la misma tabla /Differences sobre StandardEncoding (que solo cubre los códigos que la
     * tabla no define).
     */
    private static function corregirCodificaciones(Document $documento): void
    {
        $objetos = $documento->getObjects();
        $cambios = false;

        foreach ($objetos as $id => $objeto) {
            if (!$objeto instanceof Font) {
                continue;
            }

            $codificacion = $objeto->getHeader()->get('Encoding');

            if (!$codificacion instanceof Header || !$codificacion->has('Differences') || $codificacion->has('BaseEncoding')) {
                continue;
            }

            $encoding = new Encoding(
                $documento,
                new Header($codificacion->getElements() + ['BaseEncoding' => new ElementName('StandardEncoding')], $documento),
                '',
                $objeto->getConfig()
            );
            $encoding->init();

            $clase = get_class($objeto);
            $fuente = new $clase(
                $documento,
                new Header(['Encoding' => $encoding] + $objeto->getHeader()->getElements(), $documento),
                $objeto->getContent(),
                $objeto->getConfig()
            );
            $fuente->init();

            $objetos[$id] = $fuente;
            $cambios = true;
        }

        if ($cambios) {
            $documento->setObjects($objetos);
        }
    }
}
