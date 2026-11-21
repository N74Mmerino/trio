<?php
// Etapa 14: cada rol tiene su propio menú y su propia pantalla de inicio.
// Etapa 17: el usuario suma "Mis boletas" (la ficha de una boleta también marca esa sección).
// Etapa 19: los dos roles tienen "Mi perfil" a la derecha, después del botón de tema.
$inicio = Session::rutaInicio();
// Etapa 19: botón "Mi perfil" con las iniciales del usuario (salen de la sesión).
$usuarioSesion = Session::usuario();
$iniciales = mb_strtoupper(mb_substr($usuarioSesion['nombre'] ?? '', 0, 1) . mb_substr($usuarioSesion['apellido'] ?? '', 0, 1));
$enPerfil = ($seccion ?? '') === 'perfil';
$enlaces = Session::esAdministrador()
    ? ['admin' => 'Inicio', 'usuario' => 'Usuarios', 'boleta' => 'Boletas']
    : ['dashboard' => 'Dashboard', 'consumo' => 'Mis boletas', 'consumo/cargar' => 'Cargar boleta'];
?>
<header class="app-header">
    <a class="brand" href="<?= BASE_URL . $inicio ?>" aria-label="Ir al inicio de Consumo Energético">
        <span class="brand-mark" aria-hidden="true"><?php require APP_PATH . 'views/layouts/logo.php'; ?></span>
        <span>Consumo energético</span>
    </a>
    <nav class="app-nav" aria-label="Navegación principal">
        <?php foreach ($enlaces as $ruta => $texto): ?>
            <a class="nav-link<?= ($seccion ?? '') === $ruta ? ' is-active' : '' ?>" href="<?= BASE_URL . $ruta ?>"><?= $texto ?></a>
        <?php endforeach; ?>
        <a class="nav-link" href="<?= BASE_URL ?>auth/logout">Salir</a>
        <?php require APP_PATH . 'views/layouts/boton-tema.php'; ?>
        <a class="profile-chip<?= $enPerfil ? ' is-active' : '' ?>" href="<?= BASE_URL ?>perfil" aria-label="Mi perfil"<?= $enPerfil ? ' aria-current="page"' : '' ?>>
            <span class="profile-chip-avatar" aria-hidden="true"><?= htmlspecialchars($iniciales) ?></span>
            <span class="profile-chip-text" aria-hidden="true">Mi perfil</span>
        </a>
    </nav>
</header>
