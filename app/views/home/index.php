<?php
// Etapa 24: home público (propuesta B "Trío editorial").
// Lo ve solo quien no inició sesión: HomeController manda al usuario con sesión a su inicio.
// Reutiliza head.php (tema, fuente, CSS), logo.php (Trío) y boton-tema.php (modo claro / oscuro).
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="home-page">

    <header class="home-header">
        <a class="brand home-brand" href="<?= BASE_URL ?>" aria-label="Consumo Energético, inicio">
            <span class="brand-mark" aria-hidden="true"><?php require APP_PATH . 'views/layouts/logo.php'; ?></span>
            <span>consumo energético</span>
        </a>
        <nav class="home-nav" aria-label="Accesos">
            <?php require APP_PATH . 'views/layouts/boton-tema.php'; ?>
            <a class="home-btn home-btn-outline" href="<?= BASE_URL ?>auth/login">Ingresar</a>
            <a class="home-btn home-btn-solid" href="<?= BASE_URL ?>auth/registro">Crear cuenta</a>
        </nav>
    </header>

    <main>
        <!-- Portada: título grande y el logo Trío como imagen principal -->
        <section class="home-hero" aria-labelledby="home-titulo">
            <div class="home-hero-texto">
                <h1 id="home-titulo" class="home-titulo">
                    <span>Luz.</span>
                    <span>Agua.</span>
                    <span>Gas.</span>
                    <span class="home-titulo-acento">Entendé lo que pagás.</span>
                </h1>
                <p class="home-bajada">Una sola app para guardar tus boletas de servicios y convertirlas en gráficos, comparaciones y avisos de vencimiento.</p>
                <div class="home-acciones">
                    <a class="home-btn home-btn-solid home-btn-lg" href="<?= BASE_URL ?>auth/registro">Crear mi cuenta</a>
                    <a class="home-btn home-btn-outline home-btn-lg" href="#servicios">Ver qué lee</a>
                </div>
            </div>
            <div class="home-hero-logo" aria-hidden="true">
                <?php require APP_PATH . 'views/layouts/logo.php'; ?>
            </div>
        </section>

        <!-- Una franja por servicio, con los colores del logo -->
        <section id="servicios" class="home-servicios" aria-label="Servicios que lee el sistema">
            <article class="home-servicio home-servicio-luz">
                <div class="home-servicio-contenido">
                    <p class="home-servicio-num">01 · Luz</p>
                    <p class="home-servicio-unidad">kWh</p>
                    <p class="home-servicio-sub">Consumo del período</p>
                    <p class="home-servicio-texto">Lee tu boleta de la EPE: período, kWh consumidos, importe y fechas de vencimiento.</p>
                    <p class="home-servicio-empresa">EPE · Santa Fe</p>
                </div>
            </article>
            <article class="home-servicio home-servicio-agua">
                <div class="home-servicio-contenido">
                    <p class="home-servicio-num">02 · Agua</p>
                    <p class="home-servicio-unidad">m³</p>
                    <p class="home-servicio-sub">Según informa la boleta</p>
                    <p class="home-servicio-texto">Lee tu boleta de Aguas Santafesinas: período, metros cúbicos, importe y cuotas.</p>
                    <p class="home-servicio-empresa">Aguas Santafesinas</p>
                </div>
            </article>
            <article class="home-servicio home-servicio-gas">
                <div class="home-servicio-contenido">
                    <p class="home-servicio-num">03 · Gas</p>
                    <p class="home-servicio-unidad">m³</p>
                    <p class="home-servicio-sub">Consumo del período</p>
                    <p class="home-servicio-texto">Lee tu boleta de Litoral Gas: período, metros cúbicos, importe y vencimientos.</p>
                    <p class="home-servicio-empresa">Litoral Gas</p>
                </div>
            </article>
        </section>

        <!-- Frase central -->
        <section class="home-manifiesto">
            <p>Cada boleta se convierte en un dato. <span>Cada dato, en una mejor decisión sobre tu consumo.</span></p>
        </section>

        <!-- Los tres pasos de la carga (los mismos de "Cargar boleta") -->
        <section class="home-pasos" aria-label="Cómo funciona">
            <div class="home-paso">
                <p class="home-paso-num home-paso-luz" aria-hidden="true">1</p>
                <h2>Subí el PDF</h2>
                <p>Elegí luz, agua o gas y arrastrá la boleta. O cargá los datos a mano.</p>
            </div>
            <div class="home-paso">
                <p class="home-paso-num home-paso-agua" aria-hidden="true">2</p>
                <h2>Revisá</h2>
                <p>Ves la vista previa y confirmás los datos leídos antes de guardar.</p>
            </div>
            <div class="home-paso">
                <p class="home-paso-num home-paso-gas" aria-hidden="true">3</p>
                <h2>Analizá</h2>
                <p>Tu dashboard compara meses y te avisa 7 días antes de cada vencimiento.</p>
            </div>
        </section>
    </main>

    <!-- Cierre y pie -->
    <footer class="home-cierre">
        <div class="home-cierre-cuerpo">
            <div>
                <div class="home-puntos" aria-hidden="true"><span></span><span></span><span></span></div>
                <h2>Tres servicios.<br>Una sola mirada.</h2>
                <p>Creá tu cuenta y cargá tu primera boleta hoy.</p>
            </div>
            <div class="home-acciones">
                <a class="home-btn home-btn-luz home-btn-lg" href="<?= BASE_URL ?>auth/registro">Crear cuenta</a>
                <a class="home-btn home-btn-claro home-btn-lg" href="<?= BASE_URL ?>auth/login">Iniciar sesión</a>
            </div>
        </div>
        <div class="home-pie">
            <p>Proyecto final · Desarrollo de Software · Analista en Sistemas, ISEI · Rosario, <?= date('Y') ?></p>
            <p>Sin afiliación con EPE, Aguas Santafesinas ni Litoral Gas.</p>
        </div>
    </footer>

</body>
</html>
