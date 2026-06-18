<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/api/db.php";

$sql = file_get_contents(dirname(__DIR__) . "/sql/mylder_crm_guia.sql");
if ($sql === false) {
  fwrite(STDERR, "No se pudo leer mylder_crm_guia.sql\n");
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

$storageDir = dirname(__DIR__) . "/crm/storage/guia";
if (!is_dir($storageDir)) {
  mkdir($storageDir, 0755, true);
}

$sources = [
  dirname(__DIR__) . "/crm/docs/Manual_Ventas_Mylder_Final.pdf",
  "C:/Users/LCK_KATHIA/Desktop/Manual_Ventas_Mylder_Final.pdf",
];
$dest = $storageDir . "/manual-ventas-mylder-1.pdf";

foreach ($sources as $src) {
  if (is_file($src) && !is_file($dest)) {
    copy($src, $dest);
    db()->prepare("UPDATE guia_documentos SET pdf_archivo = ? WHERE id = 1")
      ->execute(["manual-ventas-mylder-1.pdf"]);
    echo "OK: Manual PDF copiado a storage/guia/\n";
    break;
  }
}

echo "Migración guía comercial finalizada.\n";
