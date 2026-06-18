<?php
declare(strict_types=1);
// Simulate stats without oculto column by using broken query
require_once __DIR__ . '/../api/db.php';
dbLoadSettings();
$pdo = db();
try {
  $pdo->query("SELECT oculto FROM prospectos LIMIT 0");
  echo "oculto column: YES\n";
} catch (Throwable $e) {
  echo "oculto column: NO - " . $e->getMessage() . "\n";
}
try {
  $pdo->query("SELECT origen FROM prospectos LIMIT 0");
  echo "origen column: YES\n";
} catch (Throwable $e) {
  echo "origen column: NO - " . $e->getMessage() . "\n";
}
