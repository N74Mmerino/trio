<?php

abstract class Model
{
    protected static string $tabla;
    protected static string $clavePrimaria = 'id';

    public static function buscarPorId(int $id): ?object
    {
        $sql = 'SELECT * FROM ' . static::$tabla . ' WHERE ' . static::$clavePrimaria . ' = :id';

        return DataBase::consultarUno($sql, ['id' => $id]);
    }

    public static function listar(): array
    {
        $sql = 'SELECT * FROM ' . static::$tabla . ' ORDER BY ' . static::$clavePrimaria;

        return DataBase::consultar($sql);
    }
}
