<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/cotizaciones.php";

$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  echo "Cotización no encontrada.";
  exit;
}

$user = requireLogin();

$stmt = db()->prepare("SELECT id, pdf_archivo, activo FROM cotizaciones WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || !(int) $row["activo"]) {
  http_response_code(404);
  echo "PDF no disponible.";
  exit;
}

$path = cotizacionPdfPath($row["pdf_archivo"] ?? null);
if ($path === null) {
  http_response_code(404);
  echo "PDF no subido aún.";
  exit;
}

authSendPdfHeaders(basename($path), $path);
readfile($path);
exit;
