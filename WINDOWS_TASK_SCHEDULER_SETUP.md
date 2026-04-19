# Windows Task Scheduler - Cron Jobs Setup

## Overview

Since Windows doesn't have cron jobs like Linux, we use **Windows Task Scheduler** to run PHP scripts at scheduled intervals.

**Two jobs to setup:**
1. `send-reminders-cron.php` - Runs every 30 minutes (sends meeting reminders)
2. `cleanup-expired-cron.php` - Runs every 1 hour (archives past meetings)

---

## Prerequisites

- Windows 10/11
- PHP installed and accessible from command line
- Laragon running (or custom PHP setup)

---

## Step 1: Find PHP Executable Path

1. **Open PowerShell as Administrator**
   - Press `Win + X` → Select "Windows PowerShell (Admin)"

2. **Find PHP path:**
   ```powershell
   where php
   ```
   
   You'll see output like:
   ```
   C:\laragon\bin\php\php-X.X.X-Win32-nts\php.exe
   ```
   
   **Copy this path** - you'll need it later.

3. **Verify PHP works:**
   ```powershell
   C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe -v
   ```

---

## Step 2: Create Scheduled Task for Reminders

### Option A: Using GUI (Easier)

1. **Open Task Scheduler**
   - Press `Win + R` → Type `taskschd.msc` → Press Enter

2. **Right-click "Task Scheduler Library"** → New → "Basic Task"

3. **Fill in Details:**
   - **Name:** `MeetDesk Send Reminders`
   - **Description:** Sends 30-minute meeting reminders
   - **Click Next**

4. **Trigger Tab:**
   - **Begin the task:** `On a schedule`
   - **Recurrence:** `Daily`
   - **Repeat task every:** `30 minutes for a duration of 1 day`
   - **Click Next**

5. **Action Tab:**
   - **Action:** `Start a program`
   - **Program/script:** 
     ```
     C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe
     ```
   - **Add arguments (optional):**
     ```
     C:\laragon\www\MD\cron\send-reminders-cron.php
     ```
   - **Click Next**

6. **Conditions Tab:**
   - **Uncheck** "Wake the computer to run this task"
   - **Click Next**

7. **Settings Tab:**
   - Leave defaults
   - **Click Finish**

### Option B: Using PowerShell (Advanced)

```powershell
# Run as Administrator

$taskName = "MeetDesk Send Reminders"
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe"
$scriptPath = "C:\laragon\www\MD\cron\send-reminders-cron.php"

$trigger = New-ScheduledTaskTrigger -Daily -At 12:00am -RepetitionInterval (New-TimeSpan -Minutes 30) -RepetitionDuration (New-TimeSpan -Days 1)
$action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName $taskName -Trigger $trigger -Action $action -Settings $settings -Force
```

---

## Step 3: Create Scheduled Task for Cleanup

### Option A: Using GUI

1. **Open Task Scheduler** → Right-click "Task Scheduler Library" → New → "Basic Task"

2. **Fill in Details:**
   - **Name:** `MeetDesk Cleanup Expired Meetings`
   - **Description:** Archives meetings that have ended
   - **Click Next**

3. **Trigger Tab:**
   - **Begin the task:** `On a schedule`
   - **Recurrence:** `Daily`
   - **Repeat task every:** `1 hour for a duration of 1 day`
   - **Click Next**

4. **Action Tab:**
   - **Program/script:** 
     ```
     C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe
     ```
   - **Add arguments:**
     ```
     C:\laragon\www\MD\cron\cleanup-expired-cron.php
     ```
   - **Click Next → Finish**

### Option B: Using PowerShell

```powershell
# Run as Administrator

$taskName = "MeetDesk Cleanup Expired Meetings"
$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe"
$scriptPath = "C:\laragon\www\MD\cron\cleanup-expired-cron.php"

$trigger = New-ScheduledTaskTrigger -Daily -At 12:00am -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Days 1)
$action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName $taskName -Trigger $trigger -Action $action -Settings $settings -Force
```

---

## Step 4: Verify Tasks Are Running

1. **Open Task Scheduler** → Look for both tasks in the library
2. **Right-click task** → **Run** (to test manually)
3. **Check logs:**
   - Open `C:\laragon\www\MD\cron\logs\` folder
   - Check latest log file: `reminders_YYYY-MM-DD.log` or `cleanup_YYYY-MM-DD.log`
   - Verify success/error messages

---

## Step 5: Monitor Task Execution

### View Task History

1. **Task Scheduler** → Click task name
2. **Go to "History" tab** to see:
   - Last run time
   - Status (Success/Failed)
   - Error codes

### Enable Detailed Logging

In **Task Scheduler → Event Viewer** section:
- **Show all tasks** to see detailed execution history
- Filter by task name to track runs

---

## 🔧 Troubleshooting

### Issue: "Task completed but found errors"

**Solution:**
- Check PHP path is correct
- Verify script permissions (give Read access to SYSTEM user)
- Check MongoDB connection is working

### Issue: Task won't run

**Solution:**
```powershell
# Test PHP script manually first
C:\laragon\bin\php\php-8.1.10-Win32-nts\php.exe "C:\laragon\www\MD\cron\send-reminders-cron.php"
```

### Issue: Can't find logs

**Solution:**
- Ensure `C:\laragon\www\MD\cron\logs\` folder exists
- Give SYSTEM user write permissions: 
  ```powershell
  icacls "C:\laragon\www\MD\cron\logs" /grant:r "SYSTEM:(F)" /t
  ```

---

## 📋 Expected Behavior

### Every 30 minutes (Reminders Job)
- Finds meetings starting in next 35 minutes
- Sends email reminders to host and attendees
- Marks `reminderSent = true` in MongoDB
- Logs status to: `cron/logs/reminders_YYYY-MM-DD.log`

### Every 1 hour (Cleanup Job)
- Finds meetings that have ended
- Updates status to `"completed"`
- Logs completion to: `cron/logs/cleanup_YYYY-MM-DD.log`

---

## 📧 Test Email Sending

Before relying on cron jobs, verify email works:

1. Visit: `http://localhost/MD/api/test-email.php`
2. Check inbox for test email
3. If no email, check SMTP settings in `api/config/mail-config.php`

---

## ✅ Checklist

- [ ] Found PHP executable path
- [ ] Created "MeetDesk Send Reminders" task (every 30 min)
- [ ] Created "MeetDesk Cleanup Expired Meetings" task (every 1 hour)
- [ ] Tested tasks manually with Task Scheduler
- [ ] Verified logs are being created
- [ ] Email is sending correctly
- [ ] Both tasks show "Success" status in Task Scheduler

---

**Questions?** Check the task's Event Viewer history for error details!
