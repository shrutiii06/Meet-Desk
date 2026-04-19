@echo off
echo ========================================
echo MeetDesk - Permanent Reminder Setup
echo ========================================
echo.
echo This will set up automated reminders FOREVER.
echo You only need to run this ONCE.
echo.
pause

echo Creating scheduled task...
echo.

schtasks /create /tn "MeetDesk Send Reminders" /tr "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe C:\laragon\www\MD\cron\send-reminders-cron.php" /sc minute /mo 30 /ru SYSTEM /f

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! Automated reminders are set up!
    echo ========================================
    echo.
    echo The task will run every 30 minutes FOREVER.
    echo You will NEVER need to run this again.
    echo.
    echo Reminders will be sent 30 minutes before meetings.
    echo.
    echo To verify, open Task Scheduler and look for:
    echo "MeetDesk Send Reminders"
    echo.
) else (
    echo.
    echo ========================================
    echo ERROR: Failed to create task
    echo ========================================
    echo.
    echo Please run this file as Administrator:
    echo 1. Right-click this file
    echo 2. Select "Run as administrator"
    echo.
)

pause
