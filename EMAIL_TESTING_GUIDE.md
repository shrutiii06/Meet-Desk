# Email System Testing Guide

## Overview
The MeetDesk application uses Gmail SMTP for sending emails. This guide will help you verify that the email system is working correctly.

## Current Email Configuration

**Location:** `api/config/mail-config.php`

```php
SMTP Host: smtp.gmail.com
SMTP Port: 587 (TLS)
SMTP User: shrutivaishnani@gmail.com
Sender Email: shrutivaishnani@gmail.com
Sender Name: Meet Desk App
```

## Step 1: Verify Gmail App Password

The configuration file shows an app password is set. To verify it's correct:

1. Go to https://myaccount.google.com/security
2. Ensure **2-Step Verification** is enabled
3. Go to **App Passwords** section
4. If needed, create a new app password for "Mail"
5. Update `smtp_password` in `api/config/mail-config.php` if necessary

## Step 2: Run Email System Test

1. **Open the test page in your browser:**
   ```
   http://localhost/MD/test-email-system.php
   ```

2. **The test will check:**
   - ✓ Mail configuration file exists
   - ✓ Mailer class is loaded
   - ✓ Email templates are available
   - ✓ SMTP connection works
   - ✓ Test email can be sent

3. **Send a test email:**
   - Enter your email address in the form
   - Click "Send Test Email"
   - Check your inbox (and spam folder)

## Step 3: Test Individual Email Features

### A. Registration Email
1. Register a new user at `http://localhost/MD/register.html`
2. Check if verification email is received
3. Click verification link or enter token at `verify-email.html`

### B. Password Reset Email
1. Go to `http://localhost/MD/login.html`
2. Click "Forgot Password"
3. Enter your email
4. Check for password reset email with code
5. Use code to reset password

### C. Meeting Invitation Email
1. Schedule a meeting with attendees
2. Attendees should receive invitation email
3. Email should contain meeting details and join link

### D. Meeting Reminder Email
1. Schedule a meeting that starts in 35 minutes
2. Wait for cron job to run (or run manually)
3. Check if reminder email is sent 30 minutes before

### E. Meeting Change Notification
1. Schedule a meeting with attendees
2. Edit the meeting (change date/time/topic)
3. Attendees should receive change notification email

## Email Templates Available

All templates are in `api/email-templates.php`:

1. **sendEmailVerificationEmail()** - Email verification on registration
2. **sendMeetingInvitationEmail()** - Meeting invitation to attendees
3. **sendMeetingReminderEmail()** - 30-minute reminder before meeting
4. **sendMeetingChangedEmail()** - Meeting update notification
5. **sendPasswordResetEmailEnhanced()** - Password reset with code

## Troubleshooting

### Issue: "Failed to connect to SMTP server"
**Solution:**
- Check internet connection
- Verify smtp.gmail.com is accessible
- Try port 465 (SSL) instead of 587 (TLS)

### Issue: "Authentication failed"
**Solution:**
- Verify app password is correct (no spaces)
- Ensure 2-Step Verification is enabled on Gmail
- Create a new app password
- Update `smtp_password` in mail-config.php

### Issue: "Email sent but not received"
**Solution:**
- Check spam/junk folder
- Verify recipient email is correct
- Check Gmail "Sent" folder to confirm email was sent
- Wait a few minutes (email delivery can be delayed)

### Issue: "SSL/TLS connection error"
**Solution:**
- Update PHP OpenSSL extension
- Try disabling SSL verification (already set in code)
- Check firewall settings

## Manual Email Testing Commands

### Test SMTP Connection (PowerShell)
```powershell
Test-NetConnection -ComputerName smtp.gmail.com -Port 587
```

### Send Test Email via PHP CLI
```powershell
php -r "require 'api/mailer.php'; $m = new MailSender(); print_r($m->sendRegistrationWelcome('your@email.com', 'Test User'));"
```

## Email Logs

Check PHP error logs for email-related errors:
- Laragon: `C:\laragon\bin\php\[version]\logs\error.log`
- Or check Apache error log: `C:\laragon\bin\apache\[version]\logs\error.log`

## Success Criteria

✅ **Email system is working if:**
1. SMTP connection test passes
2. Test email is received in inbox
3. Registration sends verification email
4. Password reset sends code email
5. Meeting invitations are sent to attendees
6. Reminders are sent 30 minutes before meetings

## Next Steps After Email Verification

Once email system is confirmed working:
1. ✅ Set up Windows Task Scheduler for cron jobs
2. ✅ Test end-to-end user flows
3. ✅ Perform security review
4. ✅ Prepare for WebRTC implementation

---

**Need Help?**
- Check `test-email-system.php` for detailed diagnostics
- Review `api/mailer.php` for SMTP implementation
- Check `api/email-templates.php` for email content
