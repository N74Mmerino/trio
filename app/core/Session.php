<?php

class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function iniciarUsuario(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
    }

    /**
     * Etapa 19: la sesión guarda una copia del nombre y del correo (se toma al iniciar sesión).
     * Si el usuario los cambia en "Mi perfil", se actualiza la copia para que el cambio se vea enseguida.
     */
    public static function actualizarUsuario(array $datos): void
    {
        $_SESSION['usuario'] = array_merge($_SESSION['usuario'] ?? [], $datos);
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario']);
    }

    public static function esAdministrador(): bool
    {
        return (self::usuario()['rol'] ?? null) === 'administrador';
    }

    /** Pantalla de inicio según el rol: el administrador tiene la suya (Etapa 14). */
    public static function rutaInicio(): string
    {
        return self::esAdministrador() ? 'admin' : 'dashboard';
    }

    public static function requiereLogin(): void
    {
        if (!self::estaAutenticado()) {
            Response::redirigir('auth/login');
        }
    }

    public static function requiereAdmin(): void
    {
        self::requiereLogin();

        if (!self::esAdministrador()) {
            Response::error404();
        }
    }

    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    public static function csrfValido(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }

    public static function flash(string $clave, string $mensaje): void
    {
        $_SESSION['flash'][$clave] = $mensaje;
    }

    public static function tomarFlash(string $clave): ?string
    {
        $mensaje = $_SESSION['flash'][$clave] ?? null;
        unset($_SESSION['flash'][$clave]);

        return $mensaje;
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function obtener(string $clave): mixed
    {
        return $_SESSION[$clave] ?? null;
    }

    public static function quitar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }
}
