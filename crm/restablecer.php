<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/brand.php";

$tipo = ($_GET["tipo"] ?? "reset") === "activacion" ? "activacion" : "reset";
$token = trim((string) ($_GET["token"] ?? ""));
$error = "";
$done = false;
$tokenRow = null;

if ($token !== "") {
  $tokenRow = authTokenConsume($token, $tipo);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!authCsrfVerify($_POST["csrf_token"] ?? null)) {
    $error = "Sesión inválida. Recarga la página.";
  } else {
    $token = trim((string) ($_POST["token"] ?? ""));
    $tipo = ($_POST["tipo"] ?? "reset") === "activacion" ? "activacion" : "reset";
    $password = (string) ($_POST["password"] ?? "");
    $confirm = (string) ($_POST["password_confirm"] ?? "");

    $tokenRow = authTokenConsume($token, $tipo);
    if (!$tokenRow) {
      $error = "El enlace expiró o ya fue usado. Solicita uno nuevo.";
    } else {
      $passErr = authPasswordConfirm($password, $confirm);
      if ($passErr !== null) {
        $error = $passErr;
      } else {
        authSetPassword((int) $tokenRow["usuario_id"], $password, true);
        authTokenMarkUsed((int) $tokenRow["id"]);
        $done = true;
      }
    }
  }
}

$title = $tipo === "activacion" ? "Activar cuenta" : "Nueva contraseña";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?= authEsc($title) ?> · Mylder</title>
  <?= crmBrandHeadLinks() ?>
  <link rel="stylesheet" href="assets/crm.css?v=20260617" />
</head>
<body class="crm-login">
  <div class="crm-login__bg" aria-hidden="true"></div>
  <div class="crm-login__shell">
    <img src="<?= authEsc(CRM_LOGO_WHITE) ?>" alt="Mylder Solutions" class="crm-logo crm-logo--hero" />
    <div class="crm-login__card">
      <div class="crm-login__card-head">
        <p class="crm-login__sub"><?= authEsc($title) ?></p>
      </div>

      <?php if ($done): ?>
        <div class="crm-alert crm-alert--ok">Contraseña guardada correctamente. Ya puedes entrar.</div>
        <a href="login.php" class="crm-login__submit" style="display:block;text-align:center;text-decoration:none;margin-top:1rem">Ir al login</a>
      <?php elseif (!$tokenRow && $_SERVER["REQUEST_METHOD"] !== "POST"): ?>
        <div class="crm-login__error">Enlace inválido o expirado.</div>
        <p class="crm-login__footer-link"><a href="recuperar.php">Solicitar nuevo enlace</a></p>
      <?php else: ?>
        <?php if ($error !== ""): ?>
          <div class="crm-login__error"><?= authEsc($error) ?></div>
        <?php endif; ?>
        <?php if ($tokenRow): ?>
          <p class="crm-empty" style="margin-bottom:1rem">Hola <strong><?= authEsc($tokenRow["nombre"]) ?></strong>, elige tu contraseña.</p>
        <?php endif; ?>
        <form method="post" class="crm-form-stack">
          <?= authCsrfField() ?>
          <input type="hidden" name="token" value="<?= authEsc($token) ?>" />
          <input type="hidden" name="tipo" value="<?= authEsc($tipo) ?>" />
          <div class="crm-field">
            <label class="crm-label" for="password">Nueva contraseña</label>
            <div class="crm-password-wrap">
              <input class="crm-input crm-password-input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Mín. 8 caracteres, letra y número" />
              <button type="button" class="crm-password-toggle" data-target="password" aria-label="Mostrar contraseña">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>
          <div class="crm-field">
            <label class="crm-label" for="password_confirm">Confirmar contraseña</label>
            <div class="crm-password-wrap">
              <input class="crm-input crm-password-input" id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password" placeholder="Repite la contraseña" />
              <button type="button" class="crm-password-toggle" data-target="password_confirm" aria-label="Mostrar contraseña">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>
          <button type="submit" class="crm-login__submit">Guardar contraseña</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <script src="assets/auth.js?v=20260617"></script>
</body>
</html>
