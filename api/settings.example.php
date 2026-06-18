<?php
declare(strict_types=1);

/*
 * Copia este archivo como settings.php y rellena los valores.
 * Seguridad recomendada:
 * - Mover settings.php fuera de public_html en producción.
 */

const APPS_SCRIPT_URL = "https://script.google.com/macros/s/TU_DEPLOYMENT_ID/exec";
const APPS_SCRIPT_TOKEN = "TU_TOKEN_SECRETO";

const PROXY_RATE_LIMIT = [
  "window_seconds" => 300,
  "max_requests" => 30
];

const N8N_WEBHOOK_URL = "";
const N8N_WEBHOOK_SECRET = "";
const N8N_WEBHOOK_TIMEOUT_SECONDS = 4;

// Correos del formulario de contacto (plantillas HTML en /email-templates)
const CONTACT_NOTIFY_EMAILS = "contacto@mylder.mx,danielsilvaramirez.dsr@gmail.com";
const MAIL_FROM_EMAIL = "contacto@mylder.mx";
const MAIL_FROM_NAME = "Mylder Solutions";

// CRM Mylder — base de datos MySQL (cPanel)
// En producción PHP usa localhost; el IP del servidor es solo para acceso remoto.
const DB_HOST = "localhost";
const DB_NAME = "bnfivdwn_mylderbdA";
const DB_USER = "bnfivdwn_m1ld3rd0l1";
const DB_PASS = "TU_PASSWORD_MYSQL";
const DB_CHARSET = "utf8mb4";

// CRM — URL pública del panel (enlaces en correos de activación / recuperación)
const CRM_BASE_URL = "https://mylder.mx/crm";
const CRM_LOGIN_MAX_ATTEMPTS = 5;
const CRM_LOGIN_LOCK_MINUTES = 15;
