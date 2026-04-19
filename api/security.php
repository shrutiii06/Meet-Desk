<?php
/**
 * Security Functions for MeetDesk
 * CSRF Protection, Rate Limiting, Session Security
 */

// Start session with secure settings
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

/**
 * CSRF Token Generation and Validation
 */
function generateCSRFToken() {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    initSecureSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

/**
 * Rate Limiting
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    initSecureSession();
    
    $key = 'rate_limit_' . $identifier;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window expired
    if ($now - $data['start'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        $remainingTime = $timeWindow - ($now - $data['start']);
        http_response_code(429);
        die(json_encode([
            'error' => 'Too many attempts. Please try again later.',
            'retry_after' => $remainingTime
        ]));
    }
    
    return true;
}

/**
 * Session Security
 */
function regenerateSession() {
    initSecureSession();
    session_regenerate_id(true);
}

/**
 * Input Sanitization
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Password Strength Validation
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Security Headers
 */
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src fonts.gstatic.com; img-src 'self' data:;");
}

/**
 * Account Lockout
 */
function checkAccountLockout($email) {
    initSecureSession();
    
    $key = 'lockout_' . md5($email);
    $now = time();
    
    if (isset($_SESSION[$key])) {
        $lockout = $_SESSION[$key];
        
        // Check if still locked
        if ($lockout['until'] > $now) {
            $remainingTime = $lockout['until'] - $now;
            http_response_code(423);
            die(json_encode([
                'error' => 'Account temporarily locked due to multiple failed login attempts',
                'retry_after' => $remainingTime,
                'locked_until' => date('H:i:s', $lockout['until'])
            ]));
        } else {
            // Lockout expired, remove it
            unset($_SESSION[$key]);
        }
    }
    
    return true;
}

function recordFailedLogin($email) {
    initSecureSession();
    
    $key = 'failed_login_' . md5($email);
    $now = time();
    $maxAttempts = 5;
    $lockoutDuration = 900; // 15 minutes
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if more than 15 minutes passed
    if ($now - $data['start'] > $lockoutDuration) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Lock account if max attempts reached
    if ($_SESSION[$key]['count'] >= $maxAttempts) {
        $lockoutKey = 'lockout_' . md5($email);
        $_SESSION[$lockoutKey] = [
            'until' => $now + $lockoutDuration,
            'attempts' => $_SESSION[$key]['count']
        ];
        
        // Clear failed login counter
        unset($_SESSION[$key]);
        
        error_log("Account locked for email: $email");
    }
}

function clearFailedLogins($email) {
    initSecureSession();
    $key = 'failed_login_' . md5($email);
    unset($_SESSION[$key]);
}
