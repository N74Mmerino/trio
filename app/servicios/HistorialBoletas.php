<?php

namespace app\servicios;

use DateTimeImmutable;

/**
 * Cálculos de "Mis boletas" y de la ficha de cada boleta (Etapa 17).
 *
 * Igual que Indicadores: funciones puras, que reciben datos y devuelven resultados
 * sin tocar la base ni la sesión. Así el controlador queda corto y esta lógica se puede probar sola.
 */
class HistorialBoletas
{
    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /** Cuántas boletas muestra el gráfico de la ficha (la que se ve y las anteriores). */
    public const BOLETAS_EN_GRAFICO = 6;

    /** '2026-08-15' => 'agosto 2026'. */
    public static function mes(string $fecha): string
    {
        return self::MESES[(int) substr($fecha, 5, 2) - 1] . ' ' . substr($fecha, 0, 4);
    }

    /**
     * Prepara las boletas del listado. A cada una le agrega:
     * - la variación de consumo y de importe contra la boleta anterior DEL MISMO SERVICIO;
     * - "barra": su consumo como % de la boleta más alta de ese servicio (0 a 100).
     *
     * @param array $boletas Filas de ConsumoModel::listarDelUsuario(), en orden cronológico.
     */
    public static function conComparaciones(array $boletas): array
    {
        $maximos = [];
        foreach ($boletas as $boleta) {
            $id = (int) $boleta->id_servicio;
            $maximos[$id] = max($maximos[$id] ?? 0.0, (float) $boleta->cantidad);
        }

        $anteriores = [];
        $resultado = [];

        foreach ($boletas as $boleta) {
            $idServicio = (int) $boleta->id_servicio;
            $cantidad = (float) $boleta->cantidad;
            $importe = (float) $boleta->importe;
            $anterior = $anteriores[$idServicio] ?? null;

            $resultado[] = [
                'id' => (int) $boleta->id_consumo,
                'idServicio' => $idServicio,
                'servicio' => $boleta->servicio,
                'unidad' => $boleta->unidad,
                'fecha' => $boleta->fecha,
                'cantidad' => $cantidad,
                'importe' => $importe,
                'costo' => Indicadores::costoUnitario($importe, $cantidad),
                'tienePdf' => $boleta->archivo !== null,
                'esPrimera' => $anterior === null,
                'variacionCantidad' => Indicadores::variacion($cantidad, $anterior['cantidad'] ?? null),
                'variacionImporte' => Indicadores::variacion($importe, $anterior['importe'] ?? null),
                'barra' => $maximos[$idServicio] > 0 ? (int) round($cantidad / $maximos[$idServicio] * 100) : 0,
            ];

            $anteriores[$idServicio] = ['cantidad' => $cantidad, 'importe' => $importe];
        }

        return $resultado;
    }

    /** Años en los que hay boletas, del más nuevo al más viejo. */
    public static function anios(array $boletas): array
    {
        $anios = array_unique(array_map(fn ($boleta) => (int) substr($boleta['fecha'], 0, 4), $boletas));
        rsort($anios);

        return $anios;
    }

    /** Las boletas de un año (y de un servicio, si se indica), de la más nueva a la más vieja. */
    public static function filtrar(array $boletas, int $anio, ?int $idServicio): array
    {
        $filtradas = array_values(array_filter(
            $boletas,
            fn ($boleta) => (int) substr($boleta['fecha'], 0, 4) === $anio
                && ($idServicio === null || $boleta['idServicio'] === $idServicio)
        ));

        usort($filtradas, fn ($a, $b) => [$b['fecha'], $b['id']] <=> [$a['fecha'], $a['id']]);

        return $filtradas;
    }

    /** Agrupa por el mes de la boleta, con el total pagado de cada mes. Respeta el orden recibido. */
    public static function agruparPorMes(array $boletas): array
    {
        $grupos = [];

        foreach ($boletas as $boleta) {
            $clave = substr($boleta['fecha'], 0, 7);

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = ['titulo' => ucfirst(self::mes($boleta['fecha'])), 'boletas' => [], 'total' => 0.0];
            }

            $grupos[$clave]['boletas'][] = $boleta;
            $grupos[$clave]['total'] += $boleta['importe'];
        }

        return array_values($grupos);
    }

    /** Resumen de las boletas que se están mostrando: cantidad por servicio, total pagado y la más cara. */
    public static function resumen(array $boletas): array
    {
        $porServicio = [];
        $total = 0.0;
        $masCara = null;

        foreach ($boletas as $boleta) {
            $porServicio[$boleta['idServicio']] ??= ['nombre' => $boleta['servicio'], 'cantidad' => 0];
            $porServicio[$boleta['idServicio']]['cantidad']++;
            $total += $boleta['importe'];

            if ($masCara === null || $boleta['importe'] > $masCara['importe']) {
                $masCara = $boleta;
            }
        }

        ksort($porServicio);

        return [
            'cantidad' => count($boletas),
            'porServicio' => array_values($porServicio),
            'total' => $total,
            'masCara' => $masCara,
        ];
    }

    /**
     * Ubica una boleta dentro del historial de su servicio, para la ficha.
     *
     * "Último año" = las boletas de los 12 meses previos a la fecha de ESTA boleta, ella incluida.
     * Se cuenta desde la boleta y no desde hoy, así una boleta vieja muestra siempre lo mismo.
     *
     * @param array $historial Boletas del mismo servicio en orden cronológico (ConsumoModel::historialServicio()).
     * @return array|null null si la boleta no está en el historial.
     */
    public static function contexto(array $historial, int $idConsumo): ?array
    {
        $posicion = null;
        foreach ($historial as $i => $boleta) {
            if ((int) $boleta->id_consumo === $idConsumo) {
                $posicion = $i;
                break;
            }
        }

        if ($posicion === null) {
            return null;
        }

        $actual = $historial[$posicion];
        $cantidad = (float) $actual->cantidad;

        // Gráfico: esta boleta y hasta 5 anteriores.
        $desdeGrafico = max(0, $posicion - (self::BOLETAS_EN_GRAFICO - 1));
        $grafico = array_slice($historial, $desdeGrafico, $posicion - $desdeGrafico + 1);

        // Último año, contado hacia atrás desde la fecha de esta boleta.
        $haceUnAnio = (new DateTimeImmutable($actual->fecha))->modify('-12 months')->format('Y-m-d');
        $ultimoAnio = array_values(array_filter(
            array_slice($historial, 0, $posicion + 1),
            fn ($boleta) => $boleta->fecha > $haceUnAnio
        ));

        $posicionAnual = null;
        if (count($ultimoAnio) >= 2) {
            $mayor = $ultimoAnio[0];
            $suma = 0.0;
            $mayores = 0;
            $menores = 0;

            foreach ($ultimoAnio as $boleta) {
                $suma += (float) $boleta->cantidad;

                if ((float) $boleta->cantidad > (float) $mayor->cantidad) {
                    $mayor = $boleta;
                }
                if ((float) $boleta->cantidad > $cantidad) {
                    $mayores++;
                } elseif ((float) $boleta->cantidad < $cantidad) {
                    $menores++;
                }
            }

            $promedio = $suma / count($ultimoAnio);

            $posicionAnual = [
                'boletas' => count($ultimoAnio),
                // Puesto de mayor a menor consumo. Con empate, comparten el mejor puesto.
                'puesto' => $mayores + 1,
                'esLaMasBaja' => $menores === 0,
                'mayor' => $mayor,
                'promedio' => $promedio,
                'variacionPromedio' => Indicadores::variacion($cantidad, $promedio),
            ];
        }

        return [
            'anterior' => $historial[$posicion - 1] ?? null,
            'siguiente' => $historial[$posicion + 1] ?? null,
            'grafico' => self::barrasGrafico($grafico, $idConsumo),
            'posicionAnual' => $posicionAnual,
        ];
    }

    /** Barras del gráfico de la ficha: alto en px proporcional a la boleta más alta del gráfico. */
    private static function barrasGrafico(array $boletas, int $idActual, int $altoMaximo = 130): array
    {
        $maximo = max(array_map(fn ($boleta) => (float) $boleta->cantidad, $boletas));

        return array_map(fn ($boleta) => [
            'fecha' => $boleta->fecha,
            'cantidad' => (float) $boleta->cantidad,
            'alto' => $maximo > 0 ? max(4, (int) round((float) $boleta->cantidad / $maximo * $altoMaximo)) : 4,
            'esActual' => (int) $boleta->id_consumo === $idActual,
        ], $boletas);
    }
}
