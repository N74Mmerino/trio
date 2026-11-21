# Etapa 19 — Mi perfil

**Fecha:** 2026-11-19
**Pedido:** una pantalla "Mi perfil" donde cada usuario vea y corrija sus datos y cambie su contraseña.
**Estado:** aplicada. Probada en un entorno aislado (MariaDB + PHP 8.3, copia del proyecto). **Falta la prueba en XAMPP** (ver §6).
**Prototipo aprobado:** lienzo "Prototipo — Mi perfil", propuesta **A · Ficha única**, **sin "Desactivar cuenta"**.

---

## 1. Qué se agregó

| URL | Qué hace |
|---|---|
| `perfil` (GET) | La pantalla: cabecera con avatar y actividad, y dos tarjetas: Datos personales y Cambiar contraseña |
| `perfil/datos` (POST) | Guarda nombre, apellido y correo |
| `perfil/password` (POST) | Cambia la contraseña |

**Acceso:** botón con las iniciales ("NM · Mi perfil") a la derecha del botón de tema, para **los dos roles**. En el celular muestra solo las iniciales y sube a la fila del logo.

**Usuario:** ve nombre, correo, fecha de registro, "Ver mis boletas" y **Tu actividad** (boletas cargadas, leídas desde PDF, primera y última carga).
**Administrador:** ve solo Datos personales y Contraseña (no carga boletas).

## 2. Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Diseño | Propuesta A (una sola pantalla), sin "Desactivar cuenta" | Lo más simple de implementar y de defender; se puede ampliar con más tarjetas |
| Cambio de correo | Permitido, **confirmando con la contraseña actual**. Se guarda en minúsculas, como en el registro | El correo es la credencial: si alguien encuentra la sesión abierta, no puede quedarse con la cuenta. Resuelve la contradicción de `usuario/editar` ("solo la puede cambiar el propio usuario") |
| Correo repetido | `existeEmailDeOtro($email, $id)` | `existeEmail()` encontraría el propio correo y daría error aunque no se cambie |
| Contraseña nueva | Mismas reglas que el registro (`Validador::REQUISITOS_PASSWORD`) y **distinta de la actual** | Una sola política en toda la app |
| Después de cambiar la contraseña | La sesión sigue abierta, con **nuevo id de sesión** | Es más cómodo y es igual de seguro que el login (`session_regenerate_id`) |
| Enlaces de recupero pendientes | Se anulan (`RecuperoModel::anularPendientes`, ahora público) | Un enlace pedido antes del cambio no debe permitir cambiarla de nuevo |
| Aviso del admin "contraseña fuera de política" | Se apaga al cambiarla | La nueva cumple la política |
| Copia de los datos en la sesión | `Session::actualizarUsuario()` | El header lee nombre y apellido de la sesión: sin esto, los datos nuevos se verían recién al volver a entrar |
| Errores | La pantalla se vuelve a mostrar con el error debajo del campo; lo escrito en el otro formulario no se pierde | Dos formularios independientes, cada uno con su CSRF |
| Éxito | Redirige a `perfil` con un mensaje (patrón PRG) | Al apretar F5 no se reenvía el formulario |
| Hash de la contraseña | Nunca llega a la vista: la pantalla usa `buscarParaFicha()` (sin hash); el hash solo se lee para `password_verify` | Mínimo privilegio |
| Cuenta desactivada con la sesión abierta | Al entrar a `perfil`, se cierra la sesión y va al login | Una cuenta inactiva no debe poder cambiar credenciales |
| `registro.js` | Busca el formulario con `password.closest('form')` en vez de `form.auth-form` | Para reutilizar los requisitos en vivo en el perfil. El registro y el restablecimiento siguen igual (probado) |
| Ids de la "contraseña actual" | `datos_password_actual` (Datos) y `password_actual` (Contraseña) | Dos formularios en la misma página: un id no se puede repetir |
| Base de datos | **Sin cambios** | Todo sale de las columnas existentes |

## 3. Respaldo previo

`C:\respaldos\consumo-energetico\consumo-energetico_2026-11-19_antes-mi-perfil.zip` (567 archivos, verificado con `zip -T` y con la cantidad de archivos). La base no cambia: no hizo falta un `.sql`.

En `C:\respaldos\consumo-energetico\_to_delete\` quedaron archivos auxiliares que se pueden borrar: el temporal de `zip` de hoy y `copia-para-pruebas-etapa19.zip` (la copia usada para las pruebas).

## 4. Cambios por archivo

### Nuevos

| Archivo | Qué hace |
|---|---|
| `app/controllers/PerfilController.php` | `actionIndex()`, `actionDatos()`, `actionPassword()` y los privados `usuarioActual()`, `exigirCuentaActiva()` y `mostrar()` |
| `app/views/perfil/index.php` | La pantalla |
| `docs/claude_etapa-19.md` | Este documento |

### Modificados

| Archivo | Cambio |
|---|---|
| `app/models/UsuarioModel.php` | `actualizarDatos()` y `existeEmailDeOtro()` |
| `app/models/RecuperoModel.php` | `anularPendientes()` pasa de `private` a `public` |
| `app/core/Session.php` | `actualizarUsuario()` |
| `app/views/layouts/header.php` | Botón "Mi perfil" con las iniciales (marcado cuando `$seccion === 'perfil'`) |
| `public/js/registro.js` | Una línea: el formulario se toma con `password.closest('form')` |
| `public/css/style.css` | Bloque "Etapa 19", al final. Solo variables de color (funciona en modo oscuro). No se modificó nada de lo existente |

## 5. Pruebas realizadas (entorno aislado)

Base nueva desde `consumo_energetico.sql` y `datos_prueba.sql` (22 boletas, 3 con PDF), con 4 usuarios: con boletas, otro usuario, sin boletas y administrador (con una contraseña fuera de política). Inicio de sesión real, con CSRF.

### Funcionales: 66 de 66 correctas

| Grupo | Casos |
|---|---|
| Acceso | Sin sesión → login (GET y POST). `GET perfil/datos` y `GET perfil/password` → `perfil` |
| Pantalla | Nombre, actividad (22 boletas, 3 PDF = 14 %), botón del menú marcado, sin hash en el HTML |
| Datos | CSRF inválido · nombre vacío · apellido de más de 60 · cambio de nombre (se ve enseguida en el menú: "NM" → "NG") · F5 no repite el mensaje · HTML en el nombre se escapa |
| Correo | Mismo correo en mayúsculas no pide contraseña · sin contraseña · contraseña incorrecta · correo de otra cuenta · correo inválido · la contraseña no vuelve a la vista · cambio correcto: el correo viejo ya no ingresa y el nuevo sí |
| Contraseña | CSRF inválido · actual vacía · actual incorrecta · nueva que no cumple · igual a la actual · no coinciden · un error no borra lo escrito en Datos · cambio correcto: nuevo id de sesión, sesión abierta, la sesión vieja ya no sirve, la contraseña vieja no ingresa y la nueva sí |
| Recupero | Un enlace vigente antes del cambio queda anulado ("Enlace no válido") |
| Admin | Ve el aviso de contraseña fuera de política · su perfil no muestra actividad ni "Ver mis boletas" · cambia la contraseña y el aviso desaparece · Usuarios, Boletas y fichas siguen en 200 |
| Otros | Usuario sin boletas: "Todavía ninguna" · cuenta desactivada con la sesión abierta → login y sesión cerrada · dashboard, Mis boletas, Cargar boleta, ficha y registro siguen funcionando |

### Visual y consola

- Escritorio (1280 px) y celular (390 px), modo claro y oscuro, usuario y admin: **sin desborde** en `perfil`.
- Requisitos en vivo en el perfil y en el registro: funcionan. Los 4 campos de contraseña tienen el "ojito".
- Sin avisos de PHP ni errores de consola.
- **Corregido durante las pruebas:** en el celular el botón dentro del menú partía "Mis boletas" y "Cargar boleta" en dos renglones (el menú necesitaba 400 px y había 358). Ahora, en pantallas angostas, el avatar va arriba a la derecha, en la fila del logo.

### No probado

- La rama que atrapa el error 23000 (dos cuentas guardando el mismo correo en el mismo instante): es muy difícil de reproducir a mano. La validación previa cubre el caso normal.

## 6. Prueba pendiente en XAMPP

Con `prueba@consumo.test`:

1. Arriba a la derecha aparece el botón con tus iniciales. Tocarlo abre "Mi perfil".
2. Cambiar solo el nombre: aparece "Datos actualizados." y cambian las iniciales del botón.
3. Cambiar el correo sin escribir la contraseña: tiene que pedirla. Con la contraseña correcta: "Desde ahora ingresás con …". Salir y entrar con el correo nuevo. **Después, volver a poner el correo original.**
4. Cambiar la contraseña con la actual mal: error debajo del campo. Con todo bien: "Contraseña actualizada." y la sesión sigue abierta. Salir y entrar con la nueva.
5. Escribir una contraseña nueva corta: la lista de requisitos se pinta en vivo.
6. Entrar como administrador: también tiene el botón. Su perfil no muestra "Tu actividad".
7. Ver la pantalla en el celular (o F12 → vista de dispositivo) y en modo oscuro.
8. Probar que el registro y "¿Olvidaste tu contraseña?" sigan funcionando.

## 7. Cómo revertir

- **Código:** borrar los 3 archivos nuevos (`PerfilController.php`, la carpeta `app/views/perfil/` y este documento) y restaurar los 6 modificados desde `consumo-energetico_2026-11-19_antes-mi-perfil.zip`.
- **Base de datos:** esta etapa no la modificó.

## 8. Pendientes

- [ ] Prueba en XAMPP (§6).
- [ ] (Mejora futura) Sumar tarjetas al perfil: la cobertura del último año o "Mi hogar" (propuesta C; esta última requiere cambios en la base).
- [ ] (Mejora futura) Avisar por correo al correo anterior cuando se cambia el correo de la cuenta.
- [ ] Siguen abiertos los pendientes de la Etapa 17: desborde de "Cargar boleta" en el celular (68 px, previo a esta etapa) y `UsuarioController` sin id → error 500.
