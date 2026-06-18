<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/includes/auth.php";
require_once dirname(__DIR__) . "/includes/presence.php";

requireRole("admin");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  authJson(["ok" => false, "error" => "Método no permitido"], 405);
}

try {
  if (!presenceColumnsReady()) {
    authJson([
      "ok" => true,
      "migration_required" => true,
      "online_count" => 0,
      "online_minutes" => PRESENCE_ONLINE_MINUTES,
      "data" => [],
      "message" => "Ejecuta sql/mylder_crm_presence.sql en phpMyAdmin para activar presencia en línea.",
    ]);
  }

  $team = presenceFetchTeam();
  $onlineCount = count(array_filter($team, static fn(array $u): bool => !empty($u["online"])));

  authJson([
    "ok" => true,
    "online_count" => $onlineCount,
    "online_minutes" => PRESENCE_ONLINE_MINUTES,
    "data" => $team,
  ]);
} catch (Throwable $e) {
  error_log("CRM presence API: " . $e->getMessage());
  authJson([
    "ok" => false,
    "error" => "No se pudo cargar el equipo en línea.",
  ], 500);
}
