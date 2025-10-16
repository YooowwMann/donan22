<?php
/**
 * Automated Sitemap Generation (Cron Job)
 * 
 * This script is designed to run via cron job as a backup mechanism
 * to ensure sitemap is always up-to-date, even if auto-regeneration fails.
 * 
 * SETUP INSTRUCTIONS:
 * 
 * Linux/Mac (Crontab):
 *   Edit crontab: crontab -e
 *   
 *   Add line for daily at 2 AM:
 *   0 2 * * * cd /var/www/donan22 && php cron-sitemap.php >> logs/cron.log 2>&1
 *   
 *   Or every 6 hours:
 *   0 [star]/6 * * * cd /var/www/donan22 && php cron-sitemap.php >> logs/cron.log 2>&1
 *   (replace [star] with asterisk)
 * 
 * Windows (Task Scheduler):
 *   1. Open Task Scheduler
 *   2. Create Basic Task: "Donan22 Sitemap Cron"
 *   3. Trigger: Daily at 2:00 AM (or every 6 hours)
 *   4. Action: Start a program
 *      - Program: C:\xampp\php\php.exe
 *      - Arguments: C:\xampp\htdocs\donan22\cron-sitemap.php
 *      - Start in: C:\xampp\htdocs\donan22
 *   5. Finish
 * 
 * @version 1.0.0
 * @date 2025-10-12
 */

// Prevent web access (CLI only)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from command line.");
}

require_once __DIR__ . '/config_modern.php';

// Log file
$logFile = __DIR__ . '/logs/cron.log';
$logDir = dirname($logFile);

// Create logs directory if not exists
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Log message with timestamp
 */
function logMessage($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

// Start
logMessage("===== CRON JOB START =====", "INFO");
logMessage("Running automated sitemap generation", "INFO");

// Step 1: Generate sitemap
logMessage("Executing sitemap generator...", "INFO");

$sitemapScript = __DIR__ . '/seo/generate_sitemap.php';

if (!file_exists($sitemapScript)) {
    logMessage("ERROR: Sitemap generator not found: {$sitemapScript}", "ERROR");
    logMessage("===== CRON JOB FAILED =====", "ERROR");
    exit(1);
}

try {
    // Execute sitemap generator
    ob_start();
    include $sitemapScript;
    $output = ob_get_clean();
    
    // Check if sitemap was generated (NOW IN ROOT!)
    $sitemapFile = __DIR__ . '/sitemap.xml';
    
    if (file_exists($sitemapFile)) {
        $fileSize = filesize($sitemapFile);
        $lastModified = date('Y-m-d H:i:s', filemtime($sitemapFile));
        
        logMessage("✅ Sitemap generated successfully", "INFO");
        logMessage("File: {$sitemapFile}", "INFO");
        logMessage("Size: " . number_format($fileSize) . " bytes", "INFO");
        logMessage("Last Modified: {$lastModified}", "INFO");
        
        // Parse sitemap to count URLs
        $xml = @simplexml_load_file($sitemapFile);
        if ($xml) {
            $urlCount = count($xml->url);
            logMessage("URLs in sitemap: {$urlCount}", "INFO");
        }
        
    } else {
        logMessage("⚠️ WARNING: Sitemap file not found after generation", "WARNING");
    }
    
} catch (Exception $e) {
    logMessage("ERROR: Sitemap generation failed - " . $e->getMessage(), "ERROR");
    logMessage("===== CRON JOB FAILED =====", "ERROR");
    exit(1);
}

// Step 2: Optional - Submit to IndexNow
// Uncomment if you want to auto-submit sitemap to IndexNow daily
/*
logMessage("Submitting to IndexNow...", "INFO");

try {
    require_once __DIR__ . '/includes/IndexNowSubmitter.php';
    
    $indexNow = new IndexNowSubmitter('localhost');
    $result = $indexNow->submitSitemap($sitemapFile, 'bing');
    
    if ($result['success']) {
        logMessage("✅ IndexNow submission successful", "INFO");
    } else {
        logMessage("⚠️ IndexNow submission failed: " . $result['message'], "WARNING");
    }
    
} catch (Exception $e) {
    logMessage("ERROR: IndexNow submission error - " . $e->getMessage(), "ERROR");
}
*/

// Step 3: Cleanup old logs (keep last 30 days)
logMessage("Cleaning up old logs...", "INFO");

$logFiles = glob(__DIR__ . '/logs/*.log');
$cutoffTime = time() - (30 * 24 * 60 * 60); // 30 days ago

$deletedCount = 0;
foreach ($logFiles as $file) {
    if (filemtime($file) < $cutoffTime) {
        if (unlink($file)) {
            $deletedCount++;
        }
    }
}

if ($deletedCount > 0) {
    logMessage("Deleted {$deletedCount} old log file(s)", "INFO");
}

// Success
logMessage("===== CRON JOB COMPLETED =====", "INFO");
logMessage("", "INFO");

exit(0);
?>
