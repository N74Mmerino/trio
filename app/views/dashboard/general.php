<?php
// Vista General: incluida desde dashboard/index.php. Todo en pesos.
// Etapa 18: tarjetas por servicio, ícono del servicio en "Mayor gasto" y leyenda propia en la dona.
$textoPeriodo = $periodo === 'todo' ? 'Todas tus boletas' : 'Últimos ' . $periodo . ' meses';
$iconos = require APP_PATH . 'views/consumo/iconos.php';

/** Clase de la etiqueta de variación: subir es malo (rojo) y bajar es bueno (verde). */
$claseEtiqueta = fn (?float $variacion): string => match (Formato::claseVariacion($variacion)) {
    'kpi-value-negative' => ' is-up',
    'kpi-value-positive' => ' is-down',
    default => '',
};
?>
<div class="dash-stack">
    <section class="kpi-grid" aria-label="Indicadores principales">
        <article class="card kpi-card">
            <div class="kpi-head">
                <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 6.5v11" /><path d="M15 8.8c-.7-.8-1.8-1.3-3-1.3-1.7 0-3 .8-3 1.9s1.3 1.5 3 1.9 3 .9 3 1.9-1.3 1.9-3 1.9c-1.2 0-2.3-.5-3-1.3" /></svg></span>
                <p class="kpi-title">Gasto del período</p>
            </div>
            <p class="kpi-value"><?= Formato::pesos($kpis['total'], 0) ?></p>
            <p class="kpi-note"><?= $textoPeriodo ?></p>
        </article>

        <article class="card kpi-card">
            <div class="kpi-head">
                <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6" /><path d="M8 13h8M8 17h5" /></svg></span>
                <p class="kpi-title">Boletas cargadas</p>
            </div>
            <p class="kpi-value"><?= $kpis['boletas'] ?></p>
            <p class="kpi-note"><?= $textoPeriodo ?></p>
        </article>

        <article class="card kpi-card">
            <div class="kpi-head">
                <?php if ($kpis['mayor'] !== null): ?>
                    <span class="kpi-icon kpi-icon-service" aria-hidden="true"><?= $iconos[(int) $kpis['mayor']->id_servicio] ?? $iconos[1] ?></span>
                <?php else: ?>
                    <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 2 3 14h7v8l10-12h-7z" fill="currentColor" /></svg></span>
                <?php endif; ?>
                <p class="kpi-title">Mayor gasto</p>
            </div>
            <?php if ($kpis['mayor'] !== null): ?>
                <p class="kpi-value"><?= htmlspecialchars($kpis['mayor']->nombre) ?></p>
                <p class="kpi-note"><?= number_format($kpis['porcentajeMayor'], 0, ',', '.') ?> % del total</p>
            <?php else: ?>
                <p class="kpi-value">—</p>
                <p class="kpi-note">Sin boletas en el período</p>
            <?php endif; ?>
        </article>

        <article class="card kpi-card">
            <div class="kpi-head">
                <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="m7 14 4-4 3 3 5-6" /></svg></span>
                <p class="kpi-title">Vs. período anterior</p>
            </div>
            <p class="kpi-value <?= Formato::claseVariacion($kpis['variacionTotal']) ?>"><?= Formato::variacion($kpis['variacionTotal']) ?></p>
            <p class="kpi-note">
                <?php if ($periodo === 'todo'): ?>
                    Elegí 6 o 12 meses para comparar
                <?php elseif ($kpis['variacionTotal'] === null): ?>
                    Sin boletas en el período anterior
                <?php else: ?>
                    Gasto de los <?= $periodo ?> meses anteriores
                <?php endif; ?>
            </p>
        </article>
    </section>

    <?php if ($resumen !== []): ?>
        <section class="dash-section" aria-labelledby="resumen-titulo">
            <div class="section-head">
                <h2 id="resumen-titulo">Última boleta por servicio</h2>
                <p class="card-subtitle">Variación del consumo contra la boleta anterior del mismo servicio.</p>
            </div>
            <div class="service-cards">
                <?php foreach ($resumen as $fila): ?>
                    <?php
                    $idServicio = (int) $fila['servicio']->id_servicio;
                    $nombre = htmlspecialchars($fila['servicio']->nombre);
                    $ultima = $fila['ultima'];
                    ?>
                    <article class="card service-card">
                        <div class="service-card-head">
                            <span class="service-card-icon" aria-hidden="true"><?= $iconos[$idServicio] ?? $iconos[1] ?></span>
                            <div class="service-card-title">
                                <h3><?= $nombre ?></h3>
                                <p>Boleta del <?= Formato::fecha($ultima->fecha) ?></p>
                            </div>
                            <a class="link-subtle" href="<?= BASE_URL ?>consumo/ver/<?= (int) $ultima->id_consumo ?>" aria-label="Ver la última boleta de <?= $nombre ?>">Ver boleta →</a>
                        </div>
                        <p class="service-card-value"><?= Formato::numero((float) $ultima->cantidad) ?> <span><?= htmlspecialchars($fila['servicio']->unidad) ?></span></p>
                        <div class="service-card-foot">
                            <div>
                                <p class="service-card-label">Importe</p>
                                <p class="service-card-amount"><?= Formato::pesos((float) $ultima->importe) ?></p>
                            </div>
                            <div class="service-card-change">
                                <p class="service-card-label">Consumo vs. anterior</p>
                                <span class="variation-chip<?= $claseEtiqueta($fila['variacion']) ?>"><?= Formato::variacion($fila['variacion']) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card" aria-labelledby="gasto-mensual-titulo">
        <h2 id="gasto-mensual-titulo">Gasto mensual</h2>
        <p class="card-subtitle">Suma de las boletas de cada mes, por servicio · <?= $textoPeriodo ?></p>
        <div class="chart-wrap">
            <?php if ($graficos['mensual'] !== null): ?>
                <canvas id="graficoMensual" role="img" aria-label="Gráfico de barras del gasto mensual por servicio"></canvas>
            <?php else: ?>
                <p class="chart-empty">No hay boletas en este período.</p>
            <?php endif; ?>
        </div>
    </section>

    <div class="dash-pair">
        <section class="card" aria-labelledby="distribucion-titulo">
            <h2 id="distribucion-titulo">Distribución del gasto</h2>
            <p class="card-subtitle"><?= $textoPeriodo ?></p>
            <?php if ($graficos['distribucion'] !== null): ?>
                <?php $distribucion = $graficos['distribucion']; ?>
                <div class="donut-layout">
                    <div class="donut-panel">
                        <canvas id="graficoDistribucion" role="img" aria-label="Gráfico de dona con el gasto por servicio"></canvas>
                        <div class="donut-center" aria-hidden="true">
                            <span>Total</span>
                            <strong><?= Formato::pesos($kpis['total'], 0) ?></strong>
                        </div>
                    </div>
                    <ul class="donut-legend">
                        <?php foreach ($distribucion['ids'] as $i => $idServicio): ?>
                            <?php $porcentaje = $kpis['total'] > 0 ? $distribucion['datos'][$i] / $kpis['total'] * 100 : 0; ?>
                            <li>
                                <span class="service-dot service-dot-<?= (int) $idServicio ?>" aria-hidden="true"></span>
                                <span class="legend-name"><?= htmlspecialchars($distribucion['etiquetas'][$i]) ?></span>
                                <span class="legend-amount"><?= Formato::pesos($distribucion['datos'][$i], 0) ?></span>
                                <strong><?= number_format($porcentaje, 0, ',', '.') ?> %</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="chart-panel">
                    <p class="chart-empty">No hay boletas en este período.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="recientes-titulo">
            <div class="card-head-row">
                <div>
                    <h2 id="recientes-titulo">Últimas boletas cargadas</h2>
                    <p class="card-subtitle">Tu actividad más reciente</p>
                </div>
                <a class="link-subtle" href="<?= BASE_URL ?>consumo">Ver todas →</a>
            </div>
            <ul class="recent-list">
                <?php foreach ($recientes as $boleta): ?>
                    <li class="row-item">
                        <span class="recent-icon" aria-hidden="true"><?= $iconos[(int) $boleta->id_servicio] ?? $iconos[1] ?></span>
                        <div class="row-main">
                            <a class="row-title" href="<?= BASE_URL ?>consumo/ver/<?= (int) $boleta->id_consumo ?>"><?= htmlspecialchars($boleta->servicio) ?> · boleta del <?= Formato::fecha($boleta->fecha) ?></a>
                            <span class="row-meta">Cargada <?= Formato::haceTiempo($boleta->fecha_carga) ?> · <?= Formato::numero((float) $boleta->cantidad) ?> <?= htmlspecialchars($boleta->unidad) ?></span>
                        </div>
                        <span class="recent-amount"><?= Formato::pesos((float) $boleta->importe) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>
