<?php

namespace app\controllers;

use Controller;
use Session;
use Validador;
use app\models\UsuarioModel;

class AuthController extends Controller
{
    public function actionLogin(): void
    {
        if (Session::estaAutenticado()) {
            $this->redirigir(Session::rutaInicio());
        }

        $email = '';
        $error = null;

        if ($this->esPost()) {
            $email = mb_strtolower($this->campo('email'));
            $password = $this->campo('password', false);

            if (!Session::csrfValido($_POST['csrf'] ?? null)) {
                $error = 'La sesión expiró. Volvé a enviar el formulario.';
            } else {
                $usuario = UsuarioModel::buscarActivoPorEmail($email);

                if ($usuario !== null && password_verify($password, $usuario->password_hash)) {
                    Session::iniciarUsuario([
                        'id' => $usuario->id_usuario,
                        'nombre' => $usuario->nombre,
                        'apellido' => $usuario->apellido,
                        'email' => $usuario->email,
                        'rol' => $usuario->rol,
                    ]);

                    // Etapa 14: el inicio del admin avisa si la contraseña no cumple la política actual.
                    // Solo se guarda true/false; la contraseña no se guarda en ningún lado.
                    $politica = (new Validador(['password' => $password]))->passwordSegura('password', '');
                    Session::guardar('password_fuera_de_politica', !$politica->esValido());

                    $this->redirigir(Session::rutaInicio());
                }

                $error = 'Email o contraseña incorrectos.';
            }
        }

        $this->render('auth/login', [
            'titulo' => 'Iniciar sesión',
            'mensaje' => Session::tomarFlash('exito'),
            'email' => $email,
            'error' => $error,
        ]);
    }

    public function actionLogout(): void
    {
        Session::cerrar();
        $this->redirigir('auth/login');
    }

    public function actionRegistro(): void
    {
        if (Session::estaAutenticado() && !Session::esAdministrador()) {
            $this->redirigir('dashboard');
        }

        $datos = ['nombre' => '', 'apellido' => '', 'email' => ''];
        $errores = [];
        $error = null;
        $requisitos = Validador::requisitosPassword();
        $intentado = false;

        if ($this->esPost()) {
            $datos = [
                'nombre' => $this->campo('nombre'),
                'apellido' => $this->campo('apellido'),
                'email' => mb_strtolower($this->campo('email')),
                'password' => $this->campo('password', false),
                'password_confirmacion' => $this->campo('password_confirmacion', false),
            ];

            if (!Session::csrfValido($_POST['csrf'] ?? null)) {
                $error = 'La sesión expiró. Volvé a enviar el formulario.';
            } else {
                $validador = $this->validarRegistro($datos);

                if ($validador->esValido()) {
                    UsuarioModel::crear($datos['nombre'], $datos['apellido'], $datos['email'], $datos['password']);

                    if (Session::esAdministrador()) {
                        Session::flash('exito', 'Usuario creado correctamente.');
                        $this->redirigir('usuario');
                    }

                    Session::flash('exito', 'Cuenta creada. Ya podés iniciar sesión.');
                    $this->redirigir('auth/login');
                }

                $errores = $validador->errores();
                $requisitos = Validador::requisitosPassword($datos['password']);
                $intentado = true;
            }
        }

        // La contraseña nunca viaja a la vista.
        unset($datos['password'], $datos['password_confirmacion']);

        $this->render('auth/registro', [
            'titulo' => 'Crear cuenta',
            'datos' => $datos,
            'errores' => $errores,
            'error' => $error,
            'requisitos' => $requisitos,
            'intentado' => $intentado,
        ]);
    }

    private function validarRegistro(array $datos): Validador
    {
        $validador = (new Validador($datos))
            ->requerido('nombre', 'Ingresá tu nombre.')
            ->maximo('nombre', 60, 'El nombre admite hasta 60 caracteres.')
            ->requerido('apellido', 'Ingresá tu apellido.')
            ->maximo('apellido', 60, 'El apellido admite hasta 60 caracteres.')
            ->requerido('email', 'Ingresá tu correo electrónico.')
            ->email('email', 'El correo electrónico no es válido.')
            ->maximo('email', 120, 'El correo admite hasta 120 caracteres.')
            ->requerido('password', 'Ingresá una contraseña.')
            ->passwordSegura('password', 'La contraseña no cumple los requisitos.')
            ->requerido('password_confirmacion', 'Repetí la contraseña.')
            ->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');

        if (!$validador->tieneError('email') && UsuarioModel::existeEmail($datos['email'])) {
            $validador->agregarError('email', 'Ya existe una cuenta con ese correo.');
        }

        return $validador;
    }
}
