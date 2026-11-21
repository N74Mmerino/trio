<?php

namespace app\controllers;

use Controller;
use Session;
use Validador;
use app\models\RecuperoModel;
use app\models\UsuarioModel;
use app\servicios\Correo;

/**
 * Recupero de contraseña por correo (Etapa 16).
 *
 * 1. recupero              → el usuario escribe su email y recibe un enlace.
 * 2. recupero/restablecer/{token} → elige una contraseña nueva.
 *
 * El enlace vence a los 60 minutos y sirve una sola vez. Pedir otro anula el anterior.
 */
class RecuperoController extends Controller
{
    private const MINUTOS_VIGENCIA = 60;
    private const MAX_PEDIDOS = 3;
    private const VENTANA_MINUTOS = 15;

    /** Mismo texto exista o no la cuenta: así no se puede averiguar qué correos están registrados. */
    private const MENSAJE_ENVIADO = 'Si el correo está registrado, te enviamos un enlace para crear una contraseña nueva. Revisá tu bandeja de entrada (y la de spam). El enlace vence en 60 minutos.';

    public function __construct()
    {
        if (Session::estaAutenticado()) {
            $this->redirigir(Session::rutaInicio());
        }
    }

    /** GET: formulario del email. POST: genera el enlace y lo envía. */
    public function actionIndex(): void
    {
        $email = '';
        $error = null;
        $errores = [];

        if ($this->esPost()) {
            $email = mb_strtolower($this->campo('email'));
            $validador = (new Validador(['email' => $email]))
                ->requerido('email', 'Ingresá tu correo electrónico.')
                ->email('email', 'El correo electrónico no es válido.');

            if (!Session::csrfValido($_POST['csrf'] ?? null)) {
                $error = 'La sesión expiró. Volvé a enviar el formulario.';
            } elseif (!$validador->esValido()) {
                $errores = $validador->errores();
            } else {
                $this->enviarEnlace($email);

                Session::flash('exito', self::MENSAJE_ENVIADO);
                $this->redirigir('recupero');
            }
        }

        $this->render('recupero/index', [
            'titulo' => 'Recuperar contraseña',
            'mensaje' => Session::tomarFlash('exito'),
            'email' => $email,
            'error' => $error,
            'errores' => $errores,
        ]);
    }

    /** GET: formulario de contraseña nueva. POST: la guarda. */
    public function actionRestablecer(string $token = ''): void
    {
        $recupero = $this->recuperoVigente($token);

        if ($recupero === null) {
            $this->render('recupero/invalido', ['titulo' => 'Enlace no válido']);

            return;
        }

        $errores = [];
        $error = null;
        $requisitos = Validador::requisitosPassword();
        $intentado = false;

        if ($this->esPost()) {
            $datos = [
                'password' => $this->campo('password', false),
                'password_confirmacion' => $this->campo('password_confirmacion', false),
            ];

            if (!Session::csrfValido($_POST['csrf'] ?? null)) {
                $error = 'La sesión expiró. Volvé a enviar el formulario.';
            } else {
                $validador = (new Validador($datos))
                    ->requerido('password', 'Ingresá una contraseña.')
                    ->passwordSegura('password', 'La contraseña no cumple los requisitos.')
                    ->requerido('password_confirmacion', 'Repetí la contraseña.')
                    ->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');

                if ($validador->esValido()) {
                    if (!RecuperoModel::usar((int) $recupero->id_recupero, (int) $recupero->id_usuario, $datos['password'])) {
                        $this->render('recupero/invalido', ['titulo' => 'Enlace no válido']);

                        return;
                    }

                    Session::flash('exito', 'Listo, tu contraseña se actualizó. Ya podés iniciar sesión.');
                    $this->redirigir('auth/login');
                }

                $errores = $validador->errores();
                $requisitos = Validador::requisitosPassword($datos['password']);
                $intentado = true;
            }
        }

        $this->render('recupero/restablecer', [
            'titulo' => 'Nueva contraseña',
            'token' => $token,
            'nombre' => $recupero->nombre,
            'errores' => $errores,
            'error' => $error,
            'requisitos' => $requisitos,
            'intentado' => $intentado,
        ]);
    }

    /** Genera y envía el enlace si la cuenta existe, está activa y no pidió demasiados seguidos. */
    private function enviarEnlace(string $email): void
    {
        $usuario = UsuarioModel::buscarActivoPorEmail($email);

        if ($usuario === null || RecuperoModel::contarRecientes((int) $usuario->id_usuario, self::VENTANA_MINUTOS) >= self::MAX_PEDIDOS) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        RecuperoModel::crear((int) $usuario->id_usuario, hash('sha256', $token), self::MINUTOS_VIGENCIA);

        Correo::enviar($usuario->email, $usuario->nombre . ' ' . $usuario->apellido, 'Recuperá tu contraseña', 'recupero', [
            'nombre' => $usuario->nombre,
            'enlace' => BASE_URL . 'recupero/restablecer/' . $token,
            'minutos' => self::MINUTOS_VIGENCIA,
        ]);
    }

    private function recuperoVigente(string $token): ?object
    {
        // Se valida el formato antes de ir a la base: 64 caracteres hexadecimales.
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return RecuperoModel::buscarVigente(hash('sha256', $token));
    }
}
