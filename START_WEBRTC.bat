@echo off
echo ========================================
echo MeetDesk WebRTC Signaling Server
echo ========================================
echo.

cd signaling-server

echo Checking Node.js installation...
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed!
    echo.
    echo Please install Node.js from: https://nodejs.org/
    echo Recommended version: 18.x or higher
    echo.
    pause
    exit /b 1
)

echo Node.js found: 
node --version
echo.

echo Checking dependencies...
if not exist "node_modules" (
    echo Installing dependencies...
    call npm install
    echo.
)

echo Starting WebRTC signaling server...
echo Server will run on: ws://localhost:8080
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

node server.js

pause
