<?php

namespace app\controllers;

use app\models\ConsumoModel;
use app\models\ServicioModel;
use app\servicios\Indicadores;
use Controller;
use Session;

class DashboardController extends Controller
{
    public function __construct()
    {
        Session::requiereLogin();

        // Etapa 14: el administrador gestiona la plataforma; no carga ni ve consumos propios.
        if (Session::esAdministrador()) {
            $this->redirigir('admin');
        }
    }

    /**
     * GET dashboard?servicio={id}&periodo={6|12|todo}
     * Sin servicio: vista General (en $). Con servicio: vista del servicio (en su unidad).
     */
    public function actionIndex(): void
    {
        $usuario = Session::usuario();
        $idUsuario = (int) $usuario['id'];
        $servicios = ServicioModel::listar();
        $periodo = $this->periodoElegido();
        $servicio = $this->servicioElegido();

        $datos = [
            'titulo' => 'Dashboard',
            'seccion' => 'dashboard',
            'usuario' => $usuario,
            'servicios' => $servicios,
            'servicio' => $servicio,
            'periodo' => $periodo,
            'sinConsumos' => !ConsumoModel::tieneConsumos($idUsuario),
        ];

        if (!$datos['sinConsumos']) {
            $desde = Indicadores::inicioPeriodo($periodo);

            $datos += $servicio === null
                ? $this->datosGenerales($idUsuario, $servicios, $periodo, $desde)
                : $this->datosServicio($idUsuario, $servicio, $desde);
        }

        $this->render('dashboard/index', $datos);
    }

    /** Vista General: todo en pesos, porque es lo único que se puede sumar entre servicios. */
    private function datosGenerales(int $idUsuario, array $servicios, string $periodo, ?string $desde): array
    {
        // KPIs
        $porServicio = ConsumoModel::gastoPorServicio($idUsuario, $desde);
        $total = array_sum(array_map(fn ($fila) => (float) $fila->total, $porServicio));
        $boletas = array_sum(array_map(fn ($fila) => (int) $fila->boletas, $porServicio));

        $mayor = null;
        foreach ($porServicio as $fila) {
            if ($mayor === null || (float) $fila->total > (float) $mayor->total) {
                $mayor = $fila;
            }
        }

        // Comparación con el período anterior de igual duración ("Todo" no tiene anterior).
        $variacionTotal = null;
        if ($desde !== null) {
            $anterior = ConsumoModel::gastoPorServicio($idUsuario, Indicadores::inicioPeriodoAnterior($periodo), $desde);
            $totalAnterior = array_sum(array_map(fn ($fila) => (float) $fila->total, $anterior));
            $variacionTotal = Indicadores::variacion($total, $totalAnterior);
        }

        // Tabla: última boleta de cada servicio y variación de consumo contra la anterior.
        $resumen = [];
        foreach ($servicios as $s) {
            $ultimas = ConsumoModel::ultimasPorServicio($idUsuario, (int) $s->id_servicio, 2);

            if ($ultimas === []) {
                continue;
            }

            $resumen[] = [
                'servicio' => $s,
                'ultima' => $ultimas[0],
                'variacion' => Indicadores::variacion((float) $ultimas[0]->cantidad, isset($ultimas[1]) ? (float) $ultimas[1]->cantidad : null),
            ];
        }

        // Gráficos
        $mensual = ConsumoModel::gastoMensual($idUsuario, $desde);
        $graficos = ['tipo' => 'general', 'mensual' => null, 'distribucion' => null];

        if ($mensual !== []) {
            // Con "Todo" el eje arranca en el mes de la primera boleta.
            $inicioEje = $desde ?? $mensual[0]->mes . '-01';
            $graficos['mensual'] = Indicadores::seriesMensuales($mensual, $servicios, $inicioEje);
            $graficos['distribucion'] = [
                'ids' => array_map(fn ($fila) => (int) $fila->id_servicio, $porServicio),
                'etiquetas' => array_map(fn ($fila) => $fila->nombre, $porServicio),
                'datos' => array_map(fn ($fila) => (float) $fila->total, $porServicio),
            ];
        }

        return [
            'kpis' => [
                'total' => $total,
                'boletas' => $boletas,
                'mayor' => $mayor,
                'porcentajeMayor' => $mayor !== null && $total > 0 ? (float) $mayor->total / $total * 100 : null,
                'variacionTotal' => $variacionTotal,
            ],
            'resumen' => $resumen,
            'recientes' => ConsumoModel::ultimasCargadas($idUsuario, 4), // Etapa 18
            'graficos' => $graficos,
        ];
    }

    /** Vista de un servicio: consumo en su unidad, importe y costo por unidad. */
    private function datosServicio(int $idUsuario, object $servicio, ?string $desde): array
    {
        $idServicio = (int) $servicio->id_servicio;

        // KPIs: última boleta contra la anterior (sin importar el período elegido).
        $ultimas = ConsumoModel::ultimasPorServicio($idUsuario, $idServicio, 2);
        $ultima = $ultimas[0] ?? null;
        $anterior = $ultimas[1] ?? null;
        $kpis = null;

        if ($ultima !== null) {
            $costo = Indicadores::costoUnitario((float) $ultima->importe, (float) $ultima->cantidad);
            $costoAnterior = $anterior !== null
                ? Indicadores::costoUnitario((float) $anterior->importe, (float) $anterior->cantidad)
                : null;

            $kpis = [
                'ultima' => $ultima,
                'variacionCantidad' => Indicadores::variacion((float) $ultima->cantidad, $anterior !== null ? (float) $anterior->cantidad : null),
                'variacionImporte' => Indicadores::variacion((float) $ultima->importe, $anterior !== null ? (float) $anterior->importe : null),
                'costo' => $costo,
                'variacionCosto' => $costo !== null ? Indicadores::variacion($costo, $costoAnterior) : null,
            ];
        }

        // Gráficos: una entrada por boleta del período.
        $historial = ConsumoModel::historialServicio($idUsuario, $idServicio, $desde);

        return [
            'kpis' => $kpis,
            'graficos' => [
                'tipo' => 'servicio',
                'id' => $idServicio,
                'unidad' => $servicio->unidad,
                'etiquetas' => array_map(fn ($fila) => date('d/m/y', strtotime($fila->fecha)), $historial),
                'cantidades' => array_map(fn ($fila) => (float) $fila->cantidad, $historial),
                'importes' => array_map(fn ($fila) => (float) $fila->importe, $historial),
                'costos' => array_map(fn ($fila) => Indicadores::costoUnitario((float) $fila->importe, (float) $fila->cantidad), $historial),
            ],
        ];
    }

    /** Período de la URL; si falta o no es válido, el período por defecto. */
    private function periodoElegido(): string
    {
        $periodo = $_GET['periodo'] ?? '';

        return is_string($periodo) && array_key_exists($periodo, Indicadores::PERIODOS)
            ? $periodo
            : Indicadores::PERIODO_POR_DEFECTO;
    }

    /** Servicio de la URL; null (vista General) si falta, no es un número o no existe. */
    private function servicioElegido(): ?object
    {
        $id = $_GET['servicio'] ?? '';

        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }

        return ServicioModel::buscarPorId((int) $id);
    }
}
