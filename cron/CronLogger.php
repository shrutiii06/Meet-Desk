<?php
/**
 * CRON LOGGING HELPER
 * 
 * Provides logging functions for all cron jobs
 * Organizes logs by date with error/success status
 */

class CronLogger {
    private $logDir;
    private $jobName;
    private $logFile;
    private $startTime;
    
    public function __construct($jobName) {
        $this->jobName = $jobName;
        $this->startTime = microtime(true);
        
        // Create logs directory if it doesn't exist
        $this->logDir = __DIR__ . '/logs';
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0777, true);
        }
        
        // Create log file: logs/jobname_YYYY-MM-DD.log
        $logFileName = strtolower(str_replace(' ', '_', $jobName)) . '_' . date('Y-m-d') . '.log';
        $this->logFile = $this->logDir . '/' . $logFileName;
    }
    
    /**
     * Log info message
     */
    public function info($message) {
        $this->write('INFO', $message);
        echo "\n✓ " . $message;
    }
    
    /**
     * Log warning message
     */
    public function warning($message) {
        $this->write('WARNING', $message);
        echo "\n⚠ " . $message;
    }
    
    /**
     * Log error message
     */
    public function error($message) {
        $this->write('ERROR', $message);
        echo "\n✗ " . $message;
    }
    
    /**
     * Log success message
     */
    public function success($message) {
        $this->write('SUCCESS', $message);
        echo "\n✅ " . $message;
    }
    
    /**
     * Log debug message (only if DEBUG mode)
     */
    public function debug($message) {
        $this->write('DEBUG', $message);
        // Don't echo debug in console to keep it clean
    }
    
    /**
     * Write to log file
     */
    private function write($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$level}] {$message}\n";
        
        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log execution summary at end
     */
    public function logSummary($data = []) {
        $duration = microtime(true) - $this->startTime;
        $summary = "\n" . str_repeat('=', 50) . "\n";
        $summary .= "Job: {$this->jobName}\n";
        $summary .= "Completed: " . date('Y-m-d H:i:s') . "\n";
        $summary .= "Duration: " . round($duration, 2) . "s\n";
        
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $summary .= "{$key}: {$value}\n";
            }
        }
        
        $summary .= str_repeat('=', 50) . "\n";
        $this->write('SUMMARY', trim($summary));
        echo $summary;
    }
    
    /**
     * Get log file path
     */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /**
     * Get today's logs
     */
    public static function getTodayLogs($jobName) {
        $logDir = __DIR__ . '/logs';
        $jobFilePrefix = strtolower(str_replace(' ', '_', $jobName));
        $logFile = $logDir . '/' . $jobFilePrefix . '_' . date('Y-m-d') . '.log';
        
        if (file_exists($logFile)) {
            return file_get_contents($logFile);
        }
        
        return "No logs found for {$jobName} today.";
    }
    
    /**
     * Rotate old logs (keep last 30 days)
     */
    public static function rotateLogs($daysToKeep = 30) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) return;
        
        $cutoffDate = time() - ($daysToKeep * 24 * 60 * 60);
        $files = scandir($logDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $logDir . '/' . $file;
            if (is_file($filePath) && filemtime($filePath) < $cutoffDate) {
                @unlink($filePath);
            }
        }
    }
}

?>
