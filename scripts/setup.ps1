# Setup script for Windows (PowerShell).
# Run: powershell -ExecutionPolicy Bypass -File scripts\setup.ps1
# Messages are ASCII-only so the script works on any Windows code page.
$ErrorActionPreference = 'Stop'

Write-Host '== 1/4 Check PHP and Composer ==' -ForegroundColor Cyan
php -v | Select-Object -First 1
composer -V | Select-Object -First 1

Write-Host '== 2/4 Install PHP dependencies (composer install) ==' -ForegroundColor Cyan
composer install --no-interaction

Write-Host '== 3/4 Environment file and application key ==' -ForegroundColor Cyan
$envCreated = $false
if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    $envCreated = $true
}
if ($envCreated -or -not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:' -Quiet)) {
    php artisan key:generate --ansi
}

Write-Host '== 4/4 Migrations and import of resources ==' -ForegroundColor Cyan
if (-not (Test-Path 'database\database.sqlite')) {
    New-Item -ItemType File -Path 'database\database.sqlite' | Out-Null
}
php artisan migrate:fresh --seed --force --ansi

Write-Host ''
Write-Host 'Done! Start the application:' -ForegroundColor Green
Write-Host '  php artisan serve'
Write-Host 'Open http://127.0.0.1:8000'
Write-Host 'Login: isivanov / papetrov / asidorova, password: password'