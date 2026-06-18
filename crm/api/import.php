<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";

$user = requireRole("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  authJson(["ok" => false, "error" => "Método no permitido"], 405);
}

if (!isset($_FILES["csv"]) || !is_uploaded_file($_FILES["csv"]["tmp_name"])) {
  authJson(["ok" => false, "error" => "Archivo CSV requerido"], 422);
}

$categoriaId = (int) ($_POST["categoria_id"] ?? 0);
if ($categoriaId <= 0) {
  authJson(["ok" => false, "error" => "Selecciona una categoría"], 422);
}

$catCheck = db()->prepare("SELECT id FROM categorias WHERE id = ? AND activo = 1");
$catCheck->execute([$categoriaId]);
if (!$catCheck->fetch()) {
  authJson(["ok" => false, "error" => "Categoría no válida"], 422);
}

$file = $_FILES["csv"];
if ($file["error"] !== UPLOAD_ERR_OK) {
  authJson(["ok" => false, "error" => "Error al subir archivo"], 400);
}

$filename = basename((string) $file["name"]);
$handle = fopen($file["tmp_name"], "r");
if ($handle === false) {
  authJson(["ok" => false, "error" => "No se pudo leer el CSV"], 500);
}

$header = fgetcsv($handle);
if (!is_array($header)) {
  fclose($handle);
  authJson(["ok" => false, "error" => "CSV vacío o inválido"], 422);
}

require_once dirname(__DIR__) . "/includes/import-csv.php";

$map = importBuildColumnMap($header);
if (!isset($map["nombre"]) || !isset($map["telefono"])) {
  fclose($handle);
  authJson(["ok" => false, "error" => "El CSV debe tener columnas Nombre y Telefono"], 422);
}

$pdo = db();
$pdo->beginTransaction();

try {
  $importStmt = $pdo->prepare("
    INSERT INTO importaciones (categoria_id, nombre_archivo, filas_leidas, filas_nuevas, duplicados, errores, importado_por)
    VALUES (?, ?, 0, 0, 0, 0, ?)
  ");
  $importStmt->execute([$categoriaId, $filename, $user["id"]]);
  $importacionId = (int) $pdo->lastInsertId();

  $insertStmt = $pdo->prepare("
    INSERT INTO prospectos (
      categoria_id, importacion_id, ciudad, nombre, telefono, telefono_normalizado,
      direccion, calificacion, num_resenas, enlace_actual, link_google_maps, notas, asignado_a
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $dupCheck = $pdo->prepare(
    "SELECT id FROM prospectos WHERE categoria_id = ? AND telefono_normalizado = ? LIMIT 1"
  );

  $leidas = 0;
  $nuevas = 0;
  $duplicados = 0;
  $errores = 0;

  while (($row = fgetcsv($handle)) !== false) {
    if (!is_array($row) || count(array_filter($row, static fn($v) => importStripCell((string) $v) !== "")) === 0) {
      continue;
    }

    $leidas++;

    $nombre = importGetCol($row, $map, "nombre");
    $telefono = importGetCol($row, $map, "telefono");
    $telNorm = dbNormalizePhone($telefono);
    $mapsUrl = importGetCol($row, $map, "link_google_maps");

    if ($nombre === "" || $telNorm === "") {
      $errores++;
      continue;
    }

    $dupCheck->execute([$categoriaId, $telNorm]);
    if ($dupCheck->fetch()) {
      $duplicados++;
      continue;
    }

    $calificacion = dbParseRating(importGetCol($row, $map, "calificacion") ?: null);
    $numResenas = importParseInt(importGetCol($row, $map, "num_resenas") ?: null);
    $ciudad = importNormalizeCiudad(importGetCol($row, $map, "ciudad"), $mapsUrl);
    $notas = importGetCol($row, $map, "notas") ?: null;

    try {
      $insertStmt->execute([
        $categoriaId,
        $importacionId,
        $ciudad,
        $nombre,
        $telefono,
        $telNorm,
        importGetCol($row, $map, "direccion") ?: null,
        $calificacion,
        $numResenas,
        importGetCol($row, $map, "enlace_actual") ?: null,
        $mapsUrl ?: null,
        $notas,
        $user["id"],
      ]);
      $nuevas++;
    } catch (Throwable) {
      $errores++;
    }
  }

  fclose($handle);

  $updateImport = $pdo->prepare("
    UPDATE importaciones SET filas_leidas = ?, filas_nuevas = ?, duplicados = ?, errores = ? WHERE id = ?
  ");
  $updateImport->execute([$leidas, $nuevas, $duplicados, $errores, $importacionId]);

  $pdo->commit();

  authJson([
    "ok" => true,
    "importacion_id" => $importacionId,
    "filas_leidas" => $leidas,
    "filas_nuevas" => $nuevas,
    "duplicados" => $duplicados,
    "errores" => $errores,
  ]);
} catch (Throwable $e) {
  $pdo->rollBack();
  if (is_resource($handle)) {
    fclose($handle);
  }
  authJson(["ok" => false, "error" => $e->getMessage()], 500);
}
