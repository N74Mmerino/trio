<?php

abstract class Controller
{
    protected function render(string $vista, array $variables = []): void
    {
        Response::render($vista, $variables);
    }

    protected function redirigir(string $ruta = ''): void
    {
        Response::redirigir($ruta);
    }

    protected function error404(): void
    {
        Response::error404();
    }

    protected function pdf(string $ruta, string $nombreDescarga): void
    {
        Response::pdf($ruta, $nombreDescarga);
    }

    protected function esPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function campo(string $nombre, bool $recortar = true): string
    {
        $valor = $_POST[$nombre] ?? '';

        if (!is_string($valor)) {
            return '';
        }

        return $recortar ? trim($valor) : $valor;
    }

    /** Valor de la URL (?nombre=valor). Solo texto: cualquier otra cosa se ignora. */
    protected function parametro(string $nombre): string
    {
        $valor = $_GET[$nombre] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }
}
