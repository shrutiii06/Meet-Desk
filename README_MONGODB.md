# Meet Desk - MongoDB Setup Guide (PHP Backend)

## Prerequisites
- PHP 7.4 or higher with MongoDB extension
- MongoDB Atlas account (free) or local MongoDB
- Laragon (includes PHP & Apache)

## Step 1: Enable MongoDB Extension in PHP

1. Open Laragon → Menu → PHP → Version → php.ini
2. Find the line `;extension=mongodb` 
3. Remove the semicolon to enable: `extension=mongodb`
4. If the extension is not listed, you may need to install it via PECL or download from [pecl.php.net/package/mongodb](https://pecl.php.net/package/mongodb)
5. Restart Laragon (Right-click → Restart All)

## Step 2: Create MongoDB Atlas Database (Free)

1. Go to [MongoDB Atlas](https://www.mongodb.com/cloud/atlas) and sign up
2. Create a new cluster (free tier M0)
3. Click **Connect** → **Connect your application**
4. Copy the connection string (e.g., `mongodb+srv://user:pass@cluster.xxxxx.mongodb.net/`)
5. Add database name: `meetdesk` at the end

## Step 3: Configure Database Connection

1. Open `api/database.php`
2. Update the `MONGODB_URI` constant:

```php
// For MongoDB Atlas:
define('MONGODB_URI', 'mongodb+srv://username:password@cluster.xxxxx.mongodb.net/meetdesk?retryWrites=true&w=majority');

// For local MongoDB:
define('MONGODB_URI', 'mongodb://localhost:27017');
```

3. If using virtual host (e.g. md.test), update `api/.htaccess` RewriteBase to `/api/`

## Step 4: Run the App

1. Start Laragon
2. Open `http://localhost/MD/` (or your virtual host URL)
3. Register and login - data will be stored in MongoDB

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| POST | /api/auth/register | Register new user |
| POST | /api/auth/login | Login user |
| POST | /api/auth/verify-email | Verify email address |
| POST | /api/auth/send-reset-code | Send password reset code |
| POST | /api/auth/reset-password | Reset password |
| POST | /api/meetings/schedule | Schedule a meeting |
| GET | /api/meetings/get | Get user's meetings |
| POST | /api/meetings/join | Join a meeting |
| POST | /api/meetings/update | Update meeting details |
| DELETE | /api/meetings/delete | Delete a meeting |

## File Structure

```
MD/
├── api/
│   ├── config.php          # CORS & helpers
│   ├── database.php        # MongoDB connection (edit this)
│   ├── .htaccess           # URL routing
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
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
│       └── update-profile.php
├── js/api.js               # API URL config
├── dashboard.html
├── schedule.html
├── join.html
└── profile.html
```
