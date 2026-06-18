<?php
declare(strict_types=1);

/**
 * Ingesta leads desde formularios web (contacto / cita) al CRM.
 */

require_once __DIR__ . "/db.php";

const CRM_LEAD_PRIORIDAD_ORGANICO = 10;
const CRM_MAX_INTENTOS_SIN_RESPUESTA = 3;

function crmLeadBuildWebMessage(
  array $lead,
  string $origen,
  string $mensaje,
  string $servicio,
  string $source,
  string $canal
): string {
  $lines = [];

  if ($origen === "web_cita") {
    $fecha = trim((string) ($lead["date"] ?? ""));
    $hora = trim((string) ($lead["time"] ?? ""));
    if ($fecha !== "" && $hora !== "") {
      $lines[] = "Cita solicitada: {$fecha} a las {$hora}";
    }
  }

  if ($mensaje !== "" && !($origen === "web_cita" && str_contains($mensaje, "Reservó cita"))) {
    $lines[] = $mensaje;
  } elseif ($origen === "web_cita" && $mensaje !== "") {
    $lines[] = $mensaje;
  }

  if ($servicio !== "") {
    $lines[] = "Servicio de interés: {$servicio}";
  }
  if ($source !== "") {
    $lines[] = "Página de origen: {$source}";
  }
  if ($canal !== "") {
    $lines[] = "Prefiere contacto por: {$canal}";
  }

  return trim(implode("\n", $lines));
}

function crmOrganicCategoryId(PDO $pdo): int {
  $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = 'leads-organicos' LIMIT 1");
  $stmt->execute();
  $id = $stmt->fetchColumn();
  if ($id) {
    return (int) $id;
  }
  $pdo->prepare("
    INSERT INTO categorias (nombre, slug, descripcion, activo)
    VALUES ('Leads orgánicos', 'leads-organicos', 'Contactos desde formularios web mylder.mx', 1)
  ")->execute();
  return (int) $pdo->lastInsertId();
}

/** @return array{ok:bool,action?:string,prospecto_id?:int,error?:string} */
function crmIngestWebLead(array $lead): array {
  try {
    dbLoadSettings();
    $pdo = db();

    $nombre = trim((string) ($lead["name"] ?? ""));
    $email = trim((string) ($lead["email"] ?? ""));
    $telefono = trim((string) ($lead["phone"] ?? ""));
    $servicio = trim((string) ($lead["service"] ?? ""));
    $mensaje = trim((string) ($lead["message"] ?? ""));
    $source = trim((string) ($lead["source"] ?? ""));
    $canal = trim((string) ($lead["contactChannel"] ?? ""));
    $origen = ($lead["tipo"] ?? "") === "web_cita" ? "web_cita" : "web_contacto";
    $telNorm = dbNormalizePhone($telefono);

    if ($nombre === "" || $telNorm === "") {
      return ["ok" => false, "error" => "nombre y teléfono requeridos"];
    }

    $mensaje = crmLeadBuildWebMessage($lead, $origen, $mensaje, $servicio, $source, $canal);

    $catId = crmOrganicCategoryId($pdo);

    $find = $pdo->prepare("
      SELECT id, origen, prioridad FROM prospectos
      WHERE telefono_normalizado = ?
      ORDER BY prioridad DESC, id ASC
      LIMIT 1
    ");
    $find->execute([$telNorm]);
    $existing = $find->fetch();

    $notasExtra = "Lead orgánico web · " . date("Y-m-d H:i");
    if ($servicio !== "") {
      $notasExtra .= " · Servicio: {$servicio}";
    }

    if ($existing) {
      $update = $pdo->prepare("
        UPDATE prospectos SET
          nombre = ?,
          email = COALESCE(?, email),
          servicio_interes = COALESCE(?, servicio_interes),
          mensaje_web = ?,
          origen = ?,
          prioridad = GREATEST(prioridad, ?),
          oculto = 0,
          estado = CASE WHEN estado IN ('no_interesa', 'convertido') THEN estado ELSE 'interesado' END,
          notas = CONCAT(COALESCE(notas, ''), IF(notas IS NULL OR notas = '', '', '\n\n'), ?),
          actualizado_en = NOW()
        WHERE id = ?
      ");
      $update->execute([
        $nombre,
        $email !== "" ? $email : null,
        $servicio !== "" ? $servicio : null,
        $mensaje !== "" ? $mensaje : null,
        $origen,
        CRM_LEAD_PRIORIDAD_ORGANICO,
        $notasExtra,
        (int) $existing["id"],
      ]);
      return ["ok" => true, "action" => "updated", "prospecto_id" => (int) $existing["id"]];
    }

    $insert = $pdo->prepare("
      INSERT INTO prospectos (
        categoria_id, nombre, telefono, telefono_normalizado, email,
        servicio_interes, mensaje_web, origen, prioridad, estado, notas
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'interesado', ?)
    ");
    $insert->execute([
      $catId,
      $nombre,
      $telefono,
      $telNorm,
      $email !== "" ? $email : null,
      $servicio !== "" ? $servicio : null,
      $mensaje !== "" ? $mensaje : null,
      $origen,
      CRM_LEAD_PRIORIDAD_ORGANICO,
      $notasExtra,
    ]);

    return ["ok" => true, "action" => "created", "prospecto_id" => (int) $pdo->lastInsertId()];
  } catch (Throwable $e) {
    return ["ok" => false, "error" => $e->getMessage()];
  }
}

function crmProspectNoResponseResults(): array {
  return ["no_contesta", "buzon"];
}

function crmProspectApplyMarkSideEffects(PDO $pdo, int $prospectoId, string $resultado): void {
  if (in_array($resultado, crmProspectNoResponseResults(), true)) {
    $pdo->prepare("
      UPDATE prospectos SET
        intentos_sin_respuesta = intentos_sin_respuesta + 1,
        oculto = IF(intentos_sin_respuesta + 1 >= ?, 1, oculto)
      WHERE id = ?
    ")->execute([CRM_MAX_INTENTOS_SIN_RESPUESTA, $prospectoId]);
    return;
  }

  if (in_array($resultado, ["interesado", "pensando", "reunion_agendada", "convertido"], true)) {
    $pdo->prepare("UPDATE prospectos SET intentos_sin_respuesta = 0, oculto = 0 WHERE id = ?")
      ->execute([$prospectoId]);
  }
}

function crmProspectOrigenLabel(string $origen): string {
  return match ($origen) {
    "web_contacto" => "Orgánico · Contacto web",
    "web_cita" => "Orgánico · Cita web",
    default => "Importado CSV",
  };
}

function crmProspectIsOrganic(array $row): bool {
  return in_array($row["origen"] ?? "csv", ["web_contacto", "web_cita"], true);
}
