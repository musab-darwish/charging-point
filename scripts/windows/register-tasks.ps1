# Run once, from the app's real copy (not the development folder), as yourself:
#   powershell -ExecutionPolicy Bypass -File scripts\windows\register-tasks.ps1
#
# Adds two Windows scheduled tasks for the current user:
#   "Nuqta Shahn - server": starts the app at logon (start-server.ps1).
#   "Nuqta Shahn - backup": backs the database up daily at 23:00, and 5 minutes
#                           after logon in case the laptop was off at 23:00
#                           (a day already backed up is skipped).
# Remove them any time with:
#   Unregister-ScheduledTask -TaskName 'Nuqta Shahn - server','Nuqta Shahn - backup'

$ErrorActionPreference = 'Stop'

$app = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$php = (Get-Command php).Source
$user = "$env:USERDOMAIN\$env:USERNAME"

Write-Host "App folder: $app"
Write-Host "PHP:        $php"

$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Seconds 0) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive

$server = New-ScheduledTaskAction -Execute 'powershell.exe' -WorkingDirectory $app `
    -Argument "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$app\scripts\windows\start-server.ps1`" -Php `"$php`""
Register-ScheduledTask -TaskName 'Nuqta Shahn - server' -Action $server -Principal $principal -Settings $settings `
    -Trigger (New-ScheduledTaskTrigger -AtLogOn -User $user) -Force | Out-Null

$atLogon = New-ScheduledTaskTrigger -AtLogOn -User $user
$atLogon.Delay = 'PT5M'
$backup = New-ScheduledTaskAction -Execute $php -Argument 'artisan backup:database' -WorkingDirectory $app
Register-ScheduledTask -TaskName 'Nuqta Shahn - backup' -Action $backup -Principal $principal -Settings $settings `
    -Trigger @((New-ScheduledTaskTrigger -Daily -At '23:00'), $atLogon) -Force | Out-Null

Write-Host 'Done: "Nuqta Shahn - server" and "Nuqta Shahn - backup" are registered.'
