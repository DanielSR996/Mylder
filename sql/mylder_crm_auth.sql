-- Migración auth CRM — ejecutar en phpMyAdmin si ya tienes las tablas base
-- Compatible con mylder_crm local y producción

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

CREATE TABLE IF NOT EXISTS password_tokens (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id      INT UNSIGNED NOT NULL,
  token_hash      CHAR(64) NOT NULL,
  tipo            ENUM('reset', 'activacion') NOT NULL,
  expires_at      DATETIME NOT NULL,
  used_at         DATETIME DEFAULT NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_password_tokens_hash (token_hash),
  KEY idx_password_tokens_usuario (usuario_id),
  CONSTRAINT fk_password_tokens_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_intentos (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email_normalizado   VARCHAR(190) NOT NULL,
  ip                  VARCHAR(45) NOT NULL,
  intentos            INT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta     DATETIME DEFAULT NULL,
  actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_login_email_ip (email_normalizado, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
