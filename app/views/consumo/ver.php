<?php
// Etapa 17: ficha de una boleta. Recibe del controlador: boleta, anterior, siguiente, costo, costoAnterior,
// variacionCantidad, variacionImporte, variacionCosto, diferencia, grafico y posicionAnual.

use app\servicios\HistorialBoletas;
use app\servicios\Indicadores;

$iconos = require APP_PATH . 'views/consumo/iconos.php';
$idServicio = (int) $boleta->id_servicio;
$servicioMin = htmlspecialchars(mb_strtolower($boleta->servicio));
$unidad = htmlspecialchars($boleta->unidad);
$cantidad = (float) $boleta->cantidad;
$mesAnterior = $anterior !== null ? HistorialBoletas::mes($anterior->fecha) : null;

/** Variación con su color: subir es malo (rojo) y bajar es bueno (verde). */
$cambio = function (?float $porcentaje): string {
    if ($porcentaje === null) {
        return '<span class="bill-change">—</span>';
    }

    $clase = $porcentaje > 0 ? ' is-up' : ($porcentaje < 0 ? ' is-down' : '');

    return '<span class="bill-change' . $clase . '">' . Formato::variacion($porcentaje) . '</span>';
};

/** Números de un cálculo que pueden traer restos de coma flotante: menos de medio centavo cuenta como 0. */
$esCero = fn (float $valor): bool => abs($valor) < 0.005;

// Etapa 22: texto del confirm() del botón Eliminar. json_encode lo deja como texto válido de JavaScript
// (comillas y saltos de línea escapados) y htmlspecialchars lo deja seguro dentro del atributo onsubmit.
$textoConfirmar = '¿Eliminar la boleta de ' . mb_strtolower($boleta->servicio) . ' de ' . HistorialBoletas::mes($boleta->fecha) . "?\n"
    . ($boleta->archivo !== null ? 'Se borran sus datos y el PDF. ' : 'Se borran sus datos. ')
    . 'No se puede deshacer.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <p class="back-link"><a href="<?= BASE_URL ?>consumo">← Volver a Mis boletas</a></p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="page-heading bill-heading" aria-labelledby="page-title">
            <div class="bill-title">
                <span class="bill-icon bill-icon-lg service-tint-<?= $idServicio ?>" aria-hidden="true"><?= $iconos[$idServicio] ?? $iconos[1] ?></span>
                <div>
                    <p class="eyebrow">Boleta de <?= $servicioMin ?></p>
                    <h1 id="page-title"><?= ucfirst(HistorialBoletas::mes($boleta->fecha)) ?></h1>
                    <p class="section-description">
                        Boleta del <?= Formato::fecha($boleta->fecha) ?> ·
                        cargada <?= $boleta->archivo !== null ? 'desde PDF' : 'a mano' ?> el <?= Formato::fecha($boleta->fecha_carga) ?>
                    </p>
                </div>
            </div>
            <div class="bill-actions">
                <?php if ($boleta->archivo !== null): ?>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>consumo/comprobante/<?= (int) $boleta->id_consumo ?>" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" /></svg>
                        Ver PDF
                    </a>
                <?php endif; ?>
                <?php if ($anterior !== null): ?>
                    <a class="btn btn-outline" href="<?= BASE_URL ?>consumo/ver/<?= (int) $anterior->id_consumo ?>" aria-label="Boleta anterior de <?= $servicioMin ?>: <?= $mesAnterior ?>">‹ <?= ucfirst($mesAnterior) ?></a>
                <?php else: ?>
                    <span class="btn btn-outline is-disabled" aria-disabled="true">‹ Anterior</span>
                <?php endif; ?>
                <?php if ($siguiente !== null): ?>
                    <?php $mesSiguiente = HistorialBoletas::mes($siguiente->fecha); ?>
                    <a class="btn btn-outline" href="<?= BASE_URL ?>consumo/ver/<?= (int) $siguiente->id_consumo ?>" aria-label="Boleta siguiente de <?= $servicioMin ?>: <?= $mesSiguiente ?>"><?= ucfirst($mesSiguiente) ?> ›</a>
                <?php else: ?>
                    <span class="btn btn-outline is-disabled" aria-disabled="true">Siguiente ›</span>
                <?php endif; ?>
                <span class="bill-actions-divider" aria-hidden="true"></span>
                <form action="<?= BASE_URL ?>consumo/eliminar/<?= (int) $boleta->id_consumo ?>" method="post" onsubmit="return confirm(<?= htmlspecialchars(json_encode($textoConfirmar, JSON_UNESCAPED_UNICODE)) ?>);">
                    <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                    <button class="btn btn-danger-outline" type="submit">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6M14 11v6" /></svg>
                        Eliminar boleta
                    </button>
                </form>
            </div>
        </section>

        <section class="bill-kpis" aria-label="Consumo, importe y costo por unidad">
            <article class="card kpi-card">
                <p class="kpi-title">Consumiste</p>
                <p class="kpi-value"><?= Formato::numero($cantidad) ?> <span class="kpi-unit"><?= $unidad ?></span></p>
                <p class="kpi-note">
                    <?php if ($anterior === null): ?>
                        Es tu primera boleta de <?= $servicioMin ?>.
                    <?php else: ?>
                        <?= $cambio($variacionCantidad) ?> vs. <?= $mesAnterior ?> (<?= Formato::numero((float) $anterior->cantidad) ?> <?= $unidad ?>)
                    <?php endif; ?>
                </p>
            </article>
            <article class="card kpi-card">
                <p class="kpi-title">Pagaste</p>
                <p class="kpi-value"><?= Formato::pesos((float) $boleta->importe) ?></p>
                <p class="kpi-note">
                    <?php if ($anterior === null): ?>
                        Es tu primera boleta de <?= $servicioMin ?>.
                    <?php else: ?>
                        <?= $cambio($variacionImporte) ?> vs. <?= $mesAnterior ?> (<?= Formato::pesos((float) $anterior->importe) ?>)
                    <?php endif; ?>
                </p>
            </article>
            <article class="card kpi-card">
                <p class="kpi-title">Costo por <?= $unidad ?></p>
                <p class="kpi-value"><?= $costo !== null ? Formato::pesos($costo) : '—' ?></p>
                <p class="kpi-note">
                    <?php if ($costo === null): ?>
                        No se puede calcular: el consumo es 0.
                    <?php elseif ($anterior === null || $costoAnterior === null): ?>
                        Importe dividido por el consumo.
                    <?php else: ?>
                        <?= $cambio($variacionCosto) ?> vs. <?= $mesAnterior ?> (<?= Formato::pesos($costoAnterior) ?>)
                    <?php endif; ?>
                </p>
            </article>
        </section>

        <div class="grid-2 bill-detail-grid">
            <section class="card" aria-labelledby="diferencia-titulo">
                <?php if ($anterior === null): ?>
                    <h2 id="diferencia-titulo">Comparación con la boleta anterior</h2>
                    <p class="card-subtitle">Es tu primera boleta de <?= $servicioMin ?>. Cuando cargues la próxima, vas a ver acá qué cambió y por qué.</p>
                <?php elseif ($diferencia === null): ?>
                    <h2 id="diferencia-titulo">Comparación con la boleta anterior</h2>
                    <p class="card-subtitle">No se puede repartir la diferencia porque una de las dos boletas tiene consumo 0.</p>
                <?php else: ?>
                    <?php
                    $total = $diferencia['total'];
                    $dCantidad = $diferencia['diferenciaCantidad'];
                    $dCosto = $diferencia['diferenciaCosto'];
                    $porcentajes = $diferencia['porcentajes'];
                    ?>
                    <h2 id="diferencia-titulo">
                        <?php if ($esCero($total)): ?>
                            Pagaste lo mismo que en <?= $mesAnterior ?>
                        <?php else: ?>
                            ¿Por qué pagaste <?= Formato::pesos(abs($total)) ?> <?= $total > 0 ? 'más' : 'menos' ?>?
                        <?php endif; ?>
                    </h2>
                    <p class="card-subtitle">La diferencia con <?= $mesAnterior ?> se reparte entre lo que cambió tu consumo y lo que cambió el precio por <?= $unidad ?>.</p>

                    <?php if ($porcentajes !== null): ?>
                        <div class="split-bar" role="img" aria-label="<?= round($porcentajes['consumo']) ?> % por el consumo y <?= round($porcentajes['precio']) ?> % por el precio">
                            <span class="split-consumo" style="width: <?= number_format($porcentajes['consumo'], 2, '.', '') ?>%"></span>
                            <span class="split-precio" style="width: <?= number_format($porcentajes['precio'], 2, '.', '') ?>%"></span>
                        </div>
                    <?php endif; ?>

                    <ul class="split-list">
                        <li class="split-item">
                            <span class="split-swatch split-consumo" aria-hidden="true"></span>
                            <div>
                                <p class="split-head">
                                    <span><?= $esCero($dCantidad) ? 'Consumo igual' : ($dCantidad > 0 ? 'Por consumir más' : 'Por consumir menos') ?></span>
                                    <span class="split-amount"><?= Formato::pesos(abs($diferencia['porConsumo'])) ?><?= $porcentajes !== null ? ' · ' . round($porcentajes['consumo']) . ' %' : '' ?></span>
                                </p>
                                <p class="split-text">
                                    <?php if ($esCero($dCantidad)): ?>
                                        Consumiste lo mismo que en <?= $mesAnterior ?>.
                                    <?php else: ?>
                                        Usaste <?= Formato::numero(abs($dCantidad)) ?> <?= $unidad ?> <?= $dCantidad > 0 ? 'más' : 'menos' ?> que en <?= $mesAnterior ?>.
                                        Al precio de esa boleta (<?= Formato::pesos($diferencia['costoAnterior']) ?> el <?= $unidad ?>),
                                        <?= $dCantidad > 0 ? 'eso suma ' . Formato::pesos($diferencia['porConsumo']) : 'eso son ' . Formato::pesos(abs($diferencia['porConsumo'])) . ' menos' ?>.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                        <li class="split-item">
                            <span class="split-swatch split-precio" aria-hidden="true"></span>
                            <div>
                                <p class="split-head">
                                    <span><?= $esCero($dCosto) ? 'Precio igual' : ($dCosto > 0 ? 'Por el aumento del ' . $unidad : 'Por la baja del ' . $unidad) ?></span>
                                    <span class="split-amount"><?= Formato::pesos(abs($diferencia['porPrecio'])) ?><?= $porcentajes !== null ? ' · ' . round($porcentajes['precio']) . ' %' : '' ?></span>
                                </p>
                                <p class="split-text">
                                    <?php if ($esCero($dCosto)): ?>
                                        El precio por <?= $unidad ?> no cambió.
                                    <?php else: ?>
                                        Cada <?= $unidad ?> salió <?= Formato::pesos(abs($dCosto)) ?> más <?= $dCosto > 0 ? 'caro' : 'barato' ?>.
                                        Por tus <?= Formato::numero($cantidad) ?> <?= $unidad ?>,
                                        <?= $dCosto > 0 ? 'eso suma ' . Formato::pesos($diferencia['porPrecio']) : 'eso son ' . Formato::pesos(abs($diferencia['porPrecio'])) . ' menos' ?>.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                    </ul>

                    <?php if ($porcentajes === null && !$esCero($total)): ?>
                        <p class="split-note">Una parte sumó y la otra restó: lo que pagaste de diferencia es el resultado de las dos.</p>
                    <?php endif; ?>
                    <p class="card-footnote">El costo por <?= $unidad ?> incluye cargos fijos e impuestos: es un promedio de tu boleta, no la tarifa oficial.</p>
                <?php endif; ?>
            </section>

            <section class="card" aria-labelledby="historial-titulo">
                <h2 id="historial-titulo">Esta boleta en tu historial</h2>
                <?php if (count($grafico) < 2): ?>
                    <p class="card-subtitle">Es tu primera boleta de <?= $servicioMin ?>. Cuando cargues más, vas a poder compararlas acá.</p>
                <?php else: ?>
                    <?php
                    $descripcion = implode(', ', array_map(
                        fn ($barra) => HistorialBoletas::mes($barra['fecha']) . ' ' . Formato::numero($barra['cantidad']) . ' ' . $boleta->unidad,
                        $grafico
                    ));
                    ?>
                    <p class="card-subtitle">Consumo de tus últimas <?= count($grafico) ?> boletas de <?= $servicioMin ?>, en <?= $unidad ?>.</p>
                    <div class="history-chart" role="img" aria-label="<?= htmlspecialchars('Consumo por boleta: ' . $descripcion) ?>">
                        <?php foreach ($grafico as $barra): ?>
                            <div class="history-col<?= $barra['esActual'] ? ' is-current' : '' ?>">
                                <span class="history-value"><?= Formato::numero($barra['cantidad']) ?></span>
                                <span class="history-bar" style="height: <?= $barra['alto'] ?>px"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="history-labels" aria-hidden="true">
                        <?php foreach ($grafico as $barra): ?>
                            <span<?= $barra['esActual'] ? ' class="is-current"' : '' ?>><?= $barra['esActual'] ? 'Esta boleta' : Indicadores::etiquetaMes(substr($barra['fecha'], 0, 7)) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($posicionAnual !== null): ?>
                        <?php
                        $puesto = $posicionAnual['puesto'];
                        $vsPromedio = $posicionAnual['variacionPromedio'];
                        $promedio = number_format($posicionAnual['promedio'], 0, ',', '.');
                        ?>
                        <ul class="fact-list">
                            <li class="fact">
                                <span class="fact-badge" aria-hidden="true"><?= $puesto !== 1 && $posicionAnual['esLaMasBaja'] ? 'mín' : $puesto . '.ª' ?></span>
                                <span>
                                    <?php if ($puesto === 1): ?>
                                        Tu <strong>boleta de <?= $servicioMin ?> más alta</strong> del último año (entre <?= $posicionAnual['boletas'] ?> boletas).
                                    <?php elseif ($posicionAnual['esLaMasBaja']): ?>
                                        Tu <strong>boleta de <?= $servicioMin ?> más baja</strong> del último año (entre <?= $posicionAnual['boletas'] ?> boletas).
                                        La más alta fue la de <?= HistorialBoletas::mes($posicionAnual['mayor']->fecha) ?> (<?= Formato::numero((float) $posicionAnual['mayor']->cantidad) ?> <?= $unidad ?>).
                                    <?php else: ?>
                                        Tu <strong><?= $puesto ?>.ª boleta de <?= $servicioMin ?> más alta</strong> del último año, entre <?= $posicionAnual['boletas'] ?>.
                                        La más alta fue la de <?= HistorialBoletas::mes($posicionAnual['mayor']->fecha) ?> (<?= Formato::numero((float) $posicionAnual['mayor']->cantidad) ?> <?= $unidad ?>).
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="fact">
                                <?php if ($vsPromedio === null || abs($vsPromedio) < 0.5): ?>
                                    <span class="fact-badge" aria-hidden="true">=</span>
                                    <span><strong>En tu promedio</strong> de <?= $servicioMin ?> del último año, que es de <?= $promedio ?> <?= $unidad ?> por boleta.</span>
                                <?php else: ?>
                                    <span class="fact-badge" aria-hidden="true"><?= $vsPromedio > 0 ? '↑' : '↓' ?></span>
                                    <span><strong><?= number_format(abs($vsPromedio), 0, ',', '.') ?> % por <?= $vsPromedio > 0 ? 'encima' : 'debajo' ?> de tu promedio</strong> de <?= $servicioMin ?> del último año, que es de <?= $promedio ?> <?= $unidad ?> por boleta.</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <section class="card" aria-labelledby="datos-titulo">
            <h2 id="datos-titulo">Datos de la boleta</h2>
            <dl class="data-list">
                <div><dt>Servicio</dt><dd><?= htmlspecialchars($boleta->servicio) ?> (<?= $unidad ?>)</dd></div>
                <div><dt>Fecha de la boleta</dt><dd><?= Formato::fecha($boleta->fecha) ?></dd></div>
                <div><dt>Consumo</dt><dd><?= Formato::numero($cantidad) ?> <?= $unidad ?></dd></div>
                <div><dt>Importe</dt><dd><?= Formato::pesos((float) $boleta->importe) ?></dd></div>
                <div><dt>Origen</dt><dd><?= $boleta->archivo !== null ? 'PDF' : 'Carga manual' ?></dd></div>
                <div><dt>Cargada el</dt><dd><?= Formato::fecha($boleta->fecha_carga) ?></dd></div>
            </dl>
        </section>
    </main>
</body>
</html>
