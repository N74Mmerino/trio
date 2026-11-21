-- Etapa 16: recupero de contraseña por correo.
-- Se aplica sobre una base existente. En una instalación nueva ya viene en consumo_energetico.sql.

USE consumo_energetico;

-- Cada fila es un enlace de recupero pedido por un usuario.
-- Del enlace solo se guarda el hash SHA-256: quien lea la base no puede armar un enlace válido.
-- anulado_en: cuándo dejó de servir (se usó, o el usuario pidió otro). NULL = todavía sirve si no venció.
CREATE TABLE recuperos_password (
  id_recupero   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario    INT UNSIGNED NOT NULL,
  token_hash    CHAR(64)     NOT NULL,
  fecha_pedido  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira        DATETIME     NOT NULL,
  anulado_en    DATETIME     NULL,
  PRIMARY KEY (id_recupero),
  UNIQUE KEY uq_recuperos_token (token_hash),
  KEY idx_recuperos_usuario_fecha (id_usuario, fecha_pedido),
  CONSTRAINT fk_recuperos_usuarios FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
