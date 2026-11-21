<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="auth-page">
    <div class="theme-corner"><?php require APP_PATH . 'views/layouts/boton-tema.php'; ?></div>
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="invalido-title">
            <div class="energy-icon" aria-hidden="true">
                <?php require APP_PATH . 'views/layouts/logo.php'; ?>
            </div>
            <h1 id="invalido-title">El enlace no es válido</h1>
            <p class="auth-intro">Puede que haya vencido (dura 60 minutos), que ya lo hayas usado o que hayas pedido otro más nuevo. Pedí uno nuevo y usá el último que te llegue.</p>

            <a class="btn btn-primary" href="<?= BASE_URL ?>recupero">Pedir un enlace nuevo</a>

            <p class="auth-footer"><a href="<?= BASE_URL ?>auth/login">Volver a iniciar sesión</a></p>
        </section>
    </main>
</body>
</html>
