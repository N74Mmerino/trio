<?php
// Listado de boletas del administrador (Etapa 15). Solo datos de la carga: sin consumos, importes ni PDF.
use app\servicios\Indicadores;

/** URL del listado con los filtros actuales más los cambios indicados. No escribe los valores por defecto. */
$urlBoletas = function (array $cambios = []) use ($filtros): string {
    $parametros = array_merge($filtros, $cambios);
    $parametros['orden'] = $parametros['orden'] === 'carga' ? null : $parametros['orden'];
    $parametros['pagina'] = ($parametros['pagina'] ?? 1) > 1 ? $parametros['pagina'] : null;
    $parametros = array_filter($parametros, fn ($valor) => $valor !== null && $valor !== '');

    return BASE_URL . 'boleta' . ($parametros === [] ? '' : '?' . http_build_query($parametros));
};
$desdeFila = $total === 0 ? 0 : ($pagina - 1) * $porPagina + 1;
$hastaFila = ($pagina - 1) * $porPagina + count($boletas);
$hayFiltros = $urlBoletas(['pagina' => null]) !== BASE_URL . 'boleta';
$porcentajePdf = $total > 0 ? round((int) $resumen->con_pdf / $total * 100) : 0;
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
            <p class="eyebrow">Administración</p>
            <h1 id="page-title">Boletas</h1>
            <p class="section-description">Todas las cargas de la plataforma: quién, qué servicio y cuándo.</p>
        </section>

        <p class="privacy-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z" /><rect x="9" y="11" width="6" height="5" rx="1" /><path d="M10 11V9.5a2 2 0 0 1 4 0V11" /></svg>
            <span>Por privacidad no se muestran consumos, importes ni los PDF originales. Cada comprobante solo lo puede abrir su dueño.</span>
        </p>

        <form class="card filters-card" action="<?= BASE_URL ?>boleta" method="get" aria-label="Filtros del listado de boletas">
            <div class="filters-grid">
                <div class="field-group">
                    <label for="usuario">Usuario</label>
                    <select id="usuario" name="usuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u->id_usuario ?>"<?= $filtros['usuario'] === (int) $u->id_usuario ? ' selected' : '' ?>><?= htmlspecialchars($u->nombre . ' ' . $u->apellido) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label for="servicio">Servicio</label>
                    <select id="servicio" name="servicio">
                        <option value="">Todos</option>
                        <?php foreach ($servicios as $s): ?>
                            <option value="<?= $s->id_servicio ?>"<?= $filtros['servicio'] === (int) $s->id_servicio ? ' selected' : '' ?>><?= htmlspecialchars($s->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label for="origen">Origen</label>
                    <select id="origen" name="origen">
                        <option value="">Todos</option>
                        <option value="pdf"<?= $filtros['origen'] === 'pdf' ? ' selected' : '' ?>>PDF</option>
                        <option value="manual"<?= $filtros['origen'] === 'manual' ? ' selected' : '' ?>>Manual</option>
                    </select>
                </div>
                <div class="field-group">
                    <label for="periodo">Período</label>
                    <select id="periodo" name="periodo">
                        <option value="">Todo</option>
                        <option value="6"<?= $filtros['periodo'] === '6' ? ' selected' : '' ?>>Últimos 6 meses</option>
                        <option value="12"<?= $filtros['periodo'] === '12' ? ' selected' : '' ?>>Últimos 12 meses</option>
                    </select>
                </div>
                <div class="field-group">
                    <label for="orden">Ordenar por</label>
                    <select id="orden" name="orden">
                        <option value="carga">Fecha de carga</option>
                        <option value="boleta"<?= $filtros['orden'] === 'boleta' ? ' selected' : '' ?>>Fecha de la boleta</option>
                    </select>
                </div>
            </div>
            <div class="filters-actions">
                <ul class="chip-list" aria-label="Resumen del listado">
                    <li class="chip"><?= (int) $resumen->usuarios ?> <?= (int) $resumen->usuarios === 1 ? 'usuario' : 'usuarios' ?></li>
                    <li class="chip"><?= (int) $resumen->con_pdf ?> <?= (int) $resumen->con_pdf === 1 ? 'leída' : 'leídas' ?> desde PDF (<?= $porcentajePdf ?> %)</li>
                    <?php foreach ($servicios as $s): ?>
                        <li class="chip"><span class="service-dot service-dot-<?= (int) $s->id_servicio ?>" aria-hidden="true"></span><?= (int) ($porServicio[$s->id_servicio] ?? 0) ?> <?= htmlspecialchars($s->nombre) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="filters-buttons">
                    <?php if ($hayFiltros): ?>
                        <a class="link-subtle" href="<?= BASE_URL ?>boleta">Limpiar filtros</a>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                </div>
            </div>
        </form>

        <section class="card users-panel" aria-labelledby="boletas-titulo">
            <div class="panel-header">
                <div>
                    <h2 id="boletas-titulo">Listado de boletas</h2>
                    <p>Tocá un nombre para ver solo las boletas de ese usuario.</p>
                </div>
                <span class="record-count"><?= $total ?> <?= $total === 1 ? 'boleta' : 'boletas' ?></span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Usuario</th>
                            <th scope="col">Servicio</th>
                            <th scope="col">Mes de la boleta</th>
                            <th scope="col">Cargada</th>
                            <th scope="col">Origen</th>
                            <th scope="col" class="actions-column"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($boletas === []): ?>
                            <tr><td colspan="6" class="empty-cell">No hay boletas con esos filtros.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($boletas as $boleta): ?>
                            <?php $nombre = $boleta->nombre . ' ' . $boleta->apellido; ?>
                            <tr>
                                <td><a class="user-link" href="<?= htmlspecialchars($urlBoletas(['usuario' => (int) $boleta->id_usuario, 'pagina' => null])) ?>" title="Ver solo las boletas de <?= htmlspecialchars($nombre) ?>"><?= htmlspecialchars($nombre) ?></a></td>
                                <td><span class="service-dot service-dot-<?= (int) $boleta->id_servicio ?>" aria-hidden="true"></span><?= htmlspecialchars($boleta->servicio) ?></td>
                                <td><?= Indicadores::mesDeFecha($boleta->fecha) ?><span class="cell-sub"><?= Formato::fecha($boleta->fecha) ?></span></td>
                                <td><?= Formato::fecha($boleta->fecha_carga) ?><span class="cell-sub"><?= date('H:i', strtotime($boleta->fecha_carga)) ?> h</span></td>
                                <td><span class="badge <?= $boleta->con_pdf ? 'badge-admin' : 'badge-user' ?>"><?= $boleta->con_pdf ? 'PDF' : 'Manual' ?></span></td>
                                <td>
                                    <a class="icon-button" href="<?= BASE_URL ?>usuario/actividad/<?= $boleta->id_usuario ?>" aria-label="Ver la actividad de <?= htmlspecialchars($nombre) ?>" title="Ver la actividad de <?= htmlspecialchars($nombre) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18" /><path d="m7 15 4-4 3 3 5-6" /></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="panel-footer">
                <span><?= $total === 0 ? 'Sin resultados' : "Mostrando {$desdeFila}–{$hastaFila} de {$total} · página {$pagina} de {$paginas}" ?></span>
                <nav class="pagination" aria-label="Páginas del listado">
                    <?php if ($pagina > 1): ?>
                        <a href="<?= htmlspecialchars($urlBoletas(['pagina' => $pagina - 1])) ?>">Anterior</a>
                    <?php else: ?>
                        <span aria-disabled="true">Anterior</span>
                    <?php endif; ?>
                    <?php if ($pagina < $paginas): ?>
                        <a href="<?= htmlspecialchars($urlBoletas(['pagina' => $pagina + 1])) ?>">Siguiente</a>
                    <?php else: ?>
                        <span aria-disabled="true">Siguiente</span>
                    <?php endif; ?>
                </nav>
            </footer>
        </section>
    </main>
</body>
</html>
