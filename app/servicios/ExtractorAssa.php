<?php

namespace app\servicios;

/**
 * Boleta de agua de Aguas Santafesinas S.A. (ASSA).
 *
 * El "molde" de la boleta (logo, nombre de la empresa, CUIT y varias etiquetas como
 * "Fecha de Emisión" o "TOTAL") está dibujado, no es texto: el PDF solo trae como texto
 * los datos variables. Por eso se reconoce por textos propios de la boleta de ASSA y
 * algunos datos se ubican por lo que tienen alrededor.
 *
 * Una boleta = un bimestre, pagadero en 2 cuotas. Igual que con la EPE se guarda el total
 * del bimestre, no una cuota. Los m3 son el "VOL.FACTURADO" que informa la boleta
 * (en el régimen "no medido" es un volumen asignado por la empresa).
 */
class ExtractorAssa extends ExtractorBoleta
{
    public function reconoce(string $texto): bool
    {
        return preg_match('/SERVICIO\s*PRESTADO\s*:\s*AGUA/u', $texto) === 1
            && preg_match('/REGIMEN\s*-\s*TARIFA/u', $texto) === 1;
    }

    public function empresa(): string
    {
        return 'Aguas Santafesinas';
    }

    public function servicio(): string
    {
        return 'Agua';
    }

    public function extraer(string $texto): array
    {
        // "VOL.FACTURADO" / "22,59m3" / "09/10/2026": la fecha de emisión viene justo después
        $bloque = '/VOL\.\s*FACTURADO\s*([\d.]+,\d{2})\s*m3\s*(\d{2}\/\d{2}\/\d{4})/u';
        $cantidad = $this->buscar($bloque, $texto);
        $fecha = preg_match($bloque, $texto, $c) === 1 ? $c[2] : '';

        // Total del bimestre: único importe suelto antes de "ULTIMOS 6 PERIODOS"
        // (los de las cuotas están en los talones, más abajo)
        $importe = $this->buscar('/^\$[*\s]*([\d.]+,\d{2})\s*\n\s*ULTIMOS\s*6\s*PERIODOS/mu', $texto);

        return [
            'cantidad' => $this->numeroArgentino($cantidad),
            'importe' => $this->numeroArgentino($importe),
            'fecha' => $this->fechaArgentina($fecha),
        ];
    }
}
