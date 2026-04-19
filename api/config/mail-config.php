<?php
// ===== EMAIL CONFIGURATION =====
// Purpose: Centralized email settings for SMTP and sender information

// SMTP Configuration
const MAIL_CONFIG = [
    'smtp_host' => 'smtp.gmail.com',           // Gmail SMTP server
    'smtp_port' => 587,                        // TLS port
    'smtp_user' => 'meetdesk26@gmail.com',     // MeetDesk professional email
    'smtp_password' => 'cloxkzuaakudosck',    // Gmail app password (removed spaces)
    'sender_email' => 'meetdesk26@gmail.com',  // From email
    'sender_name' => 'MeetDesk',               // From name
    'reply_to' => 'meetdesk26@gmail.com'       // Reply-to address
];

// Email Templates
const EMAIL_TEMPLATES = [
    'registration_welcome' => [
        'subject' => 'Welcome to Meet Desk! Registration Successful',
        'template' => 'registration_welcome.html'
    ],
    'password_reset' => [
        'subject' => 'Reset Your Meet Desk Password',
        'template' => 'password_reset.html'
    ]
];

// ===== INSTRUCTIONS FOR GMAIL SETUP =====
/*
1. Go to https://myaccount.google.com/security
2. Enable "Less secure app access" OR use App Passwords:
   - Go to Security settings
   - Create App Password for "Mail"
   - Copy the 16-character password
3. Paste app password in 'smtp_password' above
4. Update 'smtp_user' with your Gmail address
*/
?>