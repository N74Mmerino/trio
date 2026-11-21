<?php

namespace app\servicios;

/**
 * Guarda los PDF de las boletas en storage/boletas/, fuera de public/.
 *
 * - tmp/              PDF subidos que todavía no se confirmaron.
 * - {id_usuario}/     comprobantes confirmados de cada usuario.
 *
 * Los nombres en disco son aleatorios (32 caracteres hexadecimales + .pdf) y se
 * validan antes de armar cualquier ruta, para que nunca se pueda salir de la carpeta.
 */
class AlmacenBoletas
{
    private const PATRON_NOMBRE = '/^[a-f0-9]{32}\.pdf$/';
    private const HORAS_TEMPORAL = 24;

    private static function carpeta(string $subcarpeta): string
    {
        $ruta = ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR . 'boletas' . DIRECTORY_SEPARATOR . $subcarpeta . DIRECTORY_SEPARATOR;

        if (!is_dir($ruta)) {
            mkdir($ruta, 0775, true);
        }

        return $ruta;
    }

    private static function nombreValido(string $nombre): bool
    {
        return preg_match(self::PATRON_NOMBRE, $nombre) === 1;
    }

    /** Mueve el archivo recién subido a tmp/ y devuelve el nombre generado. */
    public static function guardarTemporal(string $archivoSubido): ?string
    {
        self::limpiarTemporalesViejos();

        $nombre = bin2hex(random_bytes(16)) . '.pdf';

        return move_uploaded_file($archivoSubido, self::carpeta('tmp') . $nombre) ? $nombre : null;
    }

    public static function rutaTemporal(string $nombre): ?string
    {
        if (!self::nombreValido($nombre)) {
            return null;
        }

        $ruta = self::carpeta('tmp') . $nombre;

        return is_file($ruta) ? $ruta : null;
    }

    /** Pasa el PDF de tmp/ a la carpeta del usuario. Devuelve true si se movió. */
    public static function confirmar(string $nombre, int $idUsuario): bool
    {
        $origen = self::rutaTemporal($nombre);

        return $origen !== null && rename($origen, self::carpeta((string) $idUsuario) . $nombre);
    }

    public static function descartarTemporal(string $nombre): void
    {
        $ruta = self::rutaTemporal($nombre);

        if ($ruta !== null) {
            unlink($ruta);
        }
    }

    /** Elimina un comprobante ya confirmado (se usa si falla el INSERT). */
    public static function eliminar(string $nombre, int $idUsuario): void
    {
        $ruta = self::rutaComprobante($nombre, $idUsuario);

        if ($ruta !== null) {
            unlink($ruta);
        }
    }

    public static function rutaComprobante(string $nombre, int $idUsuario): ?string
    {
        if (!self::nombreValido($nombre)) {
            return null;
        }

        $ruta = self::carpeta((string) $idUsuario) . $nombre;

        return is_file($ruta) ? $ruta : null;
    }

    /** Borra los temporales de subidas que nunca se confirmaron. */
    private static function limpiarTemporalesViejos(): void
    {
        $limite = time() - self::HORAS_TEMPORAL * 3600;

        foreach (glob(self::carpeta('tmp') . '*.pdf') ?: [] as $archivo) {
            if (filemtime($archivo) < $limite) {
                unlink($archivo);
            }
        }
    }
}
