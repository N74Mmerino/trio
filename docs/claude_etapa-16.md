# Etapa 16 — Recupero de contraseña por correo

**Fecha:** 2026-11-12
**Objetivo:** que un usuario que olvidó su contraseña pueda crear una nueva sin depender del administrador.
**Estado:** aplicada y probada en XAMPP (PHP 8.2.12). PHPMailer 6.12.0 instalado y probado contra un servidor SMTP local de prueba. **Falta completar las variables `MAIL_*` del `.env` con una cuenta real** para que los correos salgan (ver §6). Mientras `MAIL_HOST` esté vacío, los correos se guardan en `storage/correos/`.

---

## 1. Cómo funciona

1. En el login, **"¿Olvidaste tu contraseña?"** lleva a `recupero`.
2. El usuario escribe su correo. Si la cuenta existe y está activa, se genera un enlace y se envía por correo.
3. El enlace (`recupero/restablecer/{token}`) abre el formulario **"Nueva contraseña"**, con los mismos requisitos en vivo, la confirmación y el ojito del registro.
4. Al guardar, la contraseña se actualiza y el usuario vuelve al login con el aviso "Listo, tu contraseña se actualizó".

## 2. Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Mensaje al pedir el enlace | Siempre el mismo: "Si el correo está registrado, te enviamos un enlace…". | Que nadie pueda averiguar qué correos tienen cuenta. |
| Qué se guarda del enlace | Solo el **hash SHA-256** del token (64 caracteres hexadecimales, `random_bytes(32)`). | Quien lea la base no puede armar un enlace válido. |
| Vigencia | **60 minutos** y **un solo uso**. Pedir otro anula el anterior. | Si el correo queda abierto en otra compu, el enlace deja de servir pronto. |
| Límite de pedidos | **3 cada 15 minutos** por cuenta. Pasado el límite se muestra el mismo mensaje, pero no se envía nada. | Que no se pueda llenar la casilla de alguien con correos. |
| Cuentas inactivas | No reciben enlace, y un enlace pedido antes deja de servir si la cuenta se desactiva. | Desactivar una cuenta tiene que cortar el acceso. |
| Uso del enlace | La contraseña y la baja del enlace se guardan en una **transacción**. El `UPDATE … WHERE anulado_en IS NULL` hace de candado. | Si el formulario se envía dos veces a la vez, solo una vez se aplica. |
| Fechas | Se comparan con `NOW()` de MySQL. | No depende de que PHP y la base tengan la misma hora. |
| Envío del correo | Con `MAIL_HOST` en `.env`: SMTP con PHPMailer. **Sin `MAIL_HOST`: se guarda como `.html` en `storage/correos/`.** | Se puede probar todo en XAMPP sin servidor de correo. |
| Si el envío falla | El usuario ve el mismo mensaje y el error queda en el log de PHP (`[Correo] …`). | Mostrar el error revelaría que la cuenta existe. |
| Formato del correo | HTML con estilos en línea y tablas, más una versión en texto plano. | Gmail y Outlook ignoran buena parte del CSS. |

## 3. Respaldo previo

En `C:\respaldos\consumo-energetico\`: `consumo-energetico_2026-11-12_antes-recupero.zip` (475 archivos, verificado) y `consumo_energetico_2026-11-12_antes-recupero.sql` (termina en "Dump completed").

## 4. Cambios por archivo

### Nuevos

| Archivo | Qué hace |
|---|---|
| `database/etapa16_recupero.sql` | Crea la tabla `recuperos_password` sobre una base existente. **Ya aplicado.** |
| `app/controllers/RecuperoController.php` | `recupero` (pedir el enlace) y `recupero/restablecer/{token}` (contraseña nueva). Un usuario con sesión iniciada vuelve a su inicio. |
| `app/models/RecuperoModel.php` | `contarRecientes()`, `crear()` (anula los pendientes y borra los vencidos hace más de un día), `buscarVigente()` y `usar()` (transacción). |
| `app/servicios/Correo.php` | `Correo::enviar()`: SMTP con PHPMailer o, sin `MAIL_HOST`, guardado en `storage/correos/`. Arma la versión en texto plano a partir del HTML. |
| `app/views/recupero/index.php` | Formulario del correo. |
| `app/views/recupero/restablecer.php` | Formulario de contraseña nueva. Reutiliza `password.js` y `registro.js`. |
| `app/views/recupero/invalido.php` | "El enlace no es válido" (vencido, usado, reemplazado o mal copiado), con el botón "Pedir un enlace nuevo". |
| `app/views/correos/recupero.php` | Plantilla del correo. |

### Modificados

| Archivo | Cambio |
|---|---|
| `database/consumo_energetico.sql` | Incluye `recuperos_password` para instalaciones nuevas. |
| `app/models/UsuarioModel.php` | `actualizarPassword()`. |
| `app/views/auth/login.php` | Enlace "¿Olvidaste tu contraseña?". |
| `public/css/style.css` | `.field-link` y subrayado al pasar el mouse por los enlaces del pie. |
| `.env` | Variables `MAIL_*`, vacías y comentadas. |
| `.gitignore` | `storage/correos/` (los correos guardados contienen enlaces válidos). |
| `app/librerias/composer.json` / `composer.lock` / `vendor/` | `phpmailer/phpmailer` ^6.9 (instalada la v6.12.0). smalot/pdfparser (v2.12.0) y phpdotenv (v5.6.3) no cambiaron. |

**Sobre Composer:** no estaba instalado en la máquina. Se usó un `composer.phar` temporal (v2.11.2, descargado de getcomposer.org y verificado contra su SHA-256 publicado), que no quedó en el proyecto. Para usar Composer más adelante, instalarlo con el instalador de Windows de getcomposer.org.

### Tabla `recuperos_password`

| Columna | Uso |
|---|---|
| `id_recupero` | PK. |
| `id_usuario` | FK a `usuarios`. `ON DELETE CASCADE`: si se borra el usuario, se borran sus enlaces. |
| `token_hash` | SHA-256 del token, único. |
| `fecha_pedido` | Para el límite de pedidos. |
| `expira` | `fecha_pedido` + 60 minutos. |
| `anulado_en` | Cuándo dejó de servir (se usó o se pidió otro). `NULL` = sigue sirviendo si no venció. |

## 5. Pruebas realizadas

Con un usuario temporal (`prueba.etapa16@consumo.test`, borrado al final).

| Caso | Resultado |
|---|---|
| Login → "¿Olvidaste tu contraseña?" | Lleva a `recupero` |
| Pedido con el correo de la cuenta | Mensaje genérico, 1 fila en la tabla, correo guardado en `storage/correos/` |
| Pedido con un correo inexistente | **El mismo mensaje**, sin fila ni correo |
| Enlace del correo | "Hola, Prueba. Elegí una contraseña nueva…", requisitos en vivo, ojito en los dos campos |
| Contraseñas que no coinciden / que no cumplen la política (POST directo) | "Las contraseñas no coinciden." / "La contraseña no cumple los requisitos." La contraseña no cambia |
| Guardar contraseña nueva | Vuelve al login con el aviso. En la base, la nueva verifica y la vieja no |
| Login con la contraseña nueva | Entra al dashboard |
| Volver a abrir el mismo enlace | "El enlace no es válido" |
| Token mal formado (`restablecer/xyz`) | "El enlace no es válido", sin consultar la base |
| Pedir otro enlace | El anterior deja de servir; solo el último funciona |
| 4 pedidos en 15 minutos | Se crean 3; el cuarto muestra el mensaje pero no genera enlace ni correo |
| Enlace vencido (se adelantó `expira` a mano) | "El enlace no es válido" |
| Cuenta desactivada con un enlace vigente | "El enlace no es válido"; al reactivarla, vuelve a servir |
| CSRF inválido | "La sesión expiró. Volvé a enviar el formulario." |
| `recupero` con sesión iniciada | Redirige al inicio del usuario |
| `MAIL_HOST` configurado sin PHPMailer instalado (antes de instalarlo) | `enviar()` devuelve false, el usuario ve el mensaje de siempre y el log dice "Falta instalar la librería phpmailer/phpmailer" |
| **Envío por SMTP con PHPMailer** contra un servidor SMTP mínimo local (Node, puerto 2525, `MAIL_SECURE=none`), con el `.env` apuntando a él y un segundo usuario temporal | El pedido desde la web llegó al servidor: `To`, `From` y `Subject` con tildes bien codificados (UTF-8), cuerpo `multipart/alternative` (texto + HTML), y el enlace del correo abre "Nueva contraseña". Después se restauró el `.env` |
| Celular (375 px) | Sin desborde horizontal |
| Log de Apache/PHP | Sin avisos nuevos |

**Limpieza:** se borraron los dos usuarios de prueba (sus enlaces se fueron con ellos por el `CASCADE`) y los correos de `storage/correos/`. El `.env` volvió a tener `MAIL_HOST` vacío. La base quedó con los 5 usuarios previos y la tabla nueva vacía.

**No probado:** el envío a través de un proveedor real (Gmail u otro), con TLS y usuario/contraseña. Depende de la cuenta que se configure.

## 6. Para activar el envío real

1. ~~Instalar PHPMailer~~ (hecho: v6.12.0).
2. Completar en `.env` (ejemplo con Gmail):
   ```
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_SECURE=tls
   MAIL_USER=cuenta-del-proyecto@gmail.com
   MAIL_PASS=<contraseña de aplicación de 16 letras>
   MAIL_FROM=cuenta-del-proyecto@gmail.com
   ```
   La "contraseña de aplicación" se crea en la cuenta de Google → Seguridad → Verificación en 2 pasos → Contraseñas de aplicaciones. **No** es la contraseña normal de la cuenta.
3. En producción, `APP_URL` tiene que ser la dirección pública: el enlace del correo se arma con ella.
4. Probar: pedir un enlace para una cuenta propia y revisar que llegue (también en spam). Si no llega, el motivo queda en el log de PHP con el prefijo `[Correo]`.

`MAIL_SECURE` acepta `tls` (puerto 587, lo normal), `ssl` (puerto 465) o `none` (solo para servidores de prueba locales como Mailpit).

## 7. Cómo revertir

- **Código:** borrar los 8 archivos nuevos y restaurar los modificados desde `consumo-energetico_2026-11-12_antes-recupero.zip` (incluye `app/librerias/` sin PHPMailer).
- **Base de datos:** `DROP TABLE recuperos_password;`

## 8. Otros cambios del mismo día (antes de esta etapa)

| Cambio | Archivos |
|---|---|
| **Boletas EPE guardadas desde el navegador.** Al usar "Imprimir → Guardar como PDF" (Firefox/Chrome), las fuentes quedan con `/Encoding` + `/Differences` sin `/BaseEncoding`, y smalot/pdfparser devolvía el texto ilegible. `LectorBoletas` ahora reemplaza esas fuentes por una copia que smalot sí interpreta, y `ExtractorEpe` acepta palabras sin espacios entre sí (`ConsumoTotal:141kWh`). Probado con 3 boletas guardadas desde Firefox y las 3 originales. | `app/servicios/LectorBoletas.php`, `app/servicios/ExtractorEpe.php` |
| **Ojito para mostrar u ocultar la contraseña** en login, registro y ahora en "Nueva contraseña". Se agrega con JS a todo `input[type=password]`. Al enviar el formulario la contraseña se vuelve a ocultar. | `public/js/password.js` (nuevo), `public/css/style.css`, `app/views/auth/login.php`, `app/views/auth/registro.php` |

## 9. Pendientes

- [x] Instalar PHPMailer.
- [ ] Completar las variables `MAIL_*` del `.env` con una cuenta real (§6) y probar un envío.
- [ ] Cambiar la contraseña de `admin@consumo.test` (el Inicio lo avisa).
- [ ] **"Mi perfil"** para el usuario: cambiar su email y su contraseña con la sesión iniciada.
- [ ] Etapa 17: Control de información (antes "Etapa 16": duplicados, valores atípicos, integridad de comprobantes, temporales viejos).
- [ ] Etapa 18: estadísticas ampliadas y seguimiento de actividad (antes "Etapa 17").
- [ ] (Mejora) El pedido de enlace tarda un poco más cuando la cuenta existe (se envía el correo), lo que permitiría adivinar cuentas midiendo tiempos. Se puede resolver enviando el correo después de responder.
- [ ] (Validaciones) límite de intentos de login; nombre y apellido solo con letras; importe mayor que 0; fecha mínima; máximo 2 decimales.
