<?php
/**
 * Analytics Tracking System
 * Tracks page views and downloads for analytics
 */

class AnalyticsTracker {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeTables();
    }
    
    /**
     * Initialize tracking tables if they don't exist
     */
    private function initializeTables() {
        try {
            // Create page_views table with enhanced tracking
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `page_views` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `post_id` int(11) DEFAULT NULL,
                  `page_type` varchar(50) NOT NULL DEFAULT 'post',
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text,
                  `referer` text,
                  `view_date` datetime DEFAULT CURRENT_TIMESTAMP,
                  `session_id` varchar(100) DEFAULT NULL,
                  `traffic_source` varchar(50) DEFAULT 'direct',
                  `country` varchar(100) DEFAULT 'Unknown',
                  PRIMARY KEY (`id`),
                  KEY `idx_post_id` (`post_id`),
                  KEY `idx_page_type` (`page_type`),
                  KEY `idx_view_date` (`view_date`),
                  KEY `idx_traffic_source` (`traffic_source`),
                  KEY `idx_country` (`country`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Add new columns if table already exists
            $this->addColumnIfNotExists('page_views', 'traffic_source', "VARCHAR(50) DEFAULT 'direct'");
            $this->addColumnIfNotExists('page_views', 'country', "VARCHAR(100) DEFAULT 'Unknown'");
            
            // Create downloads table with enhanced tracking
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `downloads` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `post_id` int(11) NOT NULL,
                  `file_name` varchar(255) NOT NULL,
                  `file_path` varchar(500) NOT NULL,
                  `file_size` bigint(20) DEFAULT NULL,
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text,
                  `download_date` datetime DEFAULT CURRENT_TIMESTAMP,
                  `session_id` varchar(100) DEFAULT NULL,
                  `status` enum('success','failed','incomplete') DEFAULT 'success',
                  `error_message` text DEFAULT NULL,
                  `download_duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds',
                  PRIMARY KEY (`id`),
                  KEY `idx_post_id` (`post_id`),
                  KEY `idx_download_date` (`download_date`),
                  KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Add new columns for existing downloads table
            $this->addColumnIfNotExists('downloads', 'status', "ENUM('success','failed','incomplete') DEFAULT 'success'");
            $this->addColumnIfNotExists('downloads', 'error_message', "TEXT DEFAULT NULL");
            $this->addColumnIfNotExists('downloads', 'download_duration', "INT(11) DEFAULT NULL COMMENT 'Duration in seconds'");
            
            // Create sessions table for bounce rate and duration tracking
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `sessions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `session_id` varchar(100) NOT NULL,
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text,
                  `referer` text,
                  `country` varchar(100) DEFAULT 'Unknown',
                  `start_time` datetime NOT NULL,
                  `last_activity` datetime NOT NULL,
                  `duration` int(11) DEFAULT 0 COMMENT 'Duration in seconds',
                  `page_views` int(11) DEFAULT 1,
                  `is_bounce` tinyint(1) DEFAULT 1 COMMENT '1=bounce (single page), 0=engaged',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `idx_session_id` (`session_id`),
                  KEY `idx_start_time` (`start_time`),
                  KEY `idx_country` (`country`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Add columns to posts table if not exist
            $this->addColumnsIfNotExist();
            
            // Create daily stats table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `daily_stats` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `stat_date` date NOT NULL,
                  `total_views` int(11) NOT NULL DEFAULT 0,
                  `total_downloads` int(11) NOT NULL DEFAULT 0,
                  `unique_visitors` int(11) NOT NULL DEFAULT 0,
                  `top_post_id` int(11) DEFAULT NULL,
                  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `idx_stat_date` (`stat_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
        } catch (Exception $e) {
            error_log("Analytics Tracker initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Add view_count and download_count columns if they don't exist
     */
    private function addColumnsIfNotExist() {
        try {
            // Check if columns exist
            $stmt = $this->pdo->query("SHOW COLUMNS FROM posts LIKE 'view_count'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `posts` ADD COLUMN `view_count` int(11) NOT NULL DEFAULT 0");
            }
            
            $stmt = $this->pdo->query("SHOW COLUMNS FROM posts LIKE 'download_count'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `posts` ADD COLUMN `download_count` int(11) NOT NULL DEFAULT 0");
            }
        } catch (Exception $e) {
            error_log("Error adding columns: " . $e->getMessage());
        }
    }
    
    /**
     * Add single column if it doesn't exist
     */
    private function addColumnIfNotExists($table, $column, $definition) {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (Exception $e) {
            error_log("Error adding column {$column} to {$table}: " . $e->getMessage());
        }
    }
    
    /**
     * Track a page view
     */
    public function trackView($postId = null, $pageType = 'post') {
        try {
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $sessionId = session_id();
            
            // Get traffic source
            $trafficSource = $this->getTrafficSource($referer);
            
            // Get country from IP (simplified - in production use GeoIP database)
            $country = $this->getCountryFromIP($ipAddress);
            
            // Check if this view was already recorded in this session
            if ($postId && $this->isViewAlreadyRecorded($postId, $sessionId)) {
                // Update session duration
                $this->updateSessionDuration($sessionId);
                return false;
            }
            
            // Record the view with enhanced data
            $stmt = $this->pdo->prepare("
                INSERT INTO page_views (post_id, page_type, ip_address, user_agent, referer, session_id, traffic_source, country)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$postId, $pageType, $ipAddress, $userAgent, $referer, $sessionId, $trafficSource, $country]);
            
            // Track session start
            $this->trackSessionStart($sessionId, $ipAddress, $userAgent, $referer, $country);
            
            // Update post view count
            if ($postId) {
                $this->updatePostViewCount($postId);
            }
            
            // Update daily stats
            $this->updateDailyStats('views');
            
            return true;
        } catch (Exception $e) {
            error_log("Error tracking view: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track a download
     */
    public function trackDownload($postId, $fileName, $filePath, $fileSize = null, $status = 'success', $errorMessage = null) {
        try {
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $sessionId = session_id();
            
            // Record the download with status
            $stmt = $this->pdo->prepare("
                INSERT INTO downloads (post_id, file_name, file_path, file_size, ip_address, user_agent, session_id, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$postId, $fileName, $filePath, $fileSize, $ipAddress, $userAgent, $sessionId, $status, $errorMessage]);
            
            // Only update counts for successful downloads
            if ($status === 'success') {
                // Update post download count
                $this->updatePostDownloadCount($postId);
                
                // Update daily stats
                $this->updateDailyStats('downloads');
            }
            
            // Update session as engaged (not bounce)
            $this->updateSessionEngagement($sessionId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error tracking download: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update session engagement (mark as non-bounce)
     */
    private function updateSessionEngagement($sessionId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sessions 
                SET is_bounce = 0,
                    last_activity = NOW()
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            error_log("Error updating session engagement: " . $e->getMessage());
        }
    }
    
    /**
     * Check if view was already recorded in this session
     */
    private function isViewAlreadyRecorded($postId, $sessionId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM page_views 
            WHERE post_id = ? AND session_id = ? AND view_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$postId, $sessionId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Update post view count
     */
    private function updatePostViewCount($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$postId]);
    }
    
    /**
     * Update post download count
     */
    private function updatePostDownloadCount($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$postId]);
    }
    
    /**
     * Update daily statistics
     */
    private function updateDailyStats($type) {
        $today = date('Y-m-d');
        
        // Get or create today's stats
        $stmt = $this->pdo->prepare("
            INSERT INTO daily_stats (stat_date, total_views, total_downloads, unique_visitors) 
            VALUES (?, 0, 0, 0)
            ON DUPLICATE KEY UPDATE 
            total_views = CASE WHEN ? = 'views' THEN total_views + 1 ELSE total_views END,
            total_downloads = CASE WHEN ? = 'downloads' THEN total_downloads + 1 ELSE total_downloads END
        ");
        $stmt->execute([$today, $type, $type]);
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get traffic source from referer
     */
    private function getTrafficSource($referer) {
        if (empty($referer)) {
            return 'direct';
        }
        
        $referer = strtolower($referer);
        
        // Search Engines
        if (strpos($referer, 'google') !== false) return 'google';
        if (strpos($referer, 'bing') !== false) return 'bing';
        if (strpos($referer, 'yahoo') !== false) return 'yahoo';
        if (strpos($referer, 'duckduckgo') !== false) return 'duckduckgo';
        
        // Social Media
        if (strpos($referer, 'facebook') !== false || strpos($referer, 'fb.') !== false) return 'facebook';
        if (strpos($referer, 'twitter') !== false || strpos($referer, 't.co') !== false) return 'twitter';
        if (strpos($referer, 'instagram') !== false) return 'instagram';
        if (strpos($referer, 'linkedin') !== false) return 'linkedin';
        if (strpos($referer, 'youtube') !== false) return 'youtube';
        if (strpos($referer, 'tiktok') !== false) return 'tiktok';
        if (strpos($referer, 'whatsapp') !== false) return 'whatsapp';
        if (strpos($referer, 'telegram') !== false) return 'telegram';
        
        return 'referral';
    }
    
    /**
     * Get country from IP address (simplified version)
     * For production, use GeoIP2 or similar service
     */
    private function getCountryFromIP($ip) {
        // Check if localhost/private IP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Local';
        }
        
        try {
            // Use free IP geolocation API (limited requests)
            $apiUrl = "http://ip-api.com/json/{$ip}?fields=country,countryCode";
            
            // Set timeout to avoid blocking
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['country'])) {
                    return $data['country'];
                }
            }
        } catch (Exception $e) {
            error_log("GeoIP error: " . $e->getMessage());
        }
        
        return 'Unknown';
    }
    
    /**
     * Track session start
     */
    private function trackSessionStart($sessionId, $ipAddress, $userAgent, $referer, $country) {
        try {
            // Check if session already exists
            $stmt = $this->pdo->prepare("SELECT id FROM sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() == 0) {
                // Create new session
                $stmt = $this->pdo->prepare("
                    INSERT INTO sessions (session_id, ip_address, user_agent, referer, country, start_time, last_activity)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$sessionId, $ipAddress, $userAgent, $referer, $country]);
            }
        } catch (Exception $e) {
            error_log("Error tracking session: " . $e->getMessage());
        }
    }
    
    /**
     * Update session duration
     */
    private function updateSessionDuration($sessionId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sessions 
                SET last_activity = NOW(),
                    page_views = page_views + 1,
                    duration = TIMESTAMPDIFF(SECOND, start_time, NOW())
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            error_log("Error updating session: " . $e->getMessage());
        }
    }
    
    /**
     * Get analytics data for dashboard
     */
    public function getAnalyticsData() {
        try {
            $data = [];
            
            // Total views and downloads
            $stmt = $this->pdo->query("SELECT SUM(view_count) as total_views, SUM(download_count) as total_downloads FROM posts WHERE deleted_at IS NULL");
            $totals = $stmt->fetch();
            $data['total_views'] = $totals['total_views'] ?? 0;
            $data['total_downloads'] = $totals['total_downloads'] ?? 0;
            
            // Today's stats
            $stmt = $this->pdo->prepare("SELECT * FROM daily_stats WHERE stat_date = ?");
            $stmt->execute([date('Y-m-d')]);
            $todayStats = $stmt->fetch();
            $data['today_views'] = $todayStats['total_views'] ?? 0;
            $data['today_downloads'] = $todayStats['total_downloads'] ?? 0;
            
            // Weekly trend
            $stmt = $this->pdo->query("
                SELECT stat_date, total_views, total_downloads 
                FROM daily_stats 
                WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ORDER BY stat_date ASC
            ");
            $data['weekly_trend'] = $stmt->fetchAll();
            
            // Top viewed posts
            $stmt = $this->pdo->query("
                SELECT title, view_count, download_count 
                FROM posts 
                WHERE deleted_at IS NULL AND view_count > 0
                ORDER BY view_count DESC 
                LIMIT 10
            ");
            $data['top_posts'] = $stmt->fetchAll();
            
            // Popular downloads
            $stmt = $this->pdo->query("
                SELECT title, download_count, view_count
                FROM posts 
                WHERE deleted_at IS NULL AND download_count > 0
                ORDER BY download_count DESC 
                LIMIT 10
            ");
            $data['popular_downloads'] = $stmt->fetchAll();
            
            return $data;
        } catch (Exception $e) {
            error_log("Error getting analytics data: " . $e->getMessage());
            return [
                'total_views' => 0,
                'total_downloads' => 0,
                'today_views' => 0,
                'today_downloads' => 0,
                'weekly_trend' => [],
                'top_posts' => [],
                'popular_downloads' => []
            ];
        }
    }
    
    /**
     * Get real-time stats for the last 24 hours
     */
    public function getRealTimeStats() {
        try {
            $stats = [];
            
            // Views in last 24 hours
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM page_views 
                WHERE view_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stats['views_24h'] = $stmt->fetchColumn();
            
            // Downloads in last 24 hours
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM downloads 
                WHERE download_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stats['downloads_24h'] = $stmt->fetchColumn();
            
            // Unique visitors today
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT ip_address) as count 
                FROM page_views 
                WHERE DATE(view_date) = CURDATE()
            ");
            $stats['unique_visitors_today'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting real-time stats: " . $e->getMessage());
            return [
                'views_24h' => 0,
                'downloads_24h' => 0,
                'unique_visitors_today' => 0
            ];
        }
    }
    
    /**
     * Get bounce rate statistics
     */
    public function getBounceRate($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_sessions,
                    SUM(is_bounce) as bounce_sessions,
                    ROUND((SUM(is_bounce) / COUNT(*)) * 100, 2) as bounce_rate,
                    ROUND(AVG(duration), 0) as avg_duration,
                    ROUND(AVG(page_views), 2) as avg_pages_per_session
                FROM sessions 
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting bounce rate: " . $e->getMessage());
            return [
                'total_sessions' => 0,
                'bounce_sessions' => 0,
                'bounce_rate' => 0,
                'avg_duration' => 0,
                'avg_pages_per_session' => 0
            ];
        }
    }
    
    /**
     * Get geographic analytics
     */
    public function getGeographicStats($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    country,
                    COUNT(DISTINCT session_id) as sessions,
                    COUNT(*) as page_views,
                    ROUND(AVG(duration), 0) as avg_duration
                FROM sessions 
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY country
                ORDER BY sessions DESC
                LIMIT 10
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting geographic stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get traffic sources breakdown
     */
    public function getTrafficSourcesBreakdown($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    traffic_source,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT post_id) as posts_viewed
                FROM page_views 
                WHERE view_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY traffic_source
                ORDER BY visits DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting traffic sources: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get download success rate
     */
    public function getDownloadSuccessRate($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    ROUND((COUNT(*) / (SELECT COUNT(*) FROM downloads WHERE download_date >= DATE_SUB(NOW(), INTERVAL ? DAY))) * 100, 2) as percentage
                FROM downloads 
                WHERE download_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY status
            ");
            $stmt->execute([$days, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting download success rate: " . $e->getMessage());
            return [];
        }
    }
}
?>