<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="auth-page">
    <div class="theme-corner"><?php require APP_PATH . 'views/layouts/boton-tema.php'; ?></div>
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="recupero-title">
            <div class="energy-icon" aria-hidden="true">
                <?php require APP_PATH . 'views/layouts/logo.php'; ?>
            </div>
            <h1 id="recupero-title">Recuperar contraseña</h1>
            <p class="auth-intro">Escribí el correo de tu cuenta y te enviamos un enlace para crear una contraseña nueva.</p>

            <?php if (!empty($correoEnviado)): ?>
                <div class="alert alert-success" role="status">
                    Si <strong><?= htmlspecialchars($correoEnviado) ?></strong> está registrado, te enviamos un enlace para crear una contraseña nueva. Revisá tu bandeja de entrada (y la de spam). El enlace vence en <?= (int) $minutos ?> minutos.
                </div>
                <p class="auth-intro">¿El correo está mal escrito? Corregilo abajo y volvé a enviar.</p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" action="<?= BASE_URL ?>recupero" method="post">
                <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                <div class="field-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" maxlength="120" autocomplete="email" value="<?= htmlspecialchars($email) ?>"<?= isset($errores['email']) ? ' class="is-invalid" aria-describedby="email-error"' : '' ?> required>
                    <?php if (isset($errores['email'])): ?>
                        <p id="email-error" class="field-error"><?= htmlspecialchars($errores['email']) ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Enviar enlace</button>
            </form>

            <p class="auth-footer"><a href="<?= BASE_URL ?>auth/login">Volver a iniciar sesión</a></p>
        </section>
    </main>
</body>
</html>
