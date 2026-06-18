<?php
declare(strict_types=1);

require_once __DIR__ . "/schema.php";

const PRESENCE_ONLINE_MINUTES = 5;

function presenceColumnsReady(): bool {
  return crmSchemaHasColumn("usuarios", "ultima_actividad")
    && crmSchemaHasColumn("usuarios", "sesion_inicio");
}

function authForcePresenceTouch(int $userId): void {
  if (!presenceColumnsReady()) {
    return;
  }

  try {
    db()->prepare("UPDATE usuarios SET ultima_actividad = NOW() WHERE id = ?")
      ->execute([$userId]);
    $_SESSION["presence_touch"] = time();
  } catch (Throwable) {
  }
}

function authTouchPresence(bool $force = false): void {
  if (!presenceColumnsReady()) {
    return;
  }

  $user = authUser();
  if ($user === null) {
    return;
  }

  $now = time();
  $last = (int) ($_SESSION["presence_touch"] ?? 0);
  if (!$force && $now - $last < 30) {
    return;
  }

  authForcePresenceTouch($user["id"]);
}

function authMarkSessionStart(int $userId): void {
  if (!presenceColumnsReady()) {
    return;
  }

  try {
    db()->prepare("
      UPDATE usuarios SET ultima_actividad = NOW(), sesion_inicio = NOW()
      WHERE id = ?
    ")->execute([$userId]);

    unset($_SESSION["presence_touch"]);
    $_SESSION["presence_touch"] = time();
  } catch (Throwable) {
  }
}

function presenceFormatMinutes(?int $minutes, bool $prefixVisto = false): string {
  if ($minutes === null) {
    return "Sin registro";
  }

  if ($minutes <= 0) {
    return $prefixVisto ? "En línea ahora" : "Hace un momento";
  }

  if ($minutes < 60) {
    $label = $minutes === 1 ? "1 min" : "{$minutes} min";
    return $prefixVisto ? "Visto {$label}" : $label;
  }

  $hours = (int) floor($minutes / 60);
  $rest = $minutes % 60;
  $label = $rest > 0 ? "{$hours} h {$rest} min" : "{$hours} h";
  return $prefixVisto ? "Visto {$label}" : $label;
}

function presenceFormatSessionLabel(?int $minutes, string $prefix = "Conectado"): ?string {
  if ($minutes === null) {
    return null;
  }

  return $prefix . " " . presenceFormatMinutes($minutes);
}

/** @return list<array<string, mixed>> */
function presenceFetchTeam(): array {
  if (!presenceColumnsReady()) {
    return [];
  }

  $onlineMinutes = PRESENCE_ONLINE_MINUTES;
  try {
    $stmt = db()->query("
    SELECT
      id,
      nombre,
      email,
      rol,
      ultima_actividad,
      sesion_inicio,
      (
        ultima_actividad IS NOT NULL
        AND ultima_actividad >= (NOW() - INTERVAL {$onlineMinutes} MINUTE)
      ) AS online,
      CASE
        WHEN sesion_inicio IS NOT NULL
          AND ultima_actividad >= (NOW() - INTERVAL {$onlineMinutes} MINUTE)
        THEN TIMESTAMPDIFF(MINUTE, sesion_inicio, NOW())
        ELSE NULL
      END AS minutos_conectado,
      CASE
        WHEN sesion_inicio IS NOT NULL AND ultima_actividad IS NOT NULL
        THEN TIMESTAMPDIFF(MINUTE, sesion_inicio, ultima_actividad)
        ELSE NULL
      END AS minutos_ultima_sesion,
      CASE
        WHEN ultima_actividad IS NULL THEN NULL
        WHEN ultima_actividad >= (NOW() - INTERVAL {$onlineMinutes} MINUTE) THEN 0
        ELSE TIMESTAMPDIFF(MINUTE, ultima_actividad, NOW())
      END AS minutos_ausente
    FROM usuarios
    WHERE activo = 1
    ORDER BY nombre ASC
  ");
  } catch (Throwable) {
    return [];
  }

  $out = [];
  foreach ($stmt->fetchAll() as $row) {
    $online = (int) ($row["online"] ?? 0) === 1;
    $minConectado = isset($row["minutos_conectado"]) ? (int) $row["minutos_conectado"] : null;
    $minUltimaSesion = isset($row["minutos_ultima_sesion"]) ? (int) $row["minutos_ultima_sesion"] : null;
    $minAusente = isset($row["minutos_ausente"]) ? (int) $row["minutos_ausente"] : null;

    $out[] = [
      "id" => (int) $row["id"],
      "nombre" => (string) $row["nombre"],
      "email" => (string) $row["email"],
      "rol" => (string) $row["rol"],
      "online" => $online,
      "sesion_inicio" => $row["sesion_inicio"] ?? null,
      "ultima_actividad" => $row["ultima_actividad"] ?? null,
      "minutos_conectado" => $online ? $minConectado : null,
      "minutos_ultima_sesion" => !$online ? $minUltimaSesion : null,
      "conectado_desde" => $online ? presenceFormatSessionLabel($minConectado) : null,
      "duracion_ultima_sesion" => !$online && $minUltimaSesion !== null && $minUltimaSesion >= 0
        ? presenceFormatSessionLabel($minUltimaSesion, "Duró")
        : null,
      "ultima_vez" => $online
        ? "En línea ahora"
        : presenceFormatMinutes($minAusente, true),
    ];
  }

  usort($out, static function (array $a, array $b): int {
    if ($a["online"] !== $b["online"]) {
      return $a["online"] ? -1 : 1;
    }
    return strcasecmp((string) $a["nombre"], (string) $b["nombre"]);
  });

  return $out;
}
