<?php
// Etapa 19: "Mi perfil" (propuesta A, ficha única): cabecera con actividad + Datos personales + Contraseña.
$nombreCompleto = $usuario->nombre . ' ' . $usuario->apellido;
$iniciales = mb_strtoupper(mb_substr($usuario->nombre, 0, 1) . mb_substr($usuario->apellido, 0, 1));
$esAdmin = $usuario->rol === 'administrador';

/** Mensaje de error debajo de un campo. */
$errorDe = function (array $errores, string $campo): string {
    return isset($errores[$campo]) ? '<p class="field-error" id="' . $campo . '-error">' . htmlspecialchars($errores[$campo]) . '</p>' : '';
};
/** Atributos de un campo con error: borde rojo y el mensaje asociado para lectores de pantalla. */
$marca = function (array $errores, string $campo, string $ayuda = ''): string {
    $describe = trim($ayuda . (isset($errores[$campo]) ? ' ' . $campo . '-error' : ''));
    return (isset($errores[$campo]) ? ' class="is-invalid" aria-invalid="true"' : '') . ($describe !== '' ? ' aria-describedby="' . $describe . '"' : '');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <section class="page-heading" aria-labelledby="page-title">
            <p class="eyebrow">Tu cuenta</p>
            <h1 id="page-title">Mi perfil</h1>
            <p class="section-description"><?= $esAdmin ? 'Tus datos y tu contraseña.' : 'Tus datos, tu contraseña y un resumen de lo que cargaste.' ?></p>
        </section>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="card" aria-label="Resumen de tu cuenta">
            <div class="profile-head">
                <div class="profile-id">
                    <span class="avatar perfil-avatar" aria-hidden="true"><?= htmlspecialchars($iniciales) ?></span>
                    <div>
                        <p class="profile-name perfil-nombre"><?= htmlspecialchars($nombreCompleto) ?></p>
                        <p class="cell-sub"><?= htmlspecialchars($usuario->email) ?> · registrado el <?= Formato::fecha($usuario->fecha_registro) ?></p>
                    </div>
                    <span class="badge <?= $esAdmin ? 'badge-admin' : 'badge-user' ?>"><?= $esAdmin ? 'Administrador' : 'Usuario' ?></span>
                </div>
                <?php if (!$esAdmin): ?>
                    <div class="profile-actions">
                        <a class="btn btn-secondary" href="<?= BASE_URL ?>consumo">Ver mis boletas</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($actividad !== null): ?>
                <?php $total = (int) $actividad->total; $conPdf = (int) $actividad->con_pdf; ?>
                <h2 class="profile-section">Tu actividad</h2>
                <div class="stat-grid">
                    <div class="stat"><p class="stat-label">Boletas cargadas</p><p class="stat-value"><?= $total ?></p><p class="stat-note"><?= $total > 0 ? 'Desde que te registraste' : 'Todavía ninguna' ?></p></div>
                    <div class="stat"><p class="stat-label">Leídas desde PDF</p><p class="stat-value"><?= $conPdf ?></p><p class="stat-note"><?= $total > 0 ? round($conPdf / $total * 100) . ' % de tus cargas' : '—' ?></p></div>
                    <div class="stat"><p class="stat-label">Primera carga</p><p class="stat-value"><?= $actividad->primera ? Formato::fecha($actividad->primera) : '—' ?></p><p class="stat-note"><?= $actividad->primera ? Formato::haceTiempo($actividad->primera) : '—' ?></p></div>
                    <div class="stat"><p class="stat-label">Última carga</p><p class="stat-value"><?= $actividad->ultima ? Formato::fecha($actividad->ultima) : '—' ?></p><p class="stat-note"><?= $actividad->ultima ? Formato::haceTiempo($actividad->ultima) : '—' ?></p></div>
                </div>
            <?php endif; ?>
        </section>

        <div class="perfil-grid">
            <section class="card" aria-labelledby="datos-title">
                <h2 id="datos-title">Datos personales</h2>
                <p class="card-subtitle">Así aparecés en la aplicación.</p>
                <form class="bill-form" action="<?= BASE_URL ?>perfil/datos" method="post">
                    <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                    <div class="field-row">
                        <div class="field-group">
                            <label for="nombre">Nombre</label>
                            <input id="nombre" name="nombre" type="text" maxlength="60" autocomplete="given-name" value="<?= htmlspecialchars($datos['nombre']) ?>"<?= $marca($erroresDatos, 'nombre') ?> required>
                            <?= $errorDe($erroresDatos, 'nombre') ?>
                        </div>
                        <div class="field-group">
                            <label for="apellido">Apellido</label>
                            <input id="apellido" name="apellido" type="text" maxlength="60" autocomplete="family-name" value="<?= htmlspecialchars($datos['apellido']) ?>"<?= $marca($erroresDatos, 'apellido') ?> required>
                            <?= $errorDe($erroresDatos, 'apellido') ?>
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="email">Correo electrónico</label>
                        <input id="email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= htmlspecialchars($datos['email']) ?>"<?= $marca($erroresDatos, 'email', 'email-ayuda') ?> required>
                        <p id="email-ayuda" class="field-hint">Es tu usuario para ingresar.</p>
                        <?= $errorDe($erroresDatos, 'email') ?>
                    </div>
                    <div class="field-group">
                        <label for="datos_password_actual">Contraseña actual</label>
                        <input id="datos_password_actual" name="datos_password_actual" type="password" autocomplete="current-password"<?= $marca($erroresDatos, 'datos_password_actual', 'datos-password-ayuda') ?>>
                        <p id="datos-password-ayuda" class="field-hint">Solo si cambiás el correo.</p>
                        <?= $errorDe($erroresDatos, 'datos_password_actual') ?>
                    </div>
                    <div><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
                </form>
            </section>

            <section class="card" aria-labelledby="password-title">
                <h2 id="password-title">Cambiar contraseña</h2>
                <p class="card-subtitle">Primero confirmá la que usás hoy.</p>
                <form class="bill-form" action="<?= BASE_URL ?>perfil/password" method="post">
                    <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                    <div class="field-group">
                        <label for="password_actual">Contraseña actual</label>
                        <input id="password_actual" name="password_actual" type="password" autocomplete="current-password"<?= $marca($erroresPassword, 'password_actual') ?> required>
                        <?= $errorDe($erroresPassword, 'password_actual') ?>
                    </div>
                    <div class="field-group">
                        <label for="password">Contraseña nueva</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"<?= $marca($erroresPassword, 'password', 'password-requisitos') ?> required>
                        <?= $errorDe($erroresPassword, 'password') ?>
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
                        <label for="password_confirmacion">Repetí la contraseña nueva</label>
                        <input id="password_confirmacion" name="password_confirmacion" type="password" autocomplete="new-password"<?= $marca($erroresPassword, 'password_confirmacion') ?> required>
                        <?= $errorDe($erroresPassword, 'password_confirmacion') ?>
                        <p id="password-coincidencia" class="match-msg" aria-live="polite" hidden></p>
                    </div>
                    <div><button class="btn btn-primary" type="submit">Actualizar contraseña</button></div>
                </form>
            </section>
        </div>
    </main>
    <script src="<?= BASE_URL ?>js/password.js"></script>
    <!-- Requisitos en vivo, igual que en el registro (Etapa 13). -->
    <script src="<?= BASE_URL ?>js/registro.js"></script>
</body>
</html>
