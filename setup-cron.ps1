# MeetDesk Cron Jobs Setup
Write-Host "MeetDesk Cron Jobs Setup" -ForegroundColor Cyan

# Check admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: Run as Administrator!" -ForegroundColor Red
    exit 1
}

# Find PHP
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
if (-not (Test-Path $phpPath)) {
    $phpDir = Get-ChildItem "C:\laragon\bin\php" -Directory | Select-Object -First 1
    if ($phpDir) {
        $phpPath = Join-Path $phpDir.FullName "php.exe"
    }
}

if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found!" -ForegroundColor Red
    exit 1
}

Write-Host "Found PHP: $phpPath" -ForegroundColor Green

# Paths
$reminderScript = "C:\laragon\www\MD\cron\send-reminders-cron.php"
$cleanupScript = "C:\laragon\www\MD\cron\cleanup-expired-cron.php"

# Remove old tasks
Get-ScheduledTask -TaskName "MeetDesk*" -ErrorAction SilentlyContinue | Unregister-ScheduledTask -Confirm:$false -ErrorAction SilentlyContinue

# Task 1: Send Reminders
$action1 = New-ScheduledTaskAction -Execute $phpPath -Argument $reminderScript
$trigger1 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 30)
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName "MeetDesk - Send Reminders" -Action $action1 -Trigger $trigger1 -Settings $settings1 -Description "Send meeting reminders" -Force | Out-Null
Write-Host "Created: MeetDesk - Send Reminders" -ForegroundColor Green

# Task 2: Cleanup
$action2 = New-ScheduledTaskAction -Execute $phpPath -Argument $cleanupScript
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
Register-ScheduledTask -TaskName "MeetDesk - Cleanup Expired" -Action $action2 -Trigger $trigger2 -Settings $settings2 -Description "Cleanup expired meetings" -Force | Out-Null
Write-Host "Created: MeetDesk - Cleanup Expired" -ForegroundColor Green

Write-Host "`nSetup Complete!" -ForegroundColor Green
Write-Host "Open Task Scheduler to verify: taskschd.msc" -ForegroundColor Cyan
