-- Migración CRM v2 — leads web, ocultar tras 3 sin respuesta, reportes
-- Ejecutar en phpMyAdmin o: php scripts/run-crm-v2-migration.php

ALTER TABLE prospectos
  ADD COLUMN IF NOT EXISTS origen ENUM('csv', 'web_contacto', 'web_cita') NOT NULL DEFAULT 'csv' AFTER link_reunion,
  ADD COLUMN IF NOT EXISTS prioridad TINYINT NOT NULL DEFAULT 0 AFTER origen,
  ADD COLUMN IF NOT EXISTS email VARCHAR(190) DEFAULT NULL AFTER prioridad,
  ADD COLUMN IF NOT EXISTS servicio_interes VARCHAR(120) DEFAULT NULL AFTER email,
  ADD COLUMN IF NOT EXISTS mensaje_web TEXT DEFAULT NULL AFTER servicio_interes,
  ADD COLUMN IF NOT EXISTS intentos_sin_respuesta INT UNSIGNED NOT NULL DEFAULT 0 AFTER intentos,
  ADD COLUMN IF NOT EXISTS oculto TINYINT(1) NOT NULL DEFAULT 0 AFTER intentos_sin_respuesta;

CREATE INDEX IF NOT EXISTS idx_prospectos_prioridad ON prospectos (oculto, prioridad DESC, estado);
CREATE INDEX IF NOT EXISTS idx_prospectos_origen ON prospectos (origen);

INSERT INTO categorias (nombre, slug, descripcion, activo)
VALUES ('Leads orgánicos', 'leads-organicos', 'Contactos desde formularios web mylder.mx', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);
