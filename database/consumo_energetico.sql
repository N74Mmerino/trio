CREATE DATABASE IF NOT EXISTS consumo_energetico
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE consumo_energetico;

CREATE TABLE roles (
  id_rol  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre  VARCHAR(30) NOT NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id_usuario      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_rol          TINYINT UNSIGNED NOT NULL,
  nombre          VARCHAR(60)  NOT NULL,
  apellido        VARCHAR(60)  NOT NULL,
  email           VARCHAR(120) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  fecha_registro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_email (email),
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol)
    REFERENCES roles (id_rol) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE servicios (
  id_servicio  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(30) NOT NULL,
  unidad       VARCHAR(10) NOT NULL,
  PRIMARY KEY (id_servicio),
  UNIQUE KEY uq_servicios_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE consumos (
  id_consumo   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario   INT UNSIGNED NOT NULL,
  id_servicio  TINYINT UNSIGNED NOT NULL,
  fecha        DATE          NOT NULL,
  cantidad     DECIMAL(10,2) NOT NULL,
  importe      DECIMAL(12,2) NOT NULL,
  archivo      VARCHAR(64)   NULL,
  fecha_carga  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_consumo),
  KEY idx_consumos_usuario_fecha (id_usuario, fecha),
  UNIQUE KEY uq_consumos_usuario_servicio_fecha (id_usuario, id_servicio, fecha),
  CONSTRAINT fk_consumos_usuarios FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_consumos_servicios FOREIGN KEY (id_servicio)
    REFERENCES servicios (id_servicio) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_consumos_cantidad CHECK (cantidad >= 0),
  CONSTRAINT chk_consumos_importe  CHECK (importe >= 0)
) ENGINE=InnoDB;

-- Etapa 16: enlaces de recupero de contraseña (solo se guarda el hash del enlace).
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

INSERT INTO roles (id_rol, nombre) VALUES
  (1, 'administrador'),
  (2, 'usuario');

INSERT INTO servicios (id_servicio, nombre, unidad) VALUES
  (1, 'Luz',  'kWh'),
  (2, 'Agua', 'm³'),
  (3, 'Gas',  'm³');
