# Runs the app on http://127.0.0.1:8080, for Tailscale to serve over HTTPS.
# Started at logon by the task that register-tasks.ps1 adds.
param(
    [string] $Php = 'php'
)

$app = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $app

# A few workers, so a slow request doesn't hold up the next one.
$env:PHP_CLI_SERVER_WORKERS = '4'

& $Php artisan serve --host=127.0.0.1 --port=8080
