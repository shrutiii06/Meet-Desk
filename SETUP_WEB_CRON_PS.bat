@echo off
echo ========================================
echo MeetDesk - Web Cron (PowerShell Method)
echo ========================================
echo.
pause

echo Removing old task...
schtasks /delete /tn "MeetDesk Send Reminders" /f >nul 2>&1

echo.
echo Creating PowerShell-based task...
echo.

schtasks /create /tn "MeetDesk Send Reminders" /tr "powershell -Command \"Invoke-WebRequest -Uri 'http://localhost/MD/api/cron-trigger.php' -UseBasicParsing\"" /sc minute /mo 30 /ru SYSTEM /f

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! PowerShell web cron is set up!
    echo ========================================
    echo.
    echo Reminders will be sent every 30 minutes automatically.
    echo This works FOREVER.
    echo.
) else (
    echo.
    echo ERROR: Failed to create task
    echo Please run as Administrator
    echo.
)

pause
