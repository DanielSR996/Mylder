-- Presencia en línea — última actividad y inicio de sesión
-- Ejecutar: php scripts/run-presence-migration.php

ALTER TABLE usuarios
  ADD COLUMN ultima_actividad DATETIME DEFAULT NULL AFTER actualizado_en,
  ADD COLUMN sesion_inicio DATETIME DEFAULT NULL AFTER ultima_actividad;

ALTER TABLE usuarios
  ADD KEY idx_usuarios_ultima_actividad (ultima_actividad);
