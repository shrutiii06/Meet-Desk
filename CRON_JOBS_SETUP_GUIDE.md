# Cron Jobs Setup Guide - MeetDesk

## 🎯 What Are Cron Jobs?

Automated tasks that run in the background:
1. **Send Reminders** - Emails sent 30 minutes before meetings
2. **Cleanup Expired** - Removes old meetings from database

---

## 🚀 Quick Setup (5 minutes)

### Step 1: Open PowerShell as Administrator
1. Press `Win + X`
2. Select **"Windows PowerShell (Admin)"** or **"Terminal (Admin)"**
3. Click "Yes" on the security prompt

### Step 2: Navigate to Project
```powershell
cd C:\laragon\www\MD
```

### Step 3: Run Setup Script
```powershell
.\install-cron-jobs.ps1
```

### Step 4: Verify
The script will:
- Auto-detect PHP installation
- Create 2 scheduled tasks
- Show success confirmation

---

## ✅ What Gets Created

### Task 1: MeetDesk - Send Reminders
- **Runs:** Every 30 minutes
- **Does:** Sends email reminders for upcoming meetings
- **Script:** `cron/send-reminders-cron.php`

### Task 2: MeetDesk - Cleanup Expired
- **Runs:** Every 1 hour
- **Does:** Removes expired meetings from database
- **Script:** `cron/cleanup-expired-cron.php`

---

## 📊 View Tasks in Task Scheduler

1. Press `Win + R`
2. Type: `taskschd.msc`
3. Press Enter
4. Look for tasks starting with "MeetDesk"

---

## 📝 Log Files

Logs are automatically created in:
```
C:\laragon\www\MD\cron\logs\
```

Files:
- `send_reminders_YYYY-MM-DD.log`
- `cleanup_expired_YYYY-MM-DD.log`

---

## 🧪 Manual Testing

### Test Send Reminders
```powershell
cd C:\laragon\www\MD
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe cron\send-reminders-cron.php
```

### Test Cleanup
```powershell
cd C:\laragon\www\MD
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe cron\cleanup-expired-cron.php
```

Check the log files after running to see results.

---

## 🔧 Troubleshooting

### "Execution Policy" Error
Run this first:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### "PHP not found" Error
Update the PHP path in `install-cron-jobs.ps1`:
```powershell
$phpPath = "C:\laragon\bin\php\YOUR_PHP_VERSION\php.exe"
```

### Tasks Not Running
1. Open Task Scheduler (`taskschd.msc`)
2. Right-click the task
3. Select "Run"
4. Check log files for errors

### No Logs Created
1. Check folder exists: `C:\laragon\www\MD\cron\logs\`
2. Create it manually if missing
3. Ensure write permissions

---

## 🗑️ Remove Cron Jobs

```powershell
Get-ScheduledTask -TaskName "MeetDesk*" | Unregister-ScheduledTask -Confirm:$false
```

---

## 📋 Requirements

- ✅ Windows 10/11
- ✅ Laragon installed
- ✅ PHP 8.1+ installed
- ✅ MongoDB running
- ✅ Administrator access

---

## 🎉 Success Indicators

After setup, you should see:
- ✓ 2 tasks in Task Scheduler
- ✓ Tasks show "Ready" status
- ✓ Log files created after first run
- ✓ Email reminders sent for upcoming meetings

---

**Ready to set up? Run the script now!**
