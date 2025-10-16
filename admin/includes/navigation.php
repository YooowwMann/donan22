<?php
// Admin Navigation Component
// Include this file in all admin pages to maintain consistent navigation

require_once __DIR__ . '/../system/role_manager.php';

// Helper function for permission checking
if (!function_exists('hasPermission')) {
    function hasPermission($action) {
        $roleManager = getRoleManager();
        return $roleManager->hasPermission($action);
    }
}

function renderAdminNavigation($currentPage = '') {
    $roleManager = getRoleManager();
    $currentRole = $roleManager->getCurrentUserRole();
    $roleName = $roleManager->getRoleDisplayName($currentRole);
?>
<style>
.sidebar {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    color: white;
    background-color: rgba(255,255,255,0.15);
    border-radius: 5px;
}
.nav-section-title {
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0.5rem 0;
}

/* Mobile Hamburger Menu */
.navbar-toggler {
    border-color: rgba(255,255,255,0.5);
    background-color: rgba(255,255,255,0.2);
}
.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Mobile Responsive */
@media (max-width: 767px) {
    .sidebar {
        position: fixed;
        top: 60px;
        left: 0;
        width: 250px;
        height: calc(100vh - 60px);
        z-index: 1040;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        overflow-y: auto;
    }
    .sidebar.show {
        transform: translateX(0);
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    .mobile-header {
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        z-index: 1050;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    /* Add padding to main content on mobile */
    main.col-md-9, main.col-lg-10 {
        padding-top: 75px !important;
        margin-left: 0 !important;
    }
}
@media (min-width: 768px) {
    .mobile-header {
        display: none !important;
    }
}
</style>

<!-- Mobile Header with Hamburger -->
<div class="mobile-header bg-primary text-white p-3 justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>DONAN22 Admin</h5>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</div>

<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">
                <i class="fas fa-rocket me-2"></i>DONAN22
            </h4>
            <small class="text-light">Admin Panel</small>
            <div class="badge bg-info mt-1"><?= htmlspecialchars($roleName) ?></div>
        </div>
        
        <ul class="nav flex-column">
            <!-- Main Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            
            <?php if (hasPermission('view_analytics')): ?>
            <li class="nav-item">
                <div class="nav-section-title px-3 mt-3">Analytics</div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'analytics' ? 'active' : '' ?>" href="analytics.php">
                    <i class="fas fa-chart-bar me-2"></i> Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="reports.php">
                    <i class="fas fa-file-chart-column me-2"></i> Reports
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <div class="nav-section-title px-3 mt-3">Content Management</div>
            </li>
            <?php if (hasPermission('manage_posts')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'posts' ? 'active' : '' ?>" href="posts.php">
                    <i class="fas fa-file-alt me-2"></i> Posts
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('manage_categories')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="categories.php">
                    <i class="fas fa-tags me-2"></i> Categories
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('manage_comments')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'comments' ? 'active' : '' ?>" href="comments.php">
                    <i class="fas fa-comments me-2"></i> Comments
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <div class="nav-section-title px-3 mt-3">SEO & Indexing</div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'indexnow_simple' ? 'active' : '' ?>" href="indexnow_simple.php">
                    <i class="fas fa-rocket me-2"></i> IndexNow Monitor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'seo_manager' ? 'active' : '' ?>" href="seo_manager.php">
                    <i class="fas fa-search me-2"></i> SEO Manager
                </a>
            </li>
            
            <li class="nav-item">
                <div class="nav-section-title px-3 mt-3">System</div>
            </li>
            <?php if (hasPermission('create_admin')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="users.php">
                    <i class="fas fa-users me-2"></i> User Management
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('manage_security')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'security' ? 'active' : '' ?>" href="security.php">
                    <i class="fas fa-shield-alt me-2"></i> Security
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="profile.php">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
            </li>
            
            <li class="nav-item mt-4 pt-3 border-top border-light border-opacity-25">
                <a class="nav-link text-warning" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i> View Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
<?php
}
?>