<?php

namespace app\models;

use DataBase;
use Model;
use Throwable;

/**
 * Enlaces de recupero de contraseña (Etapa 16). Tabla recuperos_password.
 *
 * Del enlace solo se guarda su hash SHA-256 (token_hash). Las fechas se comparan con NOW()
 * de MySQL para no depender de que PHP y la base tengan la misma hora.
 */
class RecuperoModel extends Model
{
    protected static string $tabla = 'recuperos_password';
    protected static string $clavePrimaria = 'id_recupero';

    /** Cuántos enlaces pidió el usuario en los últimos $minutos (para limitar pedidos seguidos). */
    public static function contarRecientes(int $idUsuario, int $minutos): int
    {
        $sql = 'SELECT COUNT(*) AS cantidad
                FROM recuperos_password
                WHERE id_usuario = :id AND fecha_pedido >= NOW() - INTERVAL :minutos MINUTE';

        return (int) DataBase::consultarUno($sql, ['id' => $idUsuario, 'minutos' => $minutos])->cantidad;
    }

    /** Registra un enlace nuevo. Los anteriores del usuario que seguían pendientes dejan de servir. */
    public static function crear(int $idUsuario, string $tokenHash, int $minutosVigencia): void
    {
        self::anularPendientes($idUsuario);

        // Limpieza: los enlaces vencidos hace más de un día ya no hacen falta ni para contar pedidos.
        DataBase::ejecutar('DELETE FROM recuperos_password WHERE expira < NOW() - INTERVAL 1 DAY');

        $sql = 'INSERT INTO recuperos_password (id_usuario, token_hash, expira)
                VALUES (:id, :hash, NOW() + INTERVAL :minutos MINUTE)';

        DataBase::ejecutar($sql, ['id' => $idUsuario, 'hash' => $tokenHash, 'minutos' => $minutosVigencia]);
    }

    /** Enlace que todavía sirve (no usado, no reemplazado, no vencido y de una cuenta activa), o null. */
    public static function buscarVigente(string $tokenHash): ?object
    {
        $sql = 'SELECT r.id_recupero, r.id_usuario, u.nombre
                FROM recuperos_password r
                INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
                WHERE r.token_hash = :hash
                  AND r.anulado_en IS NULL
                  AND r.expira > NOW()
                  AND u.activo = 1';

        return DataBase::consultarUno($sql, ['hash' => $tokenHash]);
    }

    /**
     * Cambia la contraseña y anula el enlace, todo junto.
     * Devuelve false si el enlace ya no servía (por ejemplo, se usó en otra pestaña un instante antes).
     */
    public static function usar(int $idRecupero, int $idUsuario, string $password): bool
    {
        $conexion = DataBase::conexion();
        $conexion->beginTransaction();

        try {
            // El UPDATE con condiciones hace de "candado": si dos envíos llegan a la vez, solo uno lo gana.
            $sql = 'UPDATE recuperos_password SET anulado_en = NOW()
                    WHERE id_recupero = :id AND anulado_en IS NULL AND expira > NOW()';

            if (DataBase::ejecutar($sql, ['id' => $idRecupero]) !== 1) {
                $conexion->rollBack();

                return false;
            }

            UsuarioModel::actualizarPassword($idUsuario, $password);
            self::anularPendientes($idUsuario);

            $conexion->commit();

            return true;
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }

    /** Anula los enlaces sin usar. Etapa 19: pasa a público para usarlo al cambiar la contraseña desde "Mi perfil". */
    public static function anularPendientes(int $idUsuario): void
    {
        $sql = 'UPDATE recuperos_password SET anulado_en = NOW() WHERE id_usuario = :id AND anulado_en IS NULL';

        DataBase::ejecutar($sql, ['id' => $idUsuario]);
    }
}
