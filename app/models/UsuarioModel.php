<?php

namespace app\models;

use DataBase;
use Model;

class UsuarioModel extends Model
{
    public const ROL_ADMINISTRADOR = 1;
    public const ROL_USUARIO = 2;

    protected static string $tabla = 'usuarios';
    protected static string $clavePrimaria = 'id_usuario';

    public static function buscarActivoPorEmail(string $email): ?object
    {
        $sql = 'SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.password_hash, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.email = :email AND u.activo = 1';

        return DataBase::consultarUno($sql, ['email' => $email]);
    }

    public static function existeEmail(string $email): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE email = :email';

        return DataBase::consultarUno($sql, ['email' => $email]) !== null;
    }

    public static function crear(string $nombre, string $apellido, string $email, string $password): int
    {
        $sql = 'INSERT INTO usuarios (id_rol, nombre, apellido, email, password_hash)
                VALUES (:id_rol, :nombre, :apellido, :email, :password_hash)';

        DataBase::ejecutar($sql, [
            'id_rol' => self::ROL_USUARIO,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return DataBase::ultimoId();
    }

    /** Etapa 15: el admin solo corrige nombre y apellido; el email es la credencial del usuario. */
    public static function actualizarNombre(int $id, string $nombre, string $apellido): void
    {
        $sql = 'UPDATE usuarios SET nombre = :nombre, apellido = :apellido WHERE id_usuario = :id';

        DataBase::ejecutar($sql, ['nombre' => $nombre, 'apellido' => $apellido, 'id' => $id]);
    }

    /** Etapa 19: el propio usuario corrige su nombre, apellido y correo desde "Mi perfil". */
    public static function actualizarDatos(int $id, string $nombre, string $apellido, string $email): void
    {
        $sql = 'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email WHERE id_usuario = :id';

        DataBase::ejecutar($sql, ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'id' => $id]);
    }

    /** Etapa 19: ¿el correo lo usa OTRA cuenta? Tu propio correo no cuenta como repetido. */
    public static function existeEmailDeOtro(string $email, int $id): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE email = :email AND id_usuario <> :id';

        return DataBase::consultarUno($sql, ['email' => $email, 'id' => $id]) !== null;
    }

    /** Etapa 16: nueva contraseña desde el recupero por correo. Etapa 19: también desde "Mi perfil". */
    public static function actualizarPassword(int $id, string $password): void
    {
        $sql = 'UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id';

        DataBase::ejecutar($sql, ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public static function alternarEstado(int $id): void
    {
        $sql = 'UPDATE usuarios SET activo = NOT activo WHERE id_usuario = :id';

        DataBase::ejecutar($sql, ['id' => $id]);
    }

    // ---------------------------------------------------------------
    // Consultas del inicio del administrador (Etapa 14).
    // ---------------------------------------------------------------

    /** Totales para el inicio del administrador. "Nuevos" se mide con fecha_registro. */
    public static function resumen(string $inicioMes, string $inicioMesAnterior): object
    {
        // Con ATTR_EMULATE_PREPARES = false un parámetro con nombre no se puede repetir: por eso :mes y :mes2.
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(activo = 1), 0) AS activos,
                       COALESCE(SUM(fecha_registro >= :mes), 0) AS nuevos_mes,
                       COALESCE(SUM(fecha_registro >= :anterior AND fecha_registro < :mes2), 0) AS nuevos_anterior
                FROM usuarios';

        return DataBase::consultarUno($sql, ['mes' => $inicioMes, 'anterior' => $inicioMesAnterior, 'mes2' => $inicioMes]);
    }

    public static function ultimosRegistros(int $limite = 5): array
    {
        $sql = 'SELECT id_usuario, id_rol, nombre, apellido, activo, fecha_registro
                FROM usuarios
                ORDER BY fecha_registro DESC, id_usuario DESC
                LIMIT ' . $limite;

        return DataBase::consultar($sql);
    }

    /** Usuarios comunes activos que todavía no cargaron ninguna boleta. */
    public static function contarActivosSinBoletas(): int
    {
        $sql = 'SELECT COUNT(*) AS cantidad
                FROM usuarios u
                WHERE u.activo = 1 AND u.id_rol = :rol
                  AND NOT EXISTS (SELECT 1 FROM consumos c WHERE c.id_usuario = u.id_usuario)';

        return (int) DataBase::consultarUno($sql, ['rol' => self::ROL_USUARIO])->cantidad;
    }

    // ---------------------------------------------------------------
    // Pantalla Usuarios, ficha de actividad y filtro de Boletas (Etapa 15).
    // Ninguna consulta trae password_hash.
    // ---------------------------------------------------------------

    /** Listado de Usuarios con su actividad. $filtro: todos | activos | inactivos | sin-boletas. */
    public static function listarParaAdmin(string $filtro, string $busqueda): array
    {
        $parametros = [];
        $condiciones = match ($filtro) {
            'activos' => ['u.activo = 1'],
            'inactivos' => ['u.activo = 0'],
            'sin-boletas' => ['u.id_rol = :rol', 'a.id_usuario IS NULL'],
            default => [],
        };

        if ($filtro === 'sin-boletas') {
            $parametros['rol'] = self::ROL_USUARIO;
        }

        if ($busqueda !== '') {
            // % y _ son comodines de LIKE: se escapan para buscar el texto literal.
            $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
            $condiciones[] = "(CONCAT(u.nombre, ' ', u.apellido) LIKE :busqueda1 OR u.email LIKE :busqueda2)";
            $parametros += ['busqueda1' => $patron, 'busqueda2' => $patron];
        }

        $sql = 'SELECT u.id_usuario, u.id_rol, u.nombre, u.apellido, u.email, u.activo, r.nombre AS rol,
                       COALESCE(a.boletas, 0) AS boletas, a.ultima_carga
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                LEFT JOIN (SELECT id_usuario, COUNT(*) AS boletas, MAX(fecha_carga) AS ultima_carga
                           FROM consumos GROUP BY id_usuario) a ON a.id_usuario = u.id_usuario'
             . ($condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones))
             . ' ORDER BY u.fecha_registro';

        return DataBase::consultar($sql, $parametros);
    }

    /** Cantidades que muestra cada filtro de Usuarios. */
    public static function contarPorFiltro(): object
    {
        $sql = 'SELECT COUNT(*) AS todos,
                       COALESCE(SUM(u.activo = 1), 0) AS activos,
                       COALESCE(SUM(u.activo = 0), 0) AS inactivos,
                       COALESCE(SUM(u.id_rol = :rol AND NOT EXISTS (SELECT 1 FROM consumos c WHERE c.id_usuario = u.id_usuario)), 0) AS sin_boletas
                FROM usuarios u';

        return DataBase::consultarUno($sql, ['rol' => self::ROL_USUARIO]);
    }

    /** Datos de la ficha de actividad. */
    public static function buscarParaFicha(int $id): ?object
    {
        $sql = 'SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.activo, u.fecha_registro, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id';

        return DataBase::consultarUno($sql, ['id' => $id]);
    }

    /** Usuarios comunes, para el filtro "Usuario" del listado de Boletas. */
    public static function listarUsuariosComunes(): array
    {
        $sql = 'SELECT id_usuario, nombre, apellido FROM usuarios WHERE id_rol = :rol ORDER BY nombre, apellido';

        return DataBase::consultar($sql, ['rol' => self::ROL_USUARIO]);
    }
}
