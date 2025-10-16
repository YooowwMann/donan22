<?php
/**
 * DONAN22 - Monetization Manager
 * Handle ShrinkMe.io shortlink creation & tracking
 */

class MonetizationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get active monetizer service
     */
    public function getActiveMonetizer() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM monetizer_config 
            WHERE is_active = 1 
            ORDER BY priority DESC, id ASC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top performing monetized links
     */
    public function getTopLinks($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT ml.*, p.title as post_title
            FROM monetized_links ml
            LEFT JOIN posts p ON ml.post_id = p.id
            WHERE ml.is_active = 1
            ORDER BY ml.total_clicks DESC, ml.estimated_revenue DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create monetized link with ShrinkMe.io
     */
    public function createMonetizedLink($original_url, $post_id = null, $options = []) {
        // Generate unique short code for internal tracking
        $short_code = $this->generateShortCode();
        
        // Verify short code is unique in our database
        $checkStmt = $this->pdo->prepare("SELECT id FROM monetized_links WHERE short_code = ?");
        $checkStmt->execute([$short_code]);
        
        if ($checkStmt->fetch()) {
            // Extremely rare but possible, regenerate
            error_log("⚠️ Short code collision detected, regenerating...");
            $short_code = $this->generateShortCode();
        }
        
        error_log("🎯 Creating monetized link:");
        error_log("   - Short Code: {$short_code}");
        error_log("   - Original URL: {$original_url}");
        error_log("   - Post ID: " . ($post_id ?? 'N/A'));
        
        // Get active monetizer (ShrinkMe.io)
        $monetizer = $this->getActiveMonetizer();
        
        $monetized_url = null;
        $monetizer_service = null;
        
        if ($monetizer && !empty($monetizer['api_key']) && $monetizer['api_key'] !== 'YOUR_SHRINKME_API_KEY') {
            // Create shortened URL via ShrinkMe.io API with custom alias
            $monetized_url = $this->shrinkMeShorten($original_url, $monetizer['api_key'], $short_code);
            $monetizer_service = $monetizer['service_name'];
            
            // If failed (possibly due to duplicate), try without alias
            if (!$monetized_url) {
                error_log("   ⚠️ Failed with custom alias, retrying without alias...");
                $monetized_url = $this->shrinkMeShorten($original_url, $monetizer['api_key'], null);
            }
        }
        
        // Insert into database
        $stmt = $this->pdo->prepare("
            INSERT INTO monetized_links 
            (post_id, original_url, short_code, monetizer_service, monetized_url, 
             download_title, file_size, file_password, version, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $post_id,
            $original_url,
            $short_code,
            $monetizer_service,
            $monetized_url,
            $options['title'] ?? null,
            $options['file_size'] ?? null,
            $options['password'] ?? null,
            $options['version'] ?? null,
            $_SESSION['user_id'] ?? null
        ]);
        
        $link_id = $this->pdo->lastInsertId();
        
        error_log("💾 Link saved to database (ID: {$link_id})");
        
        return [
            'id' => $link_id,
            'short_code' => $short_code,
            'monetized_url' => $monetized_url,
            'local_url' => SITE_URL . '/go/' . $short_code
        ];
    }
    
    /**
     * ShrinkMe.io API - Create short link with custom alias
     */
    private function shrinkMeShorten($url, $api_key, $alias = null) {
        // Build API URL with custom alias if provided
        $api_url = "https://shrinkme.io/api?api={$api_key}&url=" . urlencode($url);
        
        if ($alias) {
            $api_url .= "&alias=" . urlencode($alias);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            
            // Check for success response
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['shortenedUrl'])) {
                return $data['shortenedUrl'];
            }
            
            // Sometimes returns direct URL
            if (filter_var($response, FILTER_VALIDATE_URL)) {
                return trim($response);
            }
        }
        
        return null;
    }
    
    /**
     * Generate unique short code
     */
    private function generateShortCode($length = 8) {
        do {
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            // Check if code exists
            $stmt = $this->pdo->prepare("SELECT id FROM monetized_links WHERE short_code = ?");
            $stmt->execute([$code]);
            
        } while ($stmt->fetch());
        
        return $code;
    }
    
    /**
     * Get link by short code
     */
    public function getLinkByCode($short_code) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM monetized_links 
            WHERE short_code = ? AND is_active = 1
        ");
        $stmt->execute([$short_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get links by post ID
     */
    public function getLinksByPost($post_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM monetized_links 
            WHERE post_id = ? AND is_active = 1
            ORDER BY created_at DESC
        ");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search post by shortlink code for admin debugging
     */
    public function searchPostByShortlinkForAdmin($short_code) {
        // Clean the short code - extract only alphanumeric part
        $short_code = preg_replace('/[^a-zA-Z0-9]/', '', $short_code);
        
        if (empty($short_code)) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id as post_id,
                p.title,
                p.slug,
                p.excerpt,
                p.content,
                p.category_id,
                p.status,
                p.created_at,
                p.updated_at,
                p.deleted_at,
                p.view_count,
                p.download_count,
                c.name as category_name,
                c.slug as category_slug,
                ml.id as link_id,
                ml.short_code,
                ml.original_url,
                ml.monetized_url,
                ml.download_title,
                ml.file_size,
                ml.file_password,
                ml.version,
                ml.total_clicks,
                ml.total_downloads,
                ml.estimated_revenue,
                ml.is_active as link_active,
                ml.created_at as link_created_at,
                a.username as author_name
            FROM monetized_links ml
            INNER JOIN posts p ON ml.post_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN administrators a ON p.author_id = a.id
            WHERE ml.short_code = ? 
            AND ml.is_active = 1
        ");
        
        $stmt->execute([$short_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Extract short code from full shortlink URL
     */
    public function extractShortCodeFromUrl($url) {
        // Handle various URL formats:
        // http://localhost/donan22/go/5F053521
        // /go/5F053521
        // 5F053521
        
        if (preg_match('/\/go\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // If it's just the code itself
        if (preg_match('/^[a-zA-Z0-9]+$/', $url)) {
            return $url;
        }
        
        return null;
    }
    
    /**
     * Track event (click, download, share, view)
     */
    public function trackEvent($link_id, $event_type = 'click', $monetizer_service = null) {
        // Get user info
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Calculate revenue
        $revenue = 0;
        
        if ($monetizer_service) {
            $stmt = $this->pdo->prepare("
                SELECT cpm_rate FROM monetizer_config WHERE service_name = ?
            ");
            $stmt->execute([$monetizer_service]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config && $event_type == 'click') {
                // CPM to per-click: $7 CPM = $0.007 per click
                $revenue = $config['cpm_rate'] / 1000;
            }
        }
        
        // Insert tracking
        $stmt = $this->pdo->prepare("
            INSERT INTO monetization_stats 
            (link_id, event_type, monetizer_service, user_ip, user_agent, referrer, revenue_earned) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $link_id,
            $event_type,
            $monetizer_service,
            $user_ip,
            $user_agent,
            $referrer,
            $revenue
        ]);
        
        // Update link statistics
        if ($event_type == 'click') {
            $this->pdo->prepare("
                UPDATE monetized_links 
                SET total_clicks = total_clicks + 1, 
                    estimated_revenue = estimated_revenue + ? 
                WHERE id = ?
            ")->execute([$revenue, $link_id]);
        } elseif ($event_type == 'download') {
            $this->pdo->prepare("
                UPDATE monetized_links 
                SET total_downloads = total_downloads + 1 
                WHERE id = ?
            ")->execute([$link_id]);
        }
        
        return $revenue;
    }
    
    /**
     * Get revenue statistics
     */
    public function getRevenueStats($period = 'today') {
        switch ($period) {
            case 'today':
                $where = "date = CURDATE()";
                break;
            case 'yesterday':
                $where = "date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $where = "date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                $where = "1=1";
        }
        
        $stmt = $this->pdo->query("
            SELECT 
                SUM(total_clicks) as total_clicks,
                SUM(total_downloads) as total_downloads,
                SUM(total_revenue) as total_revenue,
                monetizer_service 
            FROM revenue_daily 
            WHERE {$where} 
            GROUP BY monetizer_service
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

