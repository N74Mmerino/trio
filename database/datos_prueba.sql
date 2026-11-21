-- ------------------------------------------------------------------
-- Datos de prueba para el dashboard (Etapa 12)
--
-- Boletas FICTICIAS de oct/2025 a sep/2026 para prueba@consumo.test:
--   - Luz bimestral (más consumo en verano por el aire acondicionado)
--   - Agua bimestral
--   - Gas mensual (más consumo en invierno por la calefacción)
-- Los importes suben de a poco para que se vea el costo por unidad.
--
-- No incluye las boletas que ya cargaste (Luz 31/07/2026 y Gas 12/08/2026).
-- La Luz 01/06/2026 usa los datos reales de la boleta 3/26: si ya la subiste
-- en PDF, ON DUPLICATE KEY la deja como está (no la pisa ni da error).
--
-- Se importa desde phpMyAdmin con la base consumo_energetico seleccionada.
-- Para borrar estos datos: ver el DELETE comentado al final.
-- ------------------------------------------------------------------

USE consumo_energetico;

-- id del usuario de prueba (si el email no existe, @id queda NULL y el INSERT falla sin cargar nada)
SET @id := (SELECT id_usuario FROM usuarios WHERE email = 'prueba@consumo.test');

INSERT INTO consumos (id_usuario, id_servicio, fecha, cantidad, importe) VALUES
  -- Luz (1) · kWh
  (@id, 1, '2025-10-01', 310,  60134.56),
  (@id, 1, '2025-12-01', 420,  87334.56),
  (@id, 1, '2026-02-02', 560, 126674.56),
  (@id, 1, '2026-04-01', 430, 107014.56),
  (@id, 1, '2026-06-01', 375, 101456.06),
  -- Agua (2) · m³
  (@id, 2, '2025-10-15', 28, 18640.37),
  (@id, 2, '2025-12-15', 34, 22940.37),
  (@id, 2, '2026-02-16', 38, 26620.37),
  (@id, 2, '2026-04-15', 31, 24325.37),
  (@id, 2, '2026-06-15', 26, 22760.37),
  (@id, 2, '2026-08-14', 25, 23650.37),
  -- Gas (3) · m³
  (@id, 3, '2025-10-12', 30, 14500.45),
  (@id, 3, '2025-11-11', 22, 11954.45),
  (@id, 3, '2025-12-11', 15,  9550.45),
  (@id, 3, '2026-01-12', 12,  8632.45),
  (@id, 3, '2026-02-11', 12,  8956.45),
  (@id, 3, '2026-03-11', 16, 11140.45),
  (@id, 3, '2026-04-13', 28, 17316.45),
  (@id, 3, '2026-05-11', 55, 31385.45),
  (@id, 3, '2026-06-11', 82, 46372.45),
  (@id, 3, '2026-07-13', 95, 54815.45),
  (@id, 3, '2026-09-11', 60, 37300.45)
-- Si ya existe una boleta del mismo usuario, servicio y fecha (clave UNIQUE), no se modifica.
ON DUPLICATE KEY UPDATE id_consumo = id_consumo;

-- Para quitar los datos de prueba (deja las boletas cargadas con PDF):
-- DELETE FROM consumos
-- WHERE id_usuario = (SELECT id_usuario FROM usuarios WHERE email = 'prueba@consumo.test')
--   AND archivo IS NULL
--   AND fecha BETWEEN '2025-10-01' AND '2026-09-30'
--   AND NOT (id_servicio = 1 AND fecha = '2026-07-31')
--   AND NOT (id_servicio = 3 AND fecha = '2026-08-12');
