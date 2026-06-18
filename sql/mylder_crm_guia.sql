-- Guía comercial — documentos PDF administrables
-- Ejecutar: php scripts/run-guia-migration.php

CREATE TABLE IF NOT EXISTS guia_documentos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(160) NOT NULL,
  descripcion     VARCHAR(500) DEFAULT NULL,
  pdf_archivo     VARCHAR(255) DEFAULT NULL,
  orden           INT NOT NULL DEFAULT 0,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_guia_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO guia_documentos (nombre, descripcion, orden) VALUES
(
  'Manual Ventas Mylder',
  'Pitch, objeciones, comisiones y proceso de venta — léelo antes de tu primera llamada.',
  10
);
