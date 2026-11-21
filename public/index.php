<?php

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);

date_default_timezone_set('America/Argentina/Buenos_Aires');

require APP_PATH . 'librerias/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();
$dotenv->required(['APP_URL', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS']);

define('BASE_URL', rtrim($_ENV['APP_URL'], '/') . '/');

require APP_PATH . 'core/Autoloader.php';
Autoloader::registrar();

Session::iniciar();

(new App())->ejecutar();
