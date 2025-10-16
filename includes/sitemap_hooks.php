<?php
/**
 * DONAN22.COM - Sitemap Auto-Update Hooks
 * =========================================
 * Automatically regenerates sitemap when content changes
 * 
 * Include this file in your post/category management pages
 * Usage: require_once 'includes/sitemap_hooks.php';
 */

/**
 * Regenerate sitemap (background process)
 */
function regenerateSitemap() {
    $sitemapGenerator = __DIR__ . '/../seo/sitemap_dynamic.php';
    
    if (!file_exists($sitemapGenerator)) {
        error_log('Sitemap generator not found: ' . $sitemapGenerator);
        return false;
    }
    
    // Execute in background (non-blocking)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = 'start /B php "' . $sitemapGenerator . '" > NUL 2>&1';
        pclose(popen($command, 'r'));
    } else {
        // Linux/Unix
        $command = 'php "' . $sitemapGenerator . '" > /dev/null 2>&1 &';
        exec($command);
    }
    
    // Log regeneration
    $logFile = __DIR__ . '/../seo/sitemap_hooks.log';
    $logMessage = date('Y-m-d H:i:s') . " - Sitemap regeneration triggered\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    return true;
}

/**
 * Regenerate sitemap with delay (prevents multiple rapid regenerations)
 */
function regenerateSitemapThrottled() {
    $lockFile = __DIR__ . '/../seo/.sitemap_lock';
    $lockTimeout = 300; // 5 minutes
    
    // Check if lock file exists and is recent
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        if (time() - $lockTime < $lockTimeout) {
            // Still locked, skip regeneration
            return false;
        }
    }
    
    // Create lock file
    touch($lockFile);
    
    // Regenerate
    $result = regenerateSitemap();
    
    return $result;
}

/**
 * Hook: After post is published/updated
 */
function onPostUpdated() {
    regenerateSitemapThrottled();
}

/**
 * Hook: After category is created/updated
 */
function onCategoryUpdated() {
    regenerateSitemapThrottled();
}

/**
 * Hook: After post is deleted
 */
function onPostDeleted() {
    regenerateSitemapThrottled();
}

// Auto-trigger if called directly with action parameter
if (isset($_GET['action']) && $_GET['action'] === 'regenerate_sitemap') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Security check - must be admin
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $result = regenerateSitemap();
        
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Sitemap regenerated successfully' : 'Failed to regenerate sitemap',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        exit;
    }
}
?>
