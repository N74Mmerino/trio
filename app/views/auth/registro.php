<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body class="auth-page">
    <div class="theme-corner"><?php require APP_PATH . 'views/layouts/boton-tema.php'; ?></div>
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="register-title">
            <div class="energy-icon" aria-hidden="true">
                <?php require APP_PATH . 'views/layouts/logo.php'; ?>
            </div>
            <h1 id="register-title">Crear una cuenta</h1>
            <p class="auth-intro">Registrate para empezar a monitorear tu consumo energético</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="auth-form" action="<?= BASE_URL ?>auth/registro" method="post">
                <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                <div class="field-row">
                    <div class="field-group">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" type="text" placeholder="Juan" maxlength="60" value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>"<?= isset($errores['nombre']) ? ' class="is-invalid"' : '' ?> required>
                        <?php if (isset($errores['nombre'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['nombre']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="field-group">
                        <label for="apellido">Apellido</label>
                        <input id="apellido" name="apellido" type="text" placeholder="Pérez" maxlength="60" value="<?= htmlspecialchars($datos['apellido'] ?? '') ?>"<?= isset($errores['apellido']) ? ' class="is-invalid"' : '' ?> required>
                        <?php if (isset($errores['apellido'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['apellido']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field-group">
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" placeholder="tu@email.com" maxlength="120" value="<?= htmlspecialchars($datos['email'] ?? '') ?>"<?= isset($errores['email']) ? ' class="is-invalid"' : '' ?> required>
                    <?php if (isset($errores['email'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errores['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" placeholder="Creá una contraseña" autocomplete="new-password" aria-describedby="password-requisitos"<?= isset($errores['password']) ? ' class="is-invalid"' : '' ?> required>
                    <?php if (isset($errores['password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errores['password']) ?></p>
                    <?php endif; ?>
                    <?php $etiquetas = ['pendiente' => '(pendiente)', 'cumple' => '(cumple)', 'no-cumple' => '(no cumple)']; ?>
                    <ul id="password-requisitos" class="password-reqs" data-intentado="<?= $intentado ? '1' : '0' ?>">
                        <?php foreach ($requisitos as $requisito): ?>
                            <?php $estado = $requisito['cumple'] === null ? 'pendiente' : ($requisito['cumple'] ? 'cumple' : 'no-cumple'); ?>
                            <li class="req is-<?= $estado ?>" data-patron="<?= htmlspecialchars($requisito['patron']) ?>">
                                <?= htmlspecialchars($requisito['texto']) ?>
                                <span class="visually-hidden req-estado"><?= $etiquetas[$estado] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="field-group">
                    <label for="password_confirmacion">Confirmar contraseña</label>
                    <input id="password_confirmacion" name="password_confirmacion" type="password" placeholder="••••••••" autocomplete="new-password"<?= isset($errores['password_confirmacion']) ? ' class="is-invalid"' : '' ?> required>
                    <?php if (isset($errores['password_confirmacion'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errores['password_confirmacion']) ?></p>
                    <?php endif; ?>
                    <p id="password-coincidencia" class="match-msg" aria-live="polite" hidden></p>
                </div>
                <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </form>

            <p class="auth-footer">¿Ya tenés cuenta? <a href="<?= BASE_URL ?>auth/login">Iniciá sesión acá</a></p>
        </section>
    </main>
    <script src="<?= BASE_URL ?>js/password.js"></script>
    <script src="<?= BASE_URL ?>js/registro.js"></script>
</body>
</html>
