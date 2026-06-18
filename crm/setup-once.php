<?php
declare(strict_types=1);

/**
 * Script de configuración inicial — ejecutar UNA vez tras crear las tablas.
 * Acceso: https://mylder.mx/crm/setup-once.php
 * ELIMINAR este archivo después de crear el admin.
 */

require_once dirname(__DIR__) . "/api/db.php";

try {
  $count = (int) db()->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
} catch (Throwable $e) {
  http_response_code(500);
  echo "<!DOCTYPE html><html lang='es'><body style='font-family:sans-serif;padding:2rem'>";
  echo "<h1>No se pudo conectar a MySQL</h1>";
  echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
  echo "<h2>Si pruebas en local (recomendado)</h2>";
  echo "<ol><li>Abre XAMPP → inicia <strong>MySQL</strong></li>";
  echo "<li>En <code>api/settings.local.php</code> usa host <code>127.0.0.1</code>, BD <code>mylder_crm</code>, user <code>root</code></li>";
  echo "<li>Importa <code>sql/mylder_crm.sql</code> en phpMyAdmin local</li></ol>";
  echo "<h2>Si usas BD de producción remota</h2>";
  echo "<ol><li>cPanel → MySQL remoto → agrega tu IP</li>";
  echo "<li>Verifica contraseña en settings.php</li></ol>";
  echo "</body></html>";
  exit;
}

if ($count > 0) {
  http_response_code(403);
  echo "Ya existen usuarios. Elimina este archivo por seguridad.";
  exit;
}

$error = "";
$done = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = trim((string) ($_POST["nombre"] ?? ""));
  $email = strtolower(trim((string) ($_POST["email"] ?? "")));
  $password = (string) ($_POST["password"] ?? "");

  if ($nombre === "" || $email === "" || strlen($password) < 8) {
    $error = "Completa todos los campos. Password mínimo 8 caracteres.";
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    db()->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, 'admin', 1)")
      ->execute([$nombre, $email, $hash]);
    $done = true;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Setup CRM Mylder</title>
  <link rel="icon" type="image/png" href="/assets/favicon-mylder.png" />
  <link rel="apple-touch-icon" href="/assets/favicon-mylder.png" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl p-8 max-w-md w-full">
    <?php if ($done): ?>
      <h1 class="text-xl font-bold text-green-700 mb-2">Admin creado</h1>
      <p class="text-sm text-slate-600 mb-4">Ya puedes entrar en <a href="login.php" class="text-blue-600 underline">login.php</a>.</p>
      <p class="text-sm text-red-600 font-semibold">Elimina setup-once.php del servidor ahora.</p>
    <?php else: ?>
      <h1 class="text-xl font-bold mb-4">Crear administrador inicial</h1>
      <?php if ($error): ?><p class="text-red-600 text-sm mb-3"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="post" class="space-y-3">
        <input name="nombre" placeholder="Nombre" required class="w-full border rounded px-3 py-2" />
        <input name="email" type="email" placeholder="admin@mylder.mx" required class="w-full border rounded px-3 py-2" />
        <input name="password" type="password" placeholder="Contraseña (8+)" required minlength="8" class="w-full border rounded px-3 py-2" />
        <button type="submit" class="w-full bg-yellow-400 font-bold py-2 rounded">Crear admin</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
