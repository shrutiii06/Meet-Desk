<?php
/**
 * Test if Task Scheduler is working and diagnose issues
 */

echo "<!DOCTYPE html><html><head><title>Task Scheduler Diagnostic</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#f5f5f5;} 
.box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
.success{color:green;font-weight:bold;} 
.error{color:red;font-weight:bold;} 
.warning{color:orange;font-weight:bold;}
.info{color:blue;}
h2{color:#4285F4;}
pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;}
</style>";
echo "</head><body>";

echo "<h1>🔍 Task Scheduler Diagnostic Tool</h1>";

// Check 1: MongoDB Connection
echo "<div class='box'>";
echo "<h2>1. MongoDB Connection</h2>";
try {
    require_once __DIR__ . '/api/config.php';
    $manager = getManager();
    echo "<p class='success'>✓ MongoDB connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ MongoDB connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Fix this first before continuing.</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Check 2: Upcoming Meetings
echo "<div class='box'>";
echo "<h2>2. Upcoming Meetings (Next 60 Minutes)</h2>";
try {
    $namespace = 'meetdesk.meetings';
    $now = new DateTime();
    
    echo "<p class='info'>Current time: <strong>" . $now->format('Y-m-d H:i:s') . "</strong></p>";
    
    // Get all meetings without reminders
    $query = new MongoDB\Driver\Query([
        'reminderSent' => false
    ]);
    
    $cursor = $manager->executeQuery($namespace, $query);
    $meetings = $cursor->toArray();
    
    echo "<p>Total meetings without reminders: <strong>" . count($meetings) . "</strong></p>";
    
    if (empty($meetings)) {
        echo "<p class='warning'>⚠ No meetings found that need reminders.</p>";
        echo "<p>This could mean:</p>";
        echo "<ul>";
        echo "<li>All meetings already have reminders sent</li>";
        echo "<li>No meetings scheduled</li>";
        echo "<li>All meetings are more than 35 minutes away</li>";
        echo "</ul>";
    } else {
        echo "<table border='1' cellpadding='10' style='width:100%;border-collapse:collapse;'>";
        echo "<tr style='background:#4285F4;color:white;'>";
        echo "<th>Topic</th><th>Date</th><th>Time</th><th>Minutes Until</th><th>Reminder Status</th></tr>";
        
        foreach ($meetings as $meeting) {
            $meetingDateTime = new DateTime($meeting->date . ' ' . $meeting->time);
            $timeDiff = $meetingDateTime->getTimestamp() - $now->getTimestamp();
            $minutesUntil = round($timeDiff / 60);
            
            $inWindow = ($timeDiff > 0 && $timeDiff <= 35 * 60);
            $rowColor = $inWindow ? 'background:#d4edda;' : '';
            
            echo "<tr style='$rowColor'>";
            echo "<td>" . htmlspecialchars($meeting->topic) . "</td>";
            echo "<td>" . $meeting->date . "</td>";
            echo "<td>" . $meeting->time . "</td>";
            echo "<td>" . $minutesUntil . " min</td>";
            echo "<td>" . ($inWindow ? '<strong style="color:green;">SHOULD SEND NOW</strong>' : 'Not in window') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check 3: Task Scheduler Status
echo "<div class='box'>";
echo "<h2>3. Task Scheduler Status</h2>";
echo "<p>Checking if 'MeetDesk Send Reminders' task exists...</p>";

$taskName = "MeetDesk Send Reminders";
$checkTask = shell_exec("schtasks /query /tn \"$taskName\" /fo LIST 2>&1");

if (strpos($checkTask, 'ERROR') !== false) {
    echo "<p class='error'>✗ Task NOT found in Task Scheduler</p>";
    echo "<p>The automated task is not set up. Run <code>setup-auto-reminders.ps1</code> again.</p>";
} else {
    echo "<p class='success'>✓ Task exists in Task Scheduler</p>";
    echo "<pre>" . htmlspecialchars($checkTask) . "</pre>";
    
    // Check last run time
    if (preg_match('/Last Run Time:\s+(.+)/', $checkTask, $matches)) {
        echo "<p><strong>Last Run:</strong> " . $matches[1] . "</p>";
    }
    
    if (preg_match('/Next Run Time:\s+(.+)/', $checkTask, $matches)) {
        echo "<p><strong>Next Run:</strong> " . $matches[1] . "</p>";
    }
    
    if (preg_match('/Last Result:\s+(.+)/', $checkTask, $matches)) {
        $result = trim($matches[1]);
        if ($result === '0' || $result === '0x0') {
            echo "<p class='success'><strong>Last Result:</strong> Success (0x0)</p>";
        } else {
            echo "<p class='error'><strong>Last Result:</strong> Failed ($result)</p>";
        }
    }
}
echo "</div>";

// Check 4: Log Files
echo "<div class='box'>";
echo "<h2>4. Recent Log Files</h2>";
$logDir = __DIR__ . '/cron/logs';
if (is_dir($logDir)) {
    $logs = glob($logDir . '/send_reminders_*.log');
    rsort($logs); // Most recent first
    
    if (empty($logs)) {
        echo "<p class='warning'>⚠ No log files found. The cron job may not have run yet.</p>";
    } else {
        echo "<p>Found " . count($logs) . " log file(s). Showing most recent:</p>";
        $recentLog = $logs[0];
        echo "<p><strong>File:</strong> " . basename($recentLog) . "</p>";
        echo "<pre>" . htmlspecialchars(file_get_contents($recentLog)) . "</pre>";
    }
} else {
    echo "<p class='error'>✗ Log directory not found: $logDir</p>";
}
echo "</div>";

// Check 5: Manual Test
echo "<div class='box'>";
echo "<h2>5. Manual Test</h2>";
echo "<p>Click the button below to run the reminder script manually and see if it works:</p>";
echo "<form method='post'>";
echo "<button type='submit' name='test_now' style='background:#4285F4;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:16px;'>Run Reminder Script Now</button>";
echo "</form>";

if (isset($_POST['test_now'])) {
    echo "<hr>";
    echo "<h3>Running reminder script...</h3>";
    
    $phpPath = "C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe";
    $scriptPath = __DIR__ . "\\cron\\send-reminders-cron.php";
    
    $command = "\"$phpPath\" \"$scriptPath\" 2>&1";
    echo "<p><strong>Command:</strong> <code>$command</code></p>";
    
    $output = shell_exec($command);
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}
echo "</div>";

// Recommendations
echo "<div class='box'>";
echo "<h2>6. Recommendations</h2>";
echo "<ul>";
echo "<li>If task exists but not running: Right-click the task in Task Scheduler and select 'Run'</li>";
echo "<li>If task doesn't exist: Run <code>setup-auto-reminders.ps1</code> again as Administrator</li>";
echo "<li>If meetings exist but no reminders sent: Use the 'Run Reminder Script Now' button above</li>";
echo "<li>Check logs in <code>C:\\laragon\\www\\MD\\cron\\logs\\</code> for detailed error messages</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
