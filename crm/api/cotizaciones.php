<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/cotizaciones.php";

$user = requireLogin();
$method = $_SERVER["REQUEST_METHOD"];

try {
  if ($method === "GET") {
    $id = (int) ($_GET["id"] ?? 0);
    if ($id > 0) {
      $stmt = db()->prepare("SELECT * FROM cotizaciones WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if (!$row || (!(int) $row["activo"] && $user["rol"] !== "admin")) {
        authJson(["ok" => false, "error" => "No encontrada"], 404);
      }
      $row["incluye_lista"] = cotizacionParseIncluye($row["incluye"] ?? null);
      $row["categoria_label"] = cotizacionCategoriaLabel((string) $row["categoria"]);
      $row["tiene_pdf"] = cotizacionPdfPath($row["pdf_archivo"] ?? null) !== null;
      authJson(["ok" => true, "data" => $row]);
    }

    $onlyActive = $user["rol"] !== "admin" || !isset($_GET["all"]);
    $sql = $onlyActive
      ? "SELECT id, nombre, slug, categoria, resumen, precio_etiqueta, precio_minimo, pdf_archivo, orden FROM cotizaciones WHERE activo = 1 ORDER BY orden ASC, nombre ASC"
      : "SELECT * FROM cotizaciones ORDER BY orden ASC, nombre ASC";
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$r) {
      $r["categoria_label"] = cotizacionCategoriaLabel((string) $r["categoria"]);
      $r["tiene_pdf"] = cotizacionPdfPath($r["pdf_archivo"] ?? null) !== null;
      $r["incluye_lista"] = cotizacionParseIncluye($r["incluye"] ?? null);
    }
    unset($r);
    authJson(["ok" => true, "data" => $rows]);
  }

  requireRole("admin");

  if ($method === "POST") {
    $contentType = (string) ($_SERVER["CONTENT_TYPE"] ?? "");
    $isMultipart = str_contains($contentType, "multipart/form-data");

    if ($isMultipart) {
      handleUpload($_POST, $_FILES);
    }

    $body = authReadJsonBody();
    $action = $body["action"] ?? "save";

    if ($action === "save") {
      handleSave($body);
    }
    if ($action === "toggle") {
      $id = (int) ($body["id"] ?? 0);
      $activo = (int) ($body["activo"] ?? 0) ? 1 : 0;
      db()->prepare("UPDATE cotizaciones SET activo = ? WHERE id = ?")->execute([$activo, $id]);
      authJson(["ok" => true]);
    }
    if ($action === "delete") {
      $id = (int) ($body["id"] ?? 0);
      $stmt = db()->prepare("SELECT pdf_archivo FROM cotizaciones WHERE id = ?");
      $stmt->execute([$id]);
      $row = $stmt->fetch();
      if ($row && !empty($row["pdf_archivo"])) {
        $path = cotizacionPdfPath($row["pdf_archivo"]);
        if ($path !== null) {
          @unlink($path);
        }
      }
      db()->prepare("DELETE FROM cotizaciones WHERE id = ?")->execute([$id]);
      authJson(["ok" => true]);
    }

    authJson(["ok" => false, "error" => "Acción desconocida"], 400);
  }

  authJson(["ok" => false, "error" => "Método no permitido"], 405);
} catch (Throwable $e) {
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}

function handleSave(array $body): never {
  $id = (int) ($body["id"] ?? 0);
  $nombre = trim((string) ($body["nombre"] ?? ""));
  if ($nombre === "") {
    authJson(["ok" => false, "error" => "Nombre obligatorio"], 422);
  }

  $slug = trim((string) ($body["slug"] ?? ""));
  if ($slug === "") {
    $slug = cotizacionSlugify($nombre);
  }

  $categoria = (string) ($body["categoria"] ?? "sitio");
  if (!in_array($categoria, ["sitio", "ecommerce", "branding", "automatizacion", "soporte", "general"], true)) {
    $categoria = "sitio";
  }

  $incluyeRaw = $body["incluye"] ?? "";
  if (is_array($incluyeRaw)) {
    $incluyeRaw = implode("|", array_map("trim", $incluyeRaw));
  }

  $fields = [
    $nombre,
    $slug,
    $categoria,
    trim((string) ($body["resumen"] ?? "")) ?: null,
    trim((string) ($body["descripcion"] ?? "")) ?: null,
    trim((string) $incluyeRaw) ?: null,
    trim((string) ($body["precio_etiqueta"] ?? "")) ?: null,
    trim((string) ($body["precio_minimo"] ?? "")) ?: null,
    trim((string) ($body["precio_nota"] ?? "")) ?: null,
    trim((string) ($body["comision_nota"] ?? "")) ?: null,
    (int) ($body["orden"] ?? 0),
    (int) ($body["activo"] ?? 1) ? 1 : 0,
  ];

  if ($id > 0) {
    $fields[] = $id;
    db()->prepare("
      UPDATE cotizaciones SET
        nombre = ?, slug = ?, categoria = ?, resumen = ?, descripcion = ?, incluye = ?,
        precio_etiqueta = ?, precio_minimo = ?, precio_nota = ?, comision_nota = ?, orden = ?, activo = ?
      WHERE id = ?
    ")->execute($fields);
    authJson(["ok" => true, "id" => $id]);
  }

  db()->prepare("
    INSERT INTO cotizaciones (
      nombre, slug, categoria, resumen, descripcion, incluye,
      precio_etiqueta, precio_minimo, precio_nota, comision_nota, orden, activo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ")->execute($fields);
  authJson(["ok" => true, "id" => (int) db()->lastInsertId()]);
}

function handleUpload(array $post, array $files): never {
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

  $stmt = db()->prepare("SELECT slug, pdf_archivo FROM cotizaciones WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) {
    authJson(["ok" => false, "error" => "Cotización no encontrada"], 404);
  }

  if (!empty($row["pdf_archivo"])) {
    $old = cotizacionPdfPath($row["pdf_archivo"]);
    if ($old !== null) {
      @unlink($old);
    }
  }

  $filename = cotizacionSlugify((string) $row["slug"]) . "-" . $id . ".pdf";
  $dest = cotizacionStorageDir() . "/" . $filename;
  if (!move_uploaded_file($file["tmp_name"], $dest)) {
    authJson(["ok" => false, "error" => "No se pudo guardar el PDF"], 500);
  }

  db()->prepare("UPDATE cotizaciones SET pdf_archivo = ? WHERE id = ?")->execute([$filename, $id]);
  authJson(["ok" => true, "pdf_archivo" => $filename]);
}
