<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($titulo) ?></h1>
    <p>Router, controlador y vista funcionando.</p>

    <h2>Servicios registrados en la base</h2>
    <ul>
        <?php foreach ($servicios as $servicio): ?>
            <li><?= htmlspecialchars($servicio->nombre) ?> (<?= htmlspecialchars($servicio->unidad) ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
