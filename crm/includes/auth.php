<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/api/db.php";
require_once __DIR__ . "/security.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start([
    "cookie_httponly" => true,
    "cookie_samesite" => "Lax",
    "use_strict_mode" => true,
    "cookie_secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
  ]);
}

$crmScript = basename((string) ($_SERVER["SCRIPT_NAME"] ?? ""));
$allowPdfEmbed = in_array($crmScript, ["doc.php", "cotizacion-pdf.php", "guia-doc-pdf.php"], true);
authSendSecurityHeaders($allowPdfEmbed);

function authUser(): ?array {
  if (empty($_SESSION["user_id"])) {
    return null;
  }
  return [
    "id" => (int) $_SESSION["user_id"],
    "nombre" => (string) ($_SESSION["user_nombre"] ?? ""),
    "email" => (string) ($_SESSION["user_email"] ?? ""),
    "rol" => (string) ($_SESSION["user_rol"] ?? "agente"),
    "must_change_password" => !empty($_SESSION["must_change_password"]),
  ];
}

function authIsLoggedIn(): bool {
  return authUser() !== null;
}

function authIsAdmin(): bool {
  $user = authUser();
  return $user !== null && $user["rol"] === "admin";
}

function requireLogin(bool $allowPasswordChangePage = false): array {
  $user = authUser();
  if ($user === null) {
    if (crmIsApiRequest()) {
      authJson(["ok" => false, "error" => "Sesión expirada. Vuelve a iniciar sesión."], 401);
    }
    header("Location: " . crmLoginUrl());
    exit;
  }
  if (!$allowPasswordChangePage && !empty($_SESSION["must_change_password"])) {
    if (crmIsApiRequest()) {
      authJson(["ok" => false, "error" => "Debes cambiar tu contraseña.", "code" => "MUST_CHANGE_PASSWORD"], 403);
    }
    header("Location: cambiar-contrasena.php");
    exit;
  }

  if (!function_exists("authTouchPresence")) {
    require_once __DIR__ . "/presence.php";
  }
  authTouchPresence();

  return $user;
}

function crmIsApiRequest(): bool {
  $script = (string) ($_SERVER["SCRIPT_NAME"] ?? "");
  if (str_contains($script, "/api/")) {
    return true;
  }
  $accept = (string) ($_SERVER["HTTP_ACCEPT"] ?? "");
  return str_contains($accept, "application/json");
}

function requireRole(string $role): array {
  $user = requireLogin();
  if ($user["rol"] !== $role) {
    if (crmIsApiRequest()) {
      authJson(["ok" => false, "error" => "Acceso denegado"], 403);
    }
    http_response_code(403);
    echo "Acceso denegado.";
    exit;
  }
  return $user;
}

/** @return array{ok:bool,error?:string,must_change?:bool} */
function authAttemptLogin(string $email, string $password): array {
  $email = trim(strtolower($email));

  try {
    $blocked = authLoginIsBlocked($email);
    if ($blocked !== null) {
      return ["ok" => false, "error" => $blocked];
    }

    try {
      $stmt = db()->prepare("
        SELECT id, nombre, email, password_hash, rol, activo, debe_cambiar_password
        FROM usuarios WHERE LOWER(email) = ? LIMIT 1
      ");
      $stmt->execute([$email]);
      $row = $stmt->fetch();
    } catch (Throwable) {
      $stmt = db()->prepare("
        SELECT id, nombre, email, password_hash, rol, activo
        FROM usuarios WHERE LOWER(email) = ? LIMIT 1
      ");
      $stmt->execute([$email]);
      $row = $stmt->fetch();
      if ($row) {
        $row["debe_cambiar_password"] = 0;
      }
    }

    if (!$row || !(int) $row["activo"]) {
      authLoginRegisterFailure($email);
      return ["ok" => false, "error" => "Credenciales incorrectas o usuario bloqueado."];
    }

    if (!password_verify($password, (string) $row["password_hash"])) {
      authLoginRegisterFailure($email);
      return ["ok" => false, "error" => "Credenciales incorrectas o usuario bloqueado."];
    }

    authLoginClearFailures($email);

    try {
      session_regenerate_id(true);
    } catch (Throwable) {
    }

    $_SESSION["user_id"] = (int) $row["id"];
    $_SESSION["user_nombre"] = (string) $row["nombre"];
    $_SESSION["user_email"] = (string) $row["email"];
    $_SESSION["user_rol"] = (string) $row["rol"];
    $_SESSION["must_change_password"] = (int) ($row["debe_cambiar_password"] ?? 0) === 1;

    try {
      require_once __DIR__ . "/presence.php";
      authMarkSessionStart((int) $row["id"]);
    } catch (Throwable) {
    }

    return [
      "ok" => true,
      "must_change" => !empty($_SESSION["must_change_password"]),
    ];
  } catch (Throwable $e) {
    error_log("CRM authAttemptLogin: " . $e->getMessage());
    return ["ok" => false, "error" => "Error al iniciar sesión. Intenta de nuevo o contacta al administrador."];
  }
}

function authLogin(string $email, string $password): bool {
  return authAttemptLogin($email, $password)["ok"];
}

function authLogout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}

function authJson(array $payload, int $code = 200): never {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function authReadJsonBody(): array {
  $raw = file_get_contents("php://input") ?: "{}";
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

function authEsc(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, "UTF-8");
}

const PROSPECTO_ESTADOS = [
  "pendiente" => "Pendiente",
  "no_contesta" => "No contesta",
  "buzon" => "Buzón",
  "no_interesa" => "No interesa",
  "pensando" => "Pensando",
  "interesado" => "Interesado",
  "reunion_agendada" => "Reunión agendada",
  "convertido" => "Convertido",
];

if (authIsLoggedIn()) {
  try {
    require_once __DIR__ . "/presence.php";
    authTouchPresence();
  } catch (Throwable) {
    // Presencia opcional
  }
}
