<?php
declare(strict_types=1);

function importStripCell(string $value): string {
  if (str_starts_with($value, "\xEF\xBB\xBF")) {
    $value = substr($value, 3);
  }
  return trim($value, " \t\n\r\0\x0B");
}

function importNormalizeHeader(string $col): string {
  $col = mb_strtolower(importStripCell($col), "UTF-8");
  $aliases = [
    "ciudad" => "ciudad",
    "nombre" => "nombre",
    "telefono" => "telefono",
    "teléfono" => "telefono",
    "telefono_" => "telefono",
    "direccion" => "direccion",
    "dirección" => "direccion",
    "calificacion" => "calificacion",
    "calificación" => "calificacion",
    "rating" => "calificacion",
    "num_resenas" => "num_resenas",
    "num_reseñas" => "num_resenas",
    "num_resena" => "num_resenas",
    "num_reviews" => "num_resenas",
    "resenas" => "num_resenas",
    "reseñas" => "num_resenas",
    "reviews" => "num_resenas",
    "enlace_actual" => "enlace_actual",
    "link_google_maps" => "link_google_maps",
    "link_google_maps_" => "link_google_maps",
    "link_google_map" => "link_google_maps",
    "link google maps" => "link_google_maps",
    "google_maps" => "link_google_maps",
    "maps" => "link_google_maps",
    "comentarios" => "notas",
    "comentario" => "notas",
    "notas" => "notas",
    "review_text" => "notas",
  ];

  if (isset($aliases[$col])) {
    return $aliases[$col];
  }

  $col = preg_replace("/[^a-z0-9_áéíóúñ]/u", "_", $col) ?? $col;
  $col = trim($col, "_");
  return $aliases[$col] ?? $col;
}

/** @return array<string, int> */
function importBuildColumnMap(array $header): array {
  $map = [];
  foreach ($header as $i => $col) {
    $key = importNormalizeHeader((string) $col);
    if ($key === "") {
      continue;
    }
    $map[$key] = $i;
  }
  return $map;
}

function importGetCol(array $row, array $map, string $field): string {
  if (!isset($map[$field])) {
    return "";
  }
  return importStripCell((string) ($row[$map[$field]] ?? ""));
}

function importParseInt(?string $value): ?int {
  if ($value === null) {
    return null;
  }
  $value = importStripCell($value);
  if ($value === "") {
    return null;
  }
  $value = str_replace([" ", ","], "", $value);
  if (!is_numeric($value)) {
    return null;
  }
  return (int) $value;
}

function importNormalizeCiudad(string $value, string $mapsUrl = ""): ?string {
  $value = importStripCell($value);
  if ($value === "" || strcasecmp($value, "ciudad") === 0) {
    return importGuessCiudadFromMaps($mapsUrl);
  }
  return $value;
}

function importGuessCiudadFromMaps(string $url): ?string {
  $url = trim($url);
  if ($url === "") {
    return null;
  }

  if (!preg_match("/3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/", $url, $m)) {
    return null;
  }

  $lat = (float) $m[1];
  $lng = (float) $m[2];

  if ($lat >= 19.0 && $lat <= 19.7 && $lng >= -99.45 && $lng <= -98.9) {
    return "CDMX";
  }
  if ($lat >= 20.5 && $lat <= 20.7 && $lng >= -100.55 && $lng <= -100.25) {
    return "Querétaro";
  }
  if ($lat >= 25.6 && $lat <= 25.85 && $lng >= -100.45 && $lng <= -100.15) {
    return "Monterrey";
  }
  if ($lat >= 20.6 && $lat <= 20.78 && $lng >= -103.5 && $lng <= -103.2) {
    return "Guadalajara";
  }

  return null;
}
