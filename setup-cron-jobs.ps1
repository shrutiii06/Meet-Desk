# ========================================
# MeetDesk - Automated Cron Jobs Setup
# ========================================
# This script creates Windows Task Scheduler tasks for:
# 1. Send Reminders (every 30 minutes)
# 2. Cleanup Expired Meetings (every hour)
#
# IMPORTANT: Run this script as Administrator
# ========================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MeetDesk Cron Jobs Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Configuration
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
$projectPath = "C:\laragon\www\MD"
$reminderScript = "$projectPath\cron\send-reminders-cron.php"
$cleanupScript = "$projectPath\cron\cleanup-expired-cron.php"

# Verify paths exist
Write-Host "Checking paths..." -ForegroundColor Yellow

if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found at: $phpPath" -ForegroundColor Red
    Write-Host "Please update the `$phpPath variable in this script" -ForegroundColor Yellow
    
    # Try to find PHP automatically
    $phpDirs = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue
    if ($phpDirs) {
        Write-Host "`nAvailable PHP versions:" -ForegroundColor Cyan
        foreach ($dir in $phpDirs) {
            Write-Host "  - $($dir.Name)" -ForegroundColor White
        }
        Write-Host "`nUpdate line 24 in this script with the correct PHP path" -ForegroundColor Yellow
    }
    Read-Host "`nPress Enter to exit"
    exit 1
}

if (-not (Test-Path $reminderScript)) {
    Write-Host "ERROR: Reminder script not found at: $reminderScript" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

if (-not (Test-Path $cleanupScript)) {
    Write-Host "ERROR: Cleanup script not found at: $cleanupScript" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ PHP found: $phpPath" -ForegroundColor Green
Write-Host "✓ Reminder script found" -ForegroundColor Green
Write-Host "✓ Cleanup script found" -ForegroundColor Green
Write-Host ""

# ========================================
# Task 1: Send Reminders (Every 30 minutes)
# ========================================

Write-Host "Creating Task 1: Send Reminders..." -ForegroundColor Yellow

$taskName1 = "MeetDesk - Send Reminders"

# Remove existing task if it exists
$existingTask1 = Get-ScheduledTask -TaskName $taskName1 -ErrorAction SilentlyContinue
if ($existingTask1) {
    Write-Host "  Removing existing task..." -ForegroundColor Gray
    Unregister-ScheduledTask -TaskName $taskName1 -Confirm:$false
}

# Create trigger: Every 30 minutes, starting at midnight
$trigger1 = New-ScheduledTaskTrigger -Daily -At 12:00AM
$trigger1.Repetition = (New-ScheduledTaskTrigger -Once -At 12:00AM -RepetitionInterval (New-TimeSpan -Minutes 30) -RepetitionDuration ([TimeSpan]::MaxValue)).Repetition

# Create action: Run PHP script
$action1 = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$reminderScript`""

# Settings
$settings1 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -MultipleInstances IgnoreNew

# Register task
try {
    Register-ScheduledTask `
        -TaskName $taskName1 `
        -Trigger $trigger1 `
        -Action $action1 `
        -Settings $settings1 `
        -Description "Sends 30-minute meeting reminders to attendees" `
        -Force | Out-Null
    
    Write-Host "✓ Task created: $taskName1" -ForegroundColor Green
    Write-Host "  Runs every 30 minutes" -ForegroundColor Gray
} catch {
    Write-Host "✗ Failed to create task: $taskName1" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Red
}

Write-Host ""

# ========================================
# Task 2: Cleanup Expired (Every hour)
# ========================================

Write-Host "Creating Task 2: Cleanup Expired..." -ForegroundColor Yellow

$taskName2 = "MeetDesk - Cleanup Expired"

# Remove existing task if it exists
$existingTask2 = Get-ScheduledTask -TaskName $taskName2 -ErrorAction SilentlyContinue
if ($existingTask2) {
    Write-Host "  Removing existing task..." -ForegroundColor Gray
    Unregister-ScheduledTask -TaskName $taskName2 -Confirm:$false
}

# Create trigger: Every hour, starting at midnight
$trigger2 = New-ScheduledTaskTrigger -Daily -At 12:00AM
$trigger2.Repetition = (New-ScheduledTaskTrigger -Once -At 12:00AM -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration ([TimeSpan]::MaxValue)).Repetition

# Create action: Run PHP script
$action2 = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$cleanupScript`""

# Settings
$settings2 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -MultipleInstances IgnoreNew

# Register task
try {
    Register-ScheduledTask `
        -TaskName $taskName2 `
        -Trigger $trigger2 `
        -Action $action2 `
        -Settings $settings2 `
        -Description "Marks past meetings as completed and archives them" `
        -Force | Out-Null
    
    Write-Host "✓ Task created: $taskName2" -ForegroundColor Green
    Write-Host "  Runs every hour" -ForegroundColor Gray
} catch {
    Write-Host "✗ Failed to create task: $taskName2" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verify tasks were created
Write-Host "Verifying tasks..." -ForegroundColor Yellow
$task1 = Get-ScheduledTask -TaskName $taskName1 -ErrorAction SilentlyContinue
$task2 = Get-ScheduledTask -TaskName $taskName2 -ErrorAction SilentlyContinue

if ($task1) {
    Write-Host "✓ $taskName1 - Status: $($task1.State)" -ForegroundColor Green
} else {
    Write-Host "✗ $taskName1 - Not found" -ForegroundColor Red
}

if ($task2) {
    Write-Host "✓ $taskName2 - Status: $($task2.State)" -ForegroundColor Green
} else {
    Write-Host "✗ $taskName2 - Not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Open Task Scheduler (Win + R, type 'taskschd.msc')" -ForegroundColor White
Write-Host "2. Look for 'MeetDesk - Send Reminders' and 'MeetDesk - Cleanup Expired'" -ForegroundColor White
Write-Host "3. Right-click each task and select 'Run' to test manually" -ForegroundColor White
Write-Host "4. Check logs in: C:\laragon\www\MD\cron\logs\" -ForegroundColor White
Write-Host ""

# Create logs directory if it doesn't exist
$logsDir = "$projectPath\cron\logs"
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
    Write-Host "✓ Created logs directory: $logsDir" -ForegroundColor Green
    Write-Host ""
}

Write-Host "Press Enter to exit..." -ForegroundColor Gray
Read-Host
