<?php
declare(strict_types=1);

require_once dirname(__DIR__) . "/crm/includes/import-csv.php";

$path = $argv[1] ?? "";
if ($path === "" || !is_file($path)) {
  fwrite(STDERR, "Uso: php scripts/test-csv-import-parse.php <archivo.csv>\n");
  exit(1);
}

$handle = fopen($path, "r");
$header = fgetcsv($handle);
echo "Header[0] hex: " . bin2hex((string) ($header[0] ?? "")) . "\n";

$map = importBuildColumnMap($header);
print_r($map);

$row = fgetcsv($handle);
$maps = importGetCol($row, $map, "link_google_maps");
echo "ciudad raw=" . json_encode(importGetCol($row, $map, "ciudad")) . "\n";
echo "ciudad final=" . json_encode(importNormalizeCiudad(importGetCol($row, $map, "ciudad"), $maps)) . "\n";
echo "num_resenas=" . json_encode(importParseInt(importGetCol($row, $map, "num_resenas"))) . "\n";
echo "calificacion=" . json_encode(importGetCol($row, $map, "calificacion")) . "\n";
fclose($handle);
