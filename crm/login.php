<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/brand.php";

if (authIsLoggedIn()) {
  header("Location: dashboard.php");
  exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!authCsrfVerify($_POST["csrf_token"] ?? null)) {
    $error = "Sesión inválida. Recarga la página e inténtalo de nuevo.";
  } else {
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
      $error = "Email y contraseña son obligatorios.";
    } else {
      try {
        $result = authAttemptLogin($email, $password);
      } catch (Throwable $e) {
        error_log("CRM login error: " . $e->getMessage());
        $result = ["ok" => false, "error" => "No se pudo iniciar sesión. Intenta de nuevo."];
      }
      if ($result["ok"]) {
        if (!empty($result["must_change"])) {
          header("Location: cambiar-contrasena.php");
        } else {
          header("Location: dashboard.php");
        }
        exit;
      }
      $error = $result["error"] ?? "Credenciales incorrectas.";
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
  <title>Iniciar sesión · Mylder</title>
  <?= crmBrandHeadLinks() ?>
  <link rel="stylesheet" href="assets/crm.css?v=20260617" />
</head>
<body class="crm-login">
  <div class="crm-login__bg" aria-hidden="true"></div>
  <div class="crm-login__orb crm-login__orb--1" aria-hidden="true"></div>
  <div class="crm-login__orb crm-login__orb--2" aria-hidden="true"></div>

  <div class="crm-login__shell">
    <img src="<?= authEsc(CRM_LOGO_WHITE) ?>" alt="Mylder Solutions" class="crm-logo crm-logo--hero" />

    <div class="crm-login__card">
      <div class="crm-login__card-head">
        <p class="crm-login__sub">Panel interno · Contact center</p>
      </div>

      <?php if ($error !== ""): ?>
        <div class="crm-login__error" role="alert"><?= authEsc($error) ?></div>
      <?php endif; ?>

      <form method="post" action="" class="crm-form-stack">
        <?= authCsrfField() ?>
        <div class="crm-field">
          <label class="crm-label" for="email">Email</label>
          <input class="crm-input" id="email" name="email" type="email" required autocomplete="username"
            value="<?= authEsc((string) ($_POST["email"] ?? "")) ?>" placeholder="tu@mylder.mx" />
        </div>
        <div class="crm-field">
          <label class="crm-label" for="password">Contraseña</label>
          <div class="crm-password-wrap">
            <input class="crm-input crm-password-input" id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" />
            <button type="button" class="crm-password-toggle" data-target="password" aria-label="Mostrar contraseña">
              <svg class="icon-eye" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="crm-login__submit">Entrar</button>
        <p class="crm-login__footer-link"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
      </form>
    </div>
  </div>
  <script src="assets/auth.js?v=20260617"></script>
</body>
</html>
