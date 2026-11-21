<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> | Consumo Energético</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>img/favicon.svg">
<script>
    // Etapa 18: aplica el tema antes de dibujar la página (evita el parpadeo). Elección guardada o, si no hay, la del sistema.
    (function () { try { var t = localStorage.getItem('tema'); if (t !== 'dark' && t !== 'light') { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } document.documentElement.setAttribute('data-theme', t); } catch (e) {} })();
</script>
<script src="<?= BASE_URL ?>js/tema.js" defer></script>
<?php // Etapa 20: "?v=" cambia cada vez que se modifica el archivo, así el navegador no usa una copia vieja guardada. ?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/style.css?v=<?= filemtime(ROOT_PATH . 'public/css/style.css') ?>">
