<?php

use app\servicios\Indicadores;

/** URL del dashboard con los filtros indicados (null = vista General). */
$urlDashboard = function (?int $idServicio, string $periodo): string {
    $parametros = array_filter(['servicio' => $idServicio, 'periodo' => $periodo], fn ($valor) => $valor !== null);

    return BASE_URL . 'dashboard?' . http_build_query($parametros);
};

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

        <section class="page-heading" aria-labelledby="page-title">
            <p class="eyebrow">Monitoreo</p>
            <h1 id="page-title">Dashboard</h1>
            <p class="section-description">Hola, <?= htmlspecialchars($usuario['nombre']) ?>. Acá vas a seguir la evolución de tus consumos de luz, agua y gas.</p>
        </section>

        <?php if ($sinConsumos): ?>
            <section class="card empty-state" aria-labelledby="sin-datos-titulo">
                <div class="energy-icon" aria-hidden="true">
                    <?php require APP_PATH . 'views/layouts/logo.php'; ?>
                </div>
                <h2 id="sin-datos-titulo">Todavía no cargaste consumos</h2>
                <p>Cuando registres tu primera boleta vas a ver acá tus indicadores y gráficos.</p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>consumo/cargar">Cargar boleta</a>
            </section>
        <?php else: ?>
            <div class="dash-toolbar">
                <nav class="tabs" aria-label="Vistas del dashboard">
                    <a class="tab<?= $idServicioActual === null ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlDashboard(null, $periodo)) ?>"<?= $idServicioActual === null ? ' aria-current="page"' : '' ?>>General</a>
                    <?php foreach ($servicios as $s): ?>
                        <?php $activo = $idServicioActual === (int) $s->id_servicio; ?>
                        <a class="tab<?= $activo ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlDashboard((int) $s->id_servicio, $periodo)) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><span class="service-dot service-dot-<?= (int) $s->id_servicio ?>" aria-hidden="true"></span><?= htmlspecialchars($s->nombre) ?></a>
                    <?php endforeach; ?>
                </nav>
                <nav class="period-buttons" aria-label="Período">
                    <?php foreach (Indicadores::PERIODOS as $clave => $texto): ?>
                        <?php $activo = (string) $clave === $periodo; ?>
                        <a class="period-button<?= $activo ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlDashboard($idServicioActual, (string) $clave)) ?>"<?= $activo ? ' aria-current="page"' : '' ?>><?= $texto ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php require APP_PATH . 'views/dashboard/' . ($servicio === null ? 'general' : 'servicio') . '.php'; ?>

            <!-- Datos para los gráficos. JSON_HEX_* evita que un texto con "</script>" corte este bloque. -->
            <script id="datos-dashboard" type="application/json"><?= json_encode($graficos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
            <script src="<?= BASE_URL ?>js/vendor/chart.umd.min.js"></script>
            <script src="<?= BASE_URL ?>js/dashboard.js"></script>
        <?php endif; ?>
    </main>
</body>
</html>
