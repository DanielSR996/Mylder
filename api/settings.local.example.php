<?php
declare(strict_types=1);

/*
 * Desarrollo local con XAMPP MySQL (recomendado).
 * Copia a settings.local.php — no subir a producción.
 *
 * Modo remoto (BD producción): descomenta el bloque REMOTO y comenta LOCAL.
 * Requiere cPanel → MySQL remoto → agregar tu IP pública.
 */

// --- LOCAL (XAMPP) ---
$GLOBALS["MYLDER_DB_HOST_OVERRIDE"] = "127.0.0.1";
$GLOBALS["MYLDER_DB_NAME_OVERRIDE"] = "mylder_crm";
$GLOBALS["MYLDER_DB_USER_OVERRIDE"] = "root";
$GLOBALS["MYLDER_DB_PASS_OVERRIDE"] = "";

// --- Correo local (XAMPP no envía mail() real) ---
// Guarda copias en storage/mail-outbox/ y muestra enlaces en recuperar/usuarios.
if (!defined("MAIL_DEV_CATCH")) {
  define("MAIL_DEV_CATCH", true);
}
if (!defined("CRM_BASE_URL")) {
  define("CRM_BASE_URL", "http://127.0.0.1:5501/crm");
}

// --- REMOTO (producción) — descomenta si prefieres BD del hosting ---
// $GLOBALS["MYLDER_DB_HOST_OVERRIDE"] = "75.102.22.162";
// unset($GLOBALS["MYLDER_DB_NAME_OVERRIDE"], $GLOBALS["MYLDER_DB_USER_OVERRIDE"]);
// unset($GLOBALS["MYLDER_DB_PASS_OVERRIDE"]);
