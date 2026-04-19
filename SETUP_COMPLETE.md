# Meet Desk - Setup Guide

## ✅ Current Status
- MongoDB service: **Running**
- PHP MongoDB extension: **Loaded**
- Backend API: **Ready**
- Frontend: **Ready**

## 🚀 Next Steps

### 1. Start Laragon (if not already running)
- Open Laragon from Start Menu
- Click "Start All" button

### 2. Test MongoDB Connection
Visit this URL in your browser:
```
http://localhost/MD/test-connection.php
```
You should see: `"status": "success"`

### 3. Access Your Application
Open your browser and go to:
```
http://localhost/MD
```

### 4. How to Use the Application

#### **Register/Login Flow**
1. Click "Sign up" → Create new account
2. Fill in: Name, Phone (optional), Email, Password
3. Click "Sign up" → You're logged in
4. Redirected to Dashboard

#### **Dashboard**
- **New Meeting** - Create a meeting (logic to be added)
- **Join** - Join existing meeting with code
- **Schedule** - Schedule a meeting

#### **Profile Page**
- Click profile icon (bottom of left sidebar)
- Upload profile picture (stored locally)
- View and edit your information
- Change password
- Update notification preferences

---

## 📋 API Endpoints (All running on PHP Backend)

### Authentication
- `POST /api/auth/login` - Login user
- `POST /api/auth/register` - Register new user
- `POST /api/auth/verify-email` - Verify email address
- `POST /api/auth/send-reset-code` - Send password reset code
- `POST /api/auth/reset-password` - Reset password

### Meetings
- `POST /api/meetings/schedule` - Schedule a meeting
- `GET /api/meetings/get` - Get user's meetings
- `POST /api/meetings/join` - Join a meeting
- `POST /api/meetings/update` - Update meeting details
- `DELETE /api/meetings/delete` - Delete a meeting

### Users
- `GET /api/users/get-profile` - Get user profile
- `POST /api/users/update-profile` - Update user profile
- `POST /api/users/update-password` - Change password

### Database
All data is stored in MongoDB:
- Database: `meetdesk`
- Collection: `users`

---

## 🔧 File Structure
```
c:\laragon\www\MD\
├── index.html              (Splash screen)
├── login.html              (Login page)
├── register.html           (Registration page)
├── dashboard.html          (Main dashboard)
├── profile.html            (User profile)
├── schedule.html           (Schedule meetings)
├── join.html               (Join meetings)
├── verify-email.html       (Email verification)
├── test-connection.php     (MongoDB test)
├── api/
│   ├── config.php          (Config & helpers)
│   ├── database.php        (DB connection)
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── verify-email.php
│   │   ├── send-reset-code.php
│   │   └── reset-password.php
│   ├── meetings/
│   │   ├── schedule.php
│   │   ├── get.php
│   │   ├── join.php
│   │   ├── update.php
│   │   └── delete.php
│   └── users/
│       ├── get-profile.php
│       ├── update-profile.php
│       └── update-password.php
└── js/
    └── api.js              (API URL config)
```

---

## 💾 Data Storage
- **Local Storage**: User session, profile image, member since date
- **MongoDB**: Permanent user data (name, email, phone, password, status)

---

## 🐛 Troubleshooting

**Issue: "Could not connect to server"**
- Make sure Laragon is running (click Start All)
- Check if Laragon Web server shows green light

**Issue: "MongoDB connection failed"**
- Visit: http://localhost/MD/test-connection.php
- Check MongoDB service: `Get-Service MongoDB`

**Issue: Meetings not loading**
- Make sure you're logged in
- Check MongoDB is running
- Verify database.php has correct connection string

---

## 📝 Next Features to Add (Optional)

1. **Email Verification** - Send verification emails on registration
2. **Password Reset** - Allow users to reset forgotten passwords
3. **Meeting Creation** - Create actual meeting rooms
4. **Video Call Integration** - Add WebRTC or Jitsi Meet
5. **Profile Updates** - Edit user information
6. **Search Users** - Search functionality in admin panel
7. **Export Users** - Export user data to CSV

---

Ready to start? Begin with step 1! 🎉
