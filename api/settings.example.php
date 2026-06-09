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
