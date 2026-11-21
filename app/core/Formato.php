<?php

/**
 * Formatos de presentación en estilo argentino (punto de miles, coma decimal).
 * Solo para mostrar: los cálculos se hacen antes, con los números sin formato.
 */
class Formato
{
    /** 152658.33 => '$ 152.658,33'. */
    public static function pesos(float $valor, int $decimales = 2): string
    {
        return '$ ' . number_format($valor, $decimales, ',', '.');
    }

    /** 465 => '465'; 38.5 => '38,50'. Sin decimales si el número es entero. */
    public static function numero(float $valor): string
    {
        $decimales = floor($valor) == $valor ? 0 : 2;

        return number_format($valor, $decimales, ',', '.');
    }

    /** '2026-07-31' => '31/07/2026'. */
    public static function fecha(string $fecha): string
    {
        return date('d/m/Y', strtotime($fecha));
    }

    /** '2026-11-08 10:00:00' => 'hace 3 días'. A partir de 8 semanas muestra la fecha. */
    public static function haceTiempo(string $fechaHora, ?DateTimeImmutable $ahora = null): string
    {
        $ahora ??= new DateTimeImmutable();
        $dias = (int) (new DateTimeImmutable(substr($fechaHora, 0, 10)))->diff($ahora->setTime(0, 0))->format('%r%a');

        return match (true) {
            $dias <= 0 => 'hoy',
            $dias === 1 => 'ayer',
            $dias < 14 => "hace {$dias} días",
            $dias < 60 => 'hace ' . intdiv($dias, 7) . ' semanas',
            default => self::fecha($fechaHora),
        };
    }

    /** 12.5 => '↗ 12,5 %'; -3.1 => '↘ 3,1 %'; null => '—'. */
    public static function variacion(?float $porcentaje): string
    {
        if ($porcentaje === null) {
            return '—';
        }

        $flecha = $porcentaje > 0 ? '↗' : ($porcentaje < 0 ? '↘' : '→');

        return $flecha . ' ' . number_format(abs($porcentaje), 1, ',', '.') . ' %';
    }

    /**
     * Clase CSS para una variación de consumo o de gasto:
     * subir es malo (rojo) y bajar es bueno (verde).
     */
    public static function claseVariacion(?float $porcentaje): string
    {
        if ($porcentaje === null || $porcentaje == 0.0) {
            return '';
        }

        return $porcentaje > 0 ? 'kpi-value-negative' : 'kpi-value-positive';
    }
}
