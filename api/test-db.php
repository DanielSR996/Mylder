<?php
declare(strict_types=1);

require __DIR__ . "/db.php";

header("Content-Type: text/plain; charset=utf-8");

try {
  $pdo = db();
  $host = dbHost();
  $version = $pdo->query("SELECT VERSION()")->fetchColumn();
  $users = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
  $cats = (int) $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();

  echo "OK — Conexión exitosa\n";
  echo "Config: " . (dbSettingsSource() ?: "(desconocido)") . "\n";
  echo "Host: {$host}\n";
  echo "BD: " . dbName() . "\n";
  echo "Usuario MySQL: " . dbUser() . "\n";
  echo "MySQL: {$version}\n";
  echo "Usuarios en BD: {$users}\n";
  echo "Categorías: {$cats}\n";

  if ($users === 0) {
    echo "\n→ No hay admin. Abre: /crm/setup-once.php\n";
  } else {
    echo "\n→ Login: /crm/\n";
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "ERROR: " . $e->getMessage() . "\n";
  if (dbSettingsSource() !== "") {
    echo "Config cargada: " . dbSettingsSource() . "\n";
    echo "Usuario MySQL: " . dbUser() . "\n";
  }
  echo "\nEn producción (cPanel):\n";
  echo "1. MySQL → usuario ligado a la BD " . (defined("DB_NAME") ? DB_NAME : "bnfivdwn_mylderbdA") . "\n";
  echo "2. Contraseña en api/settings.php igual a la de cPanel\n";
  echo "3. Borra api/settings.local.php si existe (es solo para XAMPP local)\n";
}
