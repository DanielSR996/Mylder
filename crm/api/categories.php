<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";

$user = requireRole("admin");
$method = $_SERVER["REQUEST_METHOD"];

try {
  if ($method === "GET") {
    $rows = db()->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
    authJson(["ok" => true, "data" => $rows]);
  }

  if ($method === "POST") {
    $body = authReadJsonBody();
    $action = $body["action"] ?? "create";

    if ($action === "create") {
      $nombre = trim((string) ($body["nombre"] ?? ""));
      if ($nombre === "") {
        authJson(["ok" => false, "error" => "Nombre requerido"], 422);
      }
      $slug = dbSlugify($nombre);
      $stmt = db()->prepare("INSERT INTO categorias (nombre, slug) VALUES (?, ?)");
      $stmt->execute([$nombre, $slug]);
      authJson(["ok" => true, "id" => (int) db()->lastInsertId()]);
    }

    if ($action === "toggle") {
      $id = (int) ($body["id"] ?? 0);
      $activo = (int) ($body["activo"] ?? 0) ? 1 : 0;
      db()->prepare("UPDATE categorias SET activo = ? WHERE id = ?")->execute([$activo, $id]);
      authJson(["ok" => true]);
    }

    if ($action === "update") {
      $id = (int) ($body["id"] ?? 0);
      $nombre = trim((string) ($body["nombre"] ?? ""));
      $descripcion = trim((string) ($body["descripcion"] ?? ""));
      if ($id <= 0 || $nombre === "") {
        authJson(["ok" => false, "error" => "Datos inválidos"], 422);
      }
      db()->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, slug = ? WHERE id = ?")
        ->execute([$nombre, $descripcion ?: null, dbSlugify($nombre), $id]);
      authJson(["ok" => true]);
    }

    authJson(["ok" => false, "error" => "Acción desconocida"], 400);
  }

  authJson(["ok" => false, "error" => "Método no permitido"], 405);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}
