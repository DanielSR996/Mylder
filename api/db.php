<?php
declare(strict_types=1);

/**
 * Conexión PDO compartida para el CRM Mylder.
 * Requiere constantes DB_* definidas en settings.php.
 */

function dbIsProductionHost(): bool {
  if (PHP_SAPI === "cli") {
    return str_starts_with(str_replace("\\", "/", __DIR__), "/home/");
  }

  $host = strtolower((string) ($_SERVER["HTTP_HOST"] ?? ""));
  return $host !== "" && str_contains($host, "mylder.mx");
}

function dbIsXamppLocalSettingsFile(string $path): bool {
  if (!is_file($path)) {
    return false;
  }

  $src = (string) file_get_contents($path);
  return str_contains($src, "MYLDER_DB_USER_OVERRIDE")
    && preg_match('/MYLDER_DB_USER_OVERRIDE"\]\s*=\s*"root"/', $src) === 1;
}

function dbShouldLoadLocalSettings(): bool {
  $localOverride = __DIR__ . "/settings.local.php";
  if (!is_file($localOverride)) {
    return false;
  }

  // En producción: ignorar solo el settings.local.php de XAMPP (root sin pass).
  // Si tienes overrides de producción en ese archivo, sí se cargan.
  if (dbIsProductionHost() && dbIsXamppLocalSettingsFile($localOverride)) {
    return false;
  }

  return true;
}

/** @return list<string> */
function dbSettingsCandidates(): array {
  return [
    __DIR__ . "/settings.php",
    dirname(__DIR__) . "/secure/settings.php",
    dirname(__DIR__) . "/secure/mylder-settings.php",
    dirname(__DIR__) . "/.config/settings.php",
    dirname(__DIR__) . "/.config/mylder-settings.php",
    "/home/bnfivdwn/secure/settings.php",
    "/home/bnfivdwn/secure/mylder-settings.php",
    "/home/bnfivdwn/.config/settings.php",
    "/home/bnfivdwn/.config/mylder-settings.php",
    "/home/bnflcvdn/secure/settings.php",
    "/home/bnflcvdn/secure/mylder-settings.php",
  ];
}

function dbSettingsSource(): string {
  return (string) ($GLOBALS["MYLDER_DB_SETTINGS_SOURCE"] ?? "");
}

function dbLoadSettings(): void {
  static $loaded = false;
  if ($loaded) {
    return;
  }

  foreach (dbSettingsCandidates() as $candidate) {
    if (!is_file($candidate)) {
      continue;
    }

    require_once $candidate;

    $localOverride = __DIR__ . "/settings.local.php";
    if (dbShouldLoadLocalSettings()) {
      require_once $localOverride;
    }

    $GLOBALS["MYLDER_DB_SETTINGS_SOURCE"] = $candidate;
    if (is_file($localOverride) && dbShouldLoadLocalSettings()) {
      $GLOBALS["MYLDER_DB_SETTINGS_SOURCE"] .= " + settings.local.php";
    }

    $loaded = true;
    return;
  }

  throw new RuntimeException("No se encontró settings.php con credenciales DB.");
}

function dbHost(): string {
  if (!empty($GLOBALS["MYLDER_DB_HOST_OVERRIDE"])) {
    return (string) $GLOBALS["MYLDER_DB_HOST_OVERRIDE"];
  }
  return defined("DB_HOST") ? (string) DB_HOST : "localhost";
}

function dbName(): string {
  if (!empty($GLOBALS["MYLDER_DB_NAME_OVERRIDE"])) {
    return (string) $GLOBALS["MYLDER_DB_NAME_OVERRIDE"];
  }
  return defined("DB_NAME") ? (string) DB_NAME : "";
}

function dbUser(): string {
  if (!empty($GLOBALS["MYLDER_DB_USER_OVERRIDE"])) {
    return (string) $GLOBALS["MYLDER_DB_USER_OVERRIDE"];
  }
  return defined("DB_USER") ? (string) DB_USER : "";
}

function dbPass(): string {
  if (array_key_exists("MYLDER_DB_PASS_OVERRIDE", $GLOBALS)) {
    return (string) $GLOBALS["MYLDER_DB_PASS_OVERRIDE"];
  }
  return defined("DB_PASS") ? (string) DB_PASS : "";
}

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  dbLoadSettings();

  $name = dbName();
  $user = dbUser();
  if ($name === "" || $user === "") {
    throw new RuntimeException("Faltan credenciales DB en settings.php");
  }

  $charset = defined("DB_CHARSET") ? (string) DB_CHARSET : "utf8mb4";
  $host = dbHost();
  $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

  $pdo = new PDO($dsn, $user, dbPass(), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  $tz = defined("CRM_TIMEZONE") ? (string) CRM_TIMEZONE : "America/Mexico_City";
  date_default_timezone_set($tz);

  try {
    $pdo->exec("SET time_zone = 'America/Mexico_City'");
  } catch (Throwable) {
    try {
      $pdo->exec("SET time_zone = '-06:00'");
    } catch (Throwable) {
    }
  }

  return $pdo;
}

function dbNormalizePhone(string $phone): string {
  return preg_replace("/\D+/", "", $phone) ?? "";
}

function dbParseRating(?string $value): ?float {
  if ($value === null || trim($value) === "") {
    return null;
  }
  $normalized = str_replace(",", ".", trim($value));
  if (!is_numeric($normalized)) {
    return null;
  }
  return round((float) $normalized, 1);
}

function dbSlugify(string $text): string {
  $text = mb_strtolower(trim($text), "UTF-8");
  $text = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text) ?: $text;
  $text = preg_replace("/[^a-z0-9]+/", "-", $text) ?? $text;
  return trim($text, "-") ?: "categoria";
}
