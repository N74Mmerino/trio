<?php

namespace app\servicios;

/**
 * Boleta de luz de la Empresa Provincial de la Energía de Santa Fe (EPE).
 *
 * Probado con las liquidaciones 3/26, 4/26 y 5/26: el PDF original (generado con FPDF)
 * y el que queda al abrirlo en el navegador y guardarlo con "Imprimir → Guardar como PDF".
 * El texto de la boleta aparece repetido (cuerpo y talones de pago), por eso cada
 * expresión busca una etiqueta exacta que solo aparece una vez.
 *
 * En el PDF guardado desde el navegador las palabras salen pegadas ("ConsumoTotal:141kWh"),
 * por eso entre palabras se acepta cualquier cantidad de espacios, incluso ninguno.
 */
class ExtractorEpe extends ExtractorBoleta
{
    public function reconoce(string $texto): bool
    {
        return preg_match('/EMPRESA\s*PROVINCIAL\s*DE\s*LA\s*ENERG[IÍ]A\s*DE\s*SANTA\s*FE/u', $texto) === 1;
    }

    public function empresa(): string
    {
        return 'EPE Santa Fe';
    }

    public function servicio(): string
    {
        return 'Luz';
    }

    public function extraer(string $texto): array
    {
        // "Consumo Total:    375 kWh" (el punto sería separador de miles: 1.234 kWh)
        $cantidad = $this->buscar('/Consumo\s*Total:\s*([\d.]+)\s*kWh/u', $texto);

        // Línea "TOTAL  $**101.456,06": total del bimestre, no el importe de una cuota
        $importe = $this->buscar('/^TOTAL\s*\$[*\s]*([\d.]+,\d{2})/mu', $texto);

        // "FECHA DE EMISION: 01/06/2026"
        $fecha = $this->buscar('/FECHA\s*DE\s*EMISION:\s*(\d{2}\/\d{2}\/\d{4})/u', $texto);

        // Etapa 23: talones de pago. "Cuota 1 23/06/2026 $*50.728,03" (en el PDF guardado desde el
        // navegador sale pegado: "Cuota114/08/2026$*21.131,50"). Es el primer vencimiento, sin recargo;
        // el "Vencimiento con Recargo" tiene otro formato y no coincide con esta expresión.
        preg_match_all('/Cuota\s*([12])\s*(\d{2}\/\d{2}\/\d{4})\s*\$[*\s]*([\d.]+,\d{2})/u', $texto, $talones, PREG_SET_ORDER);
        $pares = [];
        foreach ($talones as $talon) {
            $pares[(int) $talon[1]] ??= [$talon[2], $talon[3]];
        }
        ksort($pares);
        if (!isset($pares[1])) {
            $pares = []; // sin la cuota 1 no se precarga nada (no se puede guardar una cuota 2 sola)
        }

        return [
            'cantidad' => $this->numeroArgentino($cantidad),
            'importe' => $this->numeroArgentino($importe),
            'fecha' => $this->fechaArgentina($fecha),
            'vencimientos' => $this->cuotas($pares),
        ];
    }
}
