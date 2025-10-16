<?php
/**
 * Rate Limiting Helper
 * Protects public endpoints from spam and abuse
 */

class RateLimiter {
    private $pdo;
    private $cacheFile;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->cacheFile = __DIR__ . '/../cache/rate_limits.json';
    }
    
    /**
     * Check if IP is rate limited
     * @param string $action Action being performed (e.g., 'contact', 'subscribe')
     * @param string $ipAddress IP address
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public function checkRateLimit($action, $ipAddress, $maxAttempts = 5, $timeWindow = 300) {
        $this->cleanupOldEntries();
        
        $limits = $this->loadLimits();
        $key = md5($action . $ipAddress);
        $now = time();
        
        if (!isset($limits[$key])) {
            $limits[$key] = [
                'action' => $action,
                'ip' => $ipAddress,
                'attempts' => 1,
                'first_attempt' => $now,
                'last_attempt' => $now
            ];
            $this->saveLimits($limits);
            return true;
        }
        
        $entry = $limits[$key];
        
        // Reset if time window has passed
        if ($now - $entry['first_attempt'] > $timeWindow) {
            $limits[$key] = [
                'action' => $action,
                'ip' => $ipAddress,
                'attempts' => 1,
                'first_attempt' => $now,
                'last_attempt' => $now
            ];
            $this->saveLimits($limits);
            return true;
        }
        
        // Check if exceeded max attempts
        if ($entry['attempts'] >= $maxAttempts) {
            $this->logRateLimitExceeded($action, $ipAddress);
            return false;
        }
        
        // Increment attempts
        $limits[$key]['attempts']++;
        $limits[$key]['last_attempt'] = $now;
        $this->saveLimits($limits);
        
        return true;
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts($action, $ipAddress, $maxAttempts = 5) {
        $limits = $this->loadLimits();
        $key = md5($action . $ipAddress);
        
        if (!isset($limits[$key])) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $limits[$key]['attempts']);
    }
    
    /**
     * Get time until reset
     */
    public function getTimeUntilReset($action, $ipAddress, $timeWindow = 300) {
        $limits = $this->loadLimits();
        $key = md5($action . $ipAddress);
        
        if (!isset($limits[$key])) {
            return 0;
        }
        
        $elapsed = time() - $limits[$key]['first_attempt'];
        return max(0, $timeWindow - $elapsed);
    }
    
    /**
     * Load limits from cache
     */
    private function loadLimits() {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        
        $content = @file_get_contents($this->cacheFile);
        if ($content === false) {
            return [];
        }
        
        $limits = json_decode($content, true);
        return is_array($limits) ? $limits : [];
    }
    
    /**
     * Save limits to cache
     */
    private function saveLimits($limits) {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        @file_put_contents($this->cacheFile, json_encode($limits, JSON_PRETTY_PRINT));
    }
    
    /**
     * Cleanup old entries
     */
    private function cleanupOldEntries() {
        $limits = $this->loadLimits();
        $now = time();
        $maxAge = 3600; // 1 hour
        
        foreach ($limits as $key => $entry) {
            if ($now - $entry['last_attempt'] > $maxAge) {
                unset($limits[$key]);
            }
        }
        
        $this->saveLimits($limits);
    }
    
    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded($action, $ipAddress) {
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO security_logs 
                    (event_type, ip_address, user_agent, request_uri, details, severity, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    'rate_limit_exceeded',
                    $ipAddress,
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['REQUEST_URI'] ?? '',
                    "Action: {$action}",
                    'medium'
                ]);
            } catch (Exception $e) {
                error_log("Failed to log rate limit: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
