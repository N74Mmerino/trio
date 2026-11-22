<?php

namespace app\models;

use DataBase;
use Model;

/**
 * Etapa 23: cuotas de cada boleta (tabla vencimientos).
 * Una boleta tiene 0, 1 o 2 cuotas. El dueño se obtiene por la boleta (consumos.id_usuario):
 * por eso las consultas que listan cuotas siempre hacen JOIN con consumos y filtran por usuario.
 */
class VencimientoModel extends Model
{
    protected static string $tabla = 'vencimientos';
    protected static string $clavePrimaria = 'id_vencimiento';

    public static function registrar(int $idConsumo, int $numeroCuota, string $fecha, string $importe): void
    {
        $sql = 'INSERT INTO vencimientos (id_consumo, numero_cuota, fecha_vencimiento, importe)
                VALUES (:id_consumo, :numero_cuota, :fecha, :importe)';

        DataBase::ejecutar($sql, [
            'id_consumo' => $idConsumo,
            'numero_cuota' => $numeroCuota,
            'fecha' => $fecha,
            'importe' => $importe,
        ]);
    }

    /** Cuotas de una boleta, en orden. La boleta ya se verificó que es del usuario (buscarDelUsuario). */
    public static function deConsumo(int $idConsumo): array
    {
        $sql = 'SELECT numero_cuota, fecha_vencimiento, importe, pagado_en
                FROM vencimientos
                WHERE id_consumo = :id_consumo
                ORDER BY numero_cuota';

        return DataBase::consultar($sql, ['id_consumo' => $idConsumo]);
    }

    /**
     * Todas las cuotas de las boletas del usuario, agrupadas por boleta: [id_consumo => [cuota, ...]].
     * Una sola consulta para toda la lista de "Mis boletas" (en vez de una por boleta).
     */
    public static function agrupadasDelUsuario(int $idUsuario): array
    {
        $sql = 'SELECT v.id_consumo, v.numero_cuota, v.fecha_vencimiento, v.importe, v.pagado_en
                FROM vencimientos v
                INNER JOIN consumos c ON c.id_consumo = v.id_consumo
                WHERE c.id_usuario = :id_usuario
                ORDER BY v.id_consumo, v.numero_cuota';

        $grupos = [];
        foreach (DataBase::consultar($sql, ['id_usuario' => $idUsuario]) as $cuota) {
            $grupos[(int) $cuota->id_consumo][] = $cuota;
        }

        return $grupos;
    }
}
