<?php
// Etapa 17: "Mis boletas". Recibe del controlador: servicios, servicio, anios, anio, sinBoletas, grupos y resumen.
// Etapa 23: cada boleta trae 'vencimiento' (próxima cuota, o null si no tiene vencimientos cargados).

use app\servicios\HistorialBoletas;

/** URL de Mis boletas con los filtros indicados (null = sin ese filtro). */
$urlBoletas = function (?int $idServicio, ?int $anio): string {
    $parametros = array_filter(['servicio' => $idServicio, 'anio' => $anio], fn ($valor) => $valor !== null);

    return BASE_URL . 'consumo' . ($parametros === [] ? '' : '?' . http_build_query($parametros));
};

/** Texto y clase de una variación chica: subir es malo (rojo) y bajar es bueno (verde). */
$cambio = function (?float $porcentaje): array {
    if ($porcentaje === null) {
        return ['texto' => '—', 'clase' => ''];
    }

    $clase = $porcentaje > 0 ? 'is-up' : ($porcentaje < 0 ? 'is-down' : '');

    return ['texto' => Formato::variacion($porcentaje) . ' vs. anterior', 'clase' => $clase];
};

$iconos = require APP_PATH . 'views/consumo/iconos.php';
$idServicioActual = $servicio !== null ? (int) $servicio->id_servicio : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <section class="page-heading users-heading" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Historial</p>
                <h1 id="page-title">Mis boletas</h1>
                <p class="section-description">Cada boleta que cargaste, con lo que consumiste y lo que pagaste. Tocá una para ver el detalle.</p>
            </div>
            <a class="btn btn-primary add-user" href="<?= BASE_URL ?>consumo/cargar">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                Cargar boleta
            </a>
        </section>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($sinBoletas): ?>
            <section class="card empty-state" aria-labelledby="sin-boletas-titulo">
                <div class="energy-icon" aria-hidden="true">
                    <?php require APP_PATH . 'views/layouts/logo.php'; ?>
                </div>
                <h2 id="sin-boletas-titulo">Todavía no cargaste boletas</h2>
                <p>Cuando cargues tu primera boleta, la vas a ver acá con su consumo y lo que pagaste.</p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>consumo/cargar">Cargar boleta</a>
            </section>
        <?php else: ?>
            <div class="dash-toolbar">
                <nav class="tabs" aria-label="Filtrar por servicio">
                    <a class="tab<?= $idServicioActual === null ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlBoletas(null, $anio)) ?>"<?= $idServicioActual === null ? ' aria-current="page"' : '' ?>>Todas</a>
                    <?php foreach ($servicios as $s): ?>
                        <?php $activo = $idServicioActual === (int) $s->id_servicio; ?>
                        <a class="tab<?= $activo ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlBoletas((int) $s->id_servicio, $anio)) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><span class="service-dot service-dot-<?= (int) $s->id_servicio ?>" aria-hidden="true"></span><?= htmlspecialchars($s->nombre) ?></a>
                    <?php endforeach; ?>
                </nav>
                <nav class="period-buttons" aria-label="Año">
                    <?php foreach ($anios as $a): ?>
                        <?php $activo = $a === $anio; ?>
                        <a class="period-button<?= $activo ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlBoletas($idServicioActual, $a)) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><?= $a ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <section class="bills-summary" aria-label="Resumen de las boletas que se muestran">
                <div class="stat">
                    <p class="stat-label">Boletas</p>
                    <p class="stat-value"><?= $resumen['cantidad'] ?></p>
                    <p class="stat-note">
                        <?= $resumen['porServicio'] === []
                            ? 'Sin boletas'
                            : htmlspecialchars(implode(' · ', array_map(fn ($s) => $s['nombre'] . ' ' . $s['cantidad'], $resumen['porServicio']))) ?>
                    </p>
                </div>
                <div class="stat">
                    <p class="stat-label">Total pagado</p>
                    <p class="stat-value"><?= Formato::pesos($resumen['total']) ?></p>
                    <p class="stat-note">En <?= $anio ?>, <?= $servicio === null ? 'todos los servicios' : 'solo ' . htmlspecialchars(mb_strtolower($servicio->nombre)) ?></p>
                </div>
                <div class="stat">
                    <p class="stat-label">Boleta más cara</p>
                    <?php if ($resumen['masCara'] === null): ?>
                        <p class="stat-value">—</p>
                        <p class="stat-note">Sin boletas</p>
                    <?php else: ?>
                        <p class="stat-value"><?= Formato::pesos($resumen['masCara']['importe']) ?></p>
                        <p class="stat-note"><?= htmlspecialchars($resumen['masCara']['servicio']) ?> · <?= HistorialBoletas::mes($resumen['masCara']['fecha']) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <p class="bills-legend">
                <span class="bill-bar" aria-hidden="true"><span style="width: 60%"></span></span>
                La barra compara cada consumo con tu boleta más alta de ese servicio. La flecha indica si subió o bajó respecto de la boleta anterior del mismo servicio.
            </p>

            <?php if ($grupos === []): ?>
                <section class="card empty-state">
                    <h2>No hay boletas con ese filtro</h2>
                    <p>No cargaste boletas de <?= htmlspecialchars(mb_strtolower($servicio->nombre ?? '')) ?> en <?= $anio ?>. Probá con otro servicio o con otro año.</p>
                </section>
            <?php endif; ?>

            <div class="bills-list">
                <?php foreach ($grupos as $i => $grupo): ?>
                    <section class="card bills-month" aria-labelledby="mes-<?= $i ?>">
                        <div class="bills-month-head">
                            <h2 id="mes-<?= $i ?>"><?= $grupo['titulo'] ?></h2>
                            <p><?= count($grupo['boletas']) === 1 ? '1 boleta' : count($grupo['boletas']) . ' boletas' ?> · <strong><?= Formato::pesos($grupo['total']) ?></strong></p>
                        </div>
                        <ul class="bills-rows">
                            <?php foreach ($grupo['boletas'] as $b): ?>
                                <?php
                                $unidad = htmlspecialchars($b['unidad']);
                                $cambioCantidad = $b['esPrimera'] ? ['texto' => 'Primera boleta cargada', 'clase' => ''] : $cambio($b['variacionCantidad']);
                                $cambioImporte = $b['esPrimera'] ? ['texto' => 'Primera boleta cargada', 'clase' => ''] : $cambio($b['variacionImporte']);
                                ?>
                                <li>
                                    <a class="bill-row" href="<?= BASE_URL ?>consumo/ver/<?= $b['id'] ?>">
                                        <span class="bill-id">
                                            <span class="bill-icon service-tint-<?= $b['idServicio'] ?>" aria-hidden="true"><?= $iconos[$b['idServicio']] ?? $iconos[1] ?></span>
                                            <span class="bill-name">
                                                <span class="bill-service"><?= htmlspecialchars($b['servicio']) ?></span>
                                                <span class="bill-date">
                                                    Boleta del <?= Formato::fecha($b['fecha']) ?>
                                                    <?php if ($b['tienePdf']): ?><span class="bill-pdf">PDF</span><?php endif; ?>
                                                </span>
                                                <?php if ($b['vencimiento'] !== null): ?>
                                                    <?php $v = $b['vencimiento']; ?>
                                                    <span class="due-chip is-<?= $v['estado'] ?>"><?= htmlspecialchars($v['texto']) ?><?= $v['cuotas'] > 1 && $v['estado'] !== 'pasado' ? ' · cuota ' . $v['cuota'] . ' de ' . $v['cuotas'] : '' ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                        <span class="bill-figure">
                                            <span class="bill-label">Consumo</span>
                                            <span class="bill-value"><?= Formato::numero($b['cantidad']) ?> <?= $unidad ?></span>
                                            <span class="bill-bar" aria-hidden="true"><span class="service-bg-<?= $b['idServicio'] ?>" style="width: <?= $b['barra'] ?>%"></span></span>
                                            <span class="bill-change <?= $cambioCantidad['clase'] ?>"><?= $cambioCantidad['texto'] ?></span>
                                        </span>
                                        <span class="bill-figure">
                                            <span class="bill-label">Pagaste</span>
                                            <span class="bill-value"><?= Formato::pesos($b['importe']) ?></span>
                                            <span class="bill-sub"><?= $b['costo'] !== null ? Formato::pesos($b['costo']) . ' por ' . $unidad : '—' ?></span>
                                            <span class="bill-change <?= $cambioImporte['clase'] ?>"><?= $cambioImporte['texto'] ?></span>
                                        </span>
                                        <span class="bill-go" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6" /></svg></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
