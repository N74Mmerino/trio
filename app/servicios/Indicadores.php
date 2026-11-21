<?php

namespace app\servicios;

use DateTimeImmutable;

/**
 * Cálculos del dashboard.
 *
 * Funciones puras: reciben datos y devuelven resultados, sin tocar la base
 * ni la sesión. Así el controlador queda corto y esta lógica se puede probar sola.
 */
class Indicadores
{
    /** Períodos que ofrece el dashboard: clave de la URL => texto del botón. */
    public const PERIODOS = [
        '6' => '6 meses',
        '12' => '12 meses',
        'todo' => 'Todo',
    ];

    public const PERIODO_POR_DEFECTO = '12';

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * Primer día del período. "12" hoy (23/09/2026) => 2025-10-01: el mes actual más los 11 anteriores.
     * "todo" => null (sin límite).
     */
    public static function inicioPeriodo(string $periodo, ?DateTimeImmutable $hoy = null): ?string
    {
        if ($periodo === 'todo') {
            return null;
        }

        $hoy ??= new DateTimeImmutable();
        $meses = (int) $periodo - 1;

        return $hoy->modify('first day of this month')->modify("-{$meses} months")->format('Y-m-d');
    }

    /** Primer día del período anterior de igual duración. "12" => 2024-10-01. */
    public static function inicioPeriodoAnterior(string $periodo, ?DateTimeImmutable $hoy = null): ?string
    {
        $inicio = self::inicioPeriodo($periodo, $hoy);

        if ($inicio === null) {
            return null;
        }

        return (new DateTimeImmutable($inicio))->modify('-' . (int) $periodo . ' months')->format('Y-m-d');
    }

    /** Variación porcentual. null si no hay valor anterior o es 0 (no se puede dividir). */
    public static function variacion(float $actual, ?float $anterior): ?float
    {
        if ($anterior === null || $anterior == 0.0) {
            return null;
        }

        return ($actual - $anterior) / $anterior * 100;
    }

    /** Costo promedio por unidad ($/kWh o $/m³). Incluye cargos fijos e impuestos de la boleta. */
    public static function costoUnitario(float $importe, float $cantidad): ?float
    {
        return $cantidad > 0 ? $importe / $cantidad : null;
    }

    /**
     * Etapa 17: reparte la diferencia de importe entre dos boletas del mismo servicio.
     *
     * - porConsumo: lo que cambió la cantidad, al costo por unidad de la boleta anterior.
     * - porPrecio:  lo que cambió el costo por unidad, sobre la cantidad actual.
     *
     * Las dos partes suman exactamente la diferencia de importe:
     * (q1 - q0) * c0 + (c1 - c0) * q1 = c1 * q1 - c0 * q0 = importe actual - importe anterior.
     *
     * "porcentajes" (para la barra) solo cuando ninguna parte va en contra de la diferencia;
     * si una suma y la otra resta, una barra proporcional no tiene sentido y queda en null.
     * Devuelve null si alguna cantidad es 0 (no hay costo por unidad).
     */
    public static function diferenciaImporte(float $importe, float $cantidad, float $importeAnterior, float $cantidadAnterior): ?array
    {
        $costo = self::costoUnitario($importe, $cantidad);
        $costoAnterior = self::costoUnitario($importeAnterior, $cantidadAnterior);

        if ($costo === null || $costoAnterior === null) {
            return null;
        }

        $total = $importe - $importeAnterior;
        $porConsumo = ($cantidad - $cantidadAnterior) * $costoAnterior;
        $porPrecio = ($costo - $costoAnterior) * $cantidad;

        $porcentajes = null;
        if (abs($total) >= 0.01 && $porConsumo * $total >= 0 && $porPrecio * $total >= 0) {
            // abs(): las dos partes tienen el signo de la diferencia, así que el % nunca es negativo (evita un "-0 %").
            $porcentajes = [
                'consumo' => abs($porConsumo / $total * 100),
                'precio' => abs($porPrecio / $total * 100),
            ];
        }

        return [
            'total' => $total,
            'porConsumo' => $porConsumo,
            'porPrecio' => $porPrecio,
            'diferenciaCantidad' => $cantidad - $cantidadAnterior,
            'diferenciaCosto' => $costo - $costoAnterior,
            'costoAnterior' => $costoAnterior,
            'porcentajes' => $porcentajes,
        ];
    }

    /**
     * Lista de meses 'AAAA-MM' desde $desde hasta el mes de $hoy, inclusive.
     * Sirve para que los meses sin boletas aparezcan en 0 en vez de desaparecer del gráfico.
     */
    public static function mesesDesde(string $desde, ?DateTimeImmutable $hoy = null): array
    {
        $hoy ??= new DateTimeImmutable();
        $mes = (new DateTimeImmutable($desde))->modify('first day of this month');
        $ultimo = $hoy->format('Y-m');
        $meses = [];

        while ($mes->format('Y-m') <= $ultimo) {
            $meses[] = $mes->format('Y-m');
            $mes = $mes->modify('+1 month');
        }

        return $meses;
    }

    /** 'AAAA-MM' => 'Oct 25'. */
    public static function etiquetaMes(string $mes): string
    {
        [$anio, $numero] = explode('-', $mes);

        return self::MESES[(int) $numero - 1] . ' ' . substr($anio, 2);
    }

    /** '2026-07-31' => 'Jul 2026'. */
    public static function mesDeFecha(string $fecha): string
    {
        return self::MESES[(int) substr($fecha, 5, 2) - 1] . ' ' . substr($fecha, 0, 4);
    }

    /**
     * Convierte las filas de ConsumoModel::gastoMensual() al formato de Chart.js:
     * una etiqueta por mes y una serie por servicio, con 0 donde no hubo boletas.
     *
     * @param array  $filas     Filas {mes, id_servicio, total}
     * @param array  $servicios Filas de la tabla servicios
     * @param string $desde     Primer día del período
     */
    public static function seriesMensuales(array $filas, array $servicios, string $desde, ?DateTimeImmutable $hoy = null): array
    {
        $meses = self::mesesDesde($desde, $hoy);

        $totales = [];
        foreach ($filas as $fila) {
            $totales[$fila->id_servicio][$fila->mes] = (float) $fila->total;
        }

        $series = [];
        foreach ($servicios as $servicio) {
            $datos = [];
            foreach ($meses as $mes) {
                $datos[] = $totales[$servicio->id_servicio][$mes] ?? 0;
            }

            $series[] = [
                'id' => (int) $servicio->id_servicio,
                'nombre' => $servicio->nombre,
                'datos' => $datos,
            ];
        }

        return [
            'etiquetas' => array_map([self::class, 'etiquetaMes'], $meses),
            'series' => $series,
        ];
    }
}
