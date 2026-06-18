<?php
declare(strict_types=1);

function cotizacionStorageDir(): string {
  $dir = dirname(__DIR__) . "/storage/cotizaciones";
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
  return $dir;
}

function cotizacionParseIncluye(?string $raw): array {
  if ($raw === null || trim($raw) === "") {
    return [];
  }
  return array_values(array_filter(array_map("trim", explode("|", $raw))));
}

function cotizacionCategoriaLabel(string $cat): string {
  return match ($cat) {
    "sitio" => "Sitio web",
    "ecommerce" => "E-commerce",
    "branding" => "Branding",
    "automatizacion" => "Automatización IA",
    "soporte" => "Soporte web",
    default => "General",
  };
}

function cotizacionSlugify(string $text): string {
  $text = mb_strtolower(trim($text), "UTF-8");
  $text = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text) ?: $text;
  $text = preg_replace("/[^a-z0-9]+/", "-", $text) ?? $text;
  return trim($text, "-") ?: "cotizacion";
}

function cotizacionPdfPath(?string $filename): ?string {
  if ($filename === null || $filename === "") {
    return null;
  }
  $safe = basename($filename);
  if (!preg_match('/^[a-zA-Z0-9._-]+\.pdf$/', $safe)) {
    return null;
  }
  $path = cotizacionStorageDir() . "/" . $safe;
  return is_file($path) ? $path : null;
}
