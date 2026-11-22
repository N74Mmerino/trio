<?php

namespace app\controllers;

use Controller;
use finfo;
use DataBase;
use PDOException;
use RuntimeException;
use Session;
use Validador;
use app\models\ConsumoModel;
use app\models\ServicioModel;
use app\models\VencimientoModel;
use app\servicios\AlmacenBoletas;
use app\servicios\HistorialBoletas;
use app\servicios\Indicadores;
use app\servicios\LectorBoletas;
use app\servicios\Vencimientos;

class ConsumoController extends Controller
{
    /** Clave de sesión donde queda el PDF subido hasta que el usuario confirma la carga. */
    private const PENDIENTE = 'boleta_pendiente';
    private const TAMANIO_MAXIMO = 2 * 1024 * 1024;
    /** Etapa 20: la vista de revisión lo compara para marcar la boleta repetida en "Antes de guardar". */
    private const MENSAJE_DUPLICADA = 'Ya cargaste una boleta de este servicio con esa fecha.';
    /** Etapa 23: cantidad máxima de cuotas de una boleta (EPE y ASSA: 2; Litoral Gas: 1). */
    private const CUOTAS = 2;

    public function __construct()
    {
        Session::requiereLogin();

        // Etapa 14: el administrador gestiona la plataforma; no carga ni ve consumos propios.
        if (Session::esAdministrador()) {
            $this->redirigir('admin');
        }
    }

    /**
     * GET consumo?servicio={id}&anio={AAAA}  (Etapa 17: "Mis boletas")
     * Todas las boletas del usuario agrupadas por mes, cada una comparada con la anterior del mismo servicio.
     * Un filtro inválido o ausente se ignora: todos los servicios y el año más reciente con boletas.
     */
    public function actionIndex(): void
    {
        $idUsuario = (int) Session::usuario()['id'];
        $servicios = ServicioModel::listar();

        // Se traen todas las boletas porque la variación de la primera de un año
        // se calcula contra la última del año anterior.
        $boletas = HistorialBoletas::conComparaciones(ConsumoModel::listarDelUsuario($idUsuario));

        // Etapa 23: a cada boleta se le suma su próximo vencimiento (null si no tiene cuotas cargadas).
        $cuotas = VencimientoModel::agrupadasDelUsuario($idUsuario);
        foreach ($boletas as &$b) {
            $b['vencimiento'] = Vencimientos::proxima($cuotas[$b['id']] ?? []);
        }
        unset($b);
        $anios = HistorialBoletas::anios($boletas);
        $anio = $this->anioElegido($anios);
        $servicio = $this->servicioElegido($servicios);

        $visibles = $anio === null
            ? []
            : HistorialBoletas::filtrar($boletas, $anio, $servicio !== null ? (int) $servicio->id_servicio : null);

        $this->render('consumo/index', [
            'titulo' => 'Mis boletas',
            'seccion' => 'consumo',
            'servicios' => $servicios,
            'servicio' => $servicio,
            'anios' => $anios,
            'anio' => $anio,
            'sinBoletas' => $boletas === [],
            'grupos' => HistorialBoletas::agruparPorMes($visibles),
            'resumen' => HistorialBoletas::resumen($visibles),
            'mensaje' => Session::tomarFlash('exito'), // Etapa 22: "Boleta eliminada"
        ]);
    }

    /**
     * GET consumo/ver/{id}  (Etapa 17: ficha de una boleta)
     * 404 si el id no es un número, si la boleta no existe o si es de otro usuario.
     */
    public function actionVer(string $id = ''): void
    {
        $idUsuario = (int) Session::usuario()['id'];
        $boleta = ctype_digit($id) ? ConsumoModel::buscarDelUsuario((int) $id, $idUsuario) : null;

        if ($boleta === null) {
            $this->error404();
        }

        $historial = ConsumoModel::historialServicio($idUsuario, (int) $boleta->id_servicio, null);
        $contexto = HistorialBoletas::contexto($historial, (int) $boleta->id_consumo);

        if ($contexto === null) {
            $this->error404();
        }

        $cantidad = (float) $boleta->cantidad;
        $importe = (float) $boleta->importe;
        $anterior = $contexto['anterior'];
        $costo = Indicadores::costoUnitario($importe, $cantidad);
        $costoAnterior = $anterior !== null ? Indicadores::costoUnitario((float) $anterior->importe, (float) $anterior->cantidad) : null;

        $vencimientos = VencimientoModel::deConsumo((int) $boleta->id_consumo);

        $this->render('consumo/ver', [
            'titulo' => 'Boleta de ' . mb_strtolower($boleta->servicio) . ', ' . HistorialBoletas::mes($boleta->fecha),
            'seccion' => 'consumo',
            'boleta' => $boleta,
            'anterior' => $anterior,
            'siguiente' => $contexto['siguiente'],
            'costo' => $costo,
            'variacionCantidad' => Indicadores::variacion($cantidad, $anterior !== null ? (float) $anterior->cantidad : null),
            'variacionImporte' => Indicadores::variacion($importe, $anterior !== null ? (float) $anterior->importe : null),
            'variacionCosto' => $costo !== null ? Indicadores::variacion($costo, $costoAnterior) : null,
            'costoAnterior' => $costoAnterior,
            'diferencia' => $anterior !== null
                ? Indicadores::diferenciaImporte($importe, $cantidad, (float) $anterior->importe, (float) $anterior->cantidad)
                : null,
            'grafico' => $contexto['grafico'],
            'posicionAnual' => $contexto['posicionAnual'],
            'error' => Session::tomarFlash('error'), // Etapa 22: falló el borrado
            'vencimientos' => $vencimientos, // Etapa 23
            'avisoVencimiento' => Vencimientos::aviso($vencimientos),
        ]);
    }

    /**
     * POST consumo/eliminar/{id}  (Etapa 22)
     * Borra la boleta y su PDF, y vuelve a "Mis boletas" con un aviso.
     * Solo por POST con token CSRF: un enlace o una visita a la URL no borran nada.
     * 404 si el id no es un número, si la boleta no existe o si es de otro usuario (igual que la ficha).
     */
    public function actionEliminar(string $id = ''): void
    {
        if (!$this->esPost()) {
            $this->error404();
        }

        $idUsuario = (int) Session::usuario()['id'];
        $boleta = ctype_digit($id) ? ConsumoModel::buscarDelUsuario((int) $id, $idUsuario) : null;

        if ($boleta === null) {
            $this->error404();
        }

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            Session::flash('error', 'La sesión expiró. Volvé a intentarlo.');
            $this->redirigir('consumo/ver/' . (int) $boleta->id_consumo);
        }

        // Primero la fila y después el archivo: si fallara el borrado del PDF,
        // queda un archivo suelto que no afecta a nada. Al revés, podría quedar
        // una boleta apuntando a un PDF que ya no existe.
        ConsumoModel::eliminarDelUsuario((int) $boleta->id_consumo, $idUsuario);

        if ($boleta->archivo !== null) {
            AlmacenBoletas::eliminar($boleta->archivo, $idUsuario);
        }

        Session::flash('exito', 'Boleta eliminada: ' . mb_strtolower($boleta->servicio) . ' de ' . HistorialBoletas::mes($boleta->fecha) . '.');
        $this->redirigir('consumo');
    }

    /**
     * Etapa 20: la carga se hace en tres pasos.
     *   GET  consumo/cargar              → paso 1: elegir cómo cargar (PDF o a mano)
     *   GET  consumo/cargar?modo=manual  → paso 2: formulario vacío para completar a mano
     *   POST consumo/cargar              → guarda; con errores vuelve al paso 2, si está bien va al paso 3
     * Si el formulario viene de una boleta leída (campo "comprobante"), adjunta el PDF pendiente de la sesión.
     */
    public function actionCargar(): void
    {
        $idUsuario = (int) Session::usuario()['id'];

        if (!$this->esPost()) {
            // Una visita nueva descarta el PDF que hubiera quedado pendiente (también "← Volver" del paso 2).
            $this->descartarPendiente();

            if ($this->parametro('modo') === 'manual') {
                $this->mostrarRevision($this->datosVacios(), [], null, null);
                return;
            }

            $this->mostrarEleccion(null);
            return;
        }

        $pendiente = Session::obtener(self::PENDIENTE);
        $datos = [
            'id_servicio' => $this->campo('id_servicio'),
            'cantidad' => $this->normalizarNumero($this->campo('cantidad')),
            'importe' => $this->normalizarNumero($this->campo('importe')),
            'fecha' => $this->campo('fecha'),
        ];
        // Etapa 23: cuotas (opcionales). Campos vencimiento_N e importe_cuota_N.
        for ($n = 1; $n <= self::CUOTAS; $n++) {
            $datos['vencimiento_' . $n] = $this->campo('vencimiento_' . $n);
            $datos['importe_cuota_' . $n] = $this->normalizarNumero($this->campo('importe_cuota_' . $n));
        }
        $conComprobante = is_array($pendiente) && $this->campo('comprobante') === '1';
        $errores = [];
        $error = null;

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            $error = 'La sesión expiró. Volvé a enviar el formulario.';
        } else {
            $validador = $this->validarConsumo($datos, $idUsuario);

            if ($validador->esValido()) {
                // Guarda y redirige al paso 3 (no vuelve).
                $this->guardarConsumo($idUsuario, $datos, $conComprobante ? $pendiente['archivo'] : null);
            }

            $errores = $validador->errores();
        }

        // Una carga manual descarta el PDF que hubiera quedado pendiente.
        if (!$conComprobante) {
            $this->descartarPendiente();
            $pendiente = null;
        }

        $this->mostrarRevision($datos, $errores, $error, $pendiente);
    }

    /**
     * POST consumo/subir: recibe el PDF y lo lee.
     * Si se pudo leer → paso 2 con los datos precargados para revisar. Si no → paso 1 con el error.
     */
    public function actionSubir(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('consumo/cargar');
        }

        $idUsuario = (int) Session::usuario()['id'];
        $error = null;

        $this->descartarPendiente();
        $archivo = $_FILES['boleta'] ?? null;

        if (!Session::csrfValido($_POST['csrf'] ?? null)) {
            $error = 'La sesión expiró. Volvé a subir la boleta.';
        } elseif (($problema = $this->validarArchivo($archivo)) !== null) {
            $error = $problema;
        } else {
            $nombre = AlmacenBoletas::guardarTemporal($archivo['tmp_name']);

            if ($nombre === null) {
                $error = 'No se pudo guardar el archivo subido. Intentá de nuevo.';
            }

            try {
                $leida = $nombre === null ? null : LectorBoletas::leer(AlmacenBoletas::rutaTemporal($nombre));
            } catch (RuntimeException $e) {
                $leida = null;
                $error = $e->getMessage();
            }

            if ($leida === null) {
                if ($nombre !== null) {
                    AlmacenBoletas::descartarTemporal($nombre);
                }
                $error ??= 'No pudimos leer esta boleta. El sistema lee boletas en PDF digital de la EPE (luz), Litoral Gas (gas) y Aguas Santafesinas (agua). Podés cargar los datos a mano.';
            } else {
                $pendiente = ['archivo' => $nombre, 'nombre' => basename((string) $archivo['name'])];
                Session::guardar(self::PENDIENTE, $pendiente);

                $datos = [
                    'id_servicio' => $this->idServicioPorNombre($leida['servicio']),
                    'cantidad' => $leida['cantidad'],
                    'importe' => $leida['importe'],
                    'fecha' => $leida['fecha'],
                ] + $this->datosVacios();

                // Etapa 23: cuotas leídas de los talones (puede no haber ninguna).
                foreach (array_slice($leida['vencimientos'] ?? [], 0, self::CUOTAS) as $i => $cuota) {
                    $datos['vencimiento_' . ($i + 1)] = $cuota['fecha'];
                    $datos['importe_cuota_' . ($i + 1)] = $cuota['importe'];
                }

                // Se valida ya mismo para marcar datos faltantes o una boleta que ya estaba cargada.
                $errores = $this->validarConsumo($datos, $idUsuario)->errores();
                $aviso = 'Leímos tu boleta de ' . $leida['empresa'] . ($errores === []
                    ? '. Revisá los datos y confirmá la carga.'
                    : ', pero hay datos para revisar.');

                // Sin errores, el control de duplicados ya se hizo: la vista lo muestra como controlado.
                $this->mostrarRevision($datos, $errores, null, $pendiente, $aviso, $errores === []);
                return;
            }
        }

        $this->mostrarEleccion($error);
    }

    /**
     * GET consumo/listo/{id}  (Etapa 20: paso 3)
     * Confirmación de la boleta recién guardada. 404 si el id no es un número o la boleta es de otro usuario.
     */
    public function actionListo(string $id = ''): void
    {
        $idUsuario = (int) Session::usuario()['id'];
        $boleta = ctype_digit($id) ? ConsumoModel::buscarDelUsuario((int) $id, $idUsuario) : null;

        if ($boleta === null) {
            $this->error404();
        }

        $this->render('consumo/listo', [
            'titulo' => 'Boleta registrada',
            'seccion' => 'consumo/cargar',
            'boleta' => $boleta,
        ]);
    }

    /** Muestra el PDF de un consumo, solo si pertenece al usuario logueado. */
    public function actionComprobante(string $id = ''): void
    {
        $idUsuario = (int) Session::usuario()['id'];
        $consumo = ConsumoModel::buscarPorId((int) $id);

        // 404 también para consumos ajenos: no se revela que existen.
        if ($consumo === null || (int) $consumo->id_usuario !== $idUsuario || $consumo->archivo === null) {
            $this->error404();
        }

        $ruta = AlmacenBoletas::rutaComprobante($consumo->archivo, $idUsuario);

        if ($ruta === null) {
            $this->error404();
        }

        $this->pdf($ruta, 'boleta-' . $consumo->fecha . '.pdf');
    }

    /** Año de la URL si tiene boletas; si no, el más reciente con boletas (null si no hay ninguna). */
    private function anioElegido(array $anios): ?int
    {
        $anio = $this->parametro('anio');

        if (ctype_digit($anio) && in_array((int) $anio, $anios, true)) {
            return (int) $anio;
        }

        return $anios[0] ?? null;
    }

    /** Servicio de la URL; null (todos) si falta, no es un número o no existe. */
    private function servicioElegido(array $servicios): ?object
    {
        $id = $this->parametro('servicio');

        if (!ctype_digit($id)) {
            return null;
        }

        foreach ($servicios as $servicio) {
            if ((int) $servicio->id_servicio === (int) $id) {
                return $servicio;
            }
        }

        return null;
    }

    private function guardarConsumo(int $idUsuario, array $datos, ?string $archivo): void
    {
        if ($archivo !== null && !AlmacenBoletas::confirmar($archivo, $idUsuario)) {
            $archivo = null;
        }

        // Etapa 23: la boleta y sus cuotas se guardan juntas en una transacción.
        // Si falla cualquiera de los INSERT, se deshace todo: no queda una boleta sin sus cuotas.
        try {
            DataBase::iniciarTransaccion();
            $idConsumo = ConsumoModel::registrar($idUsuario, (int) $datos['id_servicio'], $datos['fecha'], $datos['cantidad'], $datos['importe'], $archivo);

            foreach ($this->cuotasCompletas($datos) as $numero => $cuota) {
                VencimientoModel::registrar($idConsumo, $numero, $cuota['fecha'], $cuota['importe']);
            }

            DataBase::confirmarTransaccion();
        } catch (PDOException $e) {
            DataBase::revertirTransaccion();

            // Si el INSERT falla no dejamos el PDF huérfano en disco.
            if ($archivo !== null) {
                AlmacenBoletas::eliminar($archivo, $idUsuario);
            }
            throw $e;
        }

        Session::quitar(self::PENDIENTE);
        // Etapa 20: en vez de un mensaje en la misma pantalla, el paso 3 muestra la boleta guardada (patrón PRG).
        $this->redirigir('consumo/listo/' . $idConsumo);
    }

    /** Paso 1: elegir cómo cargar la boleta. $error: problema al subir o leer el PDF. */
    private function mostrarEleccion(?string $error): void
    {
        $this->render('consumo/cargar', [
            'titulo' => 'Cargar boleta',
            'seccion' => 'consumo/cargar',
            'error' => $error,
        ]);
    }

    /**
     * Paso 2: revisar los datos (vienen del PDF o se completan a mano).
     * $sinDuplicado: true cuando ya se controló que no exista otra boleta del mismo servicio y fecha.
     */
    private function mostrarRevision(array $datos, array $errores, ?string $error, ?array $pendiente, ?string $aviso = null, bool $sinDuplicado = false): void
    {
        $this->render('consumo/revisar', [
            'titulo' => 'Revisar boleta',
            'seccion' => 'consumo/cargar',
            'servicios' => ServicioModel::listar(),
            'datos' => $datos,
            'errores' => $errores,
            'error' => $error,
            'aviso' => $aviso,
            // Etapa 20: el aviso es una advertencia (amarillo) cuando la boleta se leyó pero hay datos para revisar.
            'avisoEsAdvertencia' => $errores !== [],
            'pendiente' => $pendiente,
            'duplicada' => ($errores['fecha'] ?? '') === self::MENSAJE_DUPLICADA,
            'sinDuplicado' => $sinDuplicado,
        ]);
    }

    private function descartarPendiente(): void
    {
        $pendiente = Session::obtener(self::PENDIENTE);

        if (is_array($pendiente)) {
            AlmacenBoletas::descartarTemporal($pendiente['archivo']);
        }

        Session::quitar(self::PENDIENTE);
    }

    /** Devuelve un mensaje de error o null si el archivo subido es un PDF válido. */
    private function validarArchivo(mixed $archivo): ?string
    {
        if (!is_array($archivo) || !isset($archivo['error'], $archivo['tmp_name']) || is_array($archivo['error'])) {
            return 'Elegí un archivo PDF.';
        }

        switch ($archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return 'Elegí un archivo PDF.';
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo supera los 2 MB.';
            default:
                return 'No se pudo subir el archivo. Intentá de nuevo.';
        }

        if ($archivo['size'] > self::TAMANIO_MAXIMO) {
            return 'El archivo supera los 2 MB.';
        }

        // Se revisa el contenido real del archivo, no la extensión ni lo que declara el navegador.
        if ((new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']) !== 'application/pdf') {
            return 'El archivo no es un PDF.';
        }

        return null;
    }

    private function idServicioPorNombre(string $nombre): string
    {
        foreach (ServicioModel::listar() as $servicio) {
            if ($servicio->nombre === $nombre) {
                return (string) $servicio->id_servicio;
            }
        }

        return '';
    }

    /**
     * Lleva un número escrito por el usuario al formato que guarda la base: punto decimal y sin separador de miles.
     *   "152.658,33" → "152658.33"  (con coma: la coma es el decimal y los puntos son de miles)
     *   "152.658"    → "152658"     (sin coma, pero con grupos de a tres: son miles)
     *   "38.5", "465", "101456.06" → sin cambios
     * No valida: si el resultado no es un número, lo rechaza Validador::numero().
     */
    private function normalizarNumero(string $valor): string
    {
        $valor = str_replace([' ', '$'], '', $valor);

        if (str_contains($valor, ',')) {
            return str_replace(',', '.', str_replace('.', '', $valor));
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor) === 1) {
            return str_replace('.', '', $valor);
        }

        return $valor;
    }

    private function validarConsumo(array $datos, int $idUsuario): Validador
    {
        $validador = (new Validador($datos))
            ->requerido('id_servicio', 'Elegí el servicio.')
            ->requerido('cantidad', 'Ingresá la cantidad consumida.')
            ->numero('cantidad', 'La cantidad debe ser un número.')
            ->rango('cantidad', 0, 99999999.99, 'Ingresá una cantidad entre 0 y 99.999.999,99.')
            ->requerido('importe', 'Ingresá el importe de la boleta.')
            ->numero('importe', 'El importe debe ser un número.')
            ->rango('importe', 0, 9999999999.99, 'Ingresá un importe entre 0 y 9.999.999.999,99.')
            ->requerido('fecha', 'Ingresá la fecha de la boleta.')
            ->fecha('fecha', 'La fecha no es válida.')
            ->noFutura('fecha', 'La fecha no puede ser posterior a hoy.');

        if (!$validador->tieneError('id_servicio') && ServicioModel::buscarPorId((int) $datos['id_servicio']) === null) {
            $validador->agregarError('id_servicio', 'El servicio elegido no existe.');
        }

        // Duplicados: mismo usuario, servicio y fecha (también lo impide la clave UNIQUE de la base).
        if ($validador->esValido() && ConsumoModel::existe($idUsuario, (int) $datos['id_servicio'], $datos['fecha'])) {
            $validador->agregarError('fecha', self::MENSAJE_DUPLICADA);
        }

        // Etapa 23: después del control de duplicados, para que un error en una cuota no lo saltee.
        $this->validarVencimientos($validador, $datos);

        return $validador;
    }

    /**
     * Etapa 23: las cuotas son opcionales, pero una cuota empezada tiene que estar completa.
     *   - Fecha e importe van juntos (no se guarda uno solo).
     *   - La cuota 2 necesita la cuota 1.
     *   - El vencimiento no puede ser anterior a la fecha de la boleta.
     *   - La cuota 2 vence después de la cuota 1.
     * Que las cuotas sumen el importe total NO es un error (puede haber redondeos): la vista lo avisa.
     */
    private function validarVencimientos(Validador $validador, array $datos): void
    {
        $anterior = null;

        for ($n = 1; $n <= self::CUOTAS; $n++) {
            $fecha = 'vencimiento_' . $n;
            $importe = 'importe_cuota_' . $n;

            if (!$this->cuotaEmpezada($datos, $n)) {
                continue;
            }

            if ($n > 1 && !$this->cuotaEmpezada($datos, $n - 1)) {
                $validador->agregarError('vencimiento_' . ($n - 1), 'Completá primero la cuota ' . ($n - 1) . '.');
            }

            $validador
                ->requerido($fecha, 'Ingresá la fecha de vencimiento de la cuota ' . $n . '.')
                ->fecha($fecha, 'La fecha de vencimiento de la cuota ' . $n . ' no es válida.')
                ->requerido($importe, 'Ingresá el importe de la cuota ' . $n . '.')
                ->numero($importe, 'El importe de la cuota ' . $n . ' debe ser un número.')
                ->rango($importe, 0, 9999999999.99, 'Ingresá un importe entre 0 y 9.999.999.999,99.');

            if ($validador->tieneError($fecha)) {
                continue;
            }

            if (!$validador->tieneError('fecha') && $datos['fecha'] !== '' && $datos[$fecha] < $datos['fecha']) {
                $validador->agregarError($fecha, 'El vencimiento no puede ser anterior a la fecha de la boleta.');
            } elseif ($anterior !== null && $datos[$fecha] <= $anterior) {
                $validador->agregarError($fecha, 'La cuota ' . $n . ' tiene que vencer después de la cuota ' . ($n - 1) . '.');
            }

            $anterior = $datos[$fecha];
        }
    }

    /** Etapa 23: la cuota tiene algo escrito (fecha o importe). */
    private function cuotaEmpezada(array $datos, int $n): bool
    {
        return trim($datos['vencimiento_' . $n] ?? '') !== '' || trim($datos['importe_cuota_' . $n] ?? '') !== '';
    }

    /** Etapa 23: cuotas para guardar, [número => ['fecha' => ..., 'importe' => ...]]. Ya validadas. */
    private function cuotasCompletas(array $datos): array
    {
        $cuotas = [];

        for ($n = 1; $n <= self::CUOTAS; $n++) {
            if ($this->cuotaEmpezada($datos, $n)) {
                $cuotas[$n] = ['fecha' => $datos['vencimiento_' . $n], 'importe' => $datos['importe_cuota_' . $n]];
            }
        }

        return $cuotas;
    }

    /** Formulario vacío del paso 2 (datos de la boleta y cuotas). */
    private function datosVacios(): array
    {
        $datos = ['id_servicio' => '', 'cantidad' => '', 'importe' => '', 'fecha' => ''];

        for ($n = 1; $n <= self::CUOTAS; $n++) {
            $datos['vencimiento_' . $n] = '';
            $datos['importe_cuota_' . $n] = '';
        }

        return $datos;
    }
}
