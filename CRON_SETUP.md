# Cron Jobs Setup Guide

This guide explains how to configure the automated cron jobs for MeetDesk.

## Overview

MeetDesk uses two cron jobs:
1. **Send Reminders** - Sends email reminders 30 minutes before meetings
2. **Cleanup Expired** - Marks past meetings as completed

---

## Cron Job 1: Send Meeting Reminders

**File:** `cron/send-reminders-cron.php`

**Purpose:** Send email notifications 30 minutes before meetings start

**How it works:**
1. Checks for meetings starting within the next 35 minutes
2. That don't have reminders sent yet
3. Sends email to host and all attendees
4. Marks reminder as sent in database

**Frequency:** Every 5 minutes (gives a 5-min buffer)

### Setup Instructions

#### For Linux/Unix (cPanel, Plesk, etc.):

1. **Access Cron Tab:**
   ```bash
   crontab -e
   ```

2. **Add this line:**
   ```
   */5 * * * * /usr/bin/php /var/www/html/MD/cron/send-reminders-cron.php >> /var/www/html/MD/logs/cron-reminders.log 2>&1
   ```

3. **Save and exit** (Ctrl+X, then Y, then Enter)

#### Using cPanel:

1. Log in to cPanel
2. Go to **Advanced → Cron Jobs**
3. Set **Common Settings** to `Every 5 minutes`
4. In **Command** field, enter:
   ```
   /usr/bin/php /var/www/html/MD/cron/send-reminders-cron.php
   ```
5. Click **Add Cron Job**

#### Using Plesk:

1. Log in to Plesk
2. Go to **Scheduled Tasks**
3. Click **Add Task**
4. Set:
   - **Type:** PHP
   - **File:** `/var/www/html/MD/cron/send-reminders-cron.php`
   - **Run:** Every 5 minutes
5. Click **OK**

---

## Cron Job 2: Cleanup Expired Meetings

**File:** `cron/cleanup-expired-cron.php`

**Purpose:** Mark past meetings as "completed" status

**How it works:**
1. Finds all meetings that have already ended
2. Updates status to "completed"
3. Records completion timestamp
4. Logs the cleanup for records

**Frequency:** Every hour (at the top of each hour)

### Setup Instructions

#### For Linux/Unix (cPanel, Plesk, etc.):

1. **Access Cron Tab:**
   ```bash
   crontab -e
   ```

2. **Add this line:**
   ```
   0 * * * * /usr/bin/php /var/www/html/MD/cron/cleanup-expired-cron.php >> /var/www/html/MD/logs/cron-cleanup.log 2>&1
   ```

3. **Save and exit**

#### Using cPanel:

1. Log in to cPanel
2. Go to **Advanced → Cron Jobs**
3. Set **Common Settings** to `Once per hour`
4. In **Command** field, enter:
   ```
   /usr/bin/php /var/www/html/MD/cron/cleanup-expired-cron.php
   ```
5. Click **Add Cron Job**

#### Using Plesk:

1. Log in to Plesk
2. Go to **Scheduled Tasks**
3. Click **Add Task**
4. Set:
   - **Type:** PHP
   - **File:** `/var/www/html/MD/cron/cleanup-expired-cron.php`
   - **Run:** Every hour
5. Click **OK**

---

## For Local Development (Laragon/XAMPP)

### Option 1: Windows Task Scheduler (if using Windows)

1. **Open Task Scheduler:**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create new task for Reminders:**
   - Click **Create Task**
   - Name: `MeetDesk - Send Reminders`
   - **Triggers:** New → Daily → Repeat every 5 minutes
   - **Actions:** Start a program
   - Program: `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe`
   - Arguments: `C:\laragon\www\MD\cron\send-reminders-cron.php`
   - Click OK

3. **Create new task for Cleanup:**
   - Repeat above but:
   - Name: `MeetDesk - Cleanup Expired`
   - Triggers: Daily → Repeat every hour
   - Arguments: `C:\laragon\www\MD\cron\cleanup-expired-cron.php`

### Option 2: Manual Testing

Run the cron jobs manually to test:

```bash
# For Laragon on Windows:
"C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" C:\laragon\www\MD\cron\send-reminders-cron.php

"C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" C:\laragon\www\MD\cron\cleanup-expired-cron.php
```

---

## Testing Your Cron Jobs

### Create a test meeting that starts in 30 minutes:

1. Go to dashboard
2. Click "Schedule"
3. Pick today's date
4. Set time to 30 minutes from now
5. Save meeting

### Monitor the logs:

```bash
# View reminders log
tail -f /var/www/html/MD/logs/cron-reminders.log

# View cleanup log
tail -f /var/www/html/MD/logs/cron-cleanup.log
```

### Test reminders manually:

```bash
php /var/www/html/MD/cron/send-reminders-cron.php
```

You should see output like:
```
====================================
CRON: Send Meeting Reminders
Time: 2026-02-25 14:30:00
====================================

Meetings found: 1

Processing: Team Meeting
  Meeting ID: 1772010584581594572
  Start Time: 2026-02-25 15:00
  Host: John Doe
  ✓ Reminder email sent to host
  ✓ Marked as sent in database

====================================
Summary:
  Sent: 1
  Failed: 0
  Total: 1
====================================
```

---

## Database Updates

The cron jobs update these fields in MongoDB:

### Reminders Sent:
```javascript
{
  "reminderSent": true,
  "reminderSentAt": ISODate("2026-02-25T14:30:00Z")
}
```

### Expired Meetings:
```javascript
{
  "status": "completed",
  "completedAt": ISODate("2026-02-25T15:00:00Z"),
  "updatedAt": ISODate("2026-02-25T15:00:00Z")
}
```

---

## Troubleshooting

### Cron jobs not running?

1. **Check PHP path:** Make sure PHP executable path is correct
   ```bash
   which php
   # or
   /usr/bin/which php
   ```

2. **Check permissions:** Ensure script has execute permissions
   ```bash
   chmod +x /var/www/html/MD/cron/send-reminders-cron.php
   ```

3. **Check log files:** Look for errors
   ```bash
   cat /var/www/html/MD/logs/cron-reminders.log
   ```

4. **Test manually:** Run the script directly
   ```bash
   php /var/www/html/MD/cron/send-reminders-cron.php
   ```

5. **Check email settings:** Ensure email is configured in `api/config.php`

### Reminders not sending?

1. Verify email configuration in `api/config.php`
2. Check email templates in `api/email-templates.php`
3. Test email manually: Create a meeting starting in 1 minute, wait 5 minutes, check logs
4. Review MongoDB for reminder timestamps

### Cleanup not working?

1. Create a meeting with past date/time
2. Run cleanup manually
3. Check MongoDB for "completed" status

---

## Summary

| Job | Frequency | Purpose | File |
|-----|-----------|---------|------|
| Send Reminders | Every 5 minutes | Email notifications 30 min before meeting | `cron/send-reminders-cron.php` |
| Cleanup Expired | Every hour | Mark past meetings as completed | `cron/cleanup-expired-cron.php` |

Both jobs are essential for MeetDesk to function smoothly! 🚀

