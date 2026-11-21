<?php

namespace app\servicios;

use DateTime;

/**
 * Base de todos los extractores de boletas.
 *
 * Cada empresa arma su boleta distinto, así que cada una tiene su propio extractor
 * (una subclase) que sabe reconocer su boleta y dónde está cada dato.
 * Los métodos protegidos son utilidades compartidas por todas las subclases.
 */
abstract class ExtractorBoleta
{
    /** Indica si el texto del PDF corresponde a la boleta de esta empresa. */
    abstract public function reconoce(string $texto): bool;

    /** Nombre de la empresa, para mostrarle al usuario qué boleta se detectó. */
    abstract public function empresa(): string;

    /** Nombre del servicio tal como está en la tabla `servicios` (Luz, Agua o Gas). */
    abstract public function servicio(): string;

    /**
     * Devuelve cantidad, importe y fecha en el formato que usa el formulario:
     * cantidad e importe con punto decimal, fecha como AAAA-MM-DD.
     * Un dato que no se encuentra se devuelve como cadena vacía.
     */
    abstract public function extraer(string $texto): array;

    /** Devuelve el primer grupo capturado por la expresión, o '' si no hay coincidencia. */
    protected function buscar(string $patron, string $texto): string
    {
        return preg_match($patron, $texto, $coincidencia) === 1 ? $coincidencia[1] : '';
    }

    /** Convierte "101.456,06" en "101456.06". */
    protected function numeroArgentino(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        return str_replace(',', '.', str_replace('.', '', $valor));
    }

    /** Convierte "01/06/2026" en "2026-06-01". Si la fecha no es válida devuelve ''. */
    protected function fechaArgentina(string $valor): string
    {
        $fecha = DateTime::createFromFormat('!d/m/Y', $valor);

        return $fecha !== false && $fecha->format('d/m/Y') === $valor ? $fecha->format('Y-m-d') : '';
    }
}
