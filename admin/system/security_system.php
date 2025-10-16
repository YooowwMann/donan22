<?php
/**
 * DONAN22 Security System
 * Sistem keamanan komprehensif untuk melindungi dari berbagai ancaman
 */

// Proteksi akses langsung
if (!defined('ADMIN_ACCESS') && !isset($_SESSION)) {
    http_response_code(403);
    die('Access Denied');
}

class SecurityManager {
    private $pdo;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Sanitize input untuk mencegah XSS dan injection
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'html':
                // Allow safe HTML tags
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            default:
                // String sanitization
                return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check if token is expired (1 hour)
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($eventType, $details = '', $severity = 'medium') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs 
                (event_type, ip_address, user_agent, request_uri, post_data, user_id, severity, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $eventType,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REQUEST_URI'] ?? '',
                !empty($_POST) ? json_encode($_POST) : null,
                $_SESSION['admin_id'] ?? null,
                $severity,
                $details
            ]);
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Check for SQL injection patterns
     */
    public function detectSQLInjection($input) {
        if (is_array($input)) {
            foreach ($input as $value) {
                if ($this->detectSQLInjection($value)) {
                    return true;
                }
            }
            return false;
        }
        
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*>)/i',
            '/(\'|\"|;|\-\-|\#)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('sql_injection', "Detected pattern: $pattern in input: " . substr($input, 0, 200), 'high');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for XSS patterns
     */
    public function detectXSS($input) {
        if (is_array($input)) {
            foreach ($input as $value) {
                if ($this->detectXSS($value)) {
                    return true;
                }
            }
            return false;
        }
        
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('xss_attempt', "Detected XSS pattern in input: " . substr($input, 0, 200), 'high');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rate limiting untuk login attempts
     */
    public function checkLoginAttempts($ipAddress, $username = '') {
        try {
            // Check recent failed attempts
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE ip_address = ? AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $this->lockoutDuration]);
            $failedAttempts = $stmt->fetchColumn();
            
            if ($failedAttempts >= $this->maxLoginAttempts) {
                $this->logSecurityEvent('failed_login', "IP blocked due to too many failed attempts: $ipAddress", 'high');
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error checking login attempts: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log login attempt
     */
    public function logLoginAttempt($username, $success, $ipAddress = null) {
        try {
            $ipAddress = $ipAddress ?? $this->getClientIP();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (ip_address, username, success, user_agent, attempted_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $ipAddress,
                $username,
                $success ? 1 : 0,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            if (!$success) {
                $this->logSecurityEvent('failed_login', "Failed login for username: $username", 'medium');
            }
        } catch (Exception $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        $maxSize = $maxSize ?? (50 * 1024 * 1024); // 50MB default
        
        // Check file errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $this->logSecurityEvent('file_upload', "File too large: " . $file['size'] . " bytes", 'medium');
            return ['success' => false, 'error' => 'File too large'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $this->logSecurityEvent('file_upload', "Unauthorized file type: $mimeType", 'high');
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        // Check for executable files
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
        
        if (in_array($extension, $dangerousExtensions)) {
            $this->logSecurityEvent('file_upload', "Dangerous file extension: $extension", 'critical');
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        return ['success' => true, 'mime_type' => $mimeType];
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Generate secure password
     */
    public function generateSecurePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
    }
    
    /**
     * Hash password securely
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Initialize security manager
try {
    $security = new SecurityManager($pdo);
    
    // Auto-check for security threats in POST data
    if (!empty($_POST)) {
        foreach ($_POST as $key => $value) {
            if ($security->detectSQLInjection($value)) {
                http_response_code(403);
                die('Security violation detected');
            }
            
            if ($security->detectXSS($value)) {
                http_response_code(403);
                die('Security violation detected');
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Security system initialization error: " . $e->getMessage());
}

/**
 * Auto-Ban System Functions
 */

/**
 * Check if IP should be auto-banned
 * @param PDO $pdo Database connection
 * @param string $ip IP address to check
 * @param int $maxAttempts Max failed attempts before ban
 * @param int $timeWindow Time window in seconds (default 15 minutes)
 * @return array ['should_ban' => bool, 'attempts' => int]
 */
function checkAutoBan($pdo, $ip, $maxAttempts = 5, $timeWindow = 900) {
    try {
        // Get failed login attempts within time window
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM security_logs 
            WHERE ip_address = ? 
            AND event_type IN ('failed_login', 'login_failed') 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $timeWindow]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $attempts = $result['attempts'] ?? 0;
        $shouldBan = $attempts >= $maxAttempts;
        
        return [
            'should_ban' => $shouldBan,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts
        ];
    } catch (Exception $e) {
        error_log("Auto-ban check error: " . $e->getMessage());
        return ['should_ban' => false, 'attempts' => 0, 'max_attempts' => $maxAttempts];
    }
}

/**
 * Auto-ban IP address
 * @param PDO $pdo Database connection
 * @param string $ip IP to ban
 * @param int $duration Ban duration in minutes
 * @param string $reason Ban reason
 * @return bool Success status
 */
function autoBanIP($pdo, $ip, $duration = 15, $reason = 'Auto-banned: Too many failed login attempts') {
    try {
        // Check if already banned
        $stmt = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) {
            return false; // Already banned
        }
        
        $lockedUntil = date('Y-m-d H:i:s', time() + ($duration * 60));
        
        $stmt = $pdo->prepare("
            INSERT INTO blocked_ips (ip_address, reason, auto_ban_enabled, blocked_at, locked_until, created_at) 
            VALUES (?, ?, 1, NOW(), ?, NOW())
        ");
        $result = $stmt->execute([$ip, $reason, $lockedUntil]);
        
        if ($result) {
            // Log auto-ban event
            $logStmt = $pdo->prepare("
                INSERT INTO security_logs (event_type, ip_address, details, severity, created_at) 
                VALUES ('auto_ban', ?, ?, 'high', NOW())
            ");
            $logStmt->execute([$ip, "Auto-banned for $duration minutes: $reason"]);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Auto-ban error: " . $e->getMessage());
        return false;
    }
}

/**
 * Auto-unlock expired bans
 * @param PDO $pdo Database connection
 * @return int Number of unlocked IPs
 */
function autoUnlockExpiredBans($pdo) {
    try {
        // Get expired bans
        $stmt = $pdo->query("
            SELECT id, ip_address 
            FROM blocked_ips 
            WHERE auto_ban_enabled = 1 
            AND locked_until IS NOT NULL 
            AND locked_until < NOW()
        ");
        $expiredBans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $unlocked = 0;
        foreach ($expiredBans as $ban) {
            $deleteStmt = $pdo->prepare("DELETE FROM blocked_ips WHERE id = ?");
            if ($deleteStmt->execute([$ban['id']])) {
                $unlocked++;
                
                // Log unlock
                $logStmt = $pdo->prepare("
                    INSERT INTO security_logs (event_type, ip_address, details, severity, created_at) 
                    VALUES ('auto_unlock', ?, 'Auto-unlocked after ban expiration', 'low', NOW())
                ");
                $logStmt->execute([$ban['ip_address']]);
            }
        }
        
        return $unlocked;
    } catch (Exception $e) {
        error_log("Auto-unlock error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if IP is currently banned
 * @param PDO $pdo Database connection
 * @param string $ip IP to check
 * @return bool True if banned
 */
function isIPBanned($pdo, $ip) {
    try {
        // Auto-unlock expired bans first
        autoUnlockExpiredBans($pdo);
        
        $stmt = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        error_log("IP ban check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get auto-ban settings from database
 * @param PDO $pdo Database connection
 * @return array Settings array
 */
function getAutoBanSettings($pdo) {
    $defaults = [
        'auto_ban_enabled' => false,
        'max_login_attempts' => 5,
        'ban_duration' => 15, // minutes
        'time_window' => 15 // minutes
    ];
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'auto_ban_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            if ($key === 'auto_ban_enabled') {
                $defaults[$key] = (bool)$value;
            } else {
                $defaults[$key] = (int)$value;
            }
        }
    } catch (Exception $e) {
        error_log("Get auto-ban settings error: " . $e->getMessage());
    }
    
    return $defaults;
}
?>