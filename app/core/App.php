<?php

class App
{
    private const CONTROLADOR_POR_DEFECTO = 'home';
    private const ACCION_POR_DEFECTO = 'index';

    private string $controlador;
    private string $accion;
    private array $parametros;

    public function __construct()
    {
        $segmentos = $this->segmentosDeUrl();

        $this->controlador = $segmentos[0] ?? self::CONTROLADOR_POR_DEFECTO;
        $this->accion = $segmentos[1] ?? self::ACCION_POR_DEFECTO;
        $this->parametros = array_slice($segmentos, 2);
    }

    public function ejecutar(): void
    {
        if (!$this->esNombreValido($this->controlador) || !$this->esNombreValido($this->accion)) {
            Response::error404();
        }

        $clase = 'app\\controllers\\' . ucfirst(strtolower($this->controlador)) . 'Controller';
        $metodo = 'action' . ucfirst(strtolower($this->accion));

        if (!class_exists($clase)) {
            Response::error404();
        }

        $controlador = new $clase();

        if (!is_callable([$controlador, $metodo])) {
            Response::error404();
        }

        $controlador->$metodo(...$this->parametros);
    }

    private function segmentosDeUrl(): array
    {
        $url = $_GET['url'] ?? '';
        $url = is_string($url) ? trim($url, '/') : '';

        return $url === '' ? [] : explode('/', $url);
    }

    private function esNombreValido(string $nombre): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $nombre) === 1;
    }
}
