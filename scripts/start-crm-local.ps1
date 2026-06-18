$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$php = "C:\xampp\php\php.exe"
$hostAddr = "127.0.0.1"
$port = 5501

if (-not (Test-Path $php)) {
  Write-Error "No se encontró XAMPP PHP en $php"
  exit 1
}

Write-Host ""
Write-Host "=== Mylder CRM local ===" -ForegroundColor Cyan
Write-Host "Proyecto: $root"
Write-Host ""
Write-Host "IMPORTANTE: Cierra Live Server (puerto 5501) antes de continuar." -ForegroundColor Yellow
Write-Host "Live Server NO ejecuta PHP; solo este servidor sirve .php" -ForegroundColor Yellow
Write-Host ""

Write-Host "Probando BD..."
& $php "$root\api\test-db.php"
Write-Host ""
Write-Host "CRM:  http://${hostAddr}:${port}/crm/" -ForegroundColor Green
Write-Host "Login: http://${hostAddr}:${port}/crm/login.php" -ForegroundColor Green
Write-Host "Sitio: http://${hostAddr}:${port}/index.html" -ForegroundColor Green
Write-Host ""
Write-Host "Ctrl+C para detener"
Write-Host ""

& $php -S "${hostAddr}:${port}" -t $root
