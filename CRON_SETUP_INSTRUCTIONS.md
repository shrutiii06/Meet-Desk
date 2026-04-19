# Cron Jobs Setup - Quick Guide

## What Are These Cron Jobs?

MeetDesk needs two automated tasks to run in the background:

1. **Send Reminders** (Every 30 minutes)
   - Checks for meetings starting in 30 minutes
   - Sends email reminders to host and attendees
   - Marks reminder as sent to avoid duplicates

2. **Cleanup Expired** (Every hour)
   - Finds meetings that have already ended
   - Marks them as "completed"
   - Keeps database clean and organized

## Automated Setup (Recommended)

### Step 1: Run PowerShell as Administrator
1. Press `Win + X`
2. Select **"Windows PowerShell (Admin)"** or **"Terminal (Admin)"**
3. Click "Yes" on the UAC prompt

### Step 2: Navigate to Project Folder
```powershell
cd C:\laragon\www\MD
```

### Step 3: Run Setup Script
```powershell
.\setup-cron-jobs.ps1
```

### Step 4: Verify Tasks Created
The script will show:
- ✓ Task created: MeetDesk - Send Reminders
- ✓ Task created: MeetDesk - Cleanup Expired

## Manual Verification

### Open Task Scheduler
1. Press `Win + R`
2. Type: `taskschd.msc`
3. Press Enter

### Find Your Tasks
Look for:
- **MeetDesk - Send Reminders** (runs every 30 min)
- **MeetDesk - Cleanup Expired** (runs every hour)

### Test Tasks Manually
1. Right-click on a task
2. Select **"Run"**
3. Check logs in: `C:\laragon\www\MD\cron\logs\`

## Logs Location

All cron job logs are saved to:
```
C:\laragon\www\MD\cron\logs\
```

Files:
- `send_reminders_YYYY-MM-DD.log`
- `cleanup_expired_YYYY-MM-DD.log`

## Troubleshooting

### Issue: "Execution policy error"
**Solution:**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Issue: "PHP not found"
**Solution:**
1. Open `setup-cron-jobs.ps1` in a text editor
2. Update line 24 with your PHP path
3. Find it with: `Get-ChildItem "C:\laragon\bin\php" -Directory`

### Issue: Tasks not running
**Solution:**
1. Open Task Scheduler
2. Right-click task → Properties
3. Check "Run whether user is logged on or not"
4. Ensure "Wake the computer to run this task" is unchecked

### Issue: No logs generated
**Solution:**
1. Manually run: `php C:\laragon\www\MD\cron\send-reminders-cron.php`
2. Check for PHP errors
3. Verify MongoDB is running: `Get-Service MongoDB`

## Testing the Cron Jobs

### Test Send Reminders
1. Schedule a meeting that starts in 35 minutes
2. Wait for the cron job to run (or run manually)
3. Check your email for reminder
4. Check log file for confirmation

### Test Cleanup Expired
1. Create a meeting with past date/time
2. Run cleanup task manually
3. Check MongoDB - meeting should be marked "completed"
4. Check log file for confirmation

## Disable/Remove Tasks

### Disable (pause) a task:
```powershell
Disable-ScheduledTask -TaskName "MeetDesk - Send Reminders"
Disable-ScheduledTask -TaskName "MeetDesk - Cleanup Expired"
```

### Remove tasks completely:
```powershell
Unregister-ScheduledTask -TaskName "MeetDesk - Send Reminders" -Confirm:$false
Unregister-ScheduledTask -TaskName "MeetDesk - Cleanup Expired" -Confirm:$false
```

## Success Criteria

✅ Cron jobs are working if:
- Both tasks appear in Task Scheduler
- Tasks show "Ready" status
- Log files are created in `cron/logs/`
- Reminders are sent 30 minutes before meetings
- Past meetings are marked as completed

---

**Need Help?** Check the detailed guide: `WINDOWS_TASK_SCHEDULER_SETUP.md`
