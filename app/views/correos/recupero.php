<?php
/**
 * Correo de recupero de contraseña (Etapa 16).
 * Variables: $asunto, $nombre, $enlace, $minutos.
 * Estilos en línea y tablas: muchos clientes de correo (Gmail, Outlook) ignoran <style> y el CSS moderno.
 */
$e = static fn (string $texto): string => htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $e($asunto) ?></title>
</head>
<body style="margin:0;padding:0;background:#f8f8f5;font-family:Arial,Helvetica,sans-serif;color:#20221f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border:1px solid #e8e8e1;border-radius:16px;">
                <tr>
                    <td style="padding:28px 28px 8px;">
                        <p style="margin:0 0 4px;font-size:12px;color:#74766f;">Consumo Energético</p>
                        <h1 style="margin:0 0 16px;font-size:20px;">Recuperá tu contraseña</h1>
                        <p style="margin:0 0 12px;font-size:14px;line-height:1.5;">Hola, <?= $e($nombre) ?>.</p>
                        <p style="margin:0 0 20px;font-size:14px;line-height:1.5;">Recibimos un pedido para crear una contraseña nueva para tu cuenta. Tocá el botón para elegirla:</p>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:0 28px 20px;">
                        <a href="<?= $e($enlace) ?>" style="display:inline-block;padding:12px 22px;background:#ffcf4d;color:#3e3109;border-radius:12px;font-size:14px;font-weight:bold;text-decoration:none;">Crear contraseña nueva</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 28px;">
                        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#74766f;">El enlace vence en <?= (int) $minutos ?> minutos y sirve una sola vez. Si el botón no funciona, copiá esta dirección en el navegador:</p>
                        <p style="margin:0 0 16px;font-size:12px;line-height:1.5;word-break:break-all;"><a href="<?= $e($enlace) ?>" style="color:#8f6d11;"><?= $e($enlace) ?></a></p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:#74766f;">Si no pediste este cambio, ignorá este correo: tu contraseña actual sigue funcionando.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
