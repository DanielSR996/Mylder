<?php
declare(strict_types=1);

require_once __DIR__ . "/schema.php";

/** Filtro opcional — por defecto todos ven la cola completa */
function prospectAgentFilter(array $user, string $alias = "p"): array {
  return ["", []];
}

function prospectCanAccess(array $user, ?array $row): bool {
  return $row !== null;
}

function prospectVisibleSql(string $alias = "p"): string {
  return crmProspectOcultoSql($alias);
}

function prospectActiveStatesSql(): string {
  $ocultoSql = crmProspectOcultoSql("p");
  return "p.estado NOT IN ('no_interesa', 'convertido') AND {$ocultoSql}";
}

function prospectOrigenBadge(?string $origen): string {
  return match ($origen) {
    "web_contacto" => "Orgánico · Web",
    "web_cita" => "Orgánico · Cita",
    default => "",
  };
}

function prospectOrigenClass(?string $origen): string {
  return in_array($origen, ["web_contacto", "web_cita"], true) ? "crm-badge-organic" : "";
}

function prospectIsOrganic(?string $origen): bool {
  return in_array($origen ?? "csv", ["web_contacto", "web_cita"], true);
}

/** @return list<array{label:string,value:string}> */
function prospectWebContextLines(array $row): array {
  if (!prospectIsOrganic($row["origen"] ?? "csv")) {
    return [];
  }

  $lines = [];
  $badge = prospectOrigenBadge($row["origen"] ?? null);
  if ($badge !== "") {
    $lines[] = ["label" => "Tipo", "value" => $badge];
  }
  if (!empty($row["email"])) {
    $lines[] = ["label" => "Email", "value" => (string) $row["email"]];
  }
  if (!empty($row["servicio_interes"])) {
    $lines[] = ["label" => "Servicio de interés", "value" => (string) $row["servicio_interes"]];
  }
  if (!empty($row["mensaje_web"])) {
    $lines[] = ["label" => "Mensaje / detalle", "value" => (string) $row["mensaje_web"]];
  }

  return $lines;
}

/** Teléfono solo dígitos para tel: / wa.me */
function prospectPhoneDigits(string $phone): string {
  return preg_replace("/\D+/", "", $phone) ?? "";
}

function prospectPhoneTel(string $phone): string {
  $digits = prospectPhoneDigits($phone);
  if ($digits === "") {
    return "";
  }
  if (strlen($digits) === 10) {
    return "+52" . $digits;
  }
  return "+" . ltrim($digits, "+");
}

function prospectPhoneWhatsApp(string $phone): string {
  $digits = prospectPhoneDigits($phone);
  if ($digits === "") {
    return "";
  }
  if (strlen($digits) === 10) {
    $digits = "52" . $digits;
  }
  return "https://wa.me/" . $digits;
}

function prospectNextOrderSql(): string {
  return "
    ORDER BY
      p.prioridad DESC,
      CASE
        WHEN p.estado = 'pensando' AND p.fecha_callback IS NOT NULL AND DATE(p.fecha_callback) <= CURDATE() THEN 0
        WHEN p.origen IN ('web_contacto', 'web_cita') AND p.estado IN ('interesado', 'pendiente') THEN 1
        WHEN p.estado = 'pendiente' THEN 2
        WHEN p.estado = 'interesado' THEN 3
        WHEN p.estado = 'no_contesta' THEN 4
        WHEN p.estado = 'buzon' THEN 5
        ELSE 6
      END,
      p.fecha_callback ASC,
      p.id ASC
  ";
}

function prospectListSelectSql(): string {
  return "
    p.*,
    c.nombre AS categoria_nombre,
    u.nombre AS agente_nombre,
    lu.nombre AS ultimo_agente_nombre,
    li.ultimo_contacto_at
  ";
}

/** @return list<string> */
function prospectCiudadesList(): array {
  $stmt = db()->query("
    SELECT DISTINCT TRIM(ciudad) AS ciudad
    FROM prospectos
    WHERE ciudad IS NOT NULL AND TRIM(ciudad) != ''
    ORDER BY ciudad ASC
  ");
  $rows = $stmt->fetchAll();
  return array_values(array_filter(array_map(
    static fn(array $r): string => trim((string) ($r["ciudad"] ?? "")),
    $rows
  )));
}

function prospectListJoinSql(): string {
  return "
    JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN usuarios u ON u.id = p.asignado_a
    LEFT JOIN (
      SELECT i.prospecto_id, i.agente_id, i.creado_en AS ultimo_contacto_at
      FROM intentos_contacto i
      INNER JOIN (
        SELECT prospecto_id, MAX(creado_en) AS max_creado
        FROM intentos_contacto
        GROUP BY prospecto_id
      ) last ON last.prospecto_id = i.prospecto_id AND last.max_creado = i.creado_en
    ) li ON li.prospecto_id = p.id
    LEFT JOIN usuarios lu ON lu.id = li.agente_id
  ";
}

/** @return list<array<string, mixed>> */
function prospectHistorialFor(int $prospectoId, int $limit = 0): array {
  $sql = "
    SELECT i.*, u.nombre AS agente_nombre
    FROM intentos_contacto i
    JOIN usuarios u ON u.id = i.agente_id
    WHERE i.prospecto_id = ?
    ORDER BY i.creado_en DESC
  ";
  if ($limit > 0) {
    $sql .= " LIMIT " . (int) $limit;
  }

  $stmt = db()->prepare($sql);
  $stmt->execute([$prospectoId]);
  return $stmt->fetchAll();
}

/**
 * Filtros compartidos entre lista de prospectos y modo llamada.
 *
 * @param array<string, mixed> $input GET, POST filters o $_GET
 * @return array{0: string, 1: list<mixed>, 2: array<string, mixed>}
 */
function prospectFiltersFromInput(array $user, array $input, bool $forNext = false): array {
  $categoriaId = (int) ($input["categoria_id"] ?? 0);
  $estado = trim((string) ($input["estado"] ?? ""));
  $ciudad = trim((string) ($input["ciudad"] ?? ""));
  $callbackHoy = !empty($input["callback_hoy"]) && (string) $input["callback_hoy"] === "1";
  $soloOrganicos = !empty($input["solo_organicos"]) && (string) $input["solo_organicos"] === "1";
  $incluirOcultos = $user["rol"] === "admin"
    && !empty($input["incluir_ocultos"])
    && (string) $input["incluir_ocultos"] === "1";

  $where = [];
  $params = [];

  if (!$incluirOcultos) {
    $where[] = prospectVisibleSql("p");
  }

  if ($soloOrganicos) {
    $where[] = "p.origen IN ('web_contacto', 'web_cita')";
  }

  if ($categoriaId > 0) {
    $where[] = "p.categoria_id = ?";
    $params[] = $categoriaId;
  }

  if ($ciudad !== "") {
    $where[] = "TRIM(p.ciudad) = ?";
    $params[] = $ciudad;
  }

  if ($estado !== "" && isset(PROSPECTO_ESTADOS[$estado])) {
    $where[] = "p.estado = ?";
    $params[] = $estado;
  } elseif ($forNext) {
    $where[] = prospectActiveStatesSql();
  }

  if ($callbackHoy) {
    $where[] = "p.fecha_callback IS NOT NULL AND DATE(p.fecha_callback) <= CURDATE()";
  }

  $meta = [
    "categoria_id" => $categoriaId > 0 ? $categoriaId : "",
    "ciudad" => $ciudad,
    "estado" => $estado,
    "callback_hoy" => $callbackHoy,
    "solo_organicos" => $soloOrganicos,
    "incluir_ocultos" => $incluirOcultos,
  ];

  $whereSql = $where !== [] ? implode(" AND ", $where) : "1=1";

  return [$whereSql, $params, $meta];
}
