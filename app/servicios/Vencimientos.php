<?php

namespace app\servicios;

use DateTimeImmutable;
use Formato;

/**
 * Etapa 23: estado de una cuota según su fecha de vencimiento.
 *
 * Todavía no se registra si una cuota se pagó ("Marcar como pagada" queda para una etapa futura),
 * así que el estado depende SOLO de la fecha. Por eso no existe un estado "vencida" en rojo:
 * una cuota con fecha pasada probablemente se pagó, y marcarla como impaga sería un aviso falso.
 *
 *   'futuro'  → faltan más de UMBRAL_DIAS días   → "Vence el 15/12/2026"  (gris)
 *   'proximo' → faltan UMBRAL_DIAS días o menos   → "Vence en 5 días"      (amarillo)
 *   'pasado'  → la fecha ya pasó                  → "Venció el 23/09/2026" (gris tenue)
 */
class Vencimientos
{
    /** Desde cuántos días antes una cuota se muestra como "por vencer". */
    public const UMBRAL_DIAS = 7;

    /** Estado de una fecha de vencimiento (AAAA-MM-DD): ['estado' => ..., 'texto' => ..., 'dias' => ...]. */
    public static function estado(string $fecha, ?DateTimeImmutable $hoy = null): array
    {
        $hoy ??= new DateTimeImmutable('today');
        $vence = new DateTimeImmutable($fecha);
        // Diferencia en días con signo: negativa si la fecha ya pasó.
        $dias = (int) $hoy->diff($vence)->format('%r%a');

        if ($dias < 0) {
            return ['estado' => 'pasado', 'texto' => 'Venció el ' . Formato::fecha($fecha), 'dias' => $dias];
        }

        if ($dias <= self::UMBRAL_DIAS) {
            $texto = match ($dias) {
                0 => 'Vence hoy',
                1 => 'Vence mañana',
                default => 'Vence en ' . $dias . ' días',
            };

            return ['estado' => 'proximo', 'texto' => $texto, 'dias' => $dias];
        }

        return ['estado' => 'futuro', 'texto' => 'Vence el ' . Formato::fecha($fecha), 'dias' => $dias];
    }

    /**
     * La cuota que conviene mostrar en "Mis boletas": la primera que todavía no venció;
     * si ya vencieron todas, la última. null si la boleta no tiene cuotas cargadas.
     * Devuelve el estado más 'cuota' (número) y 'cuotas' (total), para el texto "cuota 2 de 2".
     */
    public static function proxima(array $cuotas, ?DateTimeImmutable $hoy = null): ?array
    {
        if ($cuotas === []) {
            return null;
        }

        $elegida = end($cuotas);
        foreach ($cuotas as $cuota) {
            if (self::estado($cuota->fecha_vencimiento, $hoy)['estado'] !== 'pasado') {
                $elegida = $cuota;
                break;
            }
        }

        return self::estado($elegida->fecha_vencimiento, $hoy) + [
            'cuota' => (int) $elegida->numero_cuota,
            'cuotas' => count($cuotas),
        ];
    }

    /** Para el aviso de la ficha: la próxima cuota solo si vence dentro del umbral; si no, null. */
    public static function aviso(array $cuotas, ?DateTimeImmutable $hoy = null): ?array
    {
        $proxima = self::proxima($cuotas, $hoy);

        return $proxima !== null && $proxima['estado'] === 'proximo' ? $proxima : null;
    }
}
