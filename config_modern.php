<?php
// ====================================
// DONAN22 MODERN CONFIGURATION
// Software Download & IT Learning Platform
// ====================================

// Error reporting - based on ENVIRONMENT variable (DEFINE FIRST!)
$environment = getenv('ENVIRONMENT') ?: 'production';
$debugMode = filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN);

if ($environment === 'development' || $debugMode === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    define('DEBUG_MODE', false);
}

// Security Headers - Set ONCE at the beginning
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy (disable unnecessary features)
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    
    // HSTS - Strict Transport Security (for production with SSL)
    if (!DEBUG_MODE && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Additional security headers
    header('X-Download-Options: noopen');
    header('X-Permitted-Cross-Domain-Policies: none');
    
    // Remove server signatures
    header_remove('X-Powered-By');
    
    // Content Security Policy for CKEditor and modern features
    // Updated to include Monetag (Propeller) ad network domains
    // RELAXED for Monetag popunder ads to work properly
    $csp = "default-src 'self' https:; "
         . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; "
         . "script-src-elem 'self' 'unsafe-inline' https: data:; "
         . "style-src 'self' 'unsafe-inline' https: data:; "
         . "font-src 'self' https: data:; "
         . "img-src 'self' data: https: blob:; "
         . "media-src 'self' blob: data: https:; "
         . "connect-src 'self' https: wss: data: blob:; "
         . "worker-src 'self' blob: data:; "
         . "frame-src 'self' https: data:; "
         . "child-src 'self' https: data: blob:; "
         . "object-src 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self' https:; "
         . "frame-ancestors 'none';";
    header("Content-Security-Policy: $csp");
}

// ====================================
// DATABASE & CORE SETTINGS
// ====================================

// Load environment variables from .env file if it exists
// ALWAYS override system env vars with .env file values (localhost priority)
if (file_exists(__DIR__ . '/.env')) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // ALWAYS override - .env file takes priority!
            putenv("$key=$value");
            $_ENV[$key] = $value; // Also set in $_ENV superglobal
        }
    }
}

// Secure session configuration
// Only set ini values if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    // Force secure cookies in production
    $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', 0); // Session cookie
    ini_set('session.gc_maxlifetime', getenv('SESSION_TIMEOUT') ?: 7200);
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['session_created'])) {
        $_SESSION['session_created'] = time();
    } elseif (time() - $_SESSION['session_created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['session_created'] = time();
    }
}

// Set timezone to Indonesia (WIB - Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Database configuration - Environment variables with fallbacks
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'donan22');
define('DB_CHARSET', 'utf8mb4');

// Site configuration - Environment variables with fallbacks
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = getenv('SITE_HOST') ?: $_SERVER['HTTP_HOST'] ?: 'localhost';
$basePath = getenv('SITE_BASE_PATH') ?: '/donan22';

define('SITE_URL', getenv('SITE_URL') ?: $protocol . '://' . $host . $basePath);
define('SITE_NAME', getenv('SITE_NAME') ?: 'Donan22');
define('SITE_DESCRIPTION', getenv('SITE_DESCRIPTION') ?: 'Software Download & IT Learning Hub');
define('ADMIN_PATH', '/admin');
define('UPLOAD_PATH', 'uploads/');
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'zip', 'rar', '7z']);

// Pagination
define('POSTS_PER_PAGE', 12);
define('COMMENTS_PER_PAGE', 20);
define('ADMIN_POSTS_PER_PAGE', 20);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// Security settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('CSRF_TOKEN_EXPIRE', 3600);

// Database connection with error handling
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ]
    );
} catch(PDOException $e) {
    if (DEBUG_MODE) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Website sedang dalam maintenance. Silakan coba lagi nanti.");
    }
}

/**
 * Get list of categories with various filtering options
 * 
 * @param array $options Array of options:
 *                      - includePostCounts: bool - Whether to include post counts
 *                      - activeOnly: bool - Whether to only return active categories
 *                      - withPosts: bool - Whether to only return categories that have posts
 *                      - postTypeId: int|null - Filter by post type ID
 *                      - parentId: int|null - Filter by parent category ID
 * @return array Array of categories
 */
function getCategories($options = []) {
    global $pdo;
    
    // Set default options
    $defaults = [
        'includePostCounts' => false,
        'activeOnly' => true,
        'withPosts' => false,
        'postTypeId' => null,
        'parentId' => null
    ];
    $options = array_merge($defaults, $options);
    
    // Build base query
    $query = "SELECT c.id, c.name, c.slug, c.description, c.image, c.status, 
                     c.sort_order, c.parent_id, c.meta_title, c.meta_description";
    
    if ($options['includePostCounts']) {
        $query .= ", COUNT(p.id) as total_posts,
                    COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published_posts";
    }
    
    $query .= " FROM categories c";
    
    if ($options['includePostCounts'] || $options['withPosts']) {
        $query .= " LEFT JOIN posts p ON c.id = p.category_id";
        if ($options['withPosts']) {
            $query .= " AND p.status = 'published'";
        }
    }
    
    // Build conditions
    $conditions = [];
    $params = [];
    
    if ($options['activeOnly']) {
        $conditions[] = "(c.status = 'active' OR c.status IS NULL OR c.status = '')";
    }
    
    if ($options['parentId'] !== null) {
        $conditions[] = "c.parent_id " . ($options['parentId'] ? "= ?" : "IS NULL");
        if ($options['parentId']) $params[] = $options['parentId'];
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    if ($options['includePostCounts'] || $options['withPosts']) {
        $query .= " GROUP BY c.id";
    }
    
    $query .= " ORDER BY c.sort_order, c.name ASC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// Auto-check dan insert admin jika diperlukan
if (!function_exists('autoCheckAdmin')) {
    function autoCheckAdmin() {
        global $pdo;
        try {
            // Cek apakah tabel administrators ada dan admin utama ada
            $stmt = $pdo->query("SHOW TABLES LIKE 'administrators'");
            if (!$stmt->fetch()) return false;
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM administrators WHERE username = 'admin' AND status = 'active'");
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                // Insert admin otomatis
                $passwordHash = password_hash('adnan123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO administrators 
                    (username, email, password_hash, full_name, role, status, login_attempts, created_at, updated_at) 
                    VALUES 
                    ('admin', 'admin@donan22.com', ?, 'Super Administrator', 'super_admin', 'active', 0, NOW(), NOW()),
                    ('editor', 'editor@donan22.com', ?, 'Content Editor', 'editor', 'active', 0, NOW(), NOW()),
                    ('moderator', 'mod@donan22.com', ?, 'Content Moderator', 'admin', 'active', 0, NOW(), NOW())
                ");
                $stmt->execute([$passwordHash, $passwordHash, $passwordHash]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Jalankan auto-check admin (hanya untuk halaman admin)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/admin/') !== false) {
    autoCheckAdmin();
}

// ====================================
// UTILITY FUNCTIONS
// ====================================

/**
 * Generate secure slug from text
 */
function createSlug($text, $table = 'posts', $id = null) {
    global $pdo;
    
    // Basic slug creation
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Ensure uniqueness
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT COUNT(*) FROM $table WHERE slug = :slug";
        $params = ['slug' => $slug];
        
        if ($id) {
            $sql .= " AND id != :id";
            $params['id'] = $id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = floor(log($bytes) / log(1024));
    $power = min($power, count($units) - 1);
    
    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari lalu';
    if ($time < 31536000) return floor($time/2592000) . ' bulan lalu';
    
    return floor($time/31536000) . ' tahun lalu';
}

/**
 * Get site settings with caching
 */
/**
 * Get site settings
 */
function getSiteSettings() {
    global $pdo;
    static $settings = null;
    
    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT option_name, option_value, option_type FROM settings WHERE is_autoload = 1");
            $settings = [];
            
            while ($row = $stmt->fetch()) {
                $value = $row['option_value'];
                
                // Type casting
                switch ($row['option_type']) {
                    case 'number':
                        $value = is_numeric($value) ? (float)$value : 0;
                        break;
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    case 'json':
                        $value = json_decode($value, true) ?: [];
                        break;
                }
                
                $settings[$row['option_name']] = $value;
            }
            
            // Fallback values
            $defaults = [
                'site_name' => SITE_NAME,
                'site_description' => SITE_DESCRIPTION,
                'contact_email' => getenv('CONTACT_EMAIL') ?: 'admin@' . (getenv('SITE_HOST') ?: $_SERVER['HTTP_HOST'] ?: 'localhost'),
                'posts_per_page' => POSTS_PER_PAGE,
                'theme_color' => '#3B82F6'
            ];
            
            $settings = array_merge($defaults, $settings);
            
        } catch (PDOException $e) {
            // Fallback to defaults on error
            $settings = [
                'site_name' => SITE_NAME,
                'site_description' => SITE_DESCRIPTION,
                'contact_email' => getenv('CONTACT_EMAIL') ?: 'admin@' . (getenv('SITE_HOST') ?: $_SERVER['HTTP_HOST'] ?: 'localhost'),
                'posts_per_page' => POSTS_PER_PAGE,
                'theme_color' => '#3B82F6'
            ];
        }
    }
    
    return $settings;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']) && 
           isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
}

/**
 * Require admin login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Clear any corrupted session data
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['login_time']);
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . SITE_URL . ADMIN_PATH . '/login.php');
        exit;
    }
    
    // Update last activity
    $_SESSION['login_time'] = time();
}

/**
 * Get current admin user data
 */
function getCurrentAdmin() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM administrators WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        // Return null if no admin found or fetch failed
        return $admin ? $admin : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Check admin permissions
 */
function hasPermission($permission) {
    // Try to use RoleManager if available
    if (isset($GLOBALS['roleManager'])) {
        return $GLOBALS['roleManager']->hasPermission($permission);
    }
    
    // Fallback to legacy permission system
    $admin = getCurrentAdmin();
    if (!$admin) return false;
    
    $role = $admin['role'];
    
    $permissions = [
        'superadmin' => ['*'], // All permissions
        'super_admin' => ['*'], // Legacy compatibility
        'admin' => ['posts', 'comments', 'media', 'categories', 'tags', 'system_access'],
        'moderator' => ['posts', 'comments', 'media', 'system_access'],
        'editor' => ['posts', 'comments', 'media', 'system_access']
    ];
    
    return isset($permissions[$role]) && 
           (in_array('*', $permissions[$role]) || in_array($permission, $permissions[$role]));
}

/**
 * Require admin permission
 */
function requirePermission($permission, $redirectTo = 'dashboard.php') {
    if (!hasPermission($permission)) {
        header("Location: $redirectTo?error=" . urlencode("Access denied. Insufficient permissions."));
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRE) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token) &&
           isset($_SESSION['csrf_token_time']) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_EXPIRE;
}

/**
 * Sanitize output
 */
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get post types
 */
function getPostTypes() {
    global $pdo;
    static $types = null;
    
    if ($types === null) {
        try {
            $stmt = $pdo->query("SELECT * FROM post_types ORDER BY id");
            $types = $stmt->fetchAll();
        } catch (PDOException $e) {
            $types = [];
        }
    }
    
    return $types;
}

// Function getCategories() telah dipindahkan ke definisi sebelumnya

/**
 * Log page view
 */
function logPageView($postId = null) {
    global $pdo;
    
    if (!CACHE_ENABLED) return;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $sessionId = session_id();
    
    // Simple device detection
    $deviceType = 'desktop';
    if (preg_match('/mobile/i', $userAgent)) $deviceType = 'mobile';
    elseif (preg_match('/tablet|ipad/i', $userAgent)) $deviceType = 'tablet';
    elseif (preg_match('/bot|crawler|spider/i', $userAgent)) $deviceType = 'bot';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO page_views (post_id, ip_address, user_agent, referer, device_type, session_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$postId, $ip, $userAgent, $referer, $deviceType, $sessionId]);
        
        // Update post views count
        if ($postId) {
            $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
            $stmt->execute([$postId]);
        }
        
    } catch (PDOException $e) {
        // Silently fail for analytics
    }
}

/**
 * Upload file handler
 */
function handleFileUpload($fileInput, $allowedTypes = null, $maxSize = null) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }
    
    $file = $_FILES[$fileInput];
    $allowedTypes = $allowedTypes ?: ALLOWED_FILE_TYPES;
    $maxSize = $maxSize ?: UPLOAD_MAX_SIZE;
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Max size: ' . formatFileSize($maxSize)];
    }
    
    // Validate file type
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadDir = UPLOAD_PATH . date('Y/m/');
    $fullPath = $uploadDir . $filename;
    
    // Create directory if not exists
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $fullPath,
            'url' => SITE_URL . '/' . $fullPath,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Initialize settings
$siteSettings = getSiteSettings();

// Auto-logout on session timeout
if (isset($_SESSION['admin_id']) && isset($_SESSION['login_time']) && 
    (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
    
    session_unset();
    session_destroy();
    session_start();
}

// ====================================
// MAINTENANCE MODE FUNCTIONS
// ====================================

/**
 * Check if maintenance mode is enabled
 */
function isMaintenanceMode() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['setting_value'] : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Redirect to maintenance page if maintenance mode is enabled
 * Skip for admin pages and admin users
 */
function checkMaintenanceMode() {
    // Skip maintenance check for admin pages
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($current_uri, '/admin/') !== false || strpos($current_uri, 'maintenance.php') !== false) {
        return;
    }
    
    // Skip if user is admin
    if (isLoggedIn()) {
        return;
    }
    
    // Check if maintenance mode is enabled
    if (isMaintenanceMode()) {
        header('Location: maintenance.php');
        exit;
    }
}

// AUTO-CHECK MAINTENANCE MODE FOR PUBLIC PAGES
// This runs on every page load except admin pages
checkMaintenanceMode();

?>