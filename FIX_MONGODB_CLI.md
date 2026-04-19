# Fix MongoDB Extension for PHP CLI

## Issue
MongoDB extension is not enabled in PHP CLI, preventing automated cron jobs from running.

## Solution

### Option 1: Enable MongoDB Extension (Recommended)

1. **Open php.ini file:**
   ```
   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini
   ```

2. **Add this line** (around line 900, in the extensions section):
   ```ini
   extension=mongodb
   ```

3. **Save the file**

4. **Verify it works:**
   ```powershell
   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe -m | findstr mongodb
   ```
   Should output: `mongodb`

5. **Test cron job:**
   ```powershell
   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe C:\laragon\www\MD\cron\send-reminders-cron.php
   ```

### Option 2: Use Browser-Based Cron (Current Workaround)

If you can't enable the extension, use the browser-based tools:
- `http://localhost/MD/send-all-reminders.php` - Manual reminders
- Set up a scheduled task to call this URL using curl or PowerShell

## Automated Fix Script

Run this PowerShell script as Administrator:
```powershell
.\enable-mongodb-cli.ps1
```

This will automatically add the MongoDB extension to php.ini.
