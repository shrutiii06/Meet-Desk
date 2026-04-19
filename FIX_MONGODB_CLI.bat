@echo off
echo ========================================
echo FIX MongoDB Extension for CLI
echo ========================================
echo.
echo This will fix the MongoDB extension so
echo automated reminders can work.
echo.
pause

echo Checking PHP CLI configuration...
echo.

set PHP_INI=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini
set PHP_EXT=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\ext

echo PHP INI: %PHP_INI%
echo PHP EXT: %PHP_EXT%
echo.

REM Check if php_mongodb.dll exists
if exist "%PHP_EXT%\php_mongodb.dll" (
    echo [OK] php_mongodb.dll found
) else (
    echo [ERROR] php_mongodb.dll NOT found!
    echo.
    echo The MongoDB extension file is missing.
    echo You need to download it from:
    echo https://pecl.php.net/package/mongodb
    echo.
    echo Or copy it from your Apache PHP folder:
    echo C:\laragon\bin\php\php-8.1.10-nts-Win32-vs16-x64\ext\php_mongodb.dll
    echo.
    pause
    exit /b 1
)

echo.
echo Checking if extension is enabled in php.ini...
findstr /C:"extension=mongodb" "%PHP_INI%" >nul 2>&1

if %errorlevel% equ 0 (
    echo [OK] extension=mongodb is in php.ini
) else (
    echo [ADDING] Adding extension=mongodb to php.ini...
    echo extension=mongodb >> "%PHP_INI%"
    echo [OK] Added to php.ini
)

echo.
echo Testing MongoDB extension...
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe -m | findstr mongodb

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! MongoDB extension is working!
    echo ========================================
    echo.
    echo Automated reminders will now work.
    echo.
) else (
    echo.
    echo ========================================
    echo FAILED! MongoDB extension still not loading
    echo ========================================
    echo.
    echo Possible issues:
    echo 1. php_mongodb.dll is for wrong PHP version
    echo 2. Missing dependencies (Visual C++ Redistributable)
    echo 3. DLL file is corrupted
    echo.
    echo Check the error message above for details.
    echo.
)

pause
