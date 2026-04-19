# MeetDesk - Automated Cron Jobs Setup
# Run this script as Administrator

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  MeetDesk Cron Jobs Setup" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# Auto-detect PHP path
$phpPath = ""
$possiblePaths = @(
    "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe",
    "C:\laragon\bin\php\php-8.3.0-Win32-vs16-x64\php.exe"
)

foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $phpPath = $path
        break
    }
}

# If not found, search for any PHP installation
if (-not $phpPath) {
    $phpDir = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($phpDir) {
        $phpPath = Join-Path $phpDir.FullName "php.exe"
    }
}

if (-not $phpPath -or -not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found!" -ForegroundColor Red
    Write-Host "Please install PHP via Laragon or update the path in this script." -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "Found PHP: $phpPath" -ForegroundColor Green

# Script paths
$projectPath = "C:\laragon\www\MD"
$reminderScript = Join-Path $projectPath "cron\send-reminders-cron.php"
$cleanupScript = Join-Path $projectPath "cron\cleanup-expired-cron.php"

# Verify scripts exist
if (-not (Test-Path $reminderScript)) {
    Write-Host "ERROR: Reminder script not found at $reminderScript" -ForegroundColor Red
    pause
    exit 1
}

if (-not (Test-Path $cleanupScript)) {
    Write-Host "ERROR: Cleanup script not found at $cleanupScript" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "Scripts verified successfully`n" -ForegroundColor Green

# Remove existing tasks if they exist
Write-Host "Removing old tasks (if any)..." -ForegroundColor Yellow
Get-ScheduledTask -TaskName "MeetDesk*" -ErrorAction SilentlyContinue | Unregister-ScheduledTask -Confirm:$false -ErrorAction SilentlyContinue

# Task 1: Send Meeting Reminders (Every 30 minutes)
Write-Host "`nCreating Task 1: Send Meeting Reminders..." -ForegroundColor Yellow
$task1Name = "MeetDesk - Send Reminders"
$action1 = New-ScheduledTaskAction -Execute $phpPath -Argument $reminderScript
$trigger1 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 30)
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$principal1 = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $task1Name -Action $action1 -Trigger $trigger1 -Settings $settings1 -Principal $principal1 -Description "Sends email reminders 30 minutes before meetings" -Force | Out-Null
Write-Host "✓ Created: $task1Name (runs every 30 minutes)" -ForegroundColor Green

# Task 2: Cleanup Expired Meetings (Every 1 hour)
Write-Host "`nCreating Task 2: Cleanup Expired Meetings..." -ForegroundColor Yellow
$task2Name = "MeetDesk - Cleanup Expired"
$action2 = New-ScheduledTaskAction -Execute $phpPath -Argument $cleanupScript
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$principal2 = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $task2Name -Action $action2 -Trigger $trigger2 -Settings $settings2 -Principal $principal2 -Description "Removes expired meetings from database" -Force | Out-Null
Write-Host "✓ Created: $task2Name (runs every 1 hour)" -ForegroundColor Green

# Verify tasks were created
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Verification" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$tasks = Get-ScheduledTask -TaskName "MeetDesk*" -ErrorAction SilentlyContinue
if ($tasks) {
    Write-Host "Successfully created tasks:" -ForegroundColor Green
    foreach ($task in $tasks) {
        Write-Host "  ✓ $($task.TaskName)" -ForegroundColor Green
        Write-Host "    State: $($task.State)" -ForegroundColor Gray
    }
} else {
    Write-Host "ERROR: No tasks were created!" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Setup Complete!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Cron jobs are now active and will run automatically." -ForegroundColor Green
Write-Host "`nTo view tasks in Task Scheduler:" -ForegroundColor Yellow
Write-Host "  1. Press Win + R" -ForegroundColor Gray
Write-Host "  2. Type: taskschd.msc" -ForegroundColor Gray
Write-Host "  3. Look for 'MeetDesk' tasks`n" -ForegroundColor Gray

Write-Host "Log files will be created in:" -ForegroundColor Yellow
Write-Host "  $projectPath\cron\logs\`n" -ForegroundColor Gray

pause
