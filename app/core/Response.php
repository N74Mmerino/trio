<?php

class Response
{
    public static function render(string $vista, array $variables = []): void
    {
        // $vista y $variables quedan reservadas: EXTR_SKIP impide que una variable de la vista las pise.
        extract($variables, EXTR_SKIP);
        require APP_PATH . 'views' . DIRECTORY_SEPARATOR . $vista . '.php';
    }

    public static function redirigir(string $ruta = ''): void
    {
        header('Location: ' . BASE_URL . ltrim($ruta, '/'));
        exit;
    }

    public static function error404(): void
    {
        http_response_code(404);
        self::render('errores/404', ['titulo' => 'Página no encontrada']);
        exit;
    }

    /** Envía un PDF al navegador para verlo en línea. */
    public static function pdf(string $ruta, string $nombreDescarga): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }
}
