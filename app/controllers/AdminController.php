<?php

namespace app\controllers;

use Controller;
use DateTimeImmutable;
use Session;
use app\models\ConsumoModel;
use app\models\ServicioModel;
use app\models\UsuarioModel;
use app\servicios\Indicadores;

/** Inicio del administrador (Etapa 14): resumen de toda la plataforma. */
class AdminController extends Controller
{
    /** El gráfico muestra el mes actual y los 5 anteriores. */
    private const MESES_GRAFICO = '6';

    public function __construct()
    {
        Session::requiereAdmin();
    }

    public function actionIndex(): void
    {
        $hoy = new DateTimeImmutable();
        $inicioMes = $hoy->modify('first day of this month')->format('Y-m-d');
        $inicioMesAnterior = $hoy->modify('first day of last month')->format('Y-m-d');

        $usuarios = UsuarioModel::resumen($inicioMes, $inicioMesAnterior);
        $boletas = ConsumoModel::resumenPlataforma($inicioMes);

        $total = (int) $usuarios->total;
        $activos = (int) $usuarios->activos;
        $totalBoletas = (int) $boletas->total;
        $conPdf = (int) $boletas->con_pdf;

        $desde = Indicadores::inicioPeriodo(self::MESES_GRAFICO, $hoy);
        $mensual = ConsumoModel::boletasMensualesPlataforma($desde);

        $this->render('admin/index', [
            'titulo' => 'Inicio',
            'seccion' => 'admin',
            'usuario' => Session::usuario(),
            'hoy' => $hoy,
            'kpis' => [
                'activos' => $activos,
                'usuarios' => $total,
                'inactivos' => $total - $activos,
                'nuevosMes' => (int) $usuarios->nuevos_mes,
                'nuevosAnterior' => (int) $usuarios->nuevos_anterior,
                'boletas' => $totalBoletas,
                'boletasMes' => (int) $boletas->cargadas_mes,
                'conPdf' => $conPdf,
                'porcentajePdf' => $totalBoletas > 0 ? $conPdf / $totalBoletas * 100 : null,
            ],
            'avisos' => $this->avisos($total - $activos),
            'ultimosRegistros' => UsuarioModel::ultimosRegistros(),
            'actividad' => ConsumoModel::actividadReciente(),
            'grafico' => $mensual === [] ? null : Indicadores::seriesMensuales($mensual, ServicioModel::listar(), $desde, $hoy),
        ]);
    }

    /** "Para revisar": solo lo que pide una acción. Lista vacía = todo en orden. */
    private function avisos(int $inactivos): array
    {
        $avisos = [];

        if (Session::obtener('password_fuera_de_politica') === true) {
            $avisos[] = ['texto' => 'Tu contraseña no cumple los requisitos actuales.', 'enlace' => null];
        }

        if ($inactivos > 0) {
            $avisos[] = ['texto' => $inactivos === 1 ? '1 usuario inactivo' : "{$inactivos} usuarios inactivos", 'enlace' => 'usuario?filtro=inactivos'];
        }

        $sinBoletas = UsuarioModel::contarActivosSinBoletas();
        if ($sinBoletas > 0) {
            $avisos[] = ['texto' => $sinBoletas === 1 ? '1 usuario sin boletas' : "{$sinBoletas} usuarios sin boletas", 'enlace' => 'usuario?filtro=sin-boletas'];
        }

        return $avisos;
    }
}
