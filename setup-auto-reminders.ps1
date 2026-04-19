# MeetDesk - Automated Reminder Setup Script
# This script sets up Windows Task Scheduler to send reminders automatically

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MeetDesk - Automated Reminder Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

Write-Host "Setting up automated meeting reminders..." -ForegroundColor Green
Write-Host ""

# Define paths
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
$scriptPath = "C:\laragon\www\MD\cron\send-reminders-cron.php"
$taskName = "MeetDesk Send Reminders"

# Check if PHP exists
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found at $phpPath" -ForegroundColor Red
    Write-Host "Please update the path in this script." -ForegroundColor Yellow
    pause
    exit 1
}

# Check if cron script exists
if (-not (Test-Path $scriptPath)) {
    Write-Host "ERROR: Cron script not found at $scriptPath" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "PHP Path: $phpPath" -ForegroundColor Gray
Write-Host "Script Path: $scriptPath" -ForegroundColor Gray
Write-Host ""

# Delete existing task if it exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create the scheduled task
Write-Host "Creating scheduled task..." -ForegroundColor Green

$action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 30) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Sends meeting reminder emails 30 minutes before meetings"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "SUCCESS! Automated reminders are now set up!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "The task will run every 30 minutes automatically." -ForegroundColor Cyan
Write-Host ""
Write-Host "To verify:" -ForegroundColor Yellow
Write-Host "1. Open Task Scheduler (taskschd.msc)" -ForegroundColor White
Write-Host "2. Look for 'MeetDesk Send Reminders'" -ForegroundColor White
Write-Host "3. Right-click and select 'Run' to test immediately" -ForegroundColor White
Write-Host ""
Write-Host "Logs will be saved to: C:\laragon\www\MD\cron\logs\" -ForegroundColor Gray
Write-Host ""

pause
