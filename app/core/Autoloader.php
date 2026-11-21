<?php

class Autoloader
{
    public static function registrar(): void
    {
        spl_autoload_register(static function (string $clase): void {
            if (str_contains($clase, '\\')) {
                $archivo = ROOT_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $clase) . '.php';
            } else {
                $archivo = APP_PATH . 'core' . DIRECTORY_SEPARATOR . $clase . '.php';
            }

            if (is_file($archivo)) {
                require_once $archivo;
            }
        });
    }
}
