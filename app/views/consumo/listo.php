<?php
// Etapa 20: paso 3 de la carga. Confirma la boleta recién guardada.
// Recibe del controlador: boleta (con servicio, unidad y archivo).
$paso = 3;
$iconos = require APP_PATH . 'views/consumo/iconos.php';
$idServicio = (int) $boleta->id_servicio;
$conPdf = $boleta->archivo !== null;
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

        <section class="card done-card" aria-labelledby="listo-titulo">
            <span class="done-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7" /></svg></span>
            <h2 id="listo-titulo" role="status">Boleta registrada</h2>
            <p class="done-text"><?= $conPdf ? 'Guardamos la boleta con su comprobante.' : 'Guardamos la boleta.' ?> Ya la podés ver en Mis boletas y en el dashboard.</p>

            <a class="bill-row preview-row" href="<?= BASE_URL ?>consumo/ver/<?= (int) $boleta->id_consumo ?>">
                <span class="bill-id">
                    <span class="bill-icon" aria-hidden="true"><?= $iconos[$idServicio] ?? '' ?></span>
                    <span class="bill-name">
                        <span class="bill-service"><?= htmlspecialchars($boleta->servicio) ?></span>
                        <span class="bill-date"><?= Formato::fecha($boleta->fecha) ?><?php if ($conPdf): ?><span class="bill-pdf">PDF</span><?php endif; ?></span>
                    </span>
                </span>
                <span class="bill-figure">
                    <span class="bill-label">Consumo</span>
                    <span class="bill-value"><?= Formato::numero((float) $boleta->cantidad) ?> <?= htmlspecialchars($boleta->unidad) ?></span>
                </span>
                <span class="bill-figure">
                    <span class="bill-label">Pagaste</span>
                    <span class="bill-value"><?= Formato::pesos((float) $boleta->importe) ?></span>
                </span>
            </a>

            <div class="done-actions">
                <a class="btn btn-outline" href="<?= BASE_URL ?>consumo/ver/<?= (int) $boleta->id_consumo ?>">Ver la boleta</a>
                <a class="btn btn-primary" href="<?= BASE_URL ?>consumo/cargar">Cargar otra boleta</a>
            </div>
            <p class="done-link"><a class="link-subtle" href="<?= BASE_URL ?>dashboard">Ir al dashboard →</a></p>
        </section>
    </main>
</body>
</html>
