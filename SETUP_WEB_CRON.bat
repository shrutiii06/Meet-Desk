@echo off
echo ========================================
echo MeetDesk - Web-Based Cron Setup
echo ========================================
echo.
echo This fixes the MongoDB CLI issue by using
echo web-based cron through Apache.
echo.
echo You only need to run this ONCE.
echo.
pause

echo Removing old broken task (if exists)...
schtasks /delete /tn "MeetDesk Send Reminders" /f >nul 2>&1

echo.
echo Creating new web-based cron task...
echo.

schtasks /create /tn "MeetDesk Send Reminders" /tr "curl -s http://localhost/MD/api/cron-trigger.php" /sc minute /mo 30 /ru SYSTEM /f

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! Web-based cron is set up!
    echo ========================================
    echo.
    echo How it works:
    echo - Task Scheduler calls curl every 30 minutes
    echo - curl requests the web endpoint
    echo - Apache PHP processes it (MongoDB works!)
    echo - Reminders are sent automatically
    echo.
    echo This runs FOREVER. You never need to do this again.
    echo.
    echo To test now:
    echo 1. Open: http://localhost/MD/api/cron-trigger.php
    echo 2. Or right-click task in Task Scheduler and click Run
    echo.
) else (
    echo.
    echo ========================================
    echo ERROR: Failed to create task
    echo ========================================
    echo.
    echo curl might not be installed.
    echo.
    echo Alternative: Use PowerShell method
    echo Run: SETUP_WEB_CRON_PS.bat instead
    echo.
)

pause
