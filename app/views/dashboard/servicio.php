<?php
// Vista de un servicio: incluida desde dashboard/index.php. Consumo en la unidad del servicio.
$textoPeriodo = $periodo === 'todo' ? 'Todas tus boletas' : 'Últimos ' . $periodo . ' meses';
$unidad = htmlspecialchars($servicio->unidad);
$hayHistorial = $graficos['cantidades'] !== [];
?>
<?php if ($kpis === null): ?>
    <section class="card empty-state" aria-labelledby="sin-servicio-titulo">
        <div class="energy-icon" aria-hidden="true">
            <?php require APP_PATH . 'views/layouts/logo.php'; ?>
        </div>
        <h2 id="sin-servicio-titulo">Todavía no cargaste boletas de <?= htmlspecialchars(mb_strtolower($servicio->nombre)) ?></h2>
        <p>Cuando cargues una vas a ver acá su evolución.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>consumo/cargar">Cargar boleta</a>
    </section>
<?php else: ?>
    <div class="dash-stack">
        <section class="kpi-grid" aria-label="Indicadores de <?= htmlspecialchars($servicio->nombre) ?>">
            <article class="card kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 2 3 14h7v8l10-12h-7z" fill="currentColor" /></svg></span>
                    <p class="kpi-title">Último consumo</p>
                </div>
                <p class="kpi-value"><?= Formato::numero((float) $kpis['ultima']->cantidad) ?> <?= $unidad ?></p>
                <p class="kpi-note">Boleta del <?= Formato::fecha($kpis['ultima']->fecha) ?></p>
            </article>

            <article class="card kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="m7 14 4-4 3 3 5-6" /></svg></span>
                    <p class="kpi-title">Consumo vs. boleta anterior</p>
                </div>
                <p class="kpi-value <?= Formato::claseVariacion($kpis['variacionCantidad']) ?>"><?= Formato::variacion($kpis['variacionCantidad']) ?></p>
                <p class="kpi-note"><?= $kpis['variacionCantidad'] === null ? 'Hace falta una boleta anterior' : 'Comparado con la boleta previa' ?></p>
            </article>

            <article class="card kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 6.5v11" /><path d="M15 8.8c-.7-.8-1.8-1.3-3-1.3-1.7 0-3 .8-3 1.9s1.3 1.5 3 1.9 3 .9 3 1.9-1.3 1.9-3 1.9c-1.2 0-2.3-.5-3-1.3" /></svg></span>
                    <p class="kpi-title">Último importe</p>
                </div>
                <p class="kpi-value"><?= Formato::pesos((float) $kpis['ultima']->importe, 0) ?></p>
                <p class="kpi-note">
                    <?php if ($kpis['variacionImporte'] === null): ?>
                        Sin boleta anterior para comparar
                    <?php else: ?>
                        <span class="<?= Formato::claseVariacion($kpis['variacionImporte']) ?>"><?= Formato::variacion($kpis['variacionImporte']) ?></span> vs. boleta anterior
                    <?php endif; ?>
                </p>
            </article>

            <article class="card kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z" /><circle cx="7.5" cy="7.5" r="1.5" /></svg></span>
                    <p class="kpi-title">Costo por <?= $unidad ?></p>
                </div>
                <p class="kpi-value"><?= $kpis['costo'] !== null ? Formato::pesos($kpis['costo']) : '—' ?></p>
                <p class="kpi-note">
                    <?php if ($kpis['variacionCosto'] === null): ?>
                        Importe ÷ consumo de la última boleta
                    <?php else: ?>
                        <span class="<?= Formato::claseVariacion($kpis['variacionCosto']) ?>"><?= Formato::variacion($kpis['variacionCosto']) ?></span> vs. boleta anterior
                    <?php endif; ?>
                </p>
            </article>
        </section>

        <section class="card" aria-labelledby="consumo-titulo">
            <h2 id="consumo-titulo">Consumo por boleta</h2>
            <p class="card-subtitle"><?= $unidad ?> de cada boleta · <?= $textoPeriodo ?></p>
            <div class="chart-wrap">
                <?php if ($hayHistorial): ?>
                    <canvas id="graficoConsumo" role="img" aria-label="Gráfico de línea del consumo por boleta"></canvas>
                <?php else: ?>
                    <p class="chart-empty">No hay boletas de <?= htmlspecialchars(mb_strtolower($servicio->nombre)) ?> en este período.</p>
                <?php endif; ?>
            </div>
        </section>

        <div class="grid-2">
            <section class="card" aria-labelledby="importe-titulo">
                <h2 id="importe-titulo">Importe por boleta</h2>
                <p class="card-subtitle">Lo que pagaste en cada boleta · <?= $textoPeriodo ?></p>
                <div class="chart-panel">
                    <?php if ($hayHistorial): ?>
                        <canvas id="graficoImporte" role="img" aria-label="Gráfico de barras del importe por boleta"></canvas>
                    <?php else: ?>
                        <p class="chart-empty">Sin datos en este período.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card" aria-labelledby="costo-titulo">
                <h2 id="costo-titulo">Costo por <?= $unidad ?></h2>
                <p class="card-subtitle">Si sube con el mismo consumo, aumentó la tarifa.</p>
                <div class="chart-panel">
                    <?php if ($hayHistorial): ?>
                        <canvas id="graficoCosto" role="img" aria-label="Gráfico de línea del costo por unidad"></canvas>
                    <?php else: ?>
                        <p class="chart-empty">Sin datos en este período.</p>
                    <?php endif; ?>
                </div>
                <p class="chart-caption">Importe ÷ consumo. Incluye cargos fijos e impuestos.</p>
            </section>
        </div>
    </div>
<?php endif; ?>
