<?php
/**
 * Security Scanner
 * Comprehensive security audit system
 */

if (!defined('ADMIN_ACCESS') && !isset($_SESSION)) {
    http_response_code(403);
    die('Access Denied');
}

class SecurityScanner {
    private $pdo;
    private $issues = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Run full security scan
     */
    public function runFullScan() {
        $this->issues = [];
        
        $this->checkPHPVersion();
        $this->checkFilePermissions();
        $this->checkSecurityHeaders();
        $this->checkWeakPasswords();
        $this->checkDatabaseSecurity();
        $this->checkSensitiveFiles();
        $this->checkPHPExtensions();
        
        return $this->getResults();
    }
    
    /**
     * Check PHP version
     */
    private function checkPHPVersion() {
        $version = phpversion();
        $majorVersion = (int)explode('.', $version)[0];
        
        if ($majorVersion < 7) {
            $this->addIssue('critical', 'PHP Version', 
                "PHP version is severely outdated: $version. Upgrade to PHP 8.0 or higher.",
                "Update PHP to latest stable version for security and performance."
            );
        } elseif ($majorVersion == 7) {
            $this->addIssue('high', 'PHP Version', 
                "PHP 7.x is end-of-life: $version. Consider upgrading to PHP 8.0+.",
                "PHP 7.x no longer receives security updates."
            );
        } elseif ($majorVersion == 8 && version_compare($version, '8.0.0', '>=') && version_compare($version, '8.1.0', '<')) {
            $this->addIssue('medium', 'PHP Version', 
                "PHP 8.0 detected: $version. Consider upgrading to PHP 8.2+ for latest features.",
                "PHP 8.2+ offers better performance and security features."
            );
        } else {
            $this->addIssue('info', 'PHP Version', 
                "PHP version is up-to-date: $version",
                "No action required."
            );
        }
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        $baseDir = dirname(__DIR__, 2);
        $checkDirs = [
            'uploads' => $baseDir . '/uploads',
            'backups' => $baseDir . '/admin/backups',
            'config' => $baseDir . '/config_modern.php'
        ];
        
        foreach ($checkDirs as $name => $path) {
            if (!file_exists($path)) {
                $this->addIssue('medium', 'File Permissions', 
                    "Directory/file not found: $name",
                    "Create the missing directory/file."
                );
                continue;
            }
            
            $perms = fileperms($path);
            $octalPerms = substr(sprintf('%o', $perms), -4);
            
            // Check if world-writable
            if ($perms & 0x0002) {
                $this->addIssue('high', 'File Permissions', 
                    "$name is world-writable ($octalPerms)",
                    "Remove world-write permissions: chmod 755 (directories) or chmod 644 (files)"
                );
            } elseif ($name === 'config' && ($perms & 0x0004)) {
                $this->addIssue('medium', 'File Permissions', 
                    "Config file is world-readable ($octalPerms)",
                    "Restrict read permissions: chmod 600"
                );
            } else {
                $this->addIssue('info', 'File Permissions', 
                    "$name permissions are secure ($octalPerms)",
                    "No action required."
                );
            }
        }
    }
    
    /**
     * Check security headers
     */
    private function checkSecurityHeaders() {
        $headers = [
            'X-Frame-Options' => ['recommended' => 'DENY or SAMEORIGIN', 'severity' => 'medium'],
            'X-Content-Type-Options' => ['recommended' => 'nosniff', 'severity' => 'medium'],
            'X-XSS-Protection' => ['recommended' => '1; mode=block', 'severity' => 'low'],
            'Strict-Transport-Security' => ['recommended' => 'max-age=31536000', 'severity' => 'high'],
            'Content-Security-Policy' => ['recommended' => 'default-src \'self\'', 'severity' => 'medium']
        ];
        
        foreach ($headers as $header => $info) {
            // Note: Can't check headers in CLI mode, so mark as info
            $this->addIssue('info', 'Security Headers', 
                "Header check: $header (recommended: {$info['recommended']})",
                "Add header to .htaccess or server config if not present."
            );
        }
    }
    
    /**
     * Check for weak passwords
     */
    private function checkWeakPasswords() {
        try {
            $stmt = $this->pdo->query("SELECT id, username FROM administrators WHERE status = 'active'");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $weakCount = 0;
            foreach ($admins as $admin) {
                // Check if password is username-based (common weak pattern)
                // Note: Can't check actual password without knowing it
                $stmt = $this->pdo->prepare("SELECT password_hash FROM administrators WHERE id = ?");
                $stmt->execute([$admin['id']]);
                $hash = $stmt->fetchColumn();
                
                // Check if using default weak passwords (would match these hashes)
                $weakPasswords = ['admin', 'password', '123456', 'admin123'];
                foreach ($weakPasswords as $weak) {
                    if (password_verify($weak, $hash)) {
                        $weakCount++;
                        $this->addIssue('critical', 'Weak Passwords', 
                            "Admin '{$admin['username']}' has a weak/default password",
                            "Force password reset immediately."
                        );
                        break;
                    }
                }
            }
            
            if ($weakCount == 0) {
                $this->addIssue('info', 'Password Security', 
                    "No weak/default passwords detected among " . count($admins) . " admins",
                    "No action required."
                );
            }
        } catch (Exception $e) {
            $this->addIssue('medium', 'Password Check', 
                "Could not check passwords: " . $e->getMessage(),
                "Ensure password_hash is properly stored."
            );
        }
    }
    
    /**
     * Check database security
     */
    private function checkDatabaseSecurity() {
        try {
            // Check if using default database credentials
            if (defined('DB_USER') && DB_USER === 'root' && defined('DB_PASS') && DB_PASS === '') {
                $this->addIssue('critical', 'Database Security', 
                    "Using default MySQL root user with empty password",
                    "Create dedicated database user with strong password."
                );
            } else {
                $this->addIssue('info', 'Database Security', 
                    "Database credentials appear secure",
                    "No action required."
                );
            }
            
            // Check table encryption (InnoDB)
            $stmt = $this->pdo->query("SHOW TABLE STATUS WHERE Engine = 'InnoDB'");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($tables) > 0) {
                $this->addIssue('info', 'Database Engine', 
                    "Using InnoDB engine (" . count($tables) . " tables)",
                    "InnoDB supports transactions and better data integrity."
                );
            }
        } catch (Exception $e) {
            $this->addIssue('low', 'Database Check', 
                "Could not complete database checks: " . $e->getMessage(),
                "Check database connection."
            );
        }
    }
    
    /**
     * Check sensitive files exposure
     */
    private function checkSensitiveFiles() {
        $baseDir = dirname(__DIR__, 2);
        $sensitiveFiles = [
            '.env' => $baseDir . '/.env',
            'config.php' => $baseDir . '/config_modern.php',
            '.git' => $baseDir . '/.git'
        ];
        
        foreach ($sensitiveFiles as $name => $path) {
            if (file_exists($path)) {
                if ($name === '.env' || $name === 'config.php') {
                    $this->addIssue('medium', 'Sensitive Files', 
                        "Sensitive file exists: $name - ensure it's protected by .htaccess",
                        "Add 'Deny from all' in .htaccess for this directory."
                    );
                } elseif ($name === '.git') {
                    $this->addIssue('high', 'Sensitive Files', 
                        ".git directory exposed - contains sensitive repository information",
                        "Block access to .git directory via .htaccess or move outside webroot."
                    );
                }
            }
        }
    }
    
    /**
     * Check PHP extensions
     */
    private function checkPHPExtensions() {
        $required = ['pdo', 'pdo_mysql', 'mysqli', 'session', 'filter', 'json'];
        $recommended = ['openssl', 'mbstring', 'gd', 'curl', 'zip'];
        
        $missingRequired = [];
        $missingRecommended = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missingRequired[] = $ext;
            }
        }
        
        foreach ($recommended as $ext) {
            if (!extension_loaded($ext)) {
                $missingRecommended[] = $ext;
            }
        }
        
        if (!empty($missingRequired)) {
            $this->addIssue('critical', 'PHP Extensions', 
                "Missing required extensions: " . implode(', ', $missingRequired),
                "Install missing extensions in php.ini"
            );
        }
        
        if (!empty($missingRecommended)) {
            $this->addIssue('medium', 'PHP Extensions', 
                "Missing recommended extensions: " . implode(', ', $missingRecommended),
                "Install recommended extensions for full functionality."
            );
        }
        
        if (empty($missingRequired) && empty($missingRecommended)) {
            $this->addIssue('info', 'PHP Extensions', 
                "All required and recommended extensions are loaded",
                "No action required."
            );
        }
    }
    
    /**
     * Add issue to results
     */
    private function addIssue($severity, $category, $description, $recommendation) {
        $this->issues[] = [
            'severity' => $severity,
            'category' => $category,
            'description' => $description,
            'recommendation' => $recommendation,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get scan results
     */
    public function getResults() {
        // Sort by severity
        $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3, 'info' => 4];
        usort($this->issues, function($a, $b) use ($severityOrder) {
            return $severityOrder[$a['severity']] - $severityOrder[$b['severity']];
        });
        
        return [
            'issues' => $this->issues,
            'summary' => $this->getSummary(),
            'score' => $this->calculateScore()
        ];
    }
    
    /**
     * Get summary statistics
     */
    private function getSummary() {
        $summary = [
            'total' => count($this->issues),
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0
        ];
        
        foreach ($this->issues as $issue) {
            $summary[$issue['severity']]++;
        }
        
        return $summary;
    }
    
    /**
     * Calculate security score (0-100)
     */
    private function calculateScore() {
        $summary = $this->getSummary();
        
        $maxScore = 100;
        $deductions = [
            'critical' => 25,
            'high' => 15,
            'medium' => 10,
            'low' => 5
        ];
        
        $score = $maxScore;
        foreach ($deductions as $severity => $points) {
            $score -= ($summary[$severity] * $points);
        }
        
        return max(0, min(100, $score));
    }
}
