<?php
// Inicio del administrador (Etapa 14). Datos de toda la plataforma, sin importes ni cantidades.
use app\models\UsuarioModel;

$k = $kpis;
$iconoUsuarios = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5" /><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" /><path d="M16 4.5a3.5 3.5 0 0 1 0 7M21 20c0-2.6-1.6-4.8-4-5.6" /></svg>';
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
                <p class="eyebrow">Administración</p>
                <h1 id="page-title">Hola, <?= htmlspecialchars($usuario['nombre']) ?></h1>
                <p class="section-description">Así está la plataforma hoy, <?= $hoy->format('d/m/Y') ?>.</p>
            </div>
            <a href="<?= BASE_URL ?>auth/registro" class="btn btn-primary add-user">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                Nuevo usuario
            </a>
        </section>

        <div class="dash-stack">
            <section class="kpi-grid" aria-label="Indicadores de la plataforma">
                <a class="card kpi-card kpi-link" href="<?= BASE_URL ?>usuario">
                    <div class="kpi-head">
                        <span class="kpi-icon" aria-hidden="true"><?= $iconoUsuarios ?></span>
                        <p class="kpi-title">Usuarios activos</p>
                    </div>
                    <p class="kpi-value"><?= $k['activos'] ?></p>
                    <p class="kpi-note">de <?= $k['usuarios'] ?> · <?= $k['inactivos'] ?> <?= $k['inactivos'] === 1 ? 'inactivo' : 'inactivos' ?></p>
                </a>

                <article class="card kpi-card">
                    <div class="kpi-head">
                        <span class="kpi-icon kpi-icon-green" aria-hidden="true"><?= $iconoUsuarios ?></span>
                        <p class="kpi-title">Nuevos este mes</p>
                    </div>
                    <p class="kpi-value"><?= $k['nuevosMes'] ?></p>
                    <p class="kpi-note"><?= $k['nuevosAnterior'] ?> el mes pasado</p>
                </article>

                <a class="card kpi-card kpi-link" href="<?= BASE_URL ?>boleta">
                    <div class="kpi-head">
                        <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6" /><path d="M8 13h8M8 17h5" /></svg></span>
                        <p class="kpi-title">Boletas cargadas</p>
                    </div>
                    <p class="kpi-value"><?= $k['boletas'] ?></p>
                    <p class="kpi-note"><?= $k['boletasMes'] ?> cargadas este mes</p>
                </a>

                <a class="card kpi-card kpi-link" href="<?= BASE_URL ?>boleta?origen=pdf">
                    <div class="kpi-head">
                        <span class="kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5" /><path d="M14 3v6h6" /><circle cx="16.5" cy="16.5" r="3" /><path d="m21 21-2.3-2.3" /></svg></span>
                        <p class="kpi-title">Leídas desde PDF</p>
                    </div>
                    <p class="kpi-value"><?= $k['conPdf'] ?></p>
                    <p class="kpi-note"><?= $k['porcentajePdf'] === null ? 'Sin boletas todavía' : number_format($k['porcentajePdf'], 0, ',', '.') . ' % del total' ?></p>
                </a>
            </section>

            <div class="grid-2">
                <section class="card" aria-labelledby="grafico-titulo">
                    <h2 id="grafico-titulo">Boletas por mes</h2>
                    <p class="card-subtitle">Todos los usuarios · según la fecha de la boleta</p>
                    <div class="chart-wrap">
                        <?php if ($grafico === null): ?>
                            <p class="chart-empty">Todavía no hay boletas en los últimos 6 meses.</p>
                        <?php else: ?>
                            <canvas id="graficoAdmin" role="img" aria-label="Cantidad de boletas por mes y servicio en los últimos 6 meses"></canvas>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="card" aria-labelledby="avisos-titulo">
                    <h2 id="avisos-titulo">Para revisar</h2>
                    <p class="card-subtitle">Lo que pide tu atención</p>
                    <?php if ($avisos === []): ?>
                        <p class="list-empty">Todo en orden.</p>
                    <?php else: ?>
                        <ul class="row-list">
                            <?php foreach ($avisos as $aviso): ?>
                                <li class="row-item">
                                    <span class="row-main"><?= htmlspecialchars($aviso['texto']) ?></span>
                                    <?php if ($aviso['enlace'] !== null): ?>
                                        <a class="row-link" href="<?= BASE_URL . $aviso['enlace'] ?>">Ver</a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>

            <div class="admin-grid">
                <section class="card" aria-labelledby="registros-titulo">
                    <h2 id="registros-titulo">Últimos registros</h2>
                    <p class="card-subtitle">Cuentas creadas recientemente</p>
                    <ul class="row-list">
                        <?php foreach ($ultimosRegistros as $registro): ?>
                            <li class="row-item">
                                <span class="row-main">
                                    <?php // Etapa 15: un usuario común abre su ficha; el administrador (sin ficha) abre Editar. ?>
                                    <a class="row-title" href="<?= BASE_URL ?>usuario/<?= (int) $registro->id_rol === UsuarioModel::ROL_USUARIO ? 'actividad' : 'editar' ?>/<?= $registro->id_usuario ?>"><?= htmlspecialchars($registro->nombre . ' ' . $registro->apellido) ?></a>
                                    <span class="row-meta"><?= Formato::haceTiempo($registro->fecha_registro) ?></span>
                                </span>
                                <span class="badge <?= $registro->activo ? 'badge-active' : 'badge-inactive' ?>"><?= $registro->activo ? 'Activo' : 'Inactivo' ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="card" aria-labelledby="actividad-titulo">
                    <h2 id="actividad-titulo">Actividad reciente</h2>
                    <p class="card-subtitle">Últimas boletas cargadas (sin importes)</p>
                    <?php if ($actividad === []): ?>
                        <p class="list-empty">Todavía nadie cargó boletas.</p>
                    <?php else: ?>
                        <ul class="row-list">
                            <?php foreach ($actividad as $carga): ?>
                                <li class="row-item">
                                    <span class="row-main">
                                        <span><strong><?= htmlspecialchars($carga->nombre . ' ' . mb_substr($carga->apellido, 0, 1) . '.') ?></strong> cargó <?= htmlspecialchars($carga->servicio) ?></span>
                                        <span class="row-meta">Boleta del <?= Formato::fecha($carga->fecha) ?> · <?= Formato::haceTiempo($carga->fecha_carga) ?></span>
                                    </span>
                                    <span class="badge <?= $carga->con_pdf ? 'badge-admin' : 'badge-user' ?>"><?= $carga->con_pdf ? 'PDF' : 'Manual' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="card-link"><a class="row-link" href="<?= BASE_URL ?>boleta">Ver todas las boletas &rarr;</a></p>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <?php if ($grafico !== null): ?>
            <!-- Datos para el gráfico. JSON_HEX_* evita que un texto con "</script>" corte este bloque. -->
            <script id="datos-admin" type="application/json"><?= json_encode($grafico, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
            <script src="<?= BASE_URL ?>js/vendor/chart.umd.min.js"></script>
            <script src="<?= BASE_URL ?>js/admin.js"></script>
        <?php endif; ?>
    </main>
</body>
</html>
