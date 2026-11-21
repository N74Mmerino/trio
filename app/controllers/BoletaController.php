<?php

namespace app\controllers;

use Controller;
use Session;
use app\models\ConsumoModel;
use app\models\ServicioModel;
use app\models\UsuarioModel;
use app\servicios\Indicadores;

/** Listado de boletas del administrador (Etapa 15). Solo datos de la carga: sin consumos, importes ni PDF. */
class BoletaController extends Controller
{
    private const POR_PAGINA = 10;

    public function __construct()
    {
        Session::requiereAdmin();
    }

    /** GET boleta?usuario=&servicio=&origen=pdf|manual&periodo=6|12&orden=carga|boleta&pagina= */
    public function actionIndex(): void
    {
        $filtros = $this->filtros();
        $consulta = $filtros + ['desde' => $filtros['periodo'] === '' ? null : Indicadores::inicioPeriodo($filtros['periodo'])];

        $resumen = ConsumoModel::resumenParaAdmin($consulta);
        $total = (int) $resumen->total;
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min($filtros['pagina'], $paginas);

        $this->render('boleta/index', [
            'titulo' => 'Boletas',
            'seccion' => 'boleta',
            'filtros' => $filtros,
            'usuarios' => UsuarioModel::listarUsuariosComunes(),
            'servicios' => ServicioModel::listar(),
            'boletas' => ConsumoModel::listarParaAdmin($consulta, self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA),
            'resumen' => $resumen,
            'porServicio' => array_column(ConsumoModel::porServicioParaAdmin($consulta), 'total', 'id_servicio'),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => self::POR_PAGINA,
        ]);
    }

    /** Lee los filtros de la URL. Un valor fuera de la lista permitida se ignora (como si no estuviera). */
    private function filtros(): array
    {
        $opcion = fn (string $nombre, array $permitidos, string $defecto = ''): string =>
            in_array($this->parametro($nombre), $permitidos, true) ? $this->parametro($nombre) : $defecto;

        return [
            'usuario' => $this->entero('usuario'),
            'servicio' => $this->entero('servicio'),
            'origen' => $opcion('origen', ['pdf', 'manual']),
            'periodo' => $opcion('periodo', ['6', '12']),
            'orden' => $opcion('orden', ['carga', 'boleta'], 'carga'),
            'pagina' => max(1, $this->entero('pagina') ?? 1),
        ];
    }

    private function entero(string $nombre): ?int
    {
        $valor = $this->parametro($nombre);

        return ctype_digit($valor) && strlen($valor) <= 9 ? (int) $valor : null;
    }
}
