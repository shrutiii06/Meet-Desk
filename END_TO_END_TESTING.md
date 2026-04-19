# End-to-End Testing Checklist

## 🧪 Complete Feature Testing Guide

### ✅ 1. Authentication & User Management

#### Registration
- [ ] Go to `http://localhost/MD/register.html`
- [ ] Register with new email
- [ ] Verify validation works (email format, password strength)
- [ ] Check success message
- [ ] Verify redirect to login

#### Login
- [ ] Go to `http://localhost/MD/login.html`
- [ ] Login with: `bhavnavaishnani13@gmail.com` / `test123456`
- [ ] Verify "Remember Me" checkbox works
- [ ] Check redirect to dashboard
- [ ] Verify session persists on refresh

#### Logout
- [ ] Click logout from any page
- [ ] Verify redirect to login
- [ ] Confirm session cleared
- [ ] Try accessing dashboard without login (should redirect)

---

### ✅ 2. Dashboard

#### Layout & Responsiveness
- [ ] Desktop view: Sidebar visible, proper layout
- [ ] Mobile view (resize browser): Hamburger menu appears
- [ ] Mobile menu: Slides in/out correctly
- [ ] All navigation links work

#### Quick Actions
- [ ] "Schedule Meeting" button works
- [ ] "Join Meeting" button works
- [ ] Time and date display correctly

#### Upcoming Meetings
- [ ] Shows today's meetings
- [ ] Shows tomorrow's meetings
- [ ] Meeting cards display all info (topic, time, ID)
- [ ] "Join" button works
- [ ] Expired meetings show as grayed out

---

### ✅ 3. Schedule Meeting

#### Form Validation
- [ ] Topic: Required, shows error if empty
- [ ] Date: Cannot select past dates
- [ ] Time: Validates format
- [ ] Duration: Dropdown works (30, 60, 90, 120 min)
- [ ] Timezone: Shows correct timezone

#### Public Meeting
- [ ] Toggle "Public Meeting" ON
- [ ] Attendee email field disappears
- [ ] Schedule successfully
- [ ] Verify meeting appears in list
- [ ] Check meeting ID and password generated

#### Private Meeting
- [ ] Toggle "Public Meeting" OFF
- [ ] Attendee email field appears
- [ ] Add multiple emails (comma-separated)
- [ ] Schedule successfully
- [ ] Verify attendees saved in database

#### Meeting List
- [ ] All scheduled meetings display
- [ ] Grouped by date (Today, Tomorrow, etc.)
- [ ] Meeting cards show all details
- [ ] Time shows in 12-hour format

#### Meeting Actions (3-dot menu)
- [ ] **View Details**: Modal shows all info
- [ ] **Edit**: Can modify topic, time, date
- [ ] **Share Link**: Copies meeting link
- [ ] **Delete**: Custom blue modal appears
- [ ] **Delete Confirm**: Meeting deleted from DB
- [ ] **Delete Cancel**: Modal closes, meeting stays

#### Responsive Design
- [ ] Mobile: Form stacks vertically
- [ ] Mobile: Meeting cards full width
- [ ] Mobile: 3-dot menu accessible
- [ ] Tablet: Proper layout

---

### ✅ 4. Join Meeting

#### Form & Validation
- [ ] Meeting ID: Validates format
- [ ] Name: Required field
- [ ] Password: Required, shows/hide toggle works
- [ ] Join options: Video/Audio toggles work
- [ ] Join button: Disabled until all fields valid
- [ ] Join button: Enables with valid inputs

#### Joining Process
- [ ] Enter valid meeting ID
- [ ] Enter participant name
- [ ] Enter correct password
- [ ] Click "Join"
- [ ] Meeting details display
- [ ] "Enter Meeting" button appears

#### Video Room (Placeholder)
- [ ] Click "Enter Meeting"
- [ ] Video room interface loads
- [ ] Meeting topic displays
- [ ] Participant count shows
- [ ] Audio/Video toggle buttons work
- [ ] "Leave Meeting" button works

#### Error Handling
- [ ] Invalid meeting ID: Shows error
- [ ] Wrong password: Shows error
- [ ] Empty fields: Shows validation errors

---

### ✅ 5. Profile Management

#### View Profile
- [ ] Profile page loads
- [ ] User info displays correctly
- [ ] Profile picture placeholder shows
- [ ] All fields populated

#### Edit Profile
- [ ] Click "Edit" button
- [ ] Fields become editable
- [ ] Update name
- [ ] Update phone number
- [ ] Click "Save"
- [ ] Success message appears
- [ ] Changes persist on refresh

#### Change Password
- [ ] Click "Change Password"
- [ ] Modal opens
- [ ] Enter current password
- [ ] Enter new password
- [ ] Confirm new password
- [ ] Submit
- [ ] Success/error message shows

#### Notification Preferences
- [ ] Click "Notification Preferences"
- [ ] Modal opens
- [ ] Toggle email notifications
- [ ] Toggle browser notifications
- [ ] Toggle reminder notifications
- [ ] Save preferences
- [ ] Settings persist

#### Responsive Design
- [ ] Mobile: Profile card full width
- [ ] Mobile: Buttons stack properly
- [ ] Modals: Responsive on all screens

---

### ✅ 6. Email System

#### SMTP Connection
- [ ] Go to `http://localhost/MD/test-email-system.php`
- [ ] Verify SMTP connection successful
- [ ] Check all configuration loaded

#### Send Test Email
- [ ] Enter your email
- [ ] Click "Send Test Email"
- [ ] Check inbox for email
- [ ] Verify email formatting
- [ ] Check links work

#### Meeting Scheduled Email
- [ ] Schedule a new meeting
- [ ] Check organizer receives email
- [ ] Verify meeting details correct
- [ ] Check join link works

#### Meeting Reminder Email
- [ ] Go to `http://localhost/MD/send-all-reminders.php`
- [ ] Run manual reminder
- [ ] Check inbox for reminder
- [ ] Verify meeting ID and password shown
- [ ] Check "Join Now" link works

---

### ✅ 7. Reminder System

#### Manual Reminders
- [ ] Schedule meeting 35+ minutes away
- [ ] Go to `send-all-reminders.php`
- [ ] Click to send reminders
- [ ] Verify organizer receives email
- [ ] For private meetings: All attendees receive email
- [ ] Check reminder marked as sent in DB

#### Attendee Reminders (Private Meetings)
- [ ] Schedule private meeting with attendees
- [ ] Add 2-3 attendee emails
- [ ] Run reminder script
- [ ] Verify ALL recipients get emails:
  - [ ] Organizer
  - [ ] Attendee 1
  - [ ] Attendee 2
  - [ ] Attendee 3

---

### ✅ 8. Responsive Design - All Pages

#### Desktop (1920x1080)
- [ ] Dashboard: Sidebar + content layout
- [ ] Schedule: Two-column layout
- [ ] Profile: Centered card
- [ ] Join: Centered form

#### Tablet (768px)
- [ ] All pages: Proper spacing
- [ ] Navigation: Accessible
- [ ] Forms: Readable
- [ ] Buttons: Clickable

#### Mobile (375px)
- [ ] Dashboard: Hamburger menu
- [ ] Schedule: Stacked layout
- [ ] Profile: Full width
- [ ] Join: Full width form
- [ ] All modals: Fit screen
- [ ] All buttons: Touch-friendly

---

### ✅ 9. Data Persistence

#### MongoDB Storage
- [ ] Schedule meeting → Check DB
- [ ] Edit meeting → Verify update in DB
- [ ] Delete meeting → Confirm removed from DB
- [ ] Update profile → Check user collection
- [ ] Set preferences → Verify saved

#### Session Management
- [ ] Login → Session created
- [ ] Refresh page → Session persists
- [ ] Close browser → Session based on "Remember Me"
- [ ] Logout → Session cleared

---

### ✅ 10. Error Handling

#### Network Errors
- [ ] Stop MongoDB → Check error messages
- [ ] Invalid API calls → Proper error responses
- [ ] Timeout scenarios → User-friendly messages

#### Validation Errors
- [ ] Empty required fields → Clear error messages
- [ ] Invalid email format → Validation error
- [ ] Wrong password → Appropriate error
- [ ] Past dates → Cannot select

#### Edge Cases
- [ ] Very long meeting topics → Handles gracefully
- [ ] Special characters in input → Sanitized
- [ ] Multiple rapid clicks → No duplicate submissions
- [ ] Expired meetings → Cannot join

---

## 📊 Testing Summary

**Total Tests:** ~100+

**Categories:**
- Authentication: 10 tests
- Dashboard: 12 tests
- Schedule: 20 tests
- Join: 12 tests
- Profile: 15 tests
- Email: 10 tests
- Reminders: 8 tests
- Responsive: 12 tests
- Data: 8 tests
- Errors: 10 tests

---

## 🐛 Bug Reporting Template

If you find issues, document them:

```
**Bug:** [Short description]
**Page:** [Which page]
**Steps to Reproduce:**
1. Step 1
2. Step 2
3. Step 3

**Expected:** [What should happen]
**Actual:** [What actually happens]
**Priority:** High/Medium/Low
```

---

## ✅ Sign-off

Once all tests pass:
- [ ] All features working
- [ ] No critical bugs
- [ ] Responsive on all devices
- [ ] Emails sending correctly
- [ ] Data persisting properly

**Tested by:** _______________
**Date:** _______________
**Status:** ⬜ Pass | ⬜ Fail | ⬜ Needs Review
