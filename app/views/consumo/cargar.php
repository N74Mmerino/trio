<?php
// Etapa 20: paso 1 de la carga. Elegir entre subir el PDF (se lee solo) o completar los datos a mano.
// Recibe del controlador: $error (problema al subir o leer el PDF, o null).
$paso = 1;
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
            <div class="alert alert-error alert-with-link" role="alert">
                <span><?= htmlspecialchars($error) ?></span>
                <a href="<?= BASE_URL ?>consumo/cargar?modo=manual">Completar a mano →</a>
            </div>
        <?php endif; ?>

        <section class="card" aria-labelledby="eleccion-titulo">
            <h2 id="eleccion-titulo">¿Cómo querés cargar la boleta?</h2>
            <p class="card-subtitle">En los dos casos vas a revisar los datos antes de guardar.</p>

            <div class="choice-grid">
                <form class="choice-card is-featured" action="<?= BASE_URL ?>consumo/subir" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                    <div class="choice-head">
                        <h3>Subir el PDF</h3>
                        <span class="choice-badge">Recomendado</span>
                    </div>
                    <p class="choice-text">Leemos el consumo, el importe y la fecha, y el PDF queda guardado como comprobante.</p>

                    <!-- El input cubre toda la zona (transparente): así hacer clic o soltar un archivo funciona sin JavaScript. -->
                    <div class="dropzone">
                        <input id="boleta" name="boleta" type="file" accept="application/pdf,.pdf" aria-describedby="boleta-ayuda">
                        <span class="dropzone-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" /><path d="M12 18v-6" /><path d="m9.5 14.5 2.5-2.5 2.5 2.5" /></svg></span>
                        <label class="dropzone-title" for="boleta">Arrastrá el PDF acá o <span>elegí un archivo</span></label>
                        <span class="dropzone-hint" id="boleta-ayuda">PDF digital · hasta 2 MB · luz (EPE), gas (Litoral Gas) o agua (ASSA)</span>
                        <span class="dropzone-file" hidden></span>
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">Leer boleta</button>
                </form>

                <div class="choice-card">
                    <h3>Completar a mano</h3>
                    <p class="choice-text">Para boletas de agua o gas, boletas en papel o un PDF que no pudimos leer.</p>
                    <ul class="choice-list">
                        <li>Servicio, cantidad, importe y fecha de emisión</li>
                        <li>Se guarda sin comprobante adjunto</li>
                    </ul>
                    <a class="btn btn-outline btn-block choice-action" href="<?= BASE_URL ?>consumo/cargar?modo=manual">Completar a mano</a>
                </div>
            </div>
        </section>

        <p class="page-footnote">¿Querés ver lo que ya cargaste? <a class="link-subtle" href="<?= BASE_URL ?>consumo">Ir a Mis boletas →</a></p>
    </main>
    <script src="<?= BASE_URL ?>js/cargar.js?v=<?= filemtime(ROOT_PATH . 'public/js/cargar.js') ?>"></script>
</body>
</html>
