<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . "/api/db.php";
require_once dirname(__DIR__, 2) . "/api/mailer.php";
require_once __DIR__ . "/security.php";

function crmSendPasswordResetEmail(string $to, string $nombre, string $link): bool {
  $html = mailerRenderTemplate("crm-password-reset.html", [
    "NOMBRE" => mailerFormatDisplayName($nombre),
    "LINK" => $link,
    "EXPIRA" => "24 horas",
  ]);
  return mailerSendHtml(
    $to,
    "Restablecer contraseña · Mylder",
    $html,
    defined("MAIL_FROM_EMAIL") ? (string) MAIL_FROM_EMAIL : "contacto@mylder.mx"
  );
}

function crmSendActivationEmail(string $to, string $nombre, string $link): bool {
  $html = mailerRenderTemplate("crm-account-activate.html", [
    "NOMBRE" => mailerFormatDisplayName($nombre),
    "LINK" => $link,
    "EXPIRA" => "72 horas",
  ]);
  return mailerSendHtml(
    $to,
    "Activa tu cuenta · Mylder",
    $html,
    defined("MAIL_FROM_EMAIL") ? (string) MAIL_FROM_EMAIL : "contacto@mylder.mx"
  );
}

function crmInviteUser(int $userId, string $email, string $nombre): array {
  $token = authTokenCreate($userId, "activacion", 72);
  $link = crmBaseUrl() . "/restablecer.php?tipo=activacion&token=" . urlencode($token);
  $sent = crmSendActivationEmail($email, $nombre, $link);
  return [
    "sent" => $sent,
    "dev_link" => mailerDevCatchEnabled() ? $link : null,
  ];
}

/** @return ?string Enlace directo solo en modo local (MAIL_DEV_CATCH) */
function crmRequestPasswordReset(string $email): ?string {
  $stmt = db()->prepare("SELECT id, nombre, email, activo FROM usuarios WHERE LOWER(email) = ? LIMIT 1");
  $stmt->execute([strtolower(trim($email))]);
  $user = $stmt->fetch();
  if (!$user || !(int) $user["activo"]) {
    return null;
  }
  $token = authTokenCreate((int) $user["id"], "reset", 24);
  $link = crmBaseUrl() . "/restablecer.php?tipo=reset&token=" . urlencode($token);
  crmSendPasswordResetEmail((string) $user["email"], (string) $user["nombre"], $link);
  return mailerDevCatchEnabled() ? $link : null;
}
