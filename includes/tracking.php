<?php
/**
 * Simple tracking system for frontend
 * Include this in post.php and download.php
 */

if (!defined('TRACKING_ENABLED')) {
    define('TRACKING_ENABLED', true);
}

function trackPageView($postId = null, $pageType = 'post') {
    if (!TRACKING_ENABLED) return;
    
    try {
        require_once 'admin/system/analytics_tracker.php';
        global $pdo;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $tracker = new AnalyticsTracker($pdo);
        $tracker->trackView($postId, $pageType);
    } catch (Exception $e) {
        error_log("Frontend tracking error: " . $e->getMessage());
    }
}

function trackDownload($postId, $fileName, $filePath) {
    if (!TRACKING_ENABLED) return;
    
    try {
        require_once 'admin/system/analytics_tracker.php';
        global $pdo;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $fileSize = file_exists($filePath) ? filesize($filePath) : null;
        
        $tracker = new AnalyticsTracker($pdo);
        $tracker->trackDownload($postId, $fileName, $filePath, $fileSize);
    } catch (Exception $e) {
        error_log("Download tracking error: " . $e->getMessage());
    }
}

/**
 * Get view count for a post
 */
function getPostViewCount($postId) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT view_count FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get download count for a post
 */
function getPostDownloadCount($postId) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT download_count FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}
?>