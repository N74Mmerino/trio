# Etapa 14 — Inicio del administrador

**Fecha:** 2026-11-11
**Objetivo:** darle al administrador una pantalla de inicio propia con el resumen de la plataforma, en lugar de su dashboard de consumo, que siempre estaba vacío.
**Estado:** aplicada y probada en XAMPP (PHP 8.2.12).

---

## 1. Decisiones aprobadas

- **Roles separados:** el administrador gestiona la plataforma; no carga ni ve consumos propios. Su menú es **Inicio · Usuarios · Salir**. Si entra a `dashboard` o `consumo/...`, se lo redirige a `admin`.
- **El usuario común no ve cambios:** su menú sigue siendo Dashboard · Cargar boleta · Salir.
- **La pantalla de Usuarios no cambia:** el Inicio solo agrega accesos hacia ella.
- **Privacidad:** el admin ve quién cargó qué servicio y cuándo, **nunca importes ni cantidades**.
- **La estructura de la base no cambia:** todo se calcula con columnas existentes (`fecha_registro`, `fecha_carga`, `archivo`, `activo`).
- **Fuera de esta etapa:**
  - la pestaña "Boletas" de la maqueta;
  - el filtro en la lista de Usuarios cuando se llega desde "Ver";
  - la pantalla para cambiar la contraseña.

## 2. Respaldo previo

En `C:\respaldos\consumo-energetico\`:

| Archivo | Contenido |
|---|---|
| `consumo-energetico_2026-11-11_antes-admin.zip` | Proyecto completo (463 archivos, verificado). |
| `consumo_energetico_2026-11-11_antes-admin.sql` | `mysqldump` completo, termina en "Dump completed". |

## 3. Cambios por archivo

### Nuevos

| Archivo | Qué hace |
|---|---|
| `app/controllers/AdminController.php` | Ruta `/admin`, protegida con `Session::requiereAdmin()`: un usuario común recibe 404 y sin sesión se va al login. Arma los indicadores, los avisos de "Para revisar", los últimos registros, la actividad y los datos del gráfico (mes actual y 5 anteriores). |
| `app/views/admin/index.php` | La pantalla. Reutiliza las clases existentes (`page-heading`, `kpi-grid`, `kpi-card`, `grid-2`, `chart-wrap`, `badge-*`) para verse igual que el resto de la app. Cada lista tiene su mensaje para cuando está vacía. |
| `public/js/admin.js` | Dibuja el gráfico de barras apiladas con Chart.js, con los mismos colores por servicio que `dashboard.js`. Lee los datos del bloque JSON `#datos-admin`. Si no hay boletas, la vista no carga ni Chart.js ni este archivo. |

### Modificados

| Archivo | Cambio | Por qué |
|---|---|---|
| `app/core/Session.php` | `rutaInicio()`: devuelve `'admin'` o `'dashboard'` según el rol. | La regla "a dónde va cada rol" queda escrita en un solo lugar; antes `'dashboard'` estaba repetido. |
| `app/core/Formato.php` | `haceTiempo()`: "hoy", "ayer", "hace N días", "hace N semanas" y, desde las 8 semanas, la fecha. | Para los últimos registros y la actividad. |
| `app/models/UsuarioModel.php` | `resumen()`, `ultimosRegistros()` y `contarActivosSinBoletas()`. | Datos de usuarios para el Inicio. `resumen()` usa `:mes` y `:mes2` porque con `ATTR_EMULATE_PREPARES = false` un parámetro con nombre no se puede repetir. |
| `app/models/ConsumoModel.php` | `resumenPlataforma()`, `boletasMensualesPlataforma()` y `actividadReciente()`. | Ninguna trae `importe` ni `cantidad`. `boletasMensualesPlataforma()` devuelve el mismo formato que `gastoMensual()`, así que se reutiliza `Indicadores::seriesMensuales()` sin tocarla. |
| `app/controllers/AuthController.php` | El login redirige a `Session::rutaInicio()`. Además evalúa la contraseña con `Validador::passwordSegura()` y guarda en la sesión **solo** `true/false` (`password_fuera_de_politica`). | La base guarda solo el hash, así que el único momento para saber si la contraseña cumple la política es el login. La contraseña no se guarda en ningún lado. |
| `app/controllers/HomeController.php` | `/` redirige a `Session::rutaInicio()`. | Cada rol llega a su inicio. |
| `app/controllers/DashboardController.php` y `ConsumoController.php` | Si el usuario es administrador, se lo redirige a `admin`. | Decisión de roles separados. |
| `app/views/layouts/header.php` | Menú según el rol; el logo lleva a `rutaInicio()`. | Admin: Inicio · Usuarios. Usuario: Dashboard · Cargar boleta. |
| `public/css/style.css` | Bloque "Etapa 14 — Inicio del administrador": tarjeta con enlace, `admin-grid` y listas (`row-*`). Por debajo de 800 px queda en una columna. | Estilos de las listas y de la tarjeta clicable. |

### Qué fecha usa cada indicador

| Indicador | Fecha |
|---|---|
| Nuevos este mes / el mes pasado | `usuarios.fecha_registro` |
| Boletas cargadas este mes | `consumos.fecha_carga` |
| Gráfico "Boletas por mes" | `consumos.fecha`, la fecha de la boleta |
| Actividad reciente | Ordenada por `fecha_carga`; muestra también la fecha de la boleta |

El gráfico usa la fecha de la boleta porque los datos de prueba se importaron todos el mismo día. Si contara por fecha de carga, se vería una sola barra.

## 4. Pruebas realizadas

### Valores de referencia (SQL directo) contra la pantalla

| Indicador | SQL | Pantalla |
|---|---|---|
| Usuarios activos | 3 de 4, 1 inactivo | "3 · de 4 · 1 inactivo" |
| Nuevos este mes / el mes pasado | 0 / 0 | "0 · 0 el mes pasado" |
| Boletas cargadas / este mes | 26 / 0 | "26 · 0 cargadas este mes" |
| Leídas desde PDF | 3 de 26 | "3 · 12 % del total" |
| Gráfico jun–nov 2026 | Luz 2,2,0,0,0,0 · Agua 1,0,1,0,0,0 · Gas 1,1,1,1,0,0 | Idéntico (leído desde Chart.js) |
| Para revisar | Contraseña de admin fuera de política; 1 inactivo; 0 activos sin boletas | Los dos avisos que corresponden |
| Últimos registros y actividad | Los mismos 4 y 5 registros, en el mismo orden | Iguales, sin importes |

### Administrador

| Prueba | Resultado |
|---|---|
| Login → `/admin`; menú Inicio* · Usuarios · Salir; el logo lleva a `/admin` | OK |
| `/`, `/dashboard`, `/consumo/cargar` y `/auth/login` → `/admin` | OK |
| Enlaces: tarjeta → `/usuario`; "Ver" → `/usuario`; nombres → `/usuario/editar/N`; "Nuevo usuario" → `/auth/registro` | OK |
| Clic en la tarjeta → Usuarios igual que antes (4 filas, "Usuarios" marcado en el menú) | OK |
| Sin errores en la consola del navegador | OK |
| Escritorio (1280 px): 4 indicadores en fila, gráfico + avisos y las 2 listas en 2 columnas | OK |
| Celular (375 px): una columna, sin desplazamiento horizontal | OK |
| Sin sesión: `/admin`, `/` y `/dashboard` → login | OK |

### Estados vacíos (vista renderizada por consola con datos simulados, sin tocar la base)

| Caso | Resultado |
|---|---|
| Sin boletas en 6 meses: "Todavía no hay boletas…" y Chart.js no se carga | OK |
| Sin avisos: "Todo en orden." | OK |
| Sin actividad: "Todavía nadie cargó boletas." | OK |
| PDF sin boletas: "Sin boletas todavía" | OK |
| Sin avisos ni errores de PHP | OK |

### `Formato::haceTiempo()` (consola)

Ocho casos correctos: hoy, ayer, hace 3 días, hace 13 días, hace 2 semanas, hace 8 semanas, la fecha a partir de las 8 semanas, y una fecha futura que muestra "hoy".

### Usuario común (con un usuario temporal, autorizado)

| Prueba | Resultado |
|---|---|
| Registro de `prueba.etapa14@consumo.test` y login → `/dashboard` | OK |
| Menú Dashboard* · Cargar boleta · Salir; el logo lleva a `/dashboard`; dashboard vacío como corresponde | OK |
| `/admin` → **404**; `/usuario` → **404** | OK |
| `/consumo/cargar` funciona igual (formulario manual y subida de PDF) | OK |
| `/` y `/auth/login` → `/dashboard` | OK |
| Limpieza: se verificó que fuera 1 fila, rol usuario y 0 consumos, y se borró; la base volvió a 4 usuarios | OK |

Nota: el `AUTO_INCREMENT` de `usuarios` quedó en 8 por el usuario temporal (id 7). Es normal en MySQL y no afecta nada.

## 5. Cómo revertir

- **Código:** borrar los 3 archivos nuevos y restaurar desde `consumo-energetico_2026-11-11_antes-admin.zip` los 10 modificados (`Session.php`, `Formato.php`, `UsuarioModel.php`, `ConsumoModel.php`, `AuthController.php`, `HomeController.php`, `DashboardController.php`, `ConsumoController.php`, `header.php`, `style.css`).
- **Base de datos:** esta etapa no cambió la estructura ni dejó datos.

## 6. Pendientes

- [ ] Cambiar la contraseña de `admin@consumo.test`. Ahora el propio Inicio lo avisa en "Para revisar".
- [ ] (Futuro) Pantalla "Cambiar contraseña"; con ella, el aviso podría tener un enlace.
- [ ] (Futuro) Filtro en Usuarios (`?estado=inactivo`, sin boletas) para que los "Ver" lleven directo.
- [ ] (Futuro) Pestaña "Boletas" para el administrador.
- [ ] (Futuro, validaciones) límite de intentos de login; nombre y apellido solo con letras; importe mayor que 0; fecha mínima; máximo 2 decimales.
