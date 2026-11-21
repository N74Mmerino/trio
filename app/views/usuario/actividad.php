<?php
// Ficha de actividad de un usuario (Etapa 15): qué cargó y cuándo, sin consumos, importes ni PDF.
use app\servicios\Indicadores;

$nombreCompleto = $usuario->nombre . ' ' . $usuario->apellido;
$iniciales = mb_strtoupper(mb_substr($usuario->nombre, 0, 1) . mb_substr($usuario->apellido, 0, 1));
$total = (int) $actividad->total;
$conPdf = (int) $actividad->con_pdf;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <p class="back-link"><a href="<?= BASE_URL ?>usuario">&larr; Volver a Usuarios</a></p>

        <section class="card" aria-labelledby="page-title">
            <div class="profile-head">
                <div class="profile-id">
                    <span class="avatar" aria-hidden="true"><?= htmlspecialchars($iniciales) ?></span>
                    <div>
                        <h1 id="page-title" class="profile-name"><?= htmlspecialchars($nombreCompleto) ?></h1>
                        <p class="cell-sub"><a class="email-link" href="mailto:<?= htmlspecialchars($usuario->email) ?>"><?= htmlspecialchars($usuario->email) ?></a> · registrado el <?= Formato::fecha($usuario->fecha_registro) ?></p>
                    </div>
                    <span class="badge <?= $usuario->activo ? 'badge-active' : 'badge-inactive' ?>"><?= $usuario->activo ? 'Activo' : 'Inactivo' ?></span>
                </div>
                <div class="profile-actions">
                    <a class="btn btn-secondary" href="mailto:<?= htmlspecialchars($usuario->email) ?>">Escribirle</a>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>boleta?usuario=<?= $usuario->id_usuario ?>">Ver sus boletas</a>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>usuario/editar/<?= $usuario->id_usuario ?>">Editar nombre</a>
                </div>
            </div>

            <h2 class="profile-section">Actividad</h2>
            <div class="stat-grid">
                <div class="stat"><p class="stat-label">Boletas cargadas</p><p class="stat-value"><?= $total ?></p><p class="stat-note"><?= $total > 0 ? 'Desde que se registró' : 'Todavía ninguna' ?></p></div>
                <div class="stat"><p class="stat-label">Leídas desde PDF</p><p class="stat-value"><?= $conPdf ?></p><p class="stat-note"><?= $total > 0 ? round($conPdf / $total * 100) . ' % de sus cargas' : '—' ?></p></div>
                <div class="stat"><p class="stat-label">Primera carga</p><p class="stat-value"><?= $actividad->primera ? Formato::fecha($actividad->primera) : '—' ?></p><p class="stat-note"><?= $actividad->primera ? Formato::haceTiempo($actividad->primera) : '—' ?></p></div>
                <div class="stat"><p class="stat-label">Última carga</p><p class="stat-value"><?= $actividad->ultima ? Formato::fecha($actividad->ultima) : '—' ?></p><p class="stat-note"><?= $actividad->ultima ? Formato::haceTiempo($actividad->ultima) : '—' ?></p></div>
            </div>

            <h2 class="profile-section">Cobertura del último año</h2>
            <p class="card-subtitle">Meses con boleta cargada, sin montos. Borde oscuro: leída desde PDF.</p>
            <?php if ($total === 0): ?>
                <p class="list-empty"><?= $usuario->activo ? 'Este usuario todavía no cargó boletas.' : 'Cuenta inactiva y sin boletas cargadas.' ?></p>
            <?php else: ?>
                <div class="coverage-wrap">
                    <table class="coverage">
                        <caption class="visually-hidden">Boletas cargadas por servicio y mes en los últimos 12 meses</caption>
                        <thead>
                            <tr>
                                <td></td>
                                <?php foreach ($meses as $mes): ?>
                                    <th scope="col"><?= str_replace(' ', '<br>', Indicadores::etiquetaMes($mes)) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servicios as $s): ?>
                                <tr>
                                    <th scope="row"><span class="service-dot service-dot-<?= (int) $s->id_servicio ?>" aria-hidden="true"></span><?= htmlspecialchars($s->nombre) ?></th>
                                    <?php foreach ($meses as $mes): ?>
                                        <?php $marca = $cobertura[(int) $s->id_servicio][$mes] ?? null; ?>
                                        <td>
                                            <span class="coverage-cell<?= $marca === null ? '' : ' service-bg-' . (int) $s->id_servicio . ($marca ? ' is-pdf' : '') ?>" aria-hidden="true"></span>
                                            <span class="visually-hidden"><?= $marca === null ? 'Sin boleta' : ($marca ? 'Con boleta, leída desde PDF' : 'Con boleta') ?></span>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
