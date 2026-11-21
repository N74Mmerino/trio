<?php

namespace app\controllers;

use Controller;
use Session;
use Validador;
use app\models\ConsumoModel;
use app\models\ServicioModel;
use app\models\UsuarioModel;
use app\servicios\Indicadores;

class UsuarioController extends Controller
{
    /** Filtros de la pantalla Usuarios (Etapa 15). */
    private const FILTROS = ['todos', 'activos', 'inactivos', 'sin-boletas'];

    public function __construct()
    {
        Session::requiereAdmin();
    }

    public function actionIndex(): void
    {
        $filtro = in_array($this->parametro('filtro'), self::FILTROS, true) ? $this->parametro('filtro') : 'todos';
        $busqueda = mb_substr($this->parametro('q'), 0, 120);

        $this->render('usuario/index', [
            'titulo' => 'Usuarios',
            'seccion' => 'usuario',
            'usuarios' => UsuarioModel::listarParaAdmin($filtro, $busqueda),
            'conteos' => UsuarioModel::contarPorFiltro(),
            'filtro' => $filtro,
            'busqueda' => $busqueda,
            'mensaje' => Session::tomarFlash('exito'),
            'error' => Session::tomarFlash('error'),
        ]);
    }

    /** Ficha de actividad (Etapa 15): qué cargó y cuándo, sin consumos ni importes. */
    public function actionActividad(string $id): void
    {
        $usuario = UsuarioModel::buscarParaFicha((int) $id);

        // El administrador no carga boletas: no tiene ficha de actividad.
        if ($usuario === null || $usuario->rol !== 'usuario') {
            $this->error404();
        }

        $desde = Indicadores::inicioPeriodo('12');
        $cobertura = [];
        foreach (ConsumoModel::coberturaDeUsuario((int) $usuario->id_usuario, $desde) as $fila) {
            $cobertura[(int) $fila->id_servicio][$fila->mes] = (bool) $fila->con_pdf;
        }

        $this->render('usuario/actividad', [
            'titulo' => 'Actividad de ' . $usuario->nombre . ' ' . $usuario->apellido,
            'seccion' => 'usuario',
            'usuario' => $usuario,
            'actividad' => ConsumoModel::actividadDeUsuario((int) $usuario->id_usuario),
            'servicios' => ServicioModel::listar(),
            'meses' => Indicadores::mesesDesde($desde),
            'cobertura' => $cobertura,
        ]);
    }

    public function actionEditar(string $id): void
    {
        $idUsuario = (int) $id;
        $usuario = UsuarioModel::buscarPorId($idUsuario);

        if ($usuario === null) {
            $this->error404();
        }

        // Etapa 15: el admin solo corrige nombre y apellido; el email es la credencial del usuario.
        $datos = [
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
        ];
        $errores = [];
        $error = null;

        if ($this->esPost()) {
            $datos = [
                'nombre' => $this->campo('nombre'),
                'apellido' => $this->campo('apellido'),
            ];

            if (!Session::csrfValido($_POST['csrf'] ?? null)) {
                $error = 'La sesión expiró. Volvé a enviar el formulario.';
            } else {
                $validador = $this->validarEdicion($datos);

                if ($validador->esValido()) {
                    UsuarioModel::actualizarNombre($idUsuario, $datos['nombre'], $datos['apellido']);
                    Session::flash('exito', 'Usuario actualizado correctamente.');
                    $this->redirigir('usuario');
                }

                $errores = $validador->errores();
            }
        }

        $this->render('usuario/editar', [
            'titulo' => 'Editar usuario',
            'seccion' => 'usuario',
            'usuario' => $usuario,
            'datos' => $datos,
            'errores' => $errores,
            'error' => $error,
        ]);
    }

    public function actionEstado(string $id): void
    {
        $idUsuario = (int) $id;

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            Session::flash('error', 'La sesión expiró. Volvé a intentarlo.');
            $this->redirigir('usuario');
        }

        if ($idUsuario === (int) Session::usuario()['id']) {
            Session::flash('error', 'No podés activar o desactivar tu propio usuario.');
            $this->redirigir('usuario');
        }

        $usuario = UsuarioModel::buscarPorId($idUsuario);

        if ($usuario === null) {
            $this->error404();
        }

        UsuarioModel::alternarEstado($idUsuario);
        Session::flash('exito', $usuario->activo ? 'Usuario desactivado.' : 'Usuario activado.');
        $this->redirigir('usuario');
    }

    private function validarEdicion(array $datos): Validador
    {
        return (new Validador($datos))
            ->requerido('nombre', 'Ingresá el nombre.')
            ->maximo('nombre', 60, 'El nombre admite hasta 60 caracteres.')
            ->requerido('apellido', 'Ingresá el apellido.')
            ->maximo('apellido', 60, 'El apellido admite hasta 60 caracteres.');
    }
}
