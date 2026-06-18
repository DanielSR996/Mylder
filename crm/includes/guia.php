<?php
declare(strict_types=1);

function guiaStorageDir(): string {
  $dir = dirname(__DIR__) . "/storage/guia";
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
  return $dir;
}

function guiaPdfPath(?string $filename): ?string {
  if ($filename === null || $filename === "") {
    return null;
  }
  $safe = basename($filename);
  if (!preg_match('/^[a-zA-Z0-9._-]+\.pdf$/', $safe)) {
    return null;
  }
  $path = guiaStorageDir() . "/" . $safe;
  return is_file($path) ? $path : null;
}

function guiaSlugify(string $text): string {
  $text = mb_strtolower(trim($text), "UTF-8");
  $text = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text) ?: $text;
  $text = preg_replace("/[^a-z0-9]+/", "-", $text) ?? $text;
  return trim($text, "-") ?: "guia-doc";
}
