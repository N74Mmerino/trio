<?php
// Etapa 15: filtros, búsqueda y columnas de actividad (Boletas, Última carga).
$opcionesFiltro = ['todos' => 'Todos', 'activos' => 'Activos', 'inactivos' => 'Inactivos', 'sin-boletas' => 'Sin boletas'];
$cantidades = ['todos' => $conteos->todos, 'activos' => $conteos->activos, 'inactivos' => $conteos->inactivos, 'sin-boletas' => $conteos->sin_boletas];
$urlUsuarios = function (string $filtro, string $busqueda): string {
    $parametros = array_filter(['filtro' => $filtro === 'todos' ? '' : $filtro, 'q' => $busqueda], fn ($v) => $v !== '');

    return BASE_URL . 'usuario' . ($parametros === [] ? '' : '?' . http_build_query($parametros));
};
$hayCriterio = $filtro !== 'todos' || $busqueda !== '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require APP_PATH . 'views/layouts/head.php'; ?>
</head>
<body>
    <main class="app-container">
        <?php require APP_PATH . 'views/layouts/header.php'; ?>

        <section class="page-heading users-heading" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Administración</p>
                <h1 id="page-title">Usuarios</h1>
                <p class="section-description">Gestioná los usuarios con acceso a la plataforma de monitoreo energético.</p>
            </div>
            <a href="<?= BASE_URL ?>auth/registro" class="btn btn-primary add-user">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                Nuevo usuario
            </a>
        </section>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="card users-panel" aria-labelledby="users-table-title">
            <div class="panel-header">
                <div>
                    <h2 id="users-table-title">Listado de usuarios</h2>
                    <p>Consultá y administrá los registros disponibles.</p>
                </div>
                <span class="record-count"><?= count($usuarios) ?> <?= count($usuarios) === 1 ? 'usuario' : 'usuarios' ?></span>
            </div>
            <div class="panel-toolbar">
                <nav class="chip-list" aria-label="Filtrar usuarios">
                    <?php foreach ($opcionesFiltro as $clave => $texto): ?>
                        <a class="chip<?= $filtro === $clave ? ' is-active' : '' ?>" href="<?= htmlspecialchars($urlUsuarios($clave, $busqueda)) ?>"<?= $filtro === $clave ? ' aria-current="page"' : '' ?>><?= $texto ?> · <?= (int) $cantidades[$clave] ?></a>
                    <?php endforeach; ?>
                </nav>
                <form class="search-form" action="<?= BASE_URL ?>usuario" method="get" role="search">
                    <?php if ($filtro !== 'todos'): ?>
                        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                    <?php endif; ?>
                    <label for="q" class="visually-hidden">Buscar por nombre o email</label>
                    <input id="q" name="q" type="search" maxlength="120" placeholder="Buscar nombre o email" value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-secondary" type="submit">Buscar</button>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Usuario</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Boletas</th>
                            <th scope="col">Última carga</th>
                            <th scope="col" class="actions-column">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="empty-cell"><?= $hayCriterio ? 'Ningún usuario coincide con la búsqueda.' : 'Todavía no hay usuarios registrados.' ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php $idPropio = Session::usuario()['id']; ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php $nombreCompleto = $usuario->nombre . ' ' . $usuario->apellido; ?>
                            <?php $esUnoMismo = (int) $usuario->id_usuario === (int) $idPropio; ?>
                            <tr>
                                <td><strong class="user-name"><?= htmlspecialchars($nombreCompleto) ?></strong><a class="email-link cell-sub" href="mailto:<?= htmlspecialchars($usuario->email) ?>"><?= htmlspecialchars($usuario->email) ?></a></td>
                                <td><span class="badge <?= $usuario->rol === 'administrador' ? 'badge-admin' : 'badge-user' ?>"><?= htmlspecialchars(ucfirst($usuario->rol)) ?></span></td>
                                <td><span class="badge <?= $usuario->activo ? 'badge-active' : 'badge-inactive' ?>"><?= $usuario->activo ? 'Activo' : 'Inactivo' ?></span></td>
                                <td><?= $usuario->rol === 'usuario' ? (int) $usuario->boletas : '—' ?></td>
                                <td><?= $usuario->ultima_carga ? Formato::fecha($usuario->ultima_carga) : '—' ?></td>
                                <td>
                                    <div class="action-group">
                                        <?php if ($usuario->rol === 'usuario'): ?>
                                            <a class="icon-button" href="<?= BASE_URL ?>usuario/actividad/<?= $usuario->id_usuario ?>" aria-label="Ver la actividad de <?= htmlspecialchars($nombreCompleto) ?>" title="Ver actividad">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18" /><path d="m7 15 4-4 3 3 5-6" /></svg>
                                            </a>
                                        <?php endif; ?>
                                        <a class="icon-button" href="<?= BASE_URL ?>usuario/editar/<?= $usuario->id_usuario ?>" aria-label="Editar usuario <?= htmlspecialchars($nombreCompleto) ?>" title="Editar nombre">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" /></svg>
                                        </a>
                                        <?php if ($esUnoMismo): ?>
                                            <button class="icon-button" type="button" disabled aria-label="No podés cambiar el estado de tu propio usuario" title="No podés cambiar el estado de tu propio usuario">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="m17 8 5 5m0-5-5 5" /></svg>
                                            </button>
                                        <?php else: ?>
                                            <form action="<?= BASE_URL ?>usuario/estado/<?= $usuario->id_usuario ?>" method="post" onsubmit="return confirm('<?= $usuario->activo ? '¿Desactivar' : '¿Activar' ?> a <?= htmlspecialchars(addslashes($nombreCompleto)) ?>?');">
                                                <input type="hidden" name="csrf" value="<?= Session::tokenCsrf() ?>">
                                                <?php if ($usuario->activo): ?>
                                                    <button class="icon-button icon-button-danger" type="submit" aria-label="Desactivar usuario <?= htmlspecialchars($nombreCompleto) ?>" title="Desactivar">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="m17 8 5 5m0-5-5 5" /></svg>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="icon-button" type="submit" aria-label="Activar usuario <?= htmlspecialchars($nombreCompleto) ?>" title="Activar">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="panel-footer"><span class="status-dot" aria-hidden="true"></span><span><strong>Mostrando:</strong> <?= count($usuarios) ?> de <?= (int) $conteos->todos ?> usuarios</span></footer>
        </section>
    </main>
</body>
</html>
