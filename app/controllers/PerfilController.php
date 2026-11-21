<?php

namespace app\controllers;

use Controller;
use PDOException;
use Session;
use Validador;
use app\models\ConsumoModel;
use app\models\RecuperoModel;
use app\models\UsuarioModel;

/**
 * Etapa 19: "Mi perfil". Cada usuario (también el administrador) corrige sus datos y cambia su contraseña.
 *
 *   GET  perfil           → la pantalla
 *   POST perfil/datos     → nombre, apellido y correo
 *   POST perfil/password  → contraseña
 *
 * Si un formulario tiene errores, la pantalla se vuelve a mostrar con esos errores (sin redirigir).
 * Si se guarda bien, se redirige a "perfil" (patrón PRG: al apretar F5 no se reenvía el formulario).
 */
class PerfilController extends Controller
{
    public function __construct()
    {
        Session::requiereLogin();
    }

    public function actionIndex(): void
    {
        $this->mostrar();
    }

    public function actionDatos(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('perfil');
        }

        $usuario = $this->usuarioActual();
        $datos = [
            'nombre' => $this->campo('nombre'),
            'apellido' => $this->campo('apellido'),
            'email' => mb_strtolower($this->campo('email')),
            'datos_password_actual' => $this->campo('datos_password_actual', false),
        ];

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            $this->mostrar(['error' => 'La sesión expiró. Volvé a enviar el formulario.']);

            return;
        }

        // Mismas reglas que el registro.
        $validador = (new Validador($datos))
            ->requerido('nombre', 'Ingresá tu nombre.')
            ->maximo('nombre', 60, 'El nombre admite hasta 60 caracteres.')
            ->requerido('apellido', 'Ingresá tu apellido.')
            ->maximo('apellido', 60, 'El apellido admite hasta 60 caracteres.')
            ->requerido('email', 'Ingresá tu correo electrónico.')
            ->email('email', 'El correo electrónico no es válido.')
            ->maximo('email', 120, 'El correo admite hasta 120 caracteres.');

        $cambiaEmail = $datos['email'] !== $usuario->email;

        if ($cambiaEmail && !$validador->tieneError('email') && UsuarioModel::existeEmailDeOtro($datos['email'], (int) $usuario->id_usuario)) {
            $validador->agregarError('email', 'Ya existe una cuenta con ese correo.');
        }

        // El correo es la credencial de acceso: cambiarlo se confirma con la contraseña actual.
        if ($cambiaEmail && !password_verify($datos['datos_password_actual'], $usuario->password_hash)) {
            $validador->agregarError('datos_password_actual', $datos['datos_password_actual'] === ''
                ? 'Para cambiar el correo, ingresá tu contraseña actual.'
                : 'La contraseña actual no es correcta.');
        }

        if ($validador->esValido()) {
            try {
                UsuarioModel::actualizarDatos((int) $usuario->id_usuario, $datos['nombre'], $datos['apellido'], $datos['email']);
            } catch (PDOException $e) {
                // 23000: la clave única del email. Otra cuenta tomó ese correo entre la validación y el guardado.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $validador->agregarError('email', 'Ya existe una cuenta con ese correo.');
            }
        }

        if ($validador->esValido()) {
            Session::actualizarUsuario([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
            ]);
            Session::flash('exito', $cambiaEmail
                ? 'Datos actualizados. Desde ahora ingresás con ' . $datos['email'] . '.'
                : 'Datos actualizados.');
            $this->redirigir('perfil');
        }

        // La contraseña nunca vuelve a la vista.
        unset($datos['datos_password_actual']);
        $this->mostrar(['datos' => $datos, 'erroresDatos' => $validador->errores()]);
    }

    public function actionPassword(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('perfil');
        }

        $usuario = $this->usuarioActual();
        $datos = [
            'password_actual' => $this->campo('password_actual', false),
            'password' => $this->campo('password', false),
            'password_confirmacion' => $this->campo('password_confirmacion', false),
        ];

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            $this->mostrar(['error' => 'La sesión expiró. Volvé a enviar el formulario.']);

            return;
        }

        // Mismas reglas que el registro y el recupero (Validador::REQUISITOS_PASSWORD).
        $validador = (new Validador($datos))
            ->requerido('password_actual', 'Ingresá tu contraseña actual.')
            ->requerido('password', 'Ingresá la contraseña nueva.')
            ->passwordSegura('password', 'La contraseña no cumple los requisitos.')
            ->requerido('password_confirmacion', 'Repetí la contraseña nueva.')
            ->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');

        if (!$validador->tieneError('password_actual') && !password_verify($datos['password_actual'], $usuario->password_hash)) {
            $validador->agregarError('password_actual', 'La contraseña actual no es correcta.');
        }

        if (!$validador->tieneError('password') && password_verify($datos['password'], $usuario->password_hash)) {
            $validador->agregarError('password', 'La contraseña nueva tiene que ser distinta de la actual.');
        }

        if ($validador->esValido()) {
            UsuarioModel::actualizarPassword((int) $usuario->id_usuario, $datos['password']);

            // Un enlace de "¿Olvidaste tu contraseña?" pedido antes del cambio ya no tiene que servir.
            RecuperoModel::anularPendientes((int) $usuario->id_usuario);

            // Nuevo identificador de sesión, igual que al iniciar sesión. La sesión sigue abierta.
            Session::iniciarUsuario(Session::usuario());

            // La nueva cumple la política: se apaga el aviso del inicio del administrador (Etapa 14).
            Session::guardar('password_fuera_de_politica', false);

            Session::flash('exito', 'Contraseña actualizada.');
            $this->redirigir('perfil');
        }

        $this->mostrar([
            'erroresPassword' => $validador->errores(),
            'requisitos' => Validador::requisitosPassword($datos['password']),
            'intentado' => true,
        ]);
    }

    /** El usuario de la sesión, con su hash (solo para verificar contraseñas: no se pasa a la vista). */
    private function usuarioActual(): object
    {
        $usuario = UsuarioModel::buscarPorId((int) Session::usuario()['id']);
        $this->exigirCuentaActiva($usuario);

        return $usuario;
    }

    /** Si la cuenta ya no existe o fue desactivada, la sesión deja de servir. */
    private function exigirCuentaActiva(?object $usuario): void
    {
        if ($usuario === null || !$usuario->activo) {
            Session::cerrar();
            $this->redirigir('auth/login');
        }
    }

    /** Arma la pantalla. $variables pisa los valores por defecto (por ejemplo, los errores de un formulario). */
    private function mostrar(array $variables = []): void
    {
        // buscarParaFicha no trae password_hash: es la consulta que llega a la vista.
        $usuario = UsuarioModel::buscarParaFicha((int) Session::usuario()['id']);
        $this->exigirCuentaActiva($usuario);

        // El administrador no carga boletas: no ve el bloque de actividad.
        $esUsuario = $usuario->rol === 'usuario';

        $this->render('perfil/index', $variables + [
            'titulo' => 'Mi perfil',
            'seccion' => 'perfil',
            'usuario' => $usuario,
            'actividad' => $esUsuario ? ConsumoModel::actividadDeUsuario((int) $usuario->id_usuario) : null,
            'datos' => ['nombre' => $usuario->nombre, 'apellido' => $usuario->apellido, 'email' => $usuario->email],
            'erroresDatos' => [],
            'erroresPassword' => [],
            'requisitos' => Validador::requisitosPassword(),
            'intentado' => false,
            'mensaje' => Session::tomarFlash('exito'),
            'error' => null,
        ]);
    }
}
