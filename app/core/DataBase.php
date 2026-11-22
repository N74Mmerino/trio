<?php

class DataBase
{
    private static ?PDO $conexion = null;

    private function __construct()
    {
    }

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );

            try {
                self::$conexion = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$conexion->exec("SET time_zone = '-03:00'");
            } catch (PDOException $e) {
                throw new RuntimeException('No se pudo conectar con la base de datos: ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$conexion;
    }

    public static function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = self::conexion()->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia->fetchAll();
    }

    public static function consultarUno(string $sql, array $parametros = []): ?object
    {
        $sentencia = self::conexion()->prepare($sql);
        $sentencia->execute($parametros);
        $fila = $sentencia->fetch();

        return $fila === false ? null : $fila;
    }

    public static function ejecutar(string $sql, array $parametros = []): int
    {
        $sentencia = self::conexion()->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia->rowCount();
    }

    public static function ultimoId(): int
    {
        return (int) self::conexion()->lastInsertId();
    }

    // Etapa 23: transacciones. Se usan cuando una operación escribe en más de una tabla
    // (una boleta y sus cuotas): o se guarda todo, o no se guarda nada.

    public static function iniciarTransaccion(): void
    {
        self::conexion()->beginTransaction();
    }

    public static function confirmarTransaccion(): void
    {
        self::conexion()->commit();
    }

    public static function revertirTransaccion(): void
    {
        if (self::conexion()->inTransaction()) {
            self::conexion()->rollBack();
        }
    }
}
