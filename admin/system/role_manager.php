<?php
/**
 * Role-Based Access Control System
 * Manages permissions for different user roles
 */

class RoleManager {
    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_EDITOR = 'editor';
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeRoles();
    }
    
    /**
     * Initialize role system if not exists
     */
    private function initializeRoles() {
        try {
            // Add role column to administrators table if not exists
            $stmt = $this->pdo->query("SHOW COLUMNS FROM administrators LIKE 'role'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE administrators ADD COLUMN role VARCHAR(20) DEFAULT 'admin'");
                
                // Set first user as superadmin
                $this->pdo->exec("UPDATE administrators SET role = 'superadmin' WHERE id = 1 LIMIT 1");
            }
        } catch (Exception $e) {
            error_log("Role initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Get current admin role
     */
    public function getCurrentUserRole() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $admin = getCurrentAdmin();
        return $admin['role'] ?? 'admin';
    }
    
    /**
     * Check if current user has permission for action
     */
    public function hasPermission($action) {
        $role = $this->getCurrentUserRole();
        
        if (!$role) {
            return false;
        }
        
        $permissions = $this->getRolePermissions($role);
        return in_array($action, $permissions);
    }
    
    /**
     * Get permissions for a role
     */
    private function getRolePermissions($role) {
        $permissions = [
            self::ROLE_SUPERADMIN => [
                'create_admin', 'manage_users', 'delete_users', 'manage_settings',
                'manage_security', 'view_analytics', 'manage_posts', 'manage_comments',
                'manage_categories', 'manage_media', 'backup_database', 'system_access'
            ],
            self::ROLE_ADMIN => [
                'manage_posts', 'manage_comments', 'manage_categories', 'manage_media',
                'view_analytics', 'manage_settings', 'system_access'
            ],
            self::ROLE_MODERATOR => [
                'manage_comments', 'manage_posts', 'view_analytics', 'system_access'
            ],
            self::ROLE_EDITOR => [
                'manage_posts', 'system_access'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
    
    /**
     * Require specific permission or redirect
     */
    public function requirePermission($action, $redirectTo = 'dashboard.php') {
        if (!$this->hasPermission($action)) {
            header("Location: $redirectTo?error=" . urlencode("Access denied. Insufficient permissions."));
            exit;
        }
    }
    
    /**
     * Get role display name
     */
    public function getRoleDisplayName($role) {
        $names = [
            self::ROLE_SUPERADMIN => 'Super Administrator',
            self::ROLE_ADMIN => 'Administrator', 
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_EDITOR => 'Editor'
        ];
        
        return $names[$role] ?? 'Unknown';
    }
    
    /**
     * Get all available roles
     */
    public function getAllRoles() {
        return [
            self::ROLE_SUPERADMIN => 'Super Administrator',
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MODERATOR => 'Moderator', 
            self::ROLE_EDITOR => 'Editor'
        ];
    }
    
    /**
     * Check if current user can manage specific role
     */
    public function canManageRole($targetRole) {
        $currentRole = $this->getCurrentUserRole();
        
        // Only superadmin can manage superadmin and create admins
        if ($targetRole === self::ROLE_SUPERADMIN) {
            return $currentRole === self::ROLE_SUPERADMIN;
        }
        
        // Only superadmin can create/manage admins
        if ($targetRole === self::ROLE_ADMIN) {
            return $currentRole === self::ROLE_SUPERADMIN;
        }
        
        // Admin and superadmin can manage moderators and editors
        if (in_array($targetRole, [self::ROLE_MODERATOR, self::ROLE_EDITOR])) {
            return in_array($currentRole, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN]);
        }
        
        return false;
    }
    
    /**
     * Update user role (only for authorized users)
     */
    public function updateUserRole($userId, $newRole) {
        if (!$this->canManageRole($newRole)) {
            throw new Exception("Insufficient permissions to assign this role");
        }
        
        $stmt = $this->pdo->prepare("UPDATE administrators SET role = ? WHERE id = ?");
        return $stmt->execute([$newRole, $userId]);
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role = null) {
        if ($role) {
            $stmt = $this->pdo->prepare("SELECT * FROM administrators WHERE role = ? ORDER BY created_at DESC");
            $stmt->execute([$role]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM administrators ORDER BY created_at DESC");
        }
        
        return $stmt->fetchAll();
    }
}

/**
 * Global helper functions
 */

// Initialize role manager
if (!isset($GLOBALS['roleManager'])) {
    $GLOBALS['roleManager'] = new RoleManager($pdo);
}

function getRoleManager() {
    return $GLOBALS['roleManager'];
}

// Role-specific functions not in config_modern.php
function getCurrentUserRole() {
    return getRoleManager()->getCurrentUserRole();
}

function canManageRole($role) {
    return getRoleManager()->canManageRole($role);
}
?>