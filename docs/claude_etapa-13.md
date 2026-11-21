# Etapa 13 — Requisitos de contraseña en el registro

**Fecha:** 2026-11-11
**Pedido:** del profesor — mostrar y exigir requisitos de contraseña al crear una cuenta.
**Estado:** aplicada y probada en XAMPP (PHP 8.2.12).

---

## 1. Decisiones aprobadas

- **Política A:** entre 8 y 72 caracteres, al menos una letra y al menos un número.
  Se aplica solo al **crear** una contraseña. No afecta el login ni a los usuarios existentes.
- **PHP como base, JavaScript plano encima** (sin jQuery):
  - **PHP:** la lista de requisitos se muestra siempre y se evalúa al enviar (verde = cumple, rojo = no cumple). Funciona aunque falle o esté desactivado el JavaScript.
  - **JS:** marca los requisitos en vivo (gris = pendiente, verde = cumple; rojo solo después de intentar enviar).
  - **Confirmación:** "Las contraseñas coinciden / no coinciden".
- **Una sola fuente de verdad:** la política se define una vez en `Validador` (texto + patrón). La vista genera la lista con `data-patron` y el JS lee los patrones desde el HTML.
- El botón **"Crear cuenta" nunca se deshabilita**. La contraseña **no se devuelve** al re-renderizar.

## 2. Respaldo previo

En `C:\respaldos\consumo-energetico\`:

| Archivo | Contenido |
|---|---|
| `consumo-energetico_2026-11-11_antes-registro.zip` | Proyecto completo (461 archivos, verificado). |
| `consumo_energetico_2026-11-11_antes-registro.sql` | `mysqldump` con estructura, datos, triggers y rutinas; incluye `CREATE DATABASE`. |

## 3. Cambios por archivo

### `app/core/Validador.php`
- **Qué:** constante `REQUISITOS_PASSWORD` (clave → texto + patrón), método estático `requisitosPassword(?string)`, regla encadenable `passwordSegura()` y helper privado `cumplePatron()`.
- **Por qué:** es la única definición de la política. PHP la aplica y la vista la publica para el JS.
- **Cómo:** los patrones se escriben sin delimitadores, con sintaxis válida en PCRE y en JavaScript:

  | Clave | Texto | Patrón |
  |---|---|---|
  | `largo` | Entre 8 y 72 caracteres | `^.{8,72}$` |
  | `letra` | Al menos una letra | `\p{L}` |
  | `numero` | Al menos un número | `[0-9]` |

  En PHP se evalúan con `preg_match('~patron~uD', ...)`:
  - `u`: UTF-8, cuenta caracteres y no bytes; `\p{L}` acepta ñ y tildes.
  - `D`: `$` significa fin real de la cadena (sin `D`, PHP aceptaría un `\n` final). Así se comporta igual que en JS.
  - Si llega UTF-8 inválido, `preg_match` devuelve `false` y la contraseña se rechaza.
- `requisitosPassword(null)` devuelve todo con `cumple = null` (pendiente). Con un valor, devuelve `true/false` por requisito.

### `app/controllers/AuthController.php`
- En `validarRegistro()`: `->minimo('password', 8, ...)` se reemplazó por `->passwordSegura('password', 'La contraseña no cumple los requisitos.')`.
- En `actionRegistro()`:
  - `$requisitos` empieza en pendiente y se evalúa con la contraseña enviada cuando el CSRF es válido.
  - `$intentado` indica a la vista (y al JS) que ya hubo un envío real.
  - `unset($datos['password'], $datos['password_confirmacion'])` antes de renderizar: la contraseña nunca viaja a la vista.
  - Si falla el CSRF, la lista queda en gris, porque no hubo un intento real.
- `actionLogin()` **no se modificó**.

### `app/views/auth/registro.php`
- Se agregó la lista `<ul id="password-requisitos" data-intentado="0|1">`. Cada `<li>` tiene:
  - la clase `is-pendiente`, `is-cumple` o `is-no-cumple`;
  - el atributo `data-patron`;
  - un texto oculto para lectores de pantalla: "(pendiente)", "(cumple)" o "(no cumple)".
- El input de contraseña tiene `aria-describedby="password-requisitos"` y `autocomplete="new-password"`.
- Se quitó `minlength="8"` de ambos campos. Con ese atributo, el navegador frenaba el envío con su propio cartel antes de que la lista pudiera pasar a rojo.
- Se agregó `<p id="password-coincidencia" class="match-msg" aria-live="polite" hidden>`, que completa el JS.
- Se agregó `<script src="…/js/registro.js">` al final del `<body>`. Es la misma convención que usa el dashboard.
- **Nota:** el plan mencionaba "el layout donde se incluyen los scripts", pero ese layout no existe. `registro.php` es una página completa que solo incluye `layouts/head.php`, sin scripts. Por eso no se tocó ningún layout.

### `public/js/registro.js` (nuevo)
- Lee los patrones desde `li[data-patron]` con `new RegExp(patron, 'u')`. Si el navegador no los soporta, sale sin hacer nada y queda la versión PHP.
- Mientras se escribe:
  - pinta cada requisito: verde si cumple; gris si no cumple y todavía no hubo intento; rojo si no cumple y ya hubo intento;
  - muestra el mensaje de coincidencia, oculto mientras la confirmación está vacía.
- Frena el envío **sin deshabilitar el botón**:
  - Usa `setCustomValidity()` en contraseña y confirmación, y el navegador bloquea el envío con su mensaje nativo.
  - El evento `invalid`, escuchado en captura porque no burbujea, marca el "intento" y pasa a rojo lo que falta.
- Al cargar **no recalcula nada**: después de un envío rechazado, la lista muestra lo que evaluó PHP hasta que el usuario vuelve a escribir.

### `public/css/style.css`
Bloque nuevo "Etapa 13 — Requisitos de contraseña" al final del archivo:
- **Íconos:** ○ / ✓ / ✕, escritos como escapes CSS.
- **Colores de texto:** se usan los tonos oscuros ya existentes (`#2f6b45` verde, `#8f3a3a` rojo) por contraste en texto de 12 px.
- **`.match-msg[hidden]`:** hace falta porque `display: flex` anula el atributo `hidden`.
- **`.visually-hidden`:** texto solo para lectores de pantalla.

## 4. Pruebas realizadas

### A1 — Política en PHP (script por consola, fuera del proyecto): 12 de 12 correctas

| Caso | Esperado | Resultado |
|---|---|---|
| Vacía | rechaza | OK |
| `abcdef1` (7 caracteres) | rechaza (largo) | OK |
| 8 solo letras | rechaza (número) | OK |
| 8 solo números | rechaza (letra) | OK |
| `abcdefg1` | acepta | OK |
| 72 caracteres | acepta | OK |
| 73 caracteres | rechaza (largo) | OK |
| Con ñ y tilde | acepta | OK |
| 72 caracteres con ñ | acepta | OK |
| `abcdefg1` + `\n` final | rechaza (largo, gracias a `D`) | OK |
| UTF-8 inválido | rechaza | OK |
| Dígito no ASCII (٣) | rechaza (número) | OK |

### A2 — Navegador, sin escribir en la base

| Prueba | Resultado |
|---|---|
| HTML crudo sin JS (`curl`): lista visible, en gris, con `data-patron`; `registro.js` responde 200 | OK |
| Al escribir `abc`: letra en verde, el resto en gris (no en rojo) | OK |
| Envío con `abc`: el navegador lo frena con "La contraseña no cumple los requisitos.", lo que falta pasa a rojo, borde rojo, botón habilitado, sin POST | OK |
| Al completar `abcdefg1`: todo en verde, campo válido | OK |
| Confirmación antes de intentar: "no coinciden" en gris; después de intentar: en rojo; al igualar: "coinciden" en verde | OK |
| Validación del servidor salteando el JS (`form.noValidate = true`) con contraseña débil: PHP pinta largo y número en rojo, letra en verde, y muestra los errores de contraseña y confirmación; los campos vuelven vacíos y la contraseña no aparece en el HTML | OK |
| Después del rechazo del servidor, al volver a escribir lo que no cumple aparece en rojo enseguida (`data-intentado="1"`) | OK |
| Contraseña válida con email existente (`admin@consumo.test`): el servidor rechaza por el email, los requisitos quedan en verde y no se crea ningún usuario | OK |
| Login sin cambios: sin lista, sin `registro.js`, mismo input | OK |
| La base sigue con 4 usuarios después del grupo A | OK |

### B — Con escritura en la base (autorizado)

| Prueba | Resultado |
|---|---|
| B1: registro público de `prueba.etapa13@consumo.test` con contraseña con ñ y tilde → redirige al login con "Cuenta creada. Ya podés iniciar sesión." | OK |
| B1: login con ese usuario → dashboard (el hash bcrypt funciona con multibyte) | OK |
| B1: un usuario común que entra a `auth/registro` es redirigido al dashboard (comportamiento previo intacto) | OK |
| B2: login de `admin@consumo.test` con su contraseña actual, que **no** cumple la política → entra. Confirma que los usuarios existentes no se ven afectados | OK |
| B2: el administrador crea `prueba.admin13@consumo.test` → "Usuario creado correctamente." y aparece en la lista | OK |
| Limpieza: se verificó que fueran 2 filas, rol usuario y 0 consumos; `DELETE` de esas 2 filas; la base vuelve a 4 usuarios | OK |

Nota: el `AUTO_INCREMENT` de `usuarios` quedó en 7 porque se usaron los ids 5 y 6. Es normal en MySQL y no afecta nada.

## 5. Nota técnica: bcrypt y 72 bytes

`password_hash()` usa bcrypt, que solo tiene en cuenta los **primeros 72 bytes**. La política cuenta **caracteres**. Con caracteres multibyte (ñ y vocales con tilde ocupan 2 bytes en UTF-8), una contraseña de 72 caracteres puede superar los 72 bytes, y bcrypt ignora el resto sin avisar. Se mantuvo la política aprobada: es un caso extremo y, aun así, quedan como mínimo 36 caracteres efectivos.

## 6. Cómo revertir

- **Solo el código:** restaurar desde el ZIP los 4 archivos modificados (`Validador.php`, `AuthController.php`, `registro.php`, `style.css`) y borrar `public/js/registro.js` y `docs/claude_etapa-13.md`.
- **Base de datos:** esta etapa no cambió la estructura ni dejó datos. Si hiciera falta, se importa el `.sql` del respaldo.

## 7. Pendientes

- [ ] Cambiar la contraseña `admin` de `admin@consumo.test`, que no cumple la política nueva.
- [ ] Pruebas en XAMPP pendientes de las etapas 11 y 12.
- [ ] (Opcional, futuro) Si se agrega una pantalla de "cambiar contraseña", usar `->passwordSegura(...)` y reutilizar la lista y `registro.js`.
