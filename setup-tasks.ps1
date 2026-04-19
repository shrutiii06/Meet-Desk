# MeetDesk - Automated Cron Jobs Setup
# Run as Administrator

Write-Host "MeetDesk Cron Jobs Setup" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan

# Check admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: Run as Administrator!" -ForegroundColor Red
    exit 1
}

# Paths
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
$reminderScript = "C:\laragon\www\MD\cron\send-reminders-cron.php"
$cleanupScript = "C:\laragon\www\MD\cron\cleanup-expired-cron.php"

# Verify
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found at $phpPath" -ForegroundColor Red
    exit 1
}

Write-Host "Creating tasks..." -ForegroundColor Yellow

# Task 1: Send Reminders
$task1 = "MeetDesk - Send Reminders"
Get-ScheduledTask -TaskName $task1 -ErrorAction SilentlyContinue | Unregister-ScheduledTask -Confirm:$false -ErrorAction SilentlyContinue

$action1 = New-ScheduledTaskAction -Execute $phpPath -Argument $reminderScript
$trigger1 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 30)
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName $task1 -Action $action1 -Trigger $trigger1 -Settings $settings1 -Description "Send meeting reminders" -Force
Write-Host "✓ Created: $task1" -ForegroundColor Green

# Task 2: Cleanup Expired
$task2 = "MeetDesk - Cleanup Expired"
Get-ScheduledTask -TaskName $task2 -ErrorAction SilentlyContinue | Unregister-ScheduledTask -Confirm:$false -ErrorAction SilentlyContinue

$action2 = New-ScheduledTaskAction -Execute $phpPath -Argument $cleanupScript
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName $task2 -Action $action2 -Trigger $trigger2 -Settings $settings2 -Description "Cleanup expired meetings" -Force
Write-Host "✓ Created: $task2" -ForegroundColor Green

Write-Host "`nSetup Complete!" -ForegroundColor Green
Write-Host "Open Task Scheduler to verify: taskschd.msc" -ForegroundColor Cyan
