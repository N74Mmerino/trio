<?php

namespace app\servicios;

/**
 * Boleta (Liquidación de Servicios Públicos) de gas de Litoral Gas S.A.
 *
 * Los clientes residenciales se leen cada dos meses, pero se factura todos los meses:
 * el consumo del bimestre se reparte en dos liquidaciones ("Liquidación 1 de 2" y "2 de 2").
 * Por eso la cantidad es "m3 asignados a la presente" y no el "CONSUMO TOTAL" del bimestre:
 * así cada factura guarda lo que ella misma cobra y el bimestre no se cuenta dos veces.
 *
 * Particularidades del texto que entrega smalot/pdfparser con estas boletas:
 * - Aparecen tabulaciones en medio de palabras y números ("present\te 157.63", "618\t,75").
 *   Se quitan antes de buscar.
 * - Los m3 usan punto decimal ("157.63"), pero los importes usan formato argentino ("35.284,49").
 */
class ExtractorLitoralGas extends ExtractorBoleta
{
    public function reconoce(string $texto): bool
    {
        // Nombre de la empresa y su CUIT, que es único
        return preg_match('/Litoral\s*Gas\s*S\.A\./u', $texto) === 1
            && preg_match('/30-65786633-0/', $texto) === 1;
    }

    public function empresa(): string
    {
        return 'Litoral Gas';
    }

    public function servicio(): string
    {
        return 'Gas';
    }

    public function extraer(string $texto): array
    {
        $texto = str_replace("\t", '', $texto);

        // "m3 asignados a la presente 157.63" (punto decimal)
        $cantidad = $this->buscar('/m3\s*asignados\s*a\s*la\s*presente\s*(\d+(?:\.\d+)?)/u', $texto);

        // Talón: "0081-61860832 23/09/2026 35.284,49" (número, vencimiento, importe)
        $talon = '/^\d{4}-\d{8}\s+(\d{2}\/\d{2}\/\d{4})\s+([\d.]+,\d{2})\s*$/mu';
        $importe = preg_match($talon, $texto, $t) === 1 ? $t[2] : '';
        // Etapa 23: la factura tiene un único vencimiento, el del talón (lo confirma "TOTAL A PAGAR hasta el ...").
        $vencimiento = $importe !== '' ? $t[1] : '';

        // La etiqueta "FECHA DE EMISION:" sale separada del valor; la fecha va en la línea
        // siguiente al número de comprobante: "0081-61860832" / "03/09/2026"
        $fecha = $this->buscar('/^\d{4}-\d{8}\s*\n\s*(\d{2}\/\d{2}\/\d{4})\s*$/mu', $texto);

        return [
            'cantidad' => $cantidad,
            'importe' => $this->numeroArgentino($importe),
            'fecha' => $this->fechaArgentina($fecha),
            'vencimientos' => $vencimiento !== '' ? $this->cuotas([[$vencimiento, $importe]]) : [],
        ];
    }
}
