<?php

namespace app\models;

use DataBase;
use Model;

class ConsumoModel extends Model
{
    protected static string $tabla = 'consumos';
    protected static string $clavePrimaria = 'id_consumo';

    public static function registrar(int $idUsuario, int $idServicio, string $fecha, string $cantidad, string $importe, ?string $archivo = null): int
    {
        $sql = 'INSERT INTO consumos (id_usuario, id_servicio, fecha, cantidad, importe, archivo)
                VALUES (:id_usuario, :id_servicio, :fecha, :cantidad, :importe, :archivo)';

        DataBase::ejecutar($sql, [
            'id_usuario' => $idUsuario,
            'id_servicio' => $idServicio,
            'fecha' => $fecha,
            'cantidad' => $cantidad,
            'importe' => $importe,
            'archivo' => $archivo,
        ]);

        return DataBase::ultimoId();
    }

    /**
     * Etapa 22: borra una boleta SOLO si es del usuario indicado (borrado físico).
     * Devuelve true si se borró una fila.
     */
    public static function eliminarDelUsuario(int $idConsumo, int $idUsuario): bool
    {
        $sql = 'DELETE FROM consumos WHERE id_consumo = :id_consumo AND id_usuario = :id_usuario';

        return DataBase::ejecutar($sql, ['id_consumo' => $idConsumo, 'id_usuario' => $idUsuario]) === 1;
    }

    /** Indica si el usuario ya cargó una boleta de ese servicio con esa fecha. */
    public static function existe(int $idUsuario, int $idServicio, string $fecha): bool
    {
        $sql = 'SELECT 1 FROM consumos
                WHERE id_usuario = :id_usuario AND id_servicio = :id_servicio AND fecha = :fecha';

        return DataBase::consultarUno($sql, [
            'id_usuario' => $idUsuario,
            'id_servicio' => $idServicio,
            'fecha' => $fecha,
        ]) !== null;
    }

    public static function ultimosPorUsuario(int $idUsuario, int $limite = 5): array
    {
        $sql = 'SELECT c.id_consumo, c.fecha, c.cantidad, c.importe, c.archivo, s.nombre AS servicio, s.unidad
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                WHERE c.id_usuario = :id_usuario
                ORDER BY c.fecha DESC, c.id_consumo DESC
                LIMIT ' . $limite;

        return DataBase::consultar($sql, ['id_usuario' => $idUsuario]);
    }

    /** Etapa 17: todas las boletas del usuario con los datos del servicio, de la más vieja a la más nueva. */
    public static function listarDelUsuario(int $idUsuario): array
    {
        $sql = 'SELECT c.id_consumo, c.id_servicio, c.fecha, c.cantidad, c.importe, c.archivo, s.nombre AS servicio, s.unidad
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                WHERE c.id_usuario = :id_usuario
                ORDER BY c.fecha, c.id_consumo';

        return DataBase::consultar($sql, ['id_usuario' => $idUsuario]);
    }

    /**
     * Etapa 17: una boleta con los datos de su servicio, SOLO si es del usuario indicado.
     * Para una boleta ajena devuelve null, igual que para una inexistente.
     */
    public static function buscarDelUsuario(int $idConsumo, int $idUsuario): ?object
    {
        $sql = 'SELECT c.id_consumo, c.id_servicio, c.fecha, c.cantidad, c.importe, c.archivo, c.fecha_carga,
                       s.nombre AS servicio, s.unidad
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                WHERE c.id_consumo = :id_consumo AND c.id_usuario = :id_usuario';

        return DataBase::consultarUno($sql, ['id_consumo' => $idConsumo, 'id_usuario' => $idUsuario]);
    }

    // ---------------------------------------------------------------
    // Consultas del dashboard. Todas filtran por el usuario de la sesión.
    // $desde / $hasta en formato AAAA-MM-DD; null = sin límite.
    // $hasta es exclusivo (fecha < $hasta), así dos períodos seguidos no se superponen.
    // ---------------------------------------------------------------

    /** Indica si el usuario cargó al menos un consumo (para mostrar el estado vacío). */
    public static function tieneConsumos(int $idUsuario): bool
    {
        $sql = 'SELECT 1 FROM consumos WHERE id_usuario = :id_usuario LIMIT 1';

        return DataBase::consultarUno($sql, ['id_usuario' => $idUsuario]) !== null;
    }

    /** Gasto total por mes y servicio (barras apiladas). */
    public static function gastoMensual(int $idUsuario, ?string $desde): array
    {
        $parametros = ['id_usuario' => $idUsuario];
        $sql = "SELECT DATE_FORMAT(c.fecha, '%Y-%m') AS mes, c.id_servicio, SUM(c.importe) AS total
                FROM consumos c
                WHERE c.id_usuario = :id_usuario" . self::filtroFechas($desde, null, $parametros) . '
                GROUP BY mes, c.id_servicio
                ORDER BY mes';

        return DataBase::consultar($sql, $parametros);
    }

    /** Gasto total y cantidad de boletas por servicio (dona, KPIs y período anterior). */
    public static function gastoPorServicio(int $idUsuario, ?string $desde, ?string $hasta = null): array
    {
        $parametros = ['id_usuario' => $idUsuario];
        $sql = 'SELECT s.id_servicio, s.nombre, SUM(c.importe) AS total, COUNT(*) AS boletas
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                WHERE c.id_usuario = :id_usuario' . self::filtroFechas($desde, $hasta, $parametros) . '
                GROUP BY s.id_servicio, s.nombre
                ORDER BY s.id_servicio';

        return DataBase::consultar($sql, $parametros);
    }

    /** Boletas de un servicio en orden cronológico (gráficos de la vista por servicio y ficha de la boleta). */
    public static function historialServicio(int $idUsuario, int $idServicio, ?string $desde): array
    {
        $parametros = ['id_usuario' => $idUsuario, 'id_servicio' => $idServicio];
        $sql = 'SELECT c.id_consumo, c.fecha, c.cantidad, c.importe
                FROM consumos c
                WHERE c.id_usuario = :id_usuario AND c.id_servicio = :id_servicio' . self::filtroFechas($desde, null, $parametros) . '
                ORDER BY c.fecha';

        return DataBase::consultar($sql, $parametros);
    }

    /** Las últimas boletas de un servicio, de la más nueva a la más vieja (variación vs. la anterior). */
    public static function ultimasPorServicio(int $idUsuario, int $idServicio, int $limite = 2): array
    {
        $sql = 'SELECT c.id_consumo, c.fecha, c.cantidad, c.importe
                FROM consumos c
                WHERE c.id_usuario = :id_usuario AND c.id_servicio = :id_servicio
                ORDER BY c.fecha DESC, c.id_consumo DESC
                LIMIT ' . $limite;

        return DataBase::consultar($sql, ['id_usuario' => $idUsuario, 'id_servicio' => $idServicio]);
    }

    /**
     * Etapa 18: últimas boletas que el usuario cargó en el sistema (por fecha de carga, no por fecha de la boleta).
     * Se muestra en el dashboard como "actividad reciente"; no depende del período elegido.
     */
    public static function ultimasCargadas(int $idUsuario, int $limite = 4): array
    {
        $sql = 'SELECT c.id_consumo, c.id_servicio, c.fecha, c.cantidad, c.importe, c.fecha_carga, s.nombre AS servicio, s.unidad
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                WHERE c.id_usuario = :id_usuario
                ORDER BY c.fecha_carga DESC, c.id_consumo DESC
                LIMIT ' . $limite;

        return DataBase::consultar($sql, ['id_usuario' => $idUsuario]);
    }

    /** Arma las condiciones opcionales de fecha y agrega sus parámetros. */
    private static function filtroFechas(?string $desde, ?string $hasta, array &$parametros): string
    {
        $condiciones = '';

        if ($desde !== null) {
            $condiciones .= ' AND c.fecha >= :desde';
            $parametros['desde'] = $desde;
        }

        if ($hasta !== null) {
            $condiciones .= ' AND c.fecha < :hasta';
            $parametros['hasta'] = $hasta;
        }

        return $condiciones;
    }

    // ---------------------------------------------------------------
    // Consultas del inicio del administrador (Etapa 14).
    // Toda la plataforma y SIN importes ni cantidades: el admin no ve cuánto paga cada usuario.
    // ---------------------------------------------------------------

    public static function resumenPlataforma(string $inicioMes): object
    {
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(archivo IS NOT NULL), 0) AS con_pdf,
                       COALESCE(SUM(fecha_carga >= :mes), 0) AS cargadas_mes
                FROM consumos';

        return DataBase::consultarUno($sql, ['mes' => $inicioMes]);
    }

    /** Cantidad de boletas por mes (fecha de la boleta) y servicio. Mismo formato que gastoMensual(). */
    public static function boletasMensualesPlataforma(string $desde): array
    {
        $sql = "SELECT DATE_FORMAT(c.fecha, '%Y-%m') AS mes, c.id_servicio, COUNT(*) AS total
                FROM consumos c
                WHERE c.fecha >= :desde
                GROUP BY mes, c.id_servicio
                ORDER BY mes";

        return DataBase::consultar($sql, ['desde' => $desde]);
    }

    /** Últimas boletas cargadas: quién, qué servicio y cuándo. */
    public static function actividadReciente(int $limite = 5): array
    {
        $sql = 'SELECT c.fecha, c.fecha_carga, c.archivo IS NOT NULL AS con_pdf,
                       s.nombre AS servicio, u.nombre, u.apellido
                FROM consumos c
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio
                INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
                ORDER BY c.fecha_carga DESC, c.id_consumo DESC
                LIMIT ' . $limite;

        return DataBase::consultar($sql);
    }

    // ---------------------------------------------------------------
    // Listado de Boletas y ficha de actividad del administrador (Etapa 15).
    // Solo datos de la carga: nunca importes, cantidades ni el nombre del archivo.
    // ---------------------------------------------------------------

    public static function listarParaAdmin(array $filtros, int $limite, int $desplazamiento): array
    {
        $parametros = [];
        $orden = $filtros['orden'] === 'boleta' ? 'c.fecha DESC, c.id_consumo DESC' : 'c.fecha_carga DESC, c.id_consumo DESC';

        $sql = 'SELECT c.id_consumo, c.id_usuario, u.nombre, u.apellido, c.id_servicio, s.nombre AS servicio,
                       c.fecha, c.fecha_carga, c.archivo IS NOT NULL AS con_pdf
                FROM consumos c
                INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
                INNER JOIN servicios s ON s.id_servicio = c.id_servicio'
             . self::filtroAdmin($filtros, $parametros)
             . ' ORDER BY ' . $orden
             . ' LIMIT ' . $limite . ' OFFSET ' . $desplazamiento;

        return DataBase::consultar($sql, $parametros);
    }

    /** Totales del listado filtrado: boletas, con PDF y usuarios distintos. */
    public static function resumenParaAdmin(array $filtros): object
    {
        $parametros = [];
        $sql = 'SELECT COUNT(*) AS total, COALESCE(SUM(c.archivo IS NOT NULL), 0) AS con_pdf, COUNT(DISTINCT c.id_usuario) AS usuarios
                FROM consumos c' . self::filtroAdmin($filtros, $parametros);

        return DataBase::consultarUno($sql, $parametros);
    }

    /** Cantidad de boletas por servicio dentro del listado filtrado. */
    public static function porServicioParaAdmin(array $filtros): array
    {
        $parametros = [];
        $sql = 'SELECT c.id_servicio, COUNT(*) AS total FROM consumos c'
             . self::filtroAdmin($filtros, $parametros) . ' GROUP BY c.id_servicio';

        return DataBase::consultar($sql, $parametros);
    }

    /** Resumen de la ficha de un usuario. */
    public static function actividadDeUsuario(int $idUsuario): object
    {
        $sql = 'SELECT COUNT(*) AS total, COALESCE(SUM(archivo IS NOT NULL), 0) AS con_pdf,
                       MIN(fecha_carga) AS primera, MAX(fecha_carga) AS ultima
                FROM consumos WHERE id_usuario = :id_usuario';

        return DataBase::consultarUno($sql, ['id_usuario' => $idUsuario]);
    }

    /** Meses con boleta por servicio, para la grilla de cobertura de la ficha. */
    public static function coberturaDeUsuario(int $idUsuario, string $desde): array
    {
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, id_servicio, MAX(archivo IS NOT NULL) AS con_pdf
                FROM consumos
                WHERE id_usuario = :id_usuario AND fecha >= :desde
                GROUP BY mes, id_servicio";

        return DataBase::consultar($sql, ['id_usuario' => $idUsuario, 'desde' => $desde]);
    }

    /** WHERE del listado de Boletas. Recibe valores ya validados por BoletaController. */
    private static function filtroAdmin(array $filtros, array &$parametros): string
    {
        $condiciones = [];

        if ($filtros['usuario'] !== null) {
            $condiciones[] = 'c.id_usuario = :usuario';
            $parametros['usuario'] = $filtros['usuario'];
        }

        if ($filtros['servicio'] !== null) {
            $condiciones[] = 'c.id_servicio = :servicio';
            $parametros['servicio'] = $filtros['servicio'];
        }

        if ($filtros['origen'] !== '') {
            $condiciones[] = $filtros['origen'] === 'pdf' ? 'c.archivo IS NOT NULL' : 'c.archivo IS NULL';
        }

        if ($filtros['desde'] !== null) {
            $condiciones[] = 'c.fecha >= :desde';
            $parametros['desde'] = $filtros['desde'];
        }

        return $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);
    }
}
