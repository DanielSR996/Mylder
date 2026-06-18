-- ============================================================
-- Mylder CRM - Esquema completo
-- Ejecutar en phpMyAdmin sobre la BD: bnfivdwn_mylderbdA
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Usuarios (admin + agentes)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin', 'agente') NOT NULL DEFAULT 'agente',
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol_activo (rol, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Categorías / verticales
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  slug            VARCHAR(80) NOT NULL,
  descripcion     VARCHAR(255) DEFAULT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Registro de importaciones CSV
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS importaciones (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id    INT UNSIGNED NOT NULL,
  nombre_archivo  VARCHAR(255) NOT NULL,
  filas_leidas    INT UNSIGNED NOT NULL DEFAULT 0,
  filas_nuevas    INT UNSIGNED NOT NULL DEFAULT 0,
  duplicados      INT UNSIGNED NOT NULL DEFAULT 0,
  errores         INT UNSIGNED NOT NULL DEFAULT 0,
  importado_por   INT UNSIGNED DEFAULT NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_importaciones_categoria (categoria_id),
  KEY idx_importaciones_fecha (creado_en),
  CONSTRAINT fk_importaciones_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_importaciones_usuario
    FOREIGN KEY (importado_por) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Prospectos / negocios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prospectos (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id          INT UNSIGNED NOT NULL,
  importacion_id        INT UNSIGNED DEFAULT NULL,
  ciudad                VARCHAR(120) DEFAULT NULL,
  nombre                VARCHAR(255) NOT NULL,
  telefono              VARCHAR(40) NOT NULL,
  telefono_normalizado  VARCHAR(20) NOT NULL,
  direccion             VARCHAR(500) DEFAULT NULL,
  calificacion          DECIMAL(2,1) DEFAULT NULL,
  num_resenas           INT UNSIGNED DEFAULT NULL,
  enlace_actual         VARCHAR(500) DEFAULT NULL,
  link_google_maps      VARCHAR(1000) DEFAULT NULL,
  estado                ENUM(
                          'pendiente',
                          'no_contesta',
                          'buzon',
                          'no_interesa',
                          'pensando',
                          'interesado',
                          'reunion_agendada',
                          'convertido'
                        ) NOT NULL DEFAULT 'pendiente',
  asignado_a            INT UNSIGNED DEFAULT NULL,
  ultimo_contacto       DATETIME DEFAULT NULL,
  intentos              INT UNSIGNED NOT NULL DEFAULT 0,
  notas                 TEXT DEFAULT NULL,
  fecha_callback        DATETIME DEFAULT NULL,
  fecha_reunion         DATETIME DEFAULT NULL,
  link_reunion          VARCHAR(500) DEFAULT NULL,
  origen                ENUM('csv', 'web_contacto', 'web_cita') NOT NULL DEFAULT 'csv',
  prioridad             TINYINT NOT NULL DEFAULT 0,
  email                 VARCHAR(190) DEFAULT NULL,
  servicio_interes      VARCHAR(120) DEFAULT NULL,
  mensaje_web           TEXT DEFAULT NULL,
  intentos_sin_respuesta INT UNSIGNED NOT NULL DEFAULT 0,
  oculto                TINYINT(1) NOT NULL DEFAULT 0,
  creado_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_prospectos_categoria_estado (categoria_id, estado),
  KEY idx_prospectos_asignado_estado (asignado_a, estado),
  KEY idx_prospectos_telefono (telefono_normalizado),
  KEY idx_prospectos_ciudad (ciudad),
  KEY idx_prospectos_callback (fecha_callback),
  KEY idx_prospectos_reunion (fecha_reunion),
  KEY idx_prospectos_prioridad (oculto, prioridad, estado),
  KEY idx_prospectos_origen (origen),
  UNIQUE KEY uq_prospecto_categoria_telefono (categoria_id, telefono_normalizado),
  CONSTRAINT fk_prospectos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_prospectos_importacion
    FOREIGN KEY (importacion_id) REFERENCES importaciones (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_prospectos_asignado
    FOREIGN KEY (asignado_a) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Historial de intentos de contacto (solo INSERT, nunca UPDATE)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intentos_contacto (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  prospecto_id    INT UNSIGNED NOT NULL,
  agente_id       INT UNSIGNED NOT NULL,
  resultado       ENUM(
                    'pendiente',
                    'no_contesta',
                    'buzon',
                    'no_interesa',
                    'pensando',
                    'interesado',
                    'reunion_agendada',
                    'convertido'
                  ) NOT NULL,
  notas           TEXT DEFAULT NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_intentos_prospecto (prospecto_id),
  KEY idx_intentos_agente_fecha (agente_id, creado_en),
  KEY idx_intentos_resultado (resultado),
  CONSTRAINT fk_intentos_prospecto
    FOREIGN KEY (prospecto_id) REFERENCES prospectos (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_intentos_agente
    FOREIGN KEY (agente_id) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Auth CRM: tokens de contraseña y rate limit login
-- ------------------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Datos semilla: categorías iniciales
-- ------------------------------------------------------------
INSERT INTO categorias (nombre, slug, descripcion) VALUES
  ('Perfumerías', 'perfumerias', 'Perfumerías y tiendas de fragancias'),
  ('Gyms / Fitness', 'gyms-fitness', 'Gimnasios y centros fitness'),
  ('Restaurantes', 'restaurantes', 'Restaurantes y food service'),
  ('Retail', 'retail', 'Comercio retail general'),
  ('Leads orgánicos', 'leads-organicos', 'Contactos desde formularios web mylder.mx')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Usuario admin: créalo con /crm/setup-once.php tras ejecutar este script
-- (solo funciona si no hay usuarios en la tabla).
