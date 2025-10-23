SET time_zone = "-03:00";

DROP DATABASE IF EXISTS ard;

CREATE DATABASE ard;
USE ard;

CREATE TABLE roles (
  id          INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  nombre      VARCHAR(50) NOT NULL UNIQUE,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id                CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  nombre            VARCHAR(100) NOT NULL,
  correo            VARCHAR(190) NOT NULL,
  rol_id            INT NOT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_users_correo UNIQUE (correo),
  CONSTRAINT fk_users_role FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE user_credentials (
  user_id             CHAR(36) NOT NULL PRIMARY KEY,
  password_hash       VARCHAR(255) NOT NULL,
  password_updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_credentials_user
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE recovery_tokens (
  user_id     CHAR(36) NOT NULL,
  token       VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at  TIMESTAMP NOT NULL,
  CONSTRAINT pk_recovery_tokens PRIMARY KEY (user_id, token),
  CONSTRAINT fk_recovery_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE sellers (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  nombre      VARCHAR(100) NOT NULL,
  is_owner    BOOLEAN NOT NULL DEFAULT FALSE,
  contacto    VARCHAR(100) NULL,
  telefono    VARCHAR(20) NULL,
  comision    DECIMAL(5,2) NOT NULL,
  CHECK (comision  <= 100.00),
  activo      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE productos (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  nombre      VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  precio      DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
  activo      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE categorias (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  nombre      VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE stock (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  seller_id   CHAR(36) NOT NULL,
  producto_id CHAR(36) NOT NULL,
  categoria_id CHAR(36) NOT NULL,
  cantidad    INT NOT NULL DEFAULT 0 CHECK (cantidad >= 0),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_stocks_seller
    FOREIGN KEY (seller_id) REFERENCES sellers(id),
  CONSTRAINT fk_stocks_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE ventas (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  seller_id   CHAR(36) NOT NULL,
  comision    DECIMAL(5,2) NOT NULL,
  total       DECIMAL(10,2) NOT NULL CHECK (total >= 0),
  activo      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ventas_seller
    FOREIGN KEY (seller_id) REFERENCES sellers(id)
);
CREATE TABLE detalles_venta (
  id          CHAR(36) NOT NULL PRIMARY KEY, -- UUID
  venta_id    CHAR(36) NOT NULL,
  producto_id CHAR(36) NOT NULL,
  cantidad    INT NOT NULL CHECK (cantidad > 0),
  precio_unit DECIMAL(10,2) NOT NULL CHECK (precio_unit >= 0),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_detalles_venta_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
  CONSTRAINT fk_detalles_venta_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);


INSERT INTO roles (nombre) VALUES
  ('operator'),
  ('reporter');