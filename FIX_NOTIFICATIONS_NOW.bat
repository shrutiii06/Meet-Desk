@echo off
echo ========================================
echo MeetDesk - Fixing Notification Timings 
echo ========================================
echo.
echo Deleting old broken tasks...
schtasks /delete /tn "MeetDesk - Send Reminders" /f >nul 2>&1
schtasks /delete /tn "MeetDesk Send Reminders" /f >nul 2>&1

echo.
echo Creating new HIGH PRECISION Web Cron task...
echo.

:: Note /sc minute /mo 1 runs every 1 minute
schtasks /create /tn "MeetDesk Send Reminders" /tr "powershell -Command \"Invoke-WebRequest -Uri 'http://localhost/MD/api/cron-trigger.php' -UseBasicParsing\"" /sc minute /mo 1 /ru SYSTEM /f

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! High-precision notifications are fixed!
    echo ========================================
    echo.
    echo Notifications will now trigger EXACTLY 30 minutes before.
    echo.
) else (
    echo.
    echo ERROR: Failed to create task. 
    echo Did you right-click and "Run as Administrator"?
    echo Please try again as Administrator.
    echo.
)

pause
