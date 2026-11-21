<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="error-title">
            <p class="error-code">404</p>
            <h1 id="error-title">Página no encontrada</h1>
            <p class="auth-intro">La página que buscás no existe o fue movida.</p>
            <a class="btn btn-primary" href="<?= BASE_URL ?>">Volver al inicio</a>
        </section>
    </main>
</body>
</html>
