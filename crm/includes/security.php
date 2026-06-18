<?php
declare(strict_types=1);

function authSendSecurityHeaders(bool $allowSameOriginEmbed = false): void {
  if (headers_sent()) {
    return;
  }
  header($allowSameOriginEmbed ? "X-Frame-Options: SAMEORIGIN" : "X-Frame-Options: DENY");
  header("X-Content-Type-Options: nosniff");
  header("Referrer-Policy: strict-origin-when-cross-origin");
  header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

function authSendPdfHeaders(string $filename, string $path): void {
  if (headers_sent()) {
    return;
  }
  header("Content-Type: application/pdf");
  header("Content-Disposition: inline; filename=\"" . str_replace('"', "", $filename) . "\"");
  header("Content-Length: " . (string) filesize($path));
  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: SAMEORIGIN");
}

function authCsrfToken(): string {
  if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
  }
  return (string) $_SESSION["csrf_token"];
}

function authCsrfField(): string {
  $token = authEsc(authCsrfToken());
  return '<input type="hidden" name="csrf_token" value="' . $token . '" />';
}

function authCsrfVerify(?string $token): bool {
  if ($token === null || $token === "" || empty($_SESSION["csrf_token"])) {
    return false;
  }
  return hash_equals((string) $_SESSION["csrf_token"], $token);
}

function authClientIp(): string {
  $ip = (string) ($_SERVER["REMOTE_ADDR"] ?? "0.0.0.0");
  return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : "0.0.0.0";
}

function authPasswordValidate(string $password): ?string {
  if (strlen($password) < 8) {
    return "La contraseña debe tener al menos 8 caracteres.";
  }
  if (!preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
    return "Usa al menos una letra y un número.";
  }
  return null;
}

function authPasswordConfirm(string $password, string $confirm): ?string {
  $err = authPasswordValidate($password);
  if ($err !== null) {
    return $err;
  }
  if (!hash_equals($password, $confirm)) {
    return "Las contraseñas no coinciden.";
  }
  return null;
}

function authLoginIsBlocked(string $email): ?string {
  try {
    $stmt = db()->prepare("
      SELECT intentos, bloqueado_hasta FROM login_intentos
      WHERE email_normalizado = ? AND ip = ? LIMIT 1
    ");
    $stmt->execute([strtolower(trim($email)), authClientIp()]);
    $row = $stmt->fetch();
    if (!$row || empty($row["bloqueado_hasta"])) {
      return null;
    }
    if (strtotime((string) $row["bloqueado_hasta"]) > time()) {
      $mins = max(1, (int) ceil((strtotime((string) $row["bloqueado_hasta"]) - time()) / 60));
      return "Demasiados intentos. Espera {$mins} minuto(s) e inténtalo de nuevo.";
    }
  } catch (Throwable) {
    return null;
  }
  return null;
}

function authLoginRegisterFailure(string $email): void {
  $emailNorm = strtolower(trim($email));
  $ip = authClientIp();
  $maxAttempts = defined("CRM_LOGIN_MAX_ATTEMPTS") ? (int) CRM_LOGIN_MAX_ATTEMPTS : 5;
  $lockMinutes = defined("CRM_LOGIN_LOCK_MINUTES") ? (int) CRM_LOGIN_LOCK_MINUTES : 15;

  try {
    $stmt = db()->prepare("
      INSERT INTO login_intentos (email_normalizado, ip, intentos, bloqueado_hasta)
      VALUES (?, ?, 1, NULL)
      ON DUPLICATE KEY UPDATE
        intentos = intentos + 1,
        bloqueado_hasta = IF(intentos + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), bloqueado_hasta)
    ");
    $stmt->execute([$emailNorm, $ip, $maxAttempts, $lockMinutes]);
  } catch (Throwable) {
    // Tabla no migrada aún — no bloquear el login
  }
}

function authLoginClearFailures(string $email): void {
  try {
    db()->prepare("DELETE FROM login_intentos WHERE email_normalizado = ? AND ip = ?")
      ->execute([strtolower(trim($email)), authClientIp()]);
  } catch (Throwable) {
  }
}

function authTokenGenerate(): string {
  return bin2hex(random_bytes(32));
}

function authTokenHash(string $token): string {
  return hash("sha256", $token);
}

function authTokenCreate(int $userId, string $tipo, int $hoursValid = 24): string {
  $token = authTokenGenerate();
  $hash = authTokenHash($token);

  db()->prepare("UPDATE password_tokens SET used_at = NOW() WHERE usuario_id = ? AND used_at IS NULL AND tipo = ?")
    ->execute([$userId, $tipo]);

  db()->prepare("
    INSERT INTO password_tokens (usuario_id, token_hash, tipo, expires_at)
    VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))
  ")->execute([$userId, $hash, $tipo, $hoursValid]);

  return $token;
}

function authTokenConsume(string $token, string $tipo): ?array {
  $hash = authTokenHash($token);
  $stmt = db()->prepare("
    SELECT t.*, u.email, u.nombre, u.activo
    FROM password_tokens t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.token_hash = ? AND t.tipo = ? AND t.used_at IS NULL AND t.expires_at > NOW()
    LIMIT 1
  ");
  $stmt->execute([$hash, $tipo]);
  $row = $stmt->fetch();
  if (!$row || !(int) $row["activo"]) {
    return null;
  }
  return $row;
}

function authTokenMarkUsed(int $tokenId): void {
  db()->prepare("UPDATE password_tokens SET used_at = NOW() WHERE id = ?")->execute([$tokenId]);
}

function authSetPassword(int $userId, string $password, bool $clearMustChange = true): void {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  if ($clearMustChange) {
    db()->prepare("UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 0 WHERE id = ?")
      ->execute([$hash, $userId]);
  } else {
    db()->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")
      ->execute([$hash, $userId]);
  }
}

function crmBaseUrl(): string {
  if (defined("CRM_BASE_URL") && trim((string) CRM_BASE_URL) !== "") {
    return rtrim((string) CRM_BASE_URL, "/");
  }
  $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
  $host = (string) ($_SERVER["HTTP_HOST"] ?? "localhost");
  $script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? "/crm/login.php"));
  if (preg_match("#^(.*?/crm)(?:/.*)?$#", $script, $m)) {
    return $scheme . "://" . $host . $m[1];
  }
  return $scheme . "://" . $host . "/crm";
}

function crmLoginUrl(): string {
  return crmBaseUrl() . "/";
}
