<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/api/db.php";

$sql = file_get_contents(dirname(__DIR__) . "/sql/mylder_crm_cotizaciones.sql");
if ($sql === false) {
  fwrite(STDERR, "No se pudo leer mylder_crm_cotizaciones.sql\n");
  exit(1);
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
  }
}

$storageDir = dirname(__DIR__) . "/crm/storage/cotizaciones";
if (!is_dir($storageDir)) {
  mkdir($storageDir, 0755, true);
}

echo "Migración cotizaciones finalizada.\n";
