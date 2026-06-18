<?php
declare(strict_types=1);

function mailerEscape(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, "UTF-8");
}

function mailerEncodeHeader(string $text): string {
  return "=?UTF-8?B?" . base64_encode($text) . "?=";
}

function mailerRenderTemplate(string $filename, array $vars): string {
  $path = dirname(__DIR__) . "/email-templates/" . $filename;
  if (!is_file($path)) {
    throw new RuntimeException("Plantilla no encontrada: " . $filename);
  }
  $html = (string) file_get_contents($path);
  foreach ($vars as $key => $value) {
    $html = str_replace("{{" . $key . "}}", mailerEscape((string) $value), $html);
  }
  return $html;
}

function mailerPlainFromHtml(string $html): string {
  $text = preg_replace("/<br\\s*\\/?>/i", "\n", $html) ?? $html;
  $text = preg_replace("/<\\/p>/i", "\n\n", $text) ?? $text;
  $text = strip_tags($text);
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, "UTF-8");
  return trim(preg_replace("/\\n{3,}/", "\n\n", $text) ?? $text);
}

function mailerIsProductionHost(): bool {
  $host = strtolower((string) ($_SERVER["HTTP_HOST"] ?? ""));
  return $host !== "" && str_contains($host, "mylder.mx");
}

function mailerEnsureConfig(): void {
  static $done = false;
  if ($done) {
    return;
  }
  if (!defined("MAIL_FROM_EMAIL")) {
    $settings = __DIR__ . "/settings.php";
    if (is_file($settings)) {
      require_once $settings;
    }
  }
  // settings.local.php es solo para XAMPP local — nunca en mylder.mx
  $local = __DIR__ . "/settings.local.php";
  if (is_file($local) && !mailerIsProductionHost()) {
    require_once $local;
  }
  $done = true;
}

function mailerDevCatchEnabled(): bool {
  if (mailerIsProductionHost()) {
    return false;
  }
  mailerEnsureConfig();
  return defined("MAIL_DEV_CATCH") && MAIL_DEV_CATCH;
}

function mailerDevOutboxDir(): string {
  $dir = dirname(__DIR__) . "/storage/mail-outbox";
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
  return $dir;
}

function mailerDevCatchSave(string $to, string $subject, string $htmlBody): void {
  $safe = preg_replace("/[^a-z0-9._-]+/i", "-", $to) ?? "destino";
  $file = mailerDevOutboxDir() . "/" . date("Ymd-His") . "_" . trim($safe, "-") . ".html";
  $meta = "<!-- TO: " . $to . " | SUBJECT: " . $subject . " | " . date("c") . " -->\n";
  $content = $meta . $htmlBody;
  file_put_contents($file, $content);
  file_put_contents(mailerDevOutboxDir() . "/latest.html", $content);
}

function mailerSendHtml(string $to, string $subject, string $htmlBody, string $replyTo = ""): bool {
  mailerEnsureConfig();

  $fromEmail = defined("MAIL_FROM_EMAIL") ? trim((string) MAIL_FROM_EMAIL) : "contacto@mylder.mx";
  $fromName = defined("MAIL_FROM_NAME") ? trim((string) MAIL_FROM_NAME) : "Mylder Solutions";

  if ($fromEmail === "" || $to === "" || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    return false;
  }

  $plain = mailerPlainFromHtml($htmlBody);

  if (mailerDevCatchEnabled()) {
    mailerDevCatchSave($to, $subject, $htmlBody);
    return true;
  }

  $boundary = "mylder_" . md5((string) microtime(true));
  $headers = [
    "MIME-Version: 1.0",
    "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"",
    "From: " . mailerEncodeHeader($fromName) . " <" . $fromEmail . ">",
    "X-Mailer: MylderMailer/1.0"
  ];

  if ($replyTo !== "" && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
    $headers[] = "Reply-To: " . $replyTo;
  }

  $body = "--{$boundary}\r\n";
  $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n";
  $body .= "--{$boundary}\r\n";
  $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n";
  $body .= "--{$boundary}--";

  $headerString = implode("\r\n", $headers);
  $params = "-f" . $fromEmail;
  return @mail($to, mailerEncodeHeader($subject), $body, $headerString, $params);
}

function mailerFormatDisplayName(string $name): string {
  $name = trim(preg_replace("/\\s+/u", " ", $name) ?? "");
  if ($name === "") {
    return "";
  }
  if (function_exists("mb_convert_case")) {
    return mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
  }
  return ucwords(strtolower($name));
}

function mailerFormatDateLabel(string $isoDate): string {
  $months = [
    "enero", "febrero", "marzo", "abril", "mayo", "junio",
    "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
  ];
  if (!preg_match("/^(\\d{4})-(\\d{2})-(\\d{2})$/", $isoDate, $matches)) {
    return $isoDate;
  }
  $monthIndex = (int) $matches[2] - 1;
  $month = $months[$monthIndex] ?? $matches[2];
  return ((int) $matches[3]) . " de " . $month . " de " . $matches[1];
}

function mailerGetNotifyRecipients(): array {
  $raw = defined("CONTACT_NOTIFY_EMAILS")
    ? (string) CONTACT_NOTIFY_EMAILS
    : "contacto@mylder.mx,danielsilvaramirez.dsr@gmail.com";
  $valid = [];
  foreach (explode(",", $raw) as $email) {
    $email = trim($email);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $valid[] = $email;
    }
  }
  return $valid;
}

function mailerSendContactEmails(array $payload): array {
  $vars = [
    "NOMBRE" => $payload["name"] ?? "",
    "EMAIL" => $payload["email"] ?? "",
    "TELEFONO" => $payload["phone"] ?? "",
    "SERVICIO" => $payload["service"] ?? "",
    "ORIGEN" => $payload["source"] ?? "",
    "CANAL" => $payload["contactChannel"] ?? "",
    "MENSAJE" => $payload["message"] ?? ""
  ];

  $internalHtml = mailerRenderTemplate("internal-contact-notification.html", $vars);
  $confirmVars = $vars;
  $confirmVars["NOMBRE"] = mailerFormatDisplayName((string) ($payload["name"] ?? ""));
  $confirmHtml = mailerRenderTemplate("contact-confirmation.html", $confirmVars);
  $replyTo = (string) ($payload["email"] ?? "");
  $fromReply = defined("MAIL_FROM_EMAIL") ? (string) MAIL_FROM_EMAIL : "contacto@mylder.mx";

  $internalSent = false;
  foreach (mailerGetNotifyRecipients() as $recipient) {
    $ok = mailerSendHtml(
      $recipient,
      "Nuevo contacto web: " . ($payload["name"] ?? "") . " | " . ($payload["service"] ?? ""),
      $internalHtml,
      $replyTo
    );
    $internalSent = $internalSent || $ok;
  }

  $confirmSent = false;
  $leadEmail = (string) ($payload["email"] ?? "");
  if (filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
    $confirmSent = mailerSendHtml(
      $leadEmail,
      "Recibimos tu mensaje | Mylder Solutions",
      $confirmHtml,
      $fromReply
    );
  }

  return [
    "internal" => $internalSent,
    "confirmation" => $confirmSent
  ];
}

function mailerSendBookingEmails(array $payload): array {
  $dateLabel = mailerFormatDateLabel((string) ($payload["date"] ?? ""));
  $vars = [
    "NOMBRE" => $payload["name"] ?? "",
    "EMAIL" => $payload["email"] ?? "",
    "TELEFONO" => $payload["phone"] ?? "",
    "SERVICIO" => $payload["service"] ?? "",
    "ORIGEN" => $payload["source"] ?? "",
    "CANAL" => $payload["contactChannel"] ?? "",
    "FECHA" => $dateLabel,
    "HORA" => $payload["time"] ?? ""
  ];

  $internalHtml = mailerRenderTemplate("internal-booking-notification.html", $vars);
  $confirmVars = $vars;
  $confirmVars["NOMBRE"] = mailerFormatDisplayName((string) ($payload["name"] ?? ""));
  $confirmHtml = mailerRenderTemplate("booking-confirmation.html", $confirmVars);
  $replyTo = (string) ($payload["email"] ?? "");
  $fromReply = defined("MAIL_FROM_EMAIL") ? (string) MAIL_FROM_EMAIL : "contacto@mylder.mx";

  $internalSent = false;
  foreach (mailerGetNotifyRecipients() as $recipient) {
    $ok = mailerSendHtml(
      $recipient,
      "Nueva cita agendada: " . $dateLabel . " " . ($payload["time"] ?? "") . " | " . ($payload["name"] ?? ""),
      $internalHtml,
      $replyTo
    );
    $internalSent = $internalSent || $ok;
  }

  $confirmSent = false;
  $leadEmail = (string) ($payload["email"] ?? "");
  if (filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
    $confirmSent = mailerSendHtml(
      $leadEmail,
      "Reunión confirmada: " . $dateLabel . " " . ($payload["time"] ?? "") . " | Mylder Solutions",
      $confirmHtml,
      $fromReply
    );
  }

  return [
    "internal" => $internalSent,
    "confirmation" => $confirmSent
  ];
}
