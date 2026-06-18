<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/mailer-crm.php";

$user = requireRole("admin");
$method = $_SERVER["REQUEST_METHOD"];

try {
  if ($method === "GET") {
    $rows = db()->query("SELECT id, nombre, email, rol, activo, creado_en FROM usuarios ORDER BY nombre")->fetchAll();
    authJson(["ok" => true, "data" => $rows]);
  }

  if ($method === "POST") {
    $body = authReadJsonBody();
    $action = $body["action"] ?? "create";

    if ($action === "create") {
      $nombre = trim((string) ($body["nombre"] ?? ""));
      $email = strtolower(trim((string) ($body["email"] ?? "")));
      $password = trim((string) ($body["password"] ?? ""));
      $rol = ($body["rol"] ?? "agente") === "admin" ? "admin" : "agente";

      if ($nombre === "" || $email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        authJson(["ok" => false, "error" => "Nombre y email válidos son obligatorios."], 422);
      }

      $inviteByEmail = $password === "";
      if (!$inviteByEmail) {
        $passErr = authPasswordValidate($password);
        if ($passErr !== null) {
          authJson(["ok" => false, "error" => $passErr], 422);
        }
      }

      if ($inviteByEmail) {
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        try {
          $stmt = db()->prepare("
            INSERT INTO usuarios (nombre, email, password_hash, rol, debe_cambiar_password)
            VALUES (?, ?, ?, ?, 1)
          ");
          $stmt->execute([$nombre, $email, $hash, $rol]);
        } catch (Throwable) {
          $stmt = db()->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
          $stmt->execute([$nombre, $email, $hash, $rol]);
        }
        $newId = (int) db()->lastInsertId();
        $invite = crmInviteUser($newId, $email, $nombre);
        authJson([
          "ok" => true,
          "id" => $newId,
          "invited" => true,
          "email_sent" => $invite["sent"],
          "dev_link" => $invite["dev_link"],
        ]);
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = db()->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
      $stmt->execute([$nombre, $email, $hash, $rol]);
      authJson(["ok" => true, "id" => (int) db()->lastInsertId(), "invited" => false]);
    }

    if ($action === "toggle") {
      $id = (int) ($body["id"] ?? 0);
      if ($id === $user["id"]) {
        authJson(["ok" => false, "error" => "No puedes bloquearte a ti mismo"], 422);
      }
      $activo = (int) ($body["activo"] ?? 0) ? 1 : 0;
      db()->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo, $id]);
      authJson(["ok" => true]);
    }

    if ($action === "resend_invite") {
      $id = (int) ($body["id"] ?? 0);
      $stmt = db()->prepare("SELECT id, nombre, email, activo FROM usuarios WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);
      $target = $stmt->fetch();
      if (!$target || !(int) $target["activo"]) {
        authJson(["ok" => false, "error" => "Usuario no encontrado o inactivo."], 404);
      }
      $invite = crmInviteUser((int) $target["id"], (string) $target["email"], (string) $target["nombre"]);
      authJson([
        "ok" => true,
        "email_sent" => $invite["sent"],
        "dev_link" => $invite["dev_link"],
      ]);
    }

    if ($action === "delete") {
      $id = (int) ($body["id"] ?? 0);
      if ($id === $user["id"]) {
        authJson(["ok" => false, "error" => "No puedes eliminarte a ti mismo"], 422);
      }
      db()->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
      authJson(["ok" => true]);
    }

    authJson(["ok" => false, "error" => "Acción desconocida"], 400);
  }

  authJson(["ok" => false, "error" => "Método no permitido"], 405);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}
