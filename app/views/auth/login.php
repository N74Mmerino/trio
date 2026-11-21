<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="auth-page">
    <div class="theme-corner"><?php require APP_PATH . 'views/layouts/boton-tema.php'; ?></div>
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="login-title">
            <div class="energy-icon" aria-hidden="true">
                <?php require APP_PATH . 'views/layouts/logo.php'; ?>
            </div>
            <h1 id="login-title">Bienvenido de nuevo</h1>
            <p class="auth-intro">Ingresá tus credenciales para acceder a tu dashboard</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-success" role="status"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" action="<?= BASE_URL ?>auth/login" method="post">
                <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                <div class="field-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="field-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <a class="field-link" href="<?= BASE_URL ?>recupero">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            </form>

            <p class="auth-footer">¿No tenés cuenta? <a href="<?= BASE_URL ?>auth/registro">Registrate acá</a></p>
        </section>
    </main>
    <script src="<?= BASE_URL ?>js/password.js"></script>
</body>
</html>
