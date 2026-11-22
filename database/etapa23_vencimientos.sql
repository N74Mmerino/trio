-- Etapa 23: vencimientos de las boletas.
-- Una fila por cuota: EPE (luz) y ASSA (agua) se pagan en 2 cuotas; Litoral Gas, en 1.
-- Ejecutar UNA vez sobre la base existente (antes, exportar la base desde phpMyAdmin).

USE consumo_energetico;

CREATE TABLE vencimientos (
  id_vencimiento     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  id_consumo         INT UNSIGNED     NOT NULL,
  numero_cuota       TINYINT UNSIGNED NOT NULL,           -- 1 o 2
  fecha_vencimiento  DATE             NOT NULL,           -- primer vencimiento (sin recargo)
  importe            DECIMAL(12,2)    NOT NULL,           -- lo que dice el talón
  pagado_en          DATE             NULL,               -- NULL = sin informar (se usa en una etapa futura)
  PRIMARY KEY (id_vencimiento),
  UNIQUE KEY uq_vencimientos_consumo_cuota (id_consumo, numero_cuota),
  KEY idx_vencimientos_fecha (fecha_vencimiento),
  CONSTRAINT fk_vencimientos_consumos FOREIGN KEY (id_consumo)
    REFERENCES consumos (id_consumo) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_vencimientos_cuota   CHECK (numero_cuota BETWEEN 1 AND 2),
  CONSTRAINT chk_vencimientos_importe CHECK (importe >= 0)
) ENGINE=InnoDB;
