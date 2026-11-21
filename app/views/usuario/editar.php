<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <section class="page-heading" aria-labelledby="page-title">
            <p class="eyebrow">Administración</p>
            <h1 id="page-title">Editar usuario</h1>
            <p class="section-description">Corregí el nombre de <?= htmlspecialchars($usuario->nombre . ' ' . $usuario->apellido) ?>.</p>
        </section>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="max-width: 480px;">
            <section class="card bill-card" aria-labelledby="form-title">
                <h2 id="form-title">Datos del usuario</h2>
                <form class="bill-form" action="<?= BASE_URL ?>usuario/editar/<?= $usuario->id_usuario ?>" method="post">
                    <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                    <div class="field-group">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" type="text" maxlength="60" value="<?= htmlspecialchars($datos['nombre']) ?>"<?= isset($errores['nombre']) ? ' class="is-invalid"' : '' ?> required>
                        <?php if (isset($errores['nombre'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['nombre']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="field-group">
                        <label for="apellido">Apellido</label>
                        <input id="apellido" name="apellido" type="text" maxlength="60" value="<?= htmlspecialchars($datos['apellido']) ?>"<?= isset($errores['apellido']) ? ' class="is-invalid"' : '' ?> required>
                        <?php if (isset($errores['apellido'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errores['apellido']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Etapa 15: el email se muestra pero no se envía (sin "name"); el controlador tampoco lo lee. -->
                    <div class="field-group">
                        <label for="email">Correo electrónico</label>
                        <input id="email" type="email" value="<?= htmlspecialchars($usuario->email) ?>" readonly aria-describedby="email-ayuda">
                        <p id="email-ayuda" class="field-hint">Es la credencial de acceso: solo la puede cambiar el propio usuario.</p>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                </form>
            </section>
        </div>

        <p class="auth-footer"><a href="<?= BASE_URL ?>usuario">&larr; Volver al listado</a></p>
    </main>
</body>
</html>
