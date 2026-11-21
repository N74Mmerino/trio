<?php

namespace app\servicios;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

/**
 * Envío de correos (Etapa 16).
 *
 * - Con MAIL_HOST configurado en .env: se envía por SMTP con PHPMailer.
 * - Sin MAIL_HOST (desarrollo local): el correo no sale; se guarda como .html en storage/correos/
 *   para poder abrirlo y probar el enlace sin configurar un servidor de correo.
 *
 * Las plantillas están en app/views/correos/. Cada una arma el HTML del correo con las variables que recibe.
 */
class Correo
{
    /** Devuelve true si el correo se envió (o se guardó, en desarrollo). Los errores quedan en el log de PHP. */
    public static function enviar(string $para, string $nombre, string $asunto, string $plantilla, array $variables = []): bool
    {
        try {
            $html = self::plantilla($plantilla, $variables + ['asunto' => $asunto]);
            $texto = self::textoPlano($html);

            if (self::config('MAIL_HOST') === '') {
                self::guardarLocal($para, $asunto, $html);
            } else {
                self::enviarSmtp($para, $nombre, $asunto, $html, $texto);
            }

            return true;
        } catch (Throwable $e) {
            error_log('[Correo] No se pudo enviar "' . $asunto . '": ' . $e->getMessage());

            return false;
        }
    }

    private static function enviarSmtp(string $para, string $nombre, string $asunto, string $html, string $texto): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('Falta instalar la librería phpmailer/phpmailer (composer require phpmailer/phpmailer).');
        }

        $correo = new PHPMailer(true);
        $correo->isSMTP();
        $correo->Host = self::config('MAIL_HOST');
        $correo->Port = (int) (self::config('MAIL_PORT') ?: 587);
        $correo->SMTPAuth = self::config('MAIL_USER') !== '';
        $correo->Username = self::config('MAIL_USER');
        $correo->Password = self::config('MAIL_PASS');
        // 587 → STARTTLS (tls), 465 → SSL implícito (ssl). "none" solo para servidores de prueba locales (Mailpit, etc.).
        switch (self::config('MAIL_SECURE')) {
            case 'ssl':
                $correo->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'none':
                $correo->SMTPSecure = '';
                $correo->SMTPAutoTLS = false;
                break;
            default:
                $correo->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $correo->CharSet = PHPMailer::CHARSET_UTF8;
        $correo->Timeout = 15;

        $correo->setFrom(self::config('MAIL_FROM') ?: self::config('MAIL_USER'), self::config('MAIL_FROM_NAME') ?: 'Consumo Energético');
        $correo->addAddress($para, $nombre);
        $correo->Subject = $asunto;
        $correo->isHTML(true);
        $correo->Body = $html;
        $correo->AltBody = $texto;

        $correo->send();
    }

    /** Desarrollo: guarda el correo en storage/correos/ en lugar de enviarlo. */
    private static function guardarLocal(string $para, string $asunto, string $html): void
    {
        $carpeta = ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR . 'correos' . DIRECTORY_SEPARATOR;

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $nombre = date('Y-m-d_His') . '_' . bin2hex(random_bytes(4)) . '.html';
        $encabezado = '<!-- Para: ' . htmlspecialchars($para) . ' | Asunto: ' . htmlspecialchars($asunto) . " -->\n";

        if (file_put_contents($carpeta . $nombre, $encabezado . $html) === false) {
            throw new RuntimeException('No se pudo guardar el correo en ' . $carpeta);
        }
    }

    private static function plantilla(string $plantilla, array $variables): string
    {
        $archivo = APP_PATH . 'views' . DIRECTORY_SEPARATOR . 'correos' . DIRECTORY_SEPARATOR . $plantilla . '.php';

        return (static function () use ($archivo, $variables): string {
            extract($variables, EXTR_SKIP);
            ob_start();
            require $archivo;

            return (string) ob_get_clean();
        })();
    }

    /** Versión en texto del correo, para los clientes de correo que no muestran HTML. */
    private static function textoPlano(string $html): string
    {
        // Los enlaces se conservan como "texto (url)", o solo la url si el texto ya es la url.
        $html = preg_replace_callback('~<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>~is', static function (array $m): string {
            $texto = trim(strip_tags($m[2]));

            return $texto === $m[1] ? $m[1] : $texto . ' (' . $m[1] . ')';
        }, $html);
        $html = preg_replace('~<(br|/p|/h1|/h2|/div|/tr)\b[^>]*>~i', "\n", $html);
        $html = preg_replace('~<(style|title)\b.*?</\1>~is', '', $html);
        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lineas = array_map('trim', explode("\n", preg_replace('~[ \t]+~', ' ', $texto)));

        return trim(preg_replace("~\n{3,}~", "\n\n", implode("\n", $lineas)));
    }

    private static function config(string $clave): string
    {
        return trim((string) ($_ENV[$clave] ?? ''));
    }
}
