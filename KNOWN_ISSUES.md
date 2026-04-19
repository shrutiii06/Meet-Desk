# Known Issues & Workarounds

## 1. ⚠️ Automated Cron Jobs Not Working

**Issue:** Windows Task Scheduler tasks were created but don't run automatically.

**Root Cause:** MongoDB PHP extension is not loaded in PHP CLI (command line interface).

**Error:** `Class "MongoDB\Driver\Manager" not found`

**Impact:** 
- Automated reminder emails don't send
- Expired meetings don't get cleaned up automatically

**Workaround:**
Use the browser-based manual reminder tool:
```
http://localhost/MD/send-all-reminders.php
```

**Permanent Fix:**
1. Open `C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.ini`
2. Find the line `;extension=mongodb` and remove the semicolon
3. Or add: `extension=mongodb`
4. Restart Laragon
5. Test: `php -m | findstr mongo`

---

## 2. ⏰ Server Time Issue

**Issue:** PHP server time is 5-6 hours behind actual time.

**Observed:** Script shows 16:53 (4:53 PM) when actual time is 22:23 (10:23 PM)

**Impact:**
- Reminder timing calculations are incorrect
- Cron jobs would run at wrong times

**Workaround:**
- Manual reminder script ignores time window
- Sends all pending reminders regardless of time

**Permanent Fix:**
1. Check Windows time settings
2. Verify timezone: Should be "Asia/Kolkata" (UTC+5:30)
3. Sync with internet time server
4. Or set in PHP:
   ```php
   date_default_timezone_set('Asia/Kolkata');
   ```

---

## 3. 📧 Email Sending

**Status:** ✅ Working

**Configuration:**
- SMTP: Gmail (smtp.gmail.com:587)
- Authentication: App Password
- TLS: Enabled

**Tested:**
- ✅ SMTP connection successful
- ✅ Test emails delivered
- ✅ Reminder emails sent

---

## 4. 🗑️ Delete Meeting

**Status:** ✅ Fixed

**Previous Issue:** Meetings reappeared after page refresh

**Cause:** Frontend only removed from local array, didn't call API

**Fix:** Added API call to delete from MongoDB database

---

## 5. 🔘 Join Button Disabled

**Status:** ✅ Fixed

**Previous Issue:** Join button grayed out even with valid inputs

**Cause:** Validation not running on input change

**Fix:** Added `@input` event listeners for real-time validation

---

## 6. 📱 Responsive Design

**Status:** ✅ Complete

**Pages Updated:**
- ✅ Dashboard
- ✅ Schedule
- ✅ Profile
- ✅ Join (already responsive)
- ✅ Login/Register (already responsive)

**Features:**
- Mobile header with hamburger menu
- Slide-in navigation
- Responsive layouts
- Touch-optimized controls

---

## Summary

### Working Features:
- ✅ User registration & login
- ✅ Meeting scheduling
- ✅ Meeting join
- ✅ Profile management
- ✅ Email system (SMTP)
- ✅ Manual reminder sending
- ✅ Responsive design
- ✅ Delete meetings
- ✅ Edit meetings
- ✅ Share meeting links
- ✅ Notification preferences

### Needs Attention:
- ⚠️ Enable MongoDB in PHP CLI for automated cron jobs
- ⚠️ Fix server timezone
- ⚠️ Test automated cron jobs after fixes

### Manual Workarounds Available:
- ✅ Manual reminder tool: `send-all-reminders.php`
- ✅ Email test tool: `test-email-system.php`

---

**Last Updated:** March 29, 2026
