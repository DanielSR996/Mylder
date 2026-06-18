<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";

$user = requireLogin();
$file = basename((string) ($_GET["f"] ?? ""));

if ($file === "" || !preg_match('/^[a-zA-Z0-9._-]+\.pdf$/', $file)) {
  http_response_code(404);
  echo "Documento no encontrado.";
  exit;
}

$path = __DIR__ . "/docs/" . $file;
if (!is_file($path)) {
  http_response_code(404);
  echo "Documento no encontrado.";
  exit;
}

authSendPdfHeaders($file, $path);
readfile($path);
exit;
