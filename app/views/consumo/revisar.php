<?php
// Etapa 20: paso 2 de la carga. Revisar los datos (leídos del PDF o completados a mano), con vista previa.
// Recibe del controlador: servicios, datos, errores, error, aviso, avisoEsAdvertencia, pendiente,
// duplicada (la boleta ya estaba cargada) y sinDuplicado (ya se controló que no esté repetida).
// La lista "Antes de guardar" la arma PHP; js/cargar.js solo la actualiza mientras se escribe.
// Etapa 23: bloque "Vencimientos" (cuotas opcionales, hasta 2) y sus controles en "Antes de guardar".

use app\servicios\Vencimientos;

$paso = 2;
$iconos = require APP_PATH . 'views/consumo/iconos.php';
$conPdf = is_array($pendiente);

$idElegido = (string) $datos['id_servicio'];
$servicioElegido = null;
foreach ($servicios as $servicio) {
    if ((string) $servicio->id_servicio === $idElegido) {
        $servicioElegido = $servicio;
    }
}
$nombreServicio = $servicioElegido !== null ? $servicioElegido->nombre : 'Servicio';
$unidad = $servicioElegido !== null ? $servicioElegido->unidad : '—';

// Los números se muestran en formato argentino; lo que no es un número se muestra tal como se escribió.
$cantidadCampo = is_numeric($datos['cantidad']) ? Formato::numero((float) $datos['cantidad']) : $datos['cantidad'];
$importeCampo = is_numeric($datos['importe']) ? number_format((float) $datos['importe'], 2, ',', '.') : $datos['importe'];

$fechaEsValida = function (string $fecha): bool {
    $d = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
};
$fechaTexto = $fechaEsValida($datos['fecha']) ? Formato::fecha($datos['fecha']) : 'dd/mm/aaaa';

// Estado de cada control: 'ok', 'error' (lo marcó la validación) o 'pendiente' (todavía vacío).
$estado = function (string $campo) use ($datos, $errores): string {
    if (isset($errores[$campo])) {
        return 'error';
    }
    return trim((string) $datos[$campo]) === '' ? 'pendiente' : 'ok';
};
$estadoFecha = $duplicada ? 'ok' : $estado('fecha'); // La fecha en sí es válida: lo repetido se marca aparte.

$chequeos = [
    'servicio' => [$estado('id_servicio'), ['ok' => 'Servicio: ' . $nombreServicio, 'pendiente' => 'Elegí el servicio', 'error' => $errores['id_servicio'] ?? ''], ''],
    'cantidad' => [$estado('cantidad'), ['ok' => 'Cantidad válida', 'pendiente' => 'Falta la cantidad', 'error' => $errores['cantidad'] ?? ''], ''],
    'importe' => [$estado('importe'), ['ok' => 'Importe válido', 'pendiente' => 'Falta el importe', 'error' => $errores['importe'] ?? ''], ''],
    'fecha' => [$estadoFecha, ['ok' => 'Fecha válida y no posterior a hoy', 'pendiente' => 'Falta la fecha', 'error' => $errores['fecha'] ?? ''], ''],
];
if ($duplicada) {
    $chequeos['duplicado'] = ['error', ['error' => 'Ya tenés una boleta de ' . $nombreServicio . ' del ' . $fechaTexto], 'Si es otra boleta, corregí la fecha. Si es la misma, no hace falta cargarla de nuevo.'];
} elseif ($sinDuplicado) {
    $chequeos['duplicado'] = ['ok', ['ok' => 'No tenés otra boleta de ' . $nombreServicio . ' con esa fecha'], 'Controlado al leer el PDF'];
} else {
    $chequeos['duplicado'] = ['pendiente', ['pendiente' => 'Que no esté repetida'], 'Se controla al guardar'];
}
$chequeos['comprobante'] = $conPdf
    ? ['ok', ['ok' => 'Comprobante PDF adjunto'], '']
    : ['pendiente', ['pendiente' => 'Sin comprobante'], 'Carga a mano'];

// Etapa 23: cuotas. Los importes se muestran en formato argentino, igual que el importe total.
$cuotas = [];
foreach ([1, 2] as $n) {
    $fechaCuota = (string) ($datos['vencimiento_' . $n] ?? '');
    $importeCuota = (string) ($datos['importe_cuota_' . $n] ?? '');
    $cuotas[$n] = [
        'fecha' => $fechaCuota,
        'importe' => $importeCuota,
        'importeCampo' => is_numeric($importeCuota) ? number_format((float) $importeCuota, 2, ',', '.') : $importeCuota,
        'empezada' => trim($fechaCuota) !== '' || trim($importeCuota) !== '',
        'errorFecha' => $errores['vencimiento_' . $n] ?? null,
        'errorImporte' => $errores['importe_cuota_' . $n] ?? null,
    ];
}
$empezadas = array_filter($cuotas, fn ($c) => $c['empezada']);
$erroresCuotas = array_values(array_filter(
    $errores,
    fn ($campo) => str_starts_with($campo, 'vencimiento_') || str_starts_with($campo, 'importe_cuota_'),
    ARRAY_FILTER_USE_KEY
));

if ($erroresCuotas !== []) {
    $chequeos['vencimientos'] = ['error', ['error' => $erroresCuotas[0]], ''];
} elseif ($empezadas === []) {
    $chequeos['vencimientos'] = ['pendiente', ['pendiente' => 'Sin vencimientos'], 'Son opcionales'];
} else {
    $chequeos['vencimientos'] = ['ok', ['ok' => count($empezadas) === 1 ? 'Vencimiento válido' : 'Vencimientos válidos y en orden'], ''];
}

// Que las cuotas sumen el total es solo un aviso (amarillo): no impide guardar.
$sumaCuotas = 0.0;
$sumaCalculable = $empezadas !== [] && $erroresCuotas === [] && is_numeric($datos['importe']);
foreach ($empezadas as $c) {
    $sumaCalculable = $sumaCalculable && is_numeric($c['importe']);
    $sumaCuotas += (float) $c['importe'];
}
if ($sumaCalculable && abs($sumaCuotas - (float) $datos['importe']) < 0.005) {
    $chequeos['suma'] = ['ok', ['ok' => 'Las cuotas suman el importe total'], ''];
} elseif ($sumaCalculable) {
    $chequeos['suma'] = ['aviso', ['aviso' => 'Las cuotas suman ' . Formato::pesos($sumaCuotas) . ' y el total es ' . Formato::pesos((float) $datos['importe'])], 'Revisá los importes con la boleta. Si coinciden con el papel, podés guardar igual.'];
} else {
    $chequeos['suma'] = ['pendiente', ['pendiente' => 'Las cuotas suman el importe total'], ''];
}
$sumaVisible = $sumaCalculable;

// Vista previa: la próxima cuota, como se va a ver en Mis boletas.
$cuotasValidas = [];
foreach ($cuotas as $n => $c) {
    if ($c['empezada'] && $c['errorFecha'] === null && $fechaEsValida($c['fecha'])) {
        $cuotasValidas[] = (object) ['numero_cuota' => $n, 'fecha_vencimiento' => $c['fecha']];
    }
}
$proximaCuota = Vencimientos::proxima($cuotasValidas);

// El comprobante queda último en la lista.
$comprobante = $chequeos['comprobante'];
unset($chequeos['comprobante']);
$chequeos['comprobante'] = $comprobante;

$marcas = ['ok' => '✓', 'error' => '!', 'pendiente' => '·', 'aviso' => '!'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <section class="page-heading load-heading" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Monitoreo</p>
                <h1 id="page-title">Cargar boleta</h1>
            </div>
            <?php require APP_PATH . 'views/consumo/pasos.php'; ?>
        </section>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($aviso)): ?>
            <div class="alert <?= $avisoEsAdvertencia ? 'alert-warning' : 'alert-success' ?>" role="<?= $avisoEsAdvertencia ? 'alert' : 'status' ?>"><?= htmlspecialchars($aviso) ?></div>
        <?php endif; ?>

        <form id="form-revisar" class="review-grid" action="<?= BASE_URL ?>consumo/cargar" method="post"
              data-hoy="<?= date('Y-m-d') ?>"
              data-duplicado="<?= $duplicada ? 'repetida' : ($sinDuplicado ? 'controlado' : 'pendiente') ?>"
              data-servicio-leido="<?= htmlspecialchars($idElegido) ?>"
              data-fecha-leida="<?= htmlspecialchars($datos['fecha']) ?>"
              data-umbral="<?= Vencimientos::UMBRAL_DIAS ?>">
            <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
            <?php if ($conPdf): ?>
                <input type="hidden" name="comprobante" value="1">
            <?php endif; ?>

            <section class="card" aria-labelledby="datos-titulo">
                <h2 id="datos-titulo"><?= $conPdf ? 'Revisá los datos' : 'Completá los datos' ?></h2>
                <p class="card-subtitle"><?= $conPdf ? 'Si algo no coincide con la boleta, corregilo antes de confirmar.' : 'Copialos tal como figuran en la boleta.' ?></p>

                <div class="bill-form">
                    <?php if ($conPdf): ?>
                        <p class="file-chip">
                            <span class="file-chip-badge" aria-hidden="true">PDF</span>
                            <span class="file-chip-text"><span>Comprobante adjunto</span><strong><?= htmlspecialchars($pendiente['nombre']) ?></strong></span>
                        </p>
                    <?php endif; ?>

                    <fieldset class="service-picker">
                        <legend>Servicio</legend>
                        <div class="service-options">
                            <?php foreach ($servicios as $servicio): ?>
                                <?php $id = (int) $servicio->id_servicio; ?>
                                <label class="service-option">
                                    <input type="radio" name="id_servicio" value="<?= $id ?>" data-nombre="<?= htmlspecialchars($servicio->nombre) ?>" data-unidad="<?= htmlspecialchars($servicio->unidad) ?>"<?= (string) $id === $idElegido ? ' checked' : '' ?>>
                                    <span class="service-option-icon" aria-hidden="true"><?= $iconos[$id] ?? '' ?></span>
                                    <span class="service-option-text">
                                        <span class="service-option-name"><?= htmlspecialchars($servicio->nombre) ?></span>
                                        <span class="service-option-unit"><?= htmlspecialchars($servicio->unidad) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errores['id_servicio'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['id_servicio']) ?></p>
                        <?php elseif ($conPdf && $servicioElegido !== null): ?>
                            <p class="field-hint">Detectado en la boleta. Cambialo solo si no corresponde.</p>
                        <?php endif; ?>
                    </fieldset>

                    <div class="field-row">
                        <div class="field-group">
                            <label for="cantidad">Cantidad consumida</label>
                            <div class="input-affix<?= isset($errores['cantidad']) ? ' is-invalid' : '' ?>">
                                <input id="cantidad" name="cantidad" type="text" inputmode="decimal" autocomplete="off" placeholder="Ej.: 465" value="<?= htmlspecialchars($cantidadCampo) ?>"<?= isset($errores['cantidad']) ? ' aria-invalid="true"' : '' ?>>
                                <span class="input-affix-text" data-unidad-campo><?= htmlspecialchars($unidad) ?></span>
                            </div>
                            <?php if (isset($errores['cantidad'])): ?>
                                <p class="field-error"><?= htmlspecialchars($errores['cantidad']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="field-group">
                            <label for="importe">Importe total</label>
                            <div class="input-affix<?= isset($errores['importe']) ? ' is-invalid' : '' ?>">
                                <span class="input-affix-text" aria-hidden="true">$</span>
                                <input id="importe" name="importe" type="text" inputmode="decimal" autocomplete="off" placeholder="Ej.: 152.658,33" value="<?= htmlspecialchars($importeCampo) ?>"<?= isset($errores['importe']) ? ' aria-invalid="true"' : '' ?>>
                            </div>
                            <?php if (isset($errores['importe'])): ?>
                                <p class="field-error"><?= htmlspecialchars($errores['importe']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="field-hint">Podés escribir los importes como en la boleta: 152.658,33</p>

                    <div class="field-group">
                        <label for="fecha">Fecha de emisión</label>
                        <input id="fecha" name="fecha" type="date" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($datos['fecha']) ?>"<?= isset($errores['fecha']) ? ' class="is-invalid" aria-invalid="true"' : '' ?>>
                        <?php if (isset($errores['fecha'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['fecha']) ?></p>
                        <?php endif; ?>
                    </div>

                    <fieldset class="cuotas" data-cuotas>
                        <legend>Vencimientos <span class="optional-tag">(opcional)</span></legend>
                        <p class="field-hint">
                            <?php if ($conPdf && $empezadas !== []): ?>
                                Leídos de los talones de pago. Corregilos si no coinciden con la boleta.
                            <?php elseif ($conPdf): ?>
                                No pudimos leer los vencimientos de esta boleta. Si querés, copialos del talón de pago.
                            <?php else: ?>
                                Copialos del talón de pago. Si la boleta se paga en dos cuotas, completá las dos.
                            <?php endif; ?>
                        </p>
                        <?php foreach ($cuotas as $n => $c): ?>
                            <div class="cuota-row" data-cuota="<?= $n ?>">
                                <span class="cuota-num">Cuota <?= $n ?></span>
                                <div class="field-group">
                                    <label for="vencimiento_<?= $n ?>">Vence el</label>
                                    <input id="vencimiento_<?= $n ?>" name="vencimiento_<?= $n ?>" type="date" value="<?= htmlspecialchars($c['fecha']) ?>"<?= $c['errorFecha'] !== null ? ' class="is-invalid" aria-invalid="true"' : '' ?>>
                                    <?php if ($c['errorFecha'] !== null): ?>
                                        <p class="field-error"><?= htmlspecialchars($c['errorFecha']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="field-group">
                                    <label for="importe_cuota_<?= $n ?>">Importe</label>
                                    <div class="input-affix<?= $c['errorImporte'] !== null ? ' is-invalid' : '' ?>">
                                        <span class="input-affix-text" aria-hidden="true">$</span>
                                        <input id="importe_cuota_<?= $n ?>" name="importe_cuota_<?= $n ?>" type="text" inputmode="decimal" autocomplete="off" value="<?= htmlspecialchars($c['importeCampo']) ?>"<?= $c['errorImporte'] !== null ? ' aria-invalid="true"' : '' ?>>
                                    </div>
                                    <?php if ($c['errorImporte'] !== null): ?>
                                        <p class="field-error"><?= htmlspecialchars($c['errorImporte']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($n === 2): ?>
                                    <button class="icon-button cuota-quitar" type="button" data-quitar-cuota aria-label="Quitar la cuota 2" hidden>
                                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn btn-outline cuota-agregar" type="button" data-agregar-cuota hidden>
                            <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                            Agregar 2ª cuota
                        </button>
                    </fieldset>
                </div>
            </section>

            <section class="card" aria-labelledby="previa-titulo">
                <h2 id="previa-titulo">Vista previa</h2>
                <p class="card-subtitle">Así va a aparecer en Mis boletas.</p>

                <div class="bill-row preview-row" aria-live="polite">
                    <span class="bill-id">
                        <span class="bill-icon" aria-hidden="true">
                            <span class="preview-icon-none" data-icono="0"<?= $servicioElegido !== null ? ' hidden' : '' ?>></span>
                            <?php foreach ($iconos as $id => $svg): ?>
                                <span data-icono="<?= $id ?>"<?= (string) $id !== $idElegido ? ' hidden' : '' ?>><?= $svg ?></span>
                            <?php endforeach; ?>
                        </span>
                        <span class="bill-name">
                            <span class="bill-service" data-preview="servicio"><?= htmlspecialchars($nombreServicio) ?></span>
                            <span class="bill-date"><span data-preview="fecha"><?= $fechaTexto ?></span><?php if ($conPdf): ?><span class="bill-pdf">PDF</span><?php endif; ?></span>
                            <span class="due-chip is-<?= $proximaCuota['estado'] ?? 'futuro' ?>" data-preview="vencimiento"<?= $proximaCuota === null ? ' hidden' : '' ?>><?= htmlspecialchars($proximaCuota['texto'] ?? '') ?></span>
                        </span>
                    </span>
                    <span class="bill-figure">
                        <span class="bill-label">Consumo</span>
                        <span class="bill-value"><span data-preview="cantidad"><?= is_numeric($datos['cantidad']) ? Formato::numero((float) $datos['cantidad']) : '—' ?></span> <span data-preview="unidad"><?= htmlspecialchars($unidad) ?></span></span>
                    </span>
                    <span class="bill-figure">
                        <span class="bill-label">Pagaste</span>
                        <span class="bill-value" data-preview="importe"><?= is_numeric($datos['importe']) ? Formato::pesos((float) $datos['importe']) : '$ —' ?></span>
                    </span>
                </div>

                <h3 class="checklist-title">Antes de guardar</h3>
                <ul class="checklist">
                    <?php foreach ($chequeos as $clave => [$estadoCheck, $textos, $nota]): ?>
                        <li class="check is-<?= $estadoCheck ?>" data-check="<?= $clave ?>"<?= $clave === 'suma' && !$sumaVisible ? ' hidden' : '' ?>>
                            <span class="check-mark" aria-hidden="true"><?= $marcas[$estadoCheck] ?></span>
                            <span class="check-body">
                                <span class="check-text"><?= htmlspecialchars($textos[$estadoCheck] ?? '') ?></span>
                                <span class="check-note"><?= htmlspecialchars($nota) ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="review-actions">
                    <a class="btn btn-outline" href="<?= BASE_URL ?>consumo/cargar">← Volver</a>
                    <button class="btn btn-primary" type="submit" data-confirmar><?= $conPdf ? 'Confirmar carga' : 'Cargar boleta' ?></button>
                </div>
                <?php if ($conPdf): ?>
                    <p class="field-hint review-note">Volver descarta el PDF subido.</p>
                <?php endif; ?>
            </section>
        </form>
    </main>
    <script src="<?= BASE_URL ?>js/cargar.js?v=<?= filemtime(ROOT_PATH . 'public/js/cargar.js') ?>"></script>
</body>
</html>
