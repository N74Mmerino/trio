# Etapa 15 — Boletas, ficha de actividad y filtros en Usuarios

**Fecha:** 2026-11-11
**Objetivo:** que el administrador pueda supervisar la actividad de la plataforma (quién cargó qué boleta y cuándo) sin acceder a datos privados de los usuarios.
**Estado:** aplicada y probada en XAMPP (PHP 8.2.12).

---

## 1. Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| PDF originales | El admin **no** los abre. Ve solo los datos de cada carga. | Un PDF contiene nombre, domicilio, número de cliente e importes. Ley 25.326: principios de finalidad y proporcionalidad. |
| Período facturado | Se muestra el **mes de la boleta**, calculado a partir de `consumos.fecha`. | La base no guarda un período desde/hasta; agregarlo sería una etapa propia (base, lector de PDF y formulario). |
| Edición por el admin | Solo **nombre y apellido**. El email queda visible pero bloqueado. | El email es la credencial de acceso. **Consecuencia:** hasta que exista "Mi perfil", nadie puede cambiar un email. |
| Filtros de Boletas | Se aplican con el botón "Aplicar filtros". | Que la página cambie sola al elegir una opción es una mala práctica de accesibilidad (WCAG 3.2.2). Además funciona sin JavaScript. |
| Cobertura de la ficha | Últimos 12 meses (dic 2025 – nov 2026). | El mismo "último año" que usa el dashboard. |
| Columnas de Usuarios | Usuario (nombre + email), Rol, Estado, Boletas, Última carga, Acciones. | Según la maqueta aprobada. El ID y la fecha de registro salen de la tabla; la fecha de registro pasa a la ficha. |
| Conteos "sin boletas" | El filtro de Usuarios cuenta a todos los usuarios comunes sin cargas, activos o inactivos (hoy: 1). El aviso del Inicio cuenta solo los activos (hoy: 0). | El aviso del Inicio muestra solo lo que pide una acción. |

**Privacidad:** ninguna consulta nueva trae `importe`, `cantidad`, el nombre del archivo ni `password_hash`.

## 2. Respaldo previo

En `C:\respaldos\consumo-energetico\`: `consumo-energetico_2026-11-11_antes-boletas.zip` (467 archivos, verificado) y `consumo_energetico_2026-11-11_antes-boletas.sql` (termina en "Dump completed").

## 3. Cambios por archivo

### Nuevos

| Archivo | Qué hace |
|---|---|
| `app/controllers/BoletaController.php` | Ruta `/boleta`, solo para el admin. Lee los filtros de la URL (`usuario`, `servicio`, `origen`, `periodo`, `orden`, `pagina`) y los valida contra listas de valores permitidos o como números. Cualquier otro valor se ignora. Pagina de a 10 y lleva un pedido como `?pagina=999` a la última página. |
| `app/views/boleta/index.php` | Aviso de privacidad, formulario de filtros por GET, chips de resumen, tabla (Usuario, Servicio, Mes de la boleta, Cargada, Origen, ficha) y paginación que conserva los filtros. Tocar un nombre filtra el listado por ese usuario. |
| `app/views/usuario/actividad.php` | Ficha: datos de la cuenta, botones (Escribirle, Ver sus boletas, Editar nombre), 4 indicadores y la cobertura mes a mes. La cobertura es una tabla real (servicios × meses), con texto para lectores de pantalla en cada celda. |

### Modificados

| Archivo | Cambio |
|---|---|
| `app/core/Controller.php` | `parametro()`: lee un valor de `$_GET`, solo si es texto. Es el equivalente de `campo()` para la URL. |
| `app/servicios/Indicadores.php` | `mesDeFecha()`: '2026-07-31' → 'Jul 2026'. Usa la misma lista de meses de la clase, así los nombres siguen en un solo lugar. |
| `app/models/ConsumoModel.php` | `listarParaAdmin()`, `resumenParaAdmin()`, `porServicioParaAdmin()`, `actividadDeUsuario()`, `coberturaDeUsuario()` y el privado `filtroAdmin()`, que arma un `WHERE` compartido para que el total, los chips y las filas cuenten siempre lo mismo. |
| `app/models/UsuarioModel.php` | `listarParaAdmin()` (filtro + búsqueda; el `LIKE` escapa `%` y `_`), `contarPorFiltro()`, `buscarParaFicha()`, `listarUsuariosComunes()` y `actualizarNombre()`. `ultimosRegistros()` suma `id_rol`. **Eliminados:** `listar()`, `actualizar()` y `existeEmailParaOtroUsuario()`, que quedaron sin uso; están en el respaldo para cuando se haga "Mi perfil". |
| `app/controllers/UsuarioController.php` | `actionIndex()` con filtro y búsqueda; `actionActividad()` nueva (404 para el admin, para ids inexistentes y para texto); la edición solo lee y guarda nombre y apellido. |
| `app/controllers/AdminController.php` | Los "Ver" del Inicio llevan a `usuario?filtro=inactivos` y `usuario?filtro=sin-boletas`. |
| `app/views/admin/index.php` | Las tarjetas "Boletas cargadas" y "Leídas desde PDF" pasan a ser enlaces (a `boleta` y `boleta?origen=pdf`). "Ver todas las boletas" en la actividad. En "Últimos registros", un usuario común lleva a su ficha y el admin a Editar. |
| `app/views/usuario/index.php` | Chips de filtro con cantidades, búsqueda, columnas nuevas, ícono "Ver actividad". El ícono de Desactivar pasa de **tacho de basura** (sugería "borrar") a una persona tachada. |
| `app/views/usuario/editar.php` | El email se muestra `readonly` y **sin `name`**, con una explicación. El controlador tampoco lo lee. |
| `app/views/layouts/header.php` | El menú del admin suma **Boletas**. |
| `public/css/style.css` | Bloque "Etapa 15": filtros, chips, puntos de color por servicio, paginación, ficha, indicadores y cobertura. |

## 4. Pruebas realizadas

### Listado de Boletas contra SQL directo: 16 casos correctos

| URL | Esperado (SQL) | Pantalla |
|---|---|---|
| sin filtros | 26 · 3 PDF · 2 usuarios · 8 Luz / 6 Agua / 12 Gas | Igual · 3 páginas (10 + 10 + 6) |
| `?usuario=1` / `?usuario=3` | 24 (1 PDF) / 2 (2 PDF) | Igual |
| `?servicio=3` | 12 | Igual |
| `?origen=pdf` / `?origen=manual` | 3 / 23 | Igual |
| `?periodo=6` / `?periodo=12` | 10 (4 Luz, 2 Agua, 4 Gas) / 22 | Igual |
| `?usuario=1&servicio=1&origen=pdf` | 1 (Luz 01/06/2026) | Igual |
| `?servicio=1&periodo=6` | 4 (3 PDF) | Igual |
| `?orden=boleta` | Primera: Gas 11/09/2026 | Igual |
| `?pagina=2` / `?pagina=3` / `?pagina=999` | 11–20 / 21–26 / la última | Igual |
| `?pagina=abc&orden=x&usuario=-1&origen=hack&periodo=7` | Se ignoran los valores | Listado completo, sin errores |
| `?usuario=999` | 0 | "No hay boletas con esos filtros." |

También se comprobó que:
- la paginación conserva los filtros (`?servicio=3&orden=boleta&pagina=2`);
- tocar un nombre agrega `usuario=N` sin perder los otros filtros;
- "Limpiar filtros" aparece solo si hay algún filtro aplicado.

### Ficha de actividad

| Usuario | Resultado |
|---|---|
| Usuario Prueba | 24 boletas, 1 PDF (4 %), 21/09 – 23/09/2026. Cobertura: Luz `X·X·X·PX····`, Agua `X·X·X·X·X···`, Gas `XXXXXXXXXX··`, igual a los datos (P = PDF) |
| Nicolas Merino | 2 boletas, 2 PDF (100 %). Luz junio y julio, las dos con borde de PDF |
| Cuenta Inactiva | 0 boletas: "Cuenta inactiva y sin boletas cargadas." |
| `/actividad/4` (admin), `/999`, `/abc` | 404 |

### Usuarios

| Caso | Resultado |
|---|---|
| Chips: Todos 4 · Activos 3 · Inactivos 1 · Sin boletas 1, con el chip activo marcado | OK |
| Cada filtro muestra las filas que corresponden; el pie dice "Mostrando N de 4" | OK |
| Búsqueda: "prueba" → 1, "@consumo" → 3, "%" y "_" → 0 (se buscan como texto literal) | OK |
| Filtro + búsqueda (`activos` + "nicolas") → 1; la búsqueda conserva el filtro | OK |
| `?filtro=hack` → se ignora | OK |
| Acciones: el ícono "Ver actividad" solo en usuarios comunes; el admin no puede desactivarse a sí mismo | OK |

### Edición limitada (sobre Usuario Prueba, revertida)

1. El campo de email no tiene `name`, es `readonly` y tiene su explicación.
2. Se envió un POST a mano con nombre "UsuarioEditado" y `email=forzado@hack.test`. El nombre cambió; el email **no**. Ningún usuario quedó con el email forzado.
3. Se volvió a poner "Usuario". La base quedó igual que antes.

### Inicio

- Tarjetas: `usuario`, `boleta` y `boleta?origen=pdf`.
- "Ver" lleva a `usuario?filtro=inactivos`.
- "Ver todas las boletas" lleva a `boleta`.
- Los registros de usuarios comunes llevan a su ficha; el del admin, a Editar.
- Menú: Inicio · Usuarios · Boletas · Salir.

### Usuario común (temporal `prueba.etapa15@consumo.test`, borrado al final)

- Menú: Dashboard · Cargar boleta · Salir.
- `boleta`, `boleta?usuario=1`, `usuario`, `usuario/actividad/1`, `usuario/editar/1` y `admin`: **404**.
- `dashboard` y `consumo/cargar`: 200.
- Limpieza: se verificó 1 fila, rol usuario y 0 consumos, y se borró. La base volvió a los 4 usuarios originales.

### Dashboard del usuario común (renderizado por consola, solo lectura)

Las tres vistas (general, 6 meses y Gas 12 meses) se renderizan con sus indicadores y gráficos, sin avisos de PHP. El cambio en `Indicadores` fue solo agregar un método.

### Errores encontrados durante las pruebas y corregidos

| Error | Corrección |
|---|---|
| El chip decía "1 leídas desde PDF" | Singular y plural: "1 leída" / "3 leídas". |
| La pestaña del navegador decía "Actividad de Usuario" (solo el nombre) | Ahora usa nombre y apellido: "Actividad de Usuario Prueba". |
| Las celdas de la cobertura salían todas grises (solo se veía el borde de PDF) | El gris de `.coverage-cell` está más abajo en el CSS que el color y tiene la misma prioridad. Los colores pasaron a `.coverage-cell.service-bg-N` (dos clases). |
| En el celular, Boletas medía 759 px y la ficha 535 px (desborde horizontal) | Los textos `.visually-hidden` (`position: absolute`) dentro de las tablas escapaban del contenedor con scroll. Se agregó `position: relative` a `.table-responsive` y `.coverage-wrap`. |

Pequeño cambio respecto de la propuesta: en "Primera carga", la nota debajo de la fecha muestra "hace N semanas" (igual que "Última carga") en lugar de quedar vacía.

### Visual y consola

- Escritorio (1100 px): filtros en 5 columnas y tablas completas.
- Celular (375 px): las 7 pantallas de admin sin desborde; filtros e indicadores en 2 columnas.
- Consola: los únicos errores son los 404 provocados a propósito en las pruebas. Todos los CSS y JS cargan con 200.

## 5. Cómo revertir

- **Código:** borrar los 3 archivos nuevos y restaurar desde `consumo-energetico_2026-11-11_antes-boletas.zip` los 11 modificados.
- **Base de datos:** esta etapa no cambió la estructura ni dejó datos.

## 6. Pendientes

- [ ] Cambiar la contraseña de `admin@consumo.test` (el Inicio lo avisa).
- [ ] **"Mi perfil"** para el usuario: cambiar su propio email (hoy nadie puede) y su contraseña.
- [ ] Etapa 16: Control de información (posibles duplicados, valores atípicos comparando con la boleta anterior, integridad de comprobantes, temporales viejos).
- [ ] Etapa 17: estadísticas ampliadas y seguimiento de actividad (opcional: último ingreso).
- [ ] (Mejora) Unificar los colores por servicio, hoy repetidos en `dashboard.js`, `admin.js` y `style.css`.
- [ ] (Validaciones) límite de intentos de login; nombre y apellido solo con letras; importe mayor que 0; fecha mínima; máximo 2 decimales.
