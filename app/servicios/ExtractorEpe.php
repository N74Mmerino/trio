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

        return [
            'cantidad' => $this->numeroArgentino($cantidad),
            'importe' => $this->numeroArgentino($importe),
            'fecha' => $this->fechaArgentina($fecha),
        ];
    }
}
