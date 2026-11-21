<?php
// Etapa 20: indicador de pasos de la carga de boletas (Boleta → Revisar → Listo).
// Se incluye con $paso = 1, 2 o 3 definido antes del require.
$nombresPasos = [1 => 'Boleta', 2 => 'Revisar', 3 => 'Listo'];
?>
<ol class="steps" aria-label="Pasos de la carga">
    <?php foreach ($nombresPasos as $numero => $nombrePaso): ?>
        <?php
        // Un paso está hecho si ya se pasó; el 3 cuenta como hecho cuando se está en él (la boleta ya se guardó).
        $hecho = $numero < $paso || ($numero === 3 && $paso === 3);
        $actual = $numero === $paso;
        $clase = $hecho ? ' is-done' : '';
        $clase .= $actual ? ' is-current' : '';
        ?>
        <?php if ($numero > 1): ?><li class="step-sep" aria-hidden="true"></li><?php endif; ?>
        <li class="step<?= $clase ?>"<?= $actual ? ' aria-current="step"' : '' ?>>
            <span class="step-num" aria-hidden="true"><?= $hecho ? '✓' : $numero ?></span>
            <span class="step-name"><?= $nombrePaso ?><?php if ($hecho && !$actual): ?><span class="visually-hidden"> (hecho)</span><?php endif; ?></span>
        </li>
    <?php endforeach; ?>
</ol>
