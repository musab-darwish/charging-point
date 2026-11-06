# Bring the app's real copy up to date with the development folder:
#   powershell -ExecutionPolicy Bypass -File scripts\windows\update.ps1
# Backs the database up first, so an update can always be undone.

$ErrorActionPreference = 'Stop'

$app = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $app

function Run([string] $what, [scriptblock] $command) {
    Write-Host "== $what"
    & $command
    if ($LASTEXITCODE -ne 0) { throw "Stopped: $what failed." }
}

Run 'Backup before updating' { php artisan backup:database --force }
Run 'Pull the latest code' { git pull --ff-only }
Run 'Install PHP libraries' { composer install --no-dev --optimize-autoloader --no-interaction }
Run 'Install front-end packages' { npm ci --no-audit --no-fund }
Run 'Build CSS and JS' { npm run build }
Run 'Update the database' { php artisan migrate --force }
Run 'Cache settings, routes and views' { php artisan optimize }

Write-Host 'Updated. Restart the server task to load the new code:'
Write-Host "  Stop-ScheduledTask 'Nuqta Shahn - server'; Start-ScheduledTask 'Nuqta Shahn - server'"
