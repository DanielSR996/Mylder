<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(204);
  exit;
}

$settingsCandidates = [
  __DIR__ . "/settings.php",
  dirname(__DIR__) . "/secure/settings.php",
  dirname(__DIR__) . "/secure/mylder-settings.php",
  dirname(__DIR__) . "/.config/settings.php",
  dirname(__DIR__) . "/.config/mylder-settings.php",
  "/home/bnflcvdn/secure/settings.php",
  "/home/bnflcvdn/secure/mylder-settings.php",
  "/home/bnflcvdn/.config/settings.php",
  "/home/bnflcvdn/.config/mylder-settings.php"
];

$settingsLoaded = false;
foreach ($settingsCandidates as $candidate) {
  if (is_file($candidate)) {
    require_once $candidate;
    $settingsLoaded = true;
    break;
  }
}

if (!$settingsLoaded) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "No se encontró settings.php (ruta privada).",
    "code" => "SETTINGS_NOT_FOUND"
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

const ACTION_AVAILABILITY = "availability";
const ACTION_RESERVE = "reserve";
const ACTION_CONTACT = "contact";

$isPost = $_SERVER["REQUEST_METHOD"] === "POST";
$input = [];

if ($isPost) {
  $contentType = strtolower((string) ($_SERVER["CONTENT_TYPE"] ?? ""));
  if (strpos($contentType, "application/json") !== false) {
    $raw = file_get_contents("php://input") ?: "{}";
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      respondError("INVALID_JSON", "JSON de entrada inválido", 400);
    }
    $input = $decoded;
  } else {
    $input = $_POST;
  }
} else {
  $input = $_GET;
}

$action = strtolower(trim((string) ($input["action"] ?? "")));
if ($action === "" && isset($input["date"], $input["time"])) {
  $action = ACTION_RESERVE;
} elseif ($action === "" && isset($input["date"])) {
  $action = ACTION_AVAILABILITY;
}

if (!in_array($action, [ACTION_AVAILABILITY, ACTION_RESERVE, ACTION_CONTACT], true)) {
  respondError("UNSUPPORTED_ACTION", "Acción no soportada", 400);
}

if (($action === ACTION_RESERVE || $action === ACTION_CONTACT) && !$isPost) {
  respondError("INVALID_METHOD", "Esta acción requiere método POST", 405);
}

if (!is_array(PROXY_RATE_LIMIT) || !isset(PROXY_RATE_LIMIT["window_seconds"], PROXY_RATE_LIMIT["max_requests"])) {
  respondError("PROXY_MISCONFIGURED", "Rate limit mal configurado", 500);
}

applyRateLimit($action, (int) PROXY_RATE_LIMIT["window_seconds"], (int) PROXY_RATE_LIMIT["max_requests"]);

if ($action === ACTION_AVAILABILITY) {
  $date = trim((string) ($input["date"] ?? ""));
  if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    respondError("INVALID_DATE", "Fecha inválida", 422);
  }

  $payload = [
    "action" => ACTION_AVAILABILITY,
    "token" => APPS_SCRIPT_TOKEN,
    "date" => $date
  ];
  $upstream = forwardToAppsScript($payload, "GET");
  respondUpstream($upstream["body"], $upstream["http_code"]);
}

if ($action === ACTION_RESERVE) {
  $payload = [
    "action" => ACTION_RESERVE,
    "token" => APPS_SCRIPT_TOKEN,
    "date" => sanitizeDate($input["date"] ?? ""),
    "time" => sanitizeTime($input["time"] ?? ""),
    "name" => sanitizeText($input["name"] ?? "", 120),
    "email" => sanitizeEmail($input["email"] ?? ""),
    "phone" => sanitizeText($input["phone"] ?? "", 40),
    "service" => sanitizeText($input["service"] ?? "", 120),
    "source" => sanitizeText($input["source"] ?? "", 120),
    "contactChannel" => sanitizeText($input["contactChannel"] ?? "", 80),
    "whatsappOptIn" => parseBool($input["whatsappOptIn"] ?? false)
  ];

  if ($payload["date"] === "" || $payload["time"] === "") {
    respondError("INVALID_DATA", "Fecha y hora son obligatorias", 422);
  }
  if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $payload["date"])) {
    respondError("INVALID_DATE", "Fecha inválida", 422);
  }
  if (!preg_match("/^([01]\d|2[0-3]):00$/", $payload["time"])) {
    respondError("INVALID_TIME", "Horario inválido", 422);
  }
  if ($payload["name"] === "" || $payload["email"] === "") {
    respondError("INVALID_DATA", "Nombre y email son obligatorios", 422);
  }

  $upstream = forwardToAppsScript($payload, "GET");
  $upstreamOk = isset($upstream["body"]["ok"]) ? (bool) $upstream["body"]["ok"] : ($upstream["http_code"] < 400);
  $n8n = $upstreamOk
    ? notifyN8n("booking_reserved", [
      "lead" => [
        "name" => $payload["name"],
        "email" => $payload["email"],
        "phone" => $payload["phone"],
        "service" => $payload["service"],
        "source" => $payload["source"],
        "contactChannel" => $payload["contactChannel"],
        "whatsappOptIn" => $payload["whatsappOptIn"]
      ],
      "booking" => [
        "date" => $payload["date"],
        "time" => $payload["time"],
        "timezone" => "America/Mexico_City"
      ],
      "upstream" => $upstream["body"]
    ])
    : ["enabled" => isN8nConfigured(), "sent" => false, "reason" => "skipped_upstream_error"];
  $responseBody = attachN8nStatus($upstream["body"], $n8n);
  respondUpstream($responseBody, $upstream["http_code"]);
}

if ($action === ACTION_CONTACT) {
  $payload = [
    "action" => ACTION_CONTACT,
    "token" => APPS_SCRIPT_TOKEN,
    "name" => sanitizeText($input["name"] ?? "", 120),
    "email" => sanitizeEmail($input["email"] ?? ""),
    "phone" => sanitizeText($input["phone"] ?? "", 40),
    "service" => sanitizeText($input["service"] ?? "", 120),
    "source" => sanitizeText($input["source"] ?? "", 120),
    "contactChannel" => sanitizeText($input["contactChannel"] ?? "", 80),
    "message" => sanitizeMultiline($input["message"] ?? "", 2200),
    "whatsappOptIn" => parseBool($input["whatsappOptIn"] ?? false)
  ];

  if ($payload["name"] === "" || $payload["email"] === "" || $payload["phone"] === "" || $payload["message"] === "") {
    respondError("INVALID_DATA", "Nombre, email, teléfono y mensaje son obligatorios", 422);
  }

  // Apps Script actual acepta contacto por GET (doGet); mantenemos proxy en POST público.
  $upstream = forwardToAppsScript($payload, "GET");
  $upstreamOk = isset($upstream["body"]["ok"]) ? (bool) $upstream["body"]["ok"] : ($upstream["http_code"] < 400);
  $n8n = $upstreamOk
    ? notifyN8n("contact_submitted", [
      "lead" => [
        "name" => $payload["name"],
        "email" => $payload["email"],
        "phone" => $payload["phone"],
        "service" => $payload["service"],
        "source" => $payload["source"],
        "contactChannel" => $payload["contactChannel"],
        "whatsappOptIn" => $payload["whatsappOptIn"]
      ],
      "contact" => [
        "message" => $payload["message"]
      ],
      "upstream" => $upstream["body"]
    ])
    : ["enabled" => isN8nConfigured(), "sent" => false, "reason" => "skipped_upstream_error"];
  $responseBody = attachN8nStatus($upstream["body"], $n8n);
  respondUpstream($responseBody, $upstream["http_code"]);
}

respondError("UNSUPPORTED_ACTION", "Acción no soportada", 400);

function sanitizeText($value, int $maxLen): string {
  $text = trim((string) $value);
  $text = preg_replace("/\s+/u", " ", $text) ?? "";
  if (mb_strlen($text) > $maxLen) {
    $text = mb_substr($text, 0, $maxLen);
  }
  return $text;
}

function sanitizeMultiline($value, int $maxLen): string {
  $text = trim((string) $value);
  $text = str_replace(["\r\n", "\r"], "\n", $text);
  if (mb_strlen($text) > $maxLen) {
    $text = mb_substr($text, 0, $maxLen);
  }
  return $text;
}

function sanitizeEmail($value): string {
  $email = trim((string) $value);
  if ($email === "") return "";
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : "";
}

function sanitizeDate($value): string {
  return trim((string) $value);
}

function sanitizeTime($value): string {
  return trim((string) $value);
}

function parseBool($value): bool {
  if (is_bool($value)) return $value;
  $normalized = strtolower(trim((string) $value));
  return in_array($normalized, ["1", "true", "yes", "si", "on"], true);
}

function isN8nConfigured(): bool {
  return defined("N8N_WEBHOOK_URL") && trim((string) N8N_WEBHOOK_URL) !== "";
}

function forwardToAppsScript(array $payload, string $method): array {
  $ch = curl_init();
  if (!$ch) {
    respondError("PROXY_ERROR", "No se pudo inicializar la conexión", 500);
  }

  if ($method === "GET") {
    $query = http_build_query($payload);
    $url = APPS_SCRIPT_URL . "?" . $query;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
  } else {
    curl_setopt($ch, CURLOPT_URL, APPS_SCRIPT_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
  }

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
  curl_setopt($ch, CURLOPT_USERAGENT, "MylderProxy/1.0");

  $result = curl_exec($ch);
  $errno = curl_errno($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno !== 0 || $result === false) {
    respondError("UPSTREAM_UNAVAILABLE", "No se pudo conectar con el servicio de agenda", 502);
  }

  $raw = trim((string) $result);
  $decoded = json_decode($raw, true);
  if (!is_array($decoded) && preg_match('/\{.*\}/s', $raw, $m)) {
    $decoded = json_decode($m[0], true);
  }
  if (!is_array($decoded)) {
    respondError("UPSTREAM_INVALID_RESPONSE", "Respuesta inválida del servicio remoto", 502);
  }

  return [
    "http_code" => $httpCode >= 400 ? $httpCode : 200,
    "body" => $decoded
  ];
}

function notifyN8n(string $eventType, array $data): array {
  $url = defined("N8N_WEBHOOK_URL") ? trim((string) N8N_WEBHOOK_URL) : "";
  if ($url === "") {
    return ["enabled" => false, "sent" => false, "reason" => "not_configured"];
  }

  if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return ["enabled" => true, "sent" => false, "reason" => "invalid_url"];
  }

  $timeout = defined("N8N_WEBHOOK_TIMEOUT_SECONDS") ? max(2, (int) N8N_WEBHOOK_TIMEOUT_SECONDS) : 4;
  $secret = defined("N8N_WEBHOOK_SECRET") ? trim((string) N8N_WEBHOOK_SECRET) : "";

  $payload = [
    "eventType" => $eventType,
    "occurredAt" => gmdate("c"),
    "source" => "mylder_web_proxy",
    "requestMeta" => [
      "ip" => getClientIp(),
      "userAgent" => substr((string) ($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 300)
    ],
    "data" => $data
  ];

  $headers = ["Content-Type: application/json"];
  if ($secret !== "") {
    $headers[] = "X-Mylder-Webhook-Secret: " . $secret;
  }

  $ch = curl_init($url);
  if (!$ch) {
    return ["enabled" => true, "sent" => false, "reason" => "curl_init_failed"];
  }

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(3, $timeout));
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

  $response = curl_exec($ch);
  $errno = curl_errno($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($errno !== 0 || $response === false) {
    return ["enabled" => true, "sent" => false, "reason" => "connection_error"];
  }

  if ($httpCode >= 200 && $httpCode < 300) {
    return ["enabled" => true, "sent" => true, "httpCode" => $httpCode];
  }

  return ["enabled" => true, "sent" => false, "reason" => "http_error", "httpCode" => $httpCode];
}

function attachN8nStatus(array $body, array $n8n): array {
  $body["n8n"] = $n8n;
  return $body;
}

function respondUpstream(array $body, int $status): void {
  http_response_code($status >= 100 ? $status : 200);
  echo json_encode($body, JSON_UNESCAPED_UNICODE);
  exit;
}

function getClientIp(): string {
  $headers = [
    "HTTP_CF_CONNECTING_IP",
    "HTTP_X_FORWARDED_FOR",
    "REMOTE_ADDR"
  ];
  foreach ($headers as $header) {
    $value = trim((string) ($_SERVER[$header] ?? ""));
    if ($value === "") continue;
    $ip = explode(",", $value)[0];
    return trim($ip);
  }
  return "0.0.0.0";
}

function applyRateLimit(string $action, int $windowSeconds, int $maxRequests): void {
  $ip = preg_replace("/[^a-zA-Z0-9:.]/", "_", getClientIp());
  $bucket = floor(time() / max(1, $windowSeconds));
  $key = hash("sha256", $action . "|" . $ip . "|" . $bucket);
  $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mylder_rl_" . $key . ".txt";
  $count = 0;
  if (file_exists($file)) {
    $content = file_get_contents($file);
    $count = (int) $content;
  }
  $count++;
  file_put_contents($file, (string) $count, LOCK_EX);
  if ($count > $maxRequests) {
    respondError("RATE_LIMITED", "Demasiadas solicitudes, intenta de nuevo más tarde", 429);
  }
}

function respondError(string $code, string $message, int $status = 400): void {
  http_response_code($status);
  echo json_encode([
    "ok" => false,
    "error" => $message,
    "code" => $code
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

