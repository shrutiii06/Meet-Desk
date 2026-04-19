<?php
// Quick Diagnostic - Simplified Version
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Diagnostic</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warn { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>MeetDesk Quick Diagnostic</h1>
    
    <?php
    echo "<div class='box'>";
    echo "<h3>1. PHP Working</h3>";
    echo "<p class='ok'>✓ PHP is executing correctly</p>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>2. MongoDB Extension</h3>";
    if (extension_loaded('mongodb')) {
        echo "<p class='ok'>✓ MongoDB extension loaded</p>";
    } else {
        echo "<p class='error'>✗ MongoDB extension NOT loaded</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>3. Required Files</h3>";
    $files = ['api/config.php', 'api/mailer.php', 'login.html', 'schedule.html'];
    foreach ($files as $f) {
        if (file_exists(__DIR__ . '/' . $f)) {
            echo "<p class='ok'>✓ $f exists</p>";
        } else {
            echo "<p class='error'>✗ $f missing</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>4. MongoDB Connection</h3>";
    try {
        require_once __DIR__ . '/api/config.php';
        $manager = getManager();
        echo "<p class='ok'>✓ MongoDB connected</p>";
        
        // Count users and meetings
        $query = new MongoDB\Driver\Query([]);
        $users = $manager->executeQuery('meetdesk.users', $query);
        $userCount = count($users->toArray());
        
        $meetings = $manager->executeQuery('meetdesk.meetings', $query);
        $meetingCount = count($meetings->toArray());
        
        echo "<p>Users: $userCount</p>";
        echo "<p>Meetings: $meetingCount</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ MongoDB error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>5. Email Config</h3>";
    try {
        require_once __DIR__ . '/api/config/mail-config.php';
        echo "<p class='ok'>✓ Email config loaded</p>";
        echo "<p>SMTP: " . MAIL_CONFIG['smtp_user'] . "</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Email config error</p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>6. Critical Issues</h3>";
    
    $issues = [];
    
    if (!extension_loaded('mongodb')) {
        $issues[] = "MongoDB extension not loaded - website won't work";
    }
    
    // Check MongoDB CLI
    $phpCli = "C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe";
    if (file_exists($phpCli)) {
        $output = shell_exec("\"$phpCli\" -m 2>&1");
        if (strpos($output, 'mongodb') === false) {
            $issues[] = "MongoDB NOT in PHP CLI - automated reminders won't work";
        }
    }
    
    // Check Task Scheduler
    $taskCheck = shell_exec('schtasks /query /tn "MeetDesk Send Reminders" 2>&1');
    if (strpos($taskCheck, 'ERROR') !== false) {
        $issues[] = "Task Scheduler not set up - automated reminders won't work";
    }
    
    if (empty($issues)) {
        echo "<p class='ok'>✓ No critical issues found!</p>";
    } else {
        foreach ($issues as $issue) {
            echo "<p class='error'>✗ $issue</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>7. Fix Instructions</h3>";
    echo "<ol>";
    echo "<li><strong>Enable MongoDB in CLI:</strong> Run <code>enable-mongodb-cli.ps1</code> as Admin</li>";
    echo "<li><strong>Set up auto reminders:</strong> Run <code>setup-auto-reminders.ps1</code> as Admin</li>";
    echo "<li><strong>Test email:</strong> Visit <code>test-email-system.php</code></li>";
    echo "</ol>";
    echo "</div>";
    ?>
    
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
