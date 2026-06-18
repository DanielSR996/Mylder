<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/guia.php";

$user = requireLogin();
$method = $_SERVER["REQUEST_METHOD"];

try {
  if ($method === "GET") {
    $id = (int) ($_GET["id"] ?? 0);
    if ($id > 0) {
      $stmt = db()->prepare("SELECT * FROM guia_documentos WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if (!$row || (!(int) $row["activo"] && $user["rol"] !== "admin")) {
        authJson(["ok" => false, "error" => "No encontrado"], 404);
      }
      $row["tiene_pdf"] = guiaPdfPath($row["pdf_archivo"] ?? null) !== null;
      authJson(["ok" => true, "data" => $row]);
    }

    $onlyActive = $user["rol"] !== "admin" || !isset($_GET["all"]);
    $sql = $onlyActive
      ? "SELECT id, nombre, descripcion, pdf_archivo, orden FROM guia_documentos WHERE activo = 1 ORDER BY orden ASC, nombre ASC"
      : "SELECT * FROM guia_documentos ORDER BY orden ASC, nombre ASC";
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$r) {
      $r["tiene_pdf"] = guiaPdfPath($r["pdf_archivo"] ?? null) !== null;
    }
    unset($r);
    authJson(["ok" => true, "data" => $rows]);
  }

  requireRole("admin");

  if ($method === "POST") {
    $contentType = (string) ($_SERVER["CONTENT_TYPE"] ?? "");
    $isMultipart = str_contains($contentType, "multipart/form-data");

    if ($isMultipart) {
      handleGuiaUpload($_POST, $_FILES);
    }

    $body = authReadJsonBody();
    $action = $body["action"] ?? "save";

    if ($action === "save") {
      handleGuiaSave($body);
    }
    if ($action === "toggle") {
      $id = (int) ($body["id"] ?? 0);
      $activo = (int) ($body["activo"] ?? 0) ? 1 : 0;
      db()->prepare("UPDATE guia_documentos SET activo = ? WHERE id = ?")->execute([$activo, $id]);
      authJson(["ok" => true]);
    }
    if ($action === "delete") {
      $id = (int) ($body["id"] ?? 0);
      $stmt = db()->prepare("SELECT pdf_archivo FROM guia_documentos WHERE id = ?");
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if ($row && !empty($row["pdf_archivo"])) {
        $path = guiaPdfPath($row["pdf_archivo"]);
        if ($path !== null) {
          @unlink($path);
        }
      }
      db()->prepare("DELETE FROM guia_documentos WHERE id = ?")->execute([$id]);
      authJson(["ok" => true]);
    }

    authJson(["ok" => false, "error" => "Acción desconocida"], 400);
  }

  authJson(["ok" => false, "error" => "Método no permitido"], 405);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}

function handleGuiaSave(array $body): never {
  $id = (int) ($body["id"] ?? 0);
  $nombre = trim((string) ($body["nombre"] ?? ""));
  if ($nombre === "") {
    authJson(["ok" => false, "error" => "Nombre obligatorio"], 422);
  }

  $fields = [
    $nombre,
    trim((string) ($body["descripcion"] ?? "")) ?: null,
    (int) ($body["orden"] ?? 0),
    (int) ($body["activo"] ?? 1) ? 1 : 0,
  ];

  if ($id > 0) {
    $fields[] = $id;
    db()->prepare("
      UPDATE guia_documentos SET nombre = ?, descripcion = ?, orden = ?, activo = ?
      WHERE id = ?
    ")->execute($fields);
    authJson(["ok" => true, "id" => $id]);
  }

  db()->prepare("
    INSERT INTO guia_documentos (nombre, descripcion, orden, activo)
    VALUES (?, ?, ?, ?)
  ")->execute($fields);
  authJson(["ok" => true, "id" => (int) db()->lastInsertId()]);
}

function handleGuiaUpload(array $post, array $files): never {
  $id = (int) ($post["id"] ?? 0);
  if ($id <= 0) {
    authJson(["ok" => false, "error" => "ID inválido"], 422);
  }

  if (!isset($files["pdf"]) || !is_uploaded_file($files["pdf"]["tmp_name"])) {
    authJson(["ok" => false, "error" => "Archivo PDF requerido"], 422);
  }

  $file = $files["pdf"];
  if ($file["error"] !== UPLOAD_ERR_OK) {
    authJson(["ok" => false, "error" => "Error al subir PDF"], 400);
  }

  $ext = strtolower(pathinfo((string) $file["name"], PATHINFO_EXTENSION));
  if ($ext !== "pdf") {
    authJson(["ok" => false, "error" => "Solo se permiten archivos PDF"], 422);
  }

  $stmt = db()->prepare("SELECT nombre, pdf_archivo FROM guia_documentos WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) {
    authJson(["ok" => false, "error" => "Documento no encontrado"], 404);
  }

  if (!empty($row["pdf_archivo"])) {
    $old = guiaPdfPath($row["pdf_archivo"]);
    if ($old !== null) {
      @unlink($old);
    }
  }

  $filename = guiaSlugify((string) $row["nombre"]) . "-" . $id . ".pdf";
  $dest = guiaStorageDir() . "/" . $filename;
  if (!move_uploaded_file($file["tmp_name"], $dest)) {
    authJson(["ok" => false, "error" => "No se pudo guardar el PDF"], 500);
  }

  db()->prepare("UPDATE guia_documentos SET pdf_archivo = ? WHERE id = ?")->execute([$filename, $id]);
  authJson(["ok" => true, "pdf_archivo" => $filename]);
}
