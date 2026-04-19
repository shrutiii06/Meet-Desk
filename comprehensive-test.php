<?php
/**
 * COMPREHENSIVE PROJECT DIAGNOSTIC & ERROR CHECKER
 * Tests all components of MeetDesk application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$errors = [];
$warnings = [];
$success = [];

// HTML Header
?>
<!DOCTYPE html>
<html>
<head>
    <title>MeetDesk - Comprehensive Diagnostic Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        h1 { color: #4285F4; }
        h2 { color: #333; border-bottom: 2px solid #4285F4; padding-bottom: 10px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #4285F4; color: white; }
        .status-ok { background: #d4edda; }
        .status-error { background: #f8d7da; }
        .status-warning { background: #fff3cd; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 MeetDesk - Comprehensive Diagnostic Report</h1>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

// ==================== TEST 1: PHP CONFIGURATION ====================
echo "<div class='section'>";
echo "<h2>1. PHP Configuration</h2>";

$phpVersion = phpversion();
echo "<p><strong>PHP Version:</strong> $phpVersion</p>";

if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<p class='success'>✓ PHP version is compatible</p>";
    $success[] = "PHP version $phpVersion is compatible";
} else {
    echo "<p class='error'>✗ PHP version too old (need 7.4+)</p>";
    $errors[] = "PHP version $phpVersion is too old";
}

// Check required extensions
$requiredExtensions = ['mongodb', 'json', 'mbstring', 'openssl'];
echo "<h3>Required Extensions:</h3>";
echo "<table>";
echo "<tr><th>Extension</th><th>Status</th></tr>";

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? 'success' : 'error';
    $icon = $loaded ? '✓' : '✗';
    echo "<tr class='status-" . ($loaded ? 'ok' : 'error') . "'>";
    echo "<td>$ext</td>";
    echo "<td class='$status'>$icon " . ($loaded ? 'Loaded' : 'NOT Loaded') . "</td>";
    echo "</tr>";
    
    if ($loaded) {
        $success[] = "Extension $ext is loaded";
    } else {
        $errors[] = "Extension $ext is NOT loaded";
    }
}
echo "</table>";

echo "</div>";

// ==================== TEST 2: FILE STRUCTURE ====================
echo "<div class='section'>";
echo "<h2>2. File Structure</h2>";

$requiredFiles = [
    'api/config.php' => 'Database configuration',
    'api/config/mail-config.php' => 'Email configuration',
    'api/mailer.php' => 'Email sender class',
    'api/email-templates.php' => 'Email templates',
    'api/security.php' => 'Security functions',
    'api/auth/login.php' => 'Login endpoint',
    'api/auth/register.php' => 'Register endpoint',
    'api/meetings/schedule.php' => 'Schedule meeting endpoint',
    'api/meetings/list.php' => 'List meetings endpoint',
    'api/meetings/update.php' => 'Update meeting endpoint',
    'api/meetings/delete.php' => 'Delete meeting endpoint',
    'cron/send-reminders-cron.php' => 'Reminder cron job',
    'login.html' => 'Login page',
    'register.html' => 'Register page',
    'schedule.html' => 'Schedule page',
    'join.html' => 'Join page',
    'room.html' => 'Video room page'
];

echo "<table>";
echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";

foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? 'success' : 'error';
    $icon = $exists ? '✓' : '✗';
    echo "<tr class='status-" . ($exists ? 'ok' : 'error') . "'>";
    echo "<td>$file</td>";
    echo "<td>$desc</td>";
    echo "<td class='$status'>$icon " . ($exists ? 'Exists' : 'MISSING') . "</td>";
    echo "</tr>";
    
    if ($exists) {
        $success[] = "File $file exists";
    } else {
        $errors[] = "File $file is MISSING";
    }
}
echo "</table>";

echo "</div>";

// ==================== TEST 3: MONGODB CONNECTION ====================
echo "<div class='section'>";
echo "<h2>3. MongoDB Connection</h2>";

try {
    require_once __DIR__ . '/api/config.php';
    
    $manager = getManager();
    echo "<p class='success'>✓ MongoDB connection successful</p>";
    $success[] = "MongoDB connection successful";
    
    // Test database access
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $cursor = $manager->executeCommand('admin', $command);
    $response = $cursor->toArray()[0];
    
    if ($response->ok == 1) {
        echo "<p class='success'>✓ MongoDB server responding</p>";
        $success[] = "MongoDB server responding";
    }
    
    // Check collections
    $collections = ['users', 'meetings'];
    echo "<h3>Database Collections:</h3>";
    echo "<table>";
    echo "<tr><th>Collection</th><th>Document Count</th><th>Status</th></tr>";
    
    foreach ($collections as $collection) {
        try {
            $query = new MongoDB\Driver\Query([]);
            $cursor = $manager->executeQuery("meetdesk.$collection", $query);
            $count = count($cursor->toArray());
            
            echo "<tr class='status-ok'>";
            echo "<td>$collection</td>";
            echo "<td>$count documents</td>";
            echo "<td class='success'>✓ OK</td>";
            echo "</tr>";
            
            $success[] = "Collection $collection has $count documents";
        } catch (Exception $e) {
            echo "<tr class='status-error'>";
            echo "<td>$collection</td>";
            echo "<td>-</td>";
            echo "<td class='error'>✗ Error: " . $e->getMessage() . "</td>";
            echo "</tr>";
            
            $errors[] = "Collection $collection error: " . $e->getMessage();
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ MongoDB connection failed: " . $e->getMessage() . "</p>";
    $errors[] = "MongoDB connection failed: " . $e->getMessage();
}

echo "</div>";

// ==================== TEST 4: EMAIL CONFIGURATION ====================
echo "<div class='section'>";
echo "<h2>4. Email Configuration</h2>";

try {
    require_once __DIR__ . '/api/config/mail-config.php';
    
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>SMTP Host</td><td>" . MAIL_CONFIG['smtp_host'] . "</td></tr>";
    echo "<tr><td>SMTP Port</td><td>" . MAIL_CONFIG['smtp_port'] . "</td></tr>";
    echo "<tr><td>SMTP User</td><td>" . MAIL_CONFIG['smtp_user'] . "</td></tr>";
    echo "<tr><td>Sender Email</td><td>" . MAIL_CONFIG['sender_email'] . "</td></tr>";
    echo "<tr><td>Sender Name</td><td>" . MAIL_CONFIG['sender_name'] . "</td></tr>";
    echo "</table>";
    
    echo "<p class='success'>✓ Email configuration loaded</p>";
    $success[] = "Email configuration loaded";
    
    // Test mailer class
    require_once __DIR__ . '/api/mailer.php';
    $mailer = new MailSender();
    echo "<p class='success'>✓ MailSender class loaded</p>";
    $success[] = "MailSender class loaded";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Email configuration error: " . $e->getMessage() . "</p>";
    $errors[] = "Email configuration error: " . $e->getMessage();
}

echo "</div>";

// ==================== TEST 5: API ENDPOINTS ====================
echo "<div class='section'>";
echo "<h2>5. API Endpoints Syntax Check</h2>";

$apiFiles = [
    'api/auth/login.php',
    'api/auth/register.php',
    'api/meetings/schedule.php',
    'api/meetings/list.php',
    'api/meetings/update.php',
    'api/meetings/delete.php'
];

echo "<table>";
echo "<tr><th>Endpoint</th><th>Syntax Check</th></tr>";

foreach ($apiFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $return = 0;
        exec("php -l \"$fullPath\" 2>&1", $output, $return);
        
        if ($return === 0) {
            echo "<tr class='status-ok'>";
            echo "<td>$file</td>";
            echo "<td class='success'>✓ No syntax errors</td>";
            echo "</tr>";
            $success[] = "$file has no syntax errors";
        } else {
            echo "<tr class='status-error'>";
            echo "<td>$file</td>";
            echo "<td class='error'>✗ Syntax error: " . implode(' ', $output) . "</td>";
            echo "</tr>";
            $errors[] = "$file has syntax errors";
        }
    }
}
echo "</table>";

echo "</div>";

// ==================== TEST 6: CRON JOB & TASK SCHEDULER ====================
echo "<div class='section'>";
echo "<h2>6. Cron Job & Task Scheduler</h2>";

// Check if MongoDB extension is loaded in CLI
$phpCliPath = "C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe";
if (file_exists($phpCliPath)) {
    echo "<p class='success'>✓ PHP CLI found at: $phpCliPath</p>";
    $success[] = "PHP CLI found";
    
    // Check MongoDB extension in CLI
    $output = shell_exec("\"$phpCliPath\" -m 2>&1");
    if (strpos($output, 'mongodb') !== false) {
        echo "<p class='success'>✓ MongoDB extension loaded in PHP CLI</p>";
        $success[] = "MongoDB extension loaded in PHP CLI";
    } else {
        echo "<p class='error'>✗ MongoDB extension NOT loaded in PHP CLI</p>";
        echo "<p class='warning'>⚠ Run enable-mongodb-cli.ps1 to fix this</p>";
        $errors[] = "MongoDB extension NOT loaded in PHP CLI";
    }
} else {
    echo "<p class='warning'>⚠ PHP CLI path may be different on your system</p>";
    $warnings[] = "PHP CLI path needs verification";
}

// Check Task Scheduler
$taskName = "MeetDesk Send Reminders";
$taskCheck = shell_exec("schtasks /query /tn \"$taskName\" /fo LIST 2>&1");

if (strpos($taskCheck, 'ERROR') === false && !empty($taskCheck)) {
    echo "<p class='success'>✓ Task Scheduler task exists</p>";
    $success[] = "Task Scheduler task exists";
    
    // Extract last run time
    if (preg_match('/Last Run Time:\s+(.+)/', $taskCheck, $matches)) {
        echo "<p><strong>Last Run:</strong> " . trim($matches[1]) . "</p>";
    }
    
    if (preg_match('/Next Run Time:\s+(.+)/', $taskCheck, $matches)) {
        echo "<p><strong>Next Run:</strong> " . trim($matches[1]) . "</p>";
    }
} else {
    echo "<p class='error'>✗ Task Scheduler task NOT found</p>";
    echo "<p class='warning'>⚠ Run setup-auto-reminders.ps1 to create the task</p>";
    $errors[] = "Task Scheduler task NOT found";
}

echo "</div>";

// ==================== TEST 7: SECURITY IMPLEMENTATION ====================
echo "<div class='section'>";
echo "<h2>7. Security Implementation</h2>";

if (file_exists(__DIR__ . '/api/security.php')) {
    require_once __DIR__ . '/api/security.php';
    
    $securityFunctions = [
        'generateCSRFToken',
        'validateCSRFToken',
        'checkRateLimit',
        'sanitizeInput',
        'validatePasswordStrength',
        'setSecurityHeaders'
    ];
    
    echo "<table>";
    echo "<tr><th>Security Function</th><th>Status</th></tr>";
    
    foreach ($securityFunctions as $func) {
        $exists = function_exists($func);
        echo "<tr class='status-" . ($exists ? 'ok' : 'error') . "'>";
        echo "<td>$func()</td>";
        echo "<td class='" . ($exists ? 'success' : 'error') . "'>";
        echo ($exists ? '✓' : '✗') . " " . ($exists ? 'Defined' : 'NOT Defined');
        echo "</td>";
        echo "</tr>";
        
        if ($exists) {
            $success[] = "Security function $func exists";
        } else {
            $errors[] = "Security function $func NOT found";
        }
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ security.php file not found</p>";
    $errors[] = "security.php file not found";
}

echo "</div>";

// ==================== TEST 8: FRONTEND PAGES ====================
echo "<div class='section'>";
echo "<h2>8. Frontend Pages</h2>";

$frontendPages = [
    'login.html' => 'Login Page',
    'register.html' => 'Registration Page',
    'schedule.html' => 'Schedule Meeting Page',
    'join.html' => 'Join Meeting Page',
    'room.html' => 'Video Room Page',
    'profile.html' => 'Profile Page'
];

echo "<table>";
echo "<tr><th>Page</th><th>Description</th><th>Size</th><th>Status</th></tr>";

foreach ($frontendPages as $file => $desc) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $sizeKB = round($size / 1024, 2);
        
        echo "<tr class='status-ok'>";
        echo "<td>$file</td>";
        echo "<td>$desc</td>";
        echo "<td>{$sizeKB} KB</td>";
        echo "<td class='success'>✓ Exists</td>";
        echo "</tr>";
        
        $success[] = "$file exists ($sizeKB KB)";
    } else {
        echo "<tr class='status-error'>";
        echo "<td>$file</td>";
        echo "<td>$desc</td>";
        echo "<td>-</td>";
        echo "<td class='error'>✗ Missing</td>";
        echo "</tr>";
        
        $errors[] = "$file is missing";
    }
}
echo "</table>";

echo "</div>";

// ==================== TEST 9: TIMEZONE CONFIGURATION ====================
echo "<div class='section'>";
echo "<h2>9. Timezone Configuration</h2>";

$timezone = date_default_timezone_get();
echo "<p><strong>Current Timezone:</strong> $timezone</p>";
echo "<p><strong>Current Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

if ($timezone === 'Asia/Kolkata') {
    echo "<p class='success'>✓ Timezone correctly set to Asia/Kolkata</p>";
    $success[] = "Timezone correctly set to Asia/Kolkata";
} else {
    echo "<p class='warning'>⚠ Timezone is $timezone (expected: Asia/Kolkata)</p>";
    $warnings[] = "Timezone is $timezone instead of Asia/Kolkata";
}

echo "</div>";

// ==================== SUMMARY ====================
echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";

$totalTests = count($success) + count($errors) + count($warnings);
$successRate = $totalTests > 0 ? round((count($success) / $totalTests) * 100, 1) : 0;

echo "<table>";
echo "<tr><th>Category</th><th>Count</th><th>Percentage</th></tr>";
echo "<tr class='status-ok'><td>✓ Success</td><td>" . count($success) . "</td><td>" . round((count($success)/$totalTests)*100, 1) . "%</td></tr>";
echo "<tr class='status-error'><td>✗ Errors</td><td>" . count($errors) . "</td><td>" . round((count($errors)/$totalTests)*100, 1) . "%</td></tr>";
echo "<tr class='status-warning'><td>⚠ Warnings</td><td>" . count($warnings) . "</td><td>" . round((count($warnings)/$totalTests)*100, 1) . "%</td></tr>";
echo "<tr><td><strong>Total Tests</strong></td><td><strong>$totalTests</strong></td><td><strong>100%</strong></td></tr>";
echo "</table>";

echo "<h3>Overall Health: ";
if ($successRate >= 90) {
    echo "<span class='success'>EXCELLENT ($successRate%)</span>";
} elseif ($successRate >= 70) {
    echo "<span class='warning'>GOOD ($successRate%)</span>";
} else {
    echo "<span class='error'>NEEDS ATTENTION ($successRate%)</span>";
}
echo "</h3>";

echo "</div>";

// ==================== CRITICAL ERRORS ====================
if (!empty($errors)) {
    echo "<div class='section'>";
    echo "<h2>🚨 Critical Errors (MUST FIX)</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>✗ $error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// ==================== WARNINGS ====================
if (!empty($warnings)) {
    echo "<div class='section'>";
    echo "<h2>⚠ Warnings (Should Fix)</h2>";
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li class='warning'>⚠ $warning</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// ==================== ACTION ITEMS ====================
echo "<div class='section'>";
echo "<h2>✅ Action Items</h2>";
echo "<ol>";

if (in_array("MongoDB extension NOT loaded in PHP CLI", $errors)) {
    echo "<li><strong>Enable MongoDB in PHP CLI:</strong> Run <code>enable-mongodb-cli.ps1</code> as Administrator</li>";
}

if (in_array("Task Scheduler task NOT found", $errors)) {
    echo "<li><strong>Set up automated reminders:</strong> Run <code>setup-auto-reminders.ps1</code> as Administrator</li>";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "<li class='success'>✓ No action items - everything is working correctly!</li>";
}

echo "</ol>";
echo "</div>";

?>

</div>
</body>
</html>
