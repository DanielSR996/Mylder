<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/api/db.php";
require_once dirname(__DIR__) . "/crm/includes/schema.php";

$sql = file_get_contents(dirname(__DIR__) . "/sql/mylder_crm_presence.sql");
if ($sql === false) {
  fwrite(STDERR, "No se pudo leer mylder_crm_presence.sql\n");
  exit(1);
}

if (crmSchemaHasColumn("usuarios", "ultima_actividad")) {
  echo "OK: Columnas de presencia ya existen.\n";
  exit(0);
}

$sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;
foreach (preg_split('/;\s*\n/', $sql) ?: [] as $stmt) {
  $stmt = trim($stmt);
  if ($stmt === "") {
    continue;
  }
  try {
    db()->exec($stmt);
    echo "OK: " . substr(str_replace("\n", " ", $stmt), 0, 70) . "...\n";
  } catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
    exit(1);
  }
}

echo "Migración presencia finalizada.\n";
