# Etapa 17 — Mis boletas y ficha de cada boleta

**Fecha:** 2026-11-18
**Pedido:** crítica constructiva de un compañero: poder ver cada boleta por separado, con su consumo y su gasto, de forma fácil de leer.
**Estado:** aplicada. Probada en un entorno aislado (MariaDB 10.11 + PHP 8.3, copia del proyecto). **Falta la prueba en XAMPP** (ver §6).
**Prototipo aprobado:** lienzo "Prototipo — Mis boletas" (listado + ficha), con los estilos de `style.css`.

---

## 1. Qué se agregó

| URL | Pantalla |
|---|---|
| `consumo` | **Mis boletas**: todas las boletas del usuario agrupadas por mes, con filtros `?servicio=1..3&anio=AAAA` |
| `consumo/ver/{id}` | **Ficha** de una boleta |

Menú del usuario: Dashboard · **Mis boletas** · Cargar boleta · Salir.

**Listado.** Cada fila muestra el servicio, la fecha, el consumo con una barra (comparado con la boleta más alta de ese servicio) y lo pagado con el costo por unidad. Debajo de cada número, la variación contra la boleta anterior del mismo servicio. Cada mes muestra cuántas boletas tiene y el total pagado. Arriba: cantidad de boletas, total pagado y la boleta más cara.

**Ficha.** Consumiste, Pagaste y Costo por unidad, cada uno comparado con la boleta anterior. Además:
- "¿Por qué pagaste $ X más/menos?", que reparte la diferencia entre el consumo y el precio;
- "Esta boleta en tu historial": las últimas 6 boletas del servicio, el puesto en el último año y la comparación con el promedio;
- los datos cargados, "Ver PDF" si la boleta tiene comprobante, y enlaces a la boleta anterior y la siguiente del mismo servicio.

**Enlaces nuevos desde lo que ya existía:** en "Tus últimas cargas" (Cargar boleta), la fecha abre la ficha, y se agregó "Ver todas tus boletas". En el dashboard, en "Última boleta por servicio", el nombre del servicio abre la ficha.

## 2. Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Datos del listado | Una sola consulta con todas las boletas del usuario. Variaciones, barras y agrupación se calculan en PHP (`HistorialBoletas`). | Son pocas decenas por año. Es más fácil de explicar que un SQL con funciones de ventana, y la primera boleta de un año se compara con la última del año anterior. |
| Año por defecto | El más reciente **con boletas**. El filtro ofrece solo años con boletas. | En enero el listado no aparece vacío. |
| Filtros | Enlaces (GET), como en el dashboard. Un valor inválido se ignora. | Funcionan sin JavaScript y se pueden guardar o compartir. |
| "Último año" en la ficha | Los 12 meses previos a la **fecha de la boleta**, ella incluida. | Una boleta vieja muestra siempre lo mismo, se mire cuando se mire. |
| Puesto en el año | De mayor a menor consumo; los empates comparten el puesto. Si ninguna boleta es menor, se dice "la más baja". | Evita frases como "tu 6.ª más alta entre 6". |
| Promedio | Incluye a la propia boleta. Se muestra solo con 2 o más boletas en el año. | Es "tu promedio" tal como lo entiende el usuario. |
| Seguridad | La ficha devuelve **404** si el id no es un número, no existe o es de otro usuario (`buscarDelUsuario` filtra por `id_usuario` en el SQL). El admin sigue redirigido a `admin` por el constructor. | Mismo criterio que `actionComprobante()`: no revelar que existen boletas ajenas. |
| Gráfico | Barras en HTML y CSS, sin Chart.js ni JavaScript. | Son 6 barras: no justifican una librería. |
| Flechas | Se usa `Formato::variacion()` (↗ / ↘), igual que el dashboard, en lugar del ▲ / ▼ del prototipo. | Una sola forma de mostrar variaciones en toda la app. |
| Colores de variación | Rojo `#a63f3f` y verde `#2f6b45` para el texto chico. | Los de `.kpi-value-*` no tienen contraste suficiente en 12 px. |
| Base de datos | **Sin cambios.** | Todo sale de las columnas existentes. |

### La cuenta de "¿Por qué pagaste más?" (`Indicadores::diferenciaImporte`)

Con q = cantidad y c = costo por unidad (importe ÷ cantidad); el 0 es la boleta anterior y el 1 la actual:

- **Por consumo** = (q1 − q0) × c0 → lo que cambió el consumo, al precio anterior.
- **Por precio** = (c1 − c0) × q1 → lo que cambió el precio, sobre el consumo actual.
- Las dos partes suman exactamente la diferencia: (q1 − q0)·c0 + (c1 − c0)·q1 = c1·q1 − c0·q0 = importe actual − importe anterior.

La barra proporcional se muestra solo si ninguna parte va en contra de la diferencia. Si una suma y la otra resta (por ejemplo, consumiste menos pero el precio subió), se muestran las dos líneas sin barra, con una aclaración. El costo por unidad incluye cargos fijos e impuestos: es un promedio de la boleta, no la tarifa oficial (el mismo criterio que la Etapa 12).

## 3. Respaldo previo

`C:\respaldos\consumo-energetico\consumo-energetico_2026-11-18_antes-mis-boletas.zip` (558 archivos, verificado con `zip -T` y comparado con el proyecto). La base no cambia, así que no hizo falta un `.sql`.

En `C:\respaldos\consumo-energetico\_to_delete\` quedaron dos archivos auxiliares que se pueden borrar: un temporal de `zip` y la copia sin librerías que se usó para las pruebas.

## 4. Cambios por archivo

### Nuevos

| Archivo | Qué hace |
|---|---|
| `app/servicios/HistorialBoletas.php` | Funciones puras: `conComparaciones()`, `anios()`, `filtrar()`, `agruparPorMes()`, `resumen()`, `contexto()` (anterior, siguiente, gráfico, puesto y promedio del último año) y `mes()` ('2026-08-15' → 'agosto 2026') |
| `app/views/consumo/index.php` | Mis boletas |
| `app/views/consumo/ver.php` | Ficha de la boleta |
| `app/views/consumo/iconos.php` | Ícono SVG de cada servicio, compartido por las dos vistas |
| `docs/claude_etapa-17.md` | Este documento |

### Modificados

| Archivo | Cambio |
|---|---|
| `app/controllers/ConsumoController.php` | `actionIndex()` y `actionVer()`, más los privados `anioElegido()` y `servicioElegido()`. **Corrección:** `actionComprobante(string $id = '')`. Sin id, antes daba error 500 (`ArgumentCountError`, verificado); ahora da 404 |
| `app/models/ConsumoModel.php` | `listarDelUsuario()` y `buscarDelUsuario()`. `historialServicio()` y `ultimasPorServicio()` ahora también traen `id_consumo` (para enlazar a la ficha); sumar una columna no afecta al dashboard |
| `app/servicios/Indicadores.php` | `diferenciaImporte()` |
| `app/views/layouts/header.php` | "Mis boletas" en el menú del usuario |
| `app/views/consumo/cargar.php` | La fecha de cada fila abre la ficha, más "Ver todas tus boletas →" |
| `app/views/dashboard/general.php` | En "Última boleta por servicio", el servicio abre la ficha |
| `public/css/style.css` | Bloque "Etapa 17", al final. No se modificó nada de lo existente |

## 5. Pruebas realizadas (entorno aislado)

Base nueva desde `consumo_energetico.sql` y `datos_prueba.sql`, más una Luz del 15/08/2026 con PDF (465 kWh, $ 152.659,50) y una boleta de otro usuario. Se probó con inicio de sesión real (con CSRF) para 4 usuarios: usuario con boletas, otro usuario, usuario sin boletas y admin.

### Rutas: 23 casos correctos

| Caso | Resultado |
|---|---|
| `consumo`, `?anio=2025`, `?servicio=3`, `?servicio=1&anio=2025` | 200 |
| `?servicio=99`, `?anio=1999`, `?anio=abc&servicio=x`, `?servicio[]=1` | 200, se ignoran los valores inválidos |
| `consumo/ver/23` (propia), `consumo/ver/1` (la primera) | 200 |
| `consumo/ver/24` (**de otro usuario**), `/999`, `/abc`, `/-1`, sin id | 404 |
| `consumo/comprobante` sin id | 404 (antes, 500) |
| Admin en `consumo` y `consumo/ver/23` | Redirige a `admin` |
| Sin sesión | Redirige al login |
| `dashboard` y `consumo/cargar` | 200, sin cambios de comportamiento |

### Números contra SQL directo

| Dato | SQL | Pantalla |
|---|---|---|
| Boletas 2026 | 16 (Luz 4, Agua 4, Gas 8) | Igual |
| Total pagado 2026 | 801.080,76 | $ 801.080,76 |
| Más cara | 152.659,50 | $ 152.659,50 · Luz · agosto 2026 |
| Totales por mes (sep, ago, jul) | 37.300,45 · 176.309,87 · 54.815,45 | Iguales |
| Ficha Luz agosto: diferencia | 51.203,44 = 24.349,45 + 26.853,99 | Igual, 48 % / 52 % |
| Promedio del último año (luz) | 426,67 kWh (6 boletas, máx. 560) | "427 kWh", "9 % por encima", "2.ª más alta" |

### Funciones de cálculo con los números del prototipo

`diferenciaImporte(152659,50; 465; 101456,25; 375)` → 51.203,25 = **24.349,50 + 26.853,75**, 48 % / 52 %. También: signos opuestos (sin barra, suma correcta), cantidad 0 (null), boletas iguales (total 0, sin barra) y una parte exactamente 0 (se corrigió un "-0 %").

### Casos borde de la ficha

| Caso | Resultado |
|---|---|
| Primera boleta del servicio | "Es tu primera boleta de luz." Sin comparación ni gráfico; "‹ Anterior" deshabilitado |
| Agua: consumió menos y pagó más | "¿Por qué pagaste $ 890,00 más?": por consumir menos $ 875,40, por el aumento del m³ $ 1.765,40, sin barra y con la aclaración |
| Gas con el mismo consumo que la anterior | "Consumo igual $ 0,00 · 0 %" y "Por el aumento del m³ $ 324,00 · 100 %" |
| La más baja del año (con empate) | "Tu boleta de gas más baja del último año" |
| Filtro sin resultados / usuario sin boletas | Mensajes "No hay boletas con ese filtro" / "Todavía no cargaste boletas" |

### HTML, visual y consola

- Etiquetas HTML balanceadas en 7 páginas.
- Sin avisos de PHP y sin errores de consola.
- Escritorio (1280 px) y celular (390 px): Mis boletas y la ficha **sin desborde horizontal**.
- Error encontrado y corregido: la ficha decía "Tu 6.ª boleta de agua más alta… entre 6". Ahora dice "tu boleta más baja".

## 6. Prueba pendiente en XAMPP

Con `prueba@consumo.test`:

1. Menú: aparece "Mis boletas".
2. Mis boletas: se ve el año más reciente; probar los filtros de servicio y de año.
3. Tocar una boleta: se abre su ficha. Probar "‹ anterior" y "siguiente ›", y "Ver PDF" en una que tenga comprobante.
4. En la barra de direcciones, cambiar el número de `consumo/ver/N` por el de una boleta de otro usuario: tiene que aparecer "Página no encontrada".
5. Dashboard → "Última boleta por servicio" → tocar un servicio: abre su ficha.
6. Cargar boleta → tocar una fecha de "Tus últimas cargas": abre su ficha.
7. Entrar como administrador e ir a `consumo`: tiene que volver a su Inicio.
8. Ver las dos pantallas en el celular (o con F12 → vista de dispositivo).

## 7. Cómo revertir

- **Código:** borrar los 4 archivos nuevos (`HistorialBoletas.php`, `consumo/index.php`, `consumo/ver.php`, `consumo/iconos.php`) y restaurar los 7 modificados desde `consumo-energetico_2026-11-18_antes-mis-boletas.zip`.
- **Base de datos:** esta etapa no la modificó.

## 8. Pendientes

- [ ] Prueba en XAMPP (§6).
- [ ] **Desborde horizontal en el celular, previo a esta etapa:** "Cargar boleta" (~72 px) y el dashboard (~117 px). Lo causan las tablas dentro de las grillas de dos columnas: un hijo de grid no se achica por debajo de su contenido. Solución probable: `min-width: 0` en los hijos de `.dashboard-grid` y `.grid-2`. Falta aprobarla.
- [ ] `UsuarioController`: `actionActividad`, `actionEditar` y `actionEstado` tienen el mismo problema que tenía `actionComprobante` (sin id → error 500). Se corrige con `string $id = ''`.
- [ ] Renumeración propuesta: "Control de información" pasa a ser la Etapa 18 y "Estadísticas ampliadas", la 19.
- [ ] (Futuro) Corregir o borrar una boleta desde su ficha.
- [ ] (Mejora) Unificar los colores por servicio, hoy repetidos en `dashboard.js`, `admin.js` y los bloques de las Etapas 15 y 17 de `style.css`.
