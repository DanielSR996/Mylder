<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/brand.php";
require_once __DIR__ . "/includes/mailer-crm.php";

if (authIsLoggedIn()) {
  header("Location: dashboard.php");
  exit;
}

$error = "";
$sent = false;
$devLink = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!authCsrfVerify($_POST["csrf_token"] ?? null)) {
    $error = "Sesión inválida. Recarga la página.";
  } else {
    $email = trim((string) ($_POST["email"] ?? ""));
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Indica un email válido.";
    } else {
      try {
        $devLink = crmRequestPasswordReset($email);
        $sent = true;
      } catch (Throwable $e) {
        error_log("CRM password reset: " . $e->getMessage());
        $error = "No se pudo procesar la solicitud. Intenta más tarde.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Recuperar contraseña · Mylder</title>
  <?= crmBrandHeadLinks() ?>
  <link rel="stylesheet" href="assets/crm.css?v=20260617" />
</head>
<body class="crm-login">
  <div class="crm-login__bg" aria-hidden="true"></div>
  <div class="crm-login__shell">
    <img src="<?= authEsc(CRM_LOGO_WHITE) ?>" alt="Mylder Solutions" class="crm-logo crm-logo--hero" />
    <div class="crm-login__card">
      <div class="crm-login__card-head">
        <p class="crm-login__sub">Recuperar acceso</p>
      </div>

      <?php if ($sent): ?>
        <div class="crm-alert crm-alert--ok">
          Si el email está registrado, recibirás un enlace para restablecer tu contraseña. Revisa también spam.
        </div>
        <?php if ($devLink !== null): ?>
          <div class="crm-alert crm-alert--dev">
            <strong>Modo local:</strong> XAMPP no envía correos reales. Usa este enlace:
            <a href="<?= authEsc($devLink) ?>" class="crm-dev-link"><?= authEsc($devLink) ?></a>
            <span class="crm-field-hint">También guardado en <code>storage/mail-outbox/latest.html</code></span>
          </div>
        <?php endif; ?>
        <p class="crm-login__footer-link" style="margin-top:1rem"><a href="login.php">Volver al login</a></p>
      <?php else: ?>
        <?php if ($error !== ""): ?>
          <div class="crm-login__error"><?= authEsc($error) ?></div>
        <?php endif; ?>
        <form method="post" action="recuperar.php" class="crm-form-stack">
          <?= authCsrfField() ?>
          <div class="crm-field">
            <label class="crm-label" for="email">Tu email registrado</label>
            <input class="crm-input" id="email" name="email" type="email" required autocomplete="email" placeholder="tu@mylder.mx" />
          </div>
          <button type="submit" class="crm-login__submit">Enviar enlace</button>
          <p class="crm-login__footer-link"><a href="login.php">Volver al login</a></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
