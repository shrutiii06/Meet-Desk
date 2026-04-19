# Enable MongoDB Extension in PHP CLI
# Run as Administrator

Write-Host "Enabling MongoDB Extension for PHP CLI" -ForegroundColor Cyan

$phpIniPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini"

if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: php.ini not found at $phpIniPath" -ForegroundColor Red
    exit 1
}

# Check if already enabled
$content = Get-Content $phpIniPath
if ($content -match "^extension=mongodb") {
    Write-Host "MongoDB extension already enabled!" -ForegroundColor Green
    exit 0
}

# Backup php.ini
$backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Copy-Item $phpIniPath $backupPath
Write-Host "Backed up php.ini to: $backupPath" -ForegroundColor Yellow

# Add MongoDB extension
Add-Content $phpIniPath "`nextension=mongodb"
Write-Host "Added 'extension=mongodb' to php.ini" -ForegroundColor Green

# Verify
$phpExe = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
$result = & $phpExe -m 2>&1 | Select-String "mongodb"

if ($result) {
    Write-Host "`nSUCCESS! MongoDB extension is now enabled." -ForegroundColor Green
    Write-Host "You can now run cron jobs from command line." -ForegroundColor Green
} else {
    Write-Host "`nWARNING: Extension added but not detected. You may need to restart services." -ForegroundColor Yellow
}

Write-Host "`nTest the cron job with:" -ForegroundColor Cyan
Write-Host "$phpExe C:\laragon\www\MD\cron\send-reminders-cron.php" -ForegroundColor White
