<?php
if (!defined('SITE_URL')) {
    die('Direct access not permitted');
}

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? esc($pageTitle) . ' - ' : '' ?>Admin Panel - <?= esc($siteSettings['site_name']) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --primary-color: <?= $siteSettings['theme_color'] ?>;
            --primary-rgb: <?= implode(',', sscanf($siteSettings['theme_color'], "#%02x%02x%02x")) ?>;
            --sidebar-width: 250px;
            --header-height: 60px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, rgba(var(--primary-rgb), 0.8) 100%);
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand:hover {
            color: white;
        }
        
        .sidebar-brand i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .nav-sidebar {
            padding: 1rem 0;
        }
        
        .nav-sidebar .nav-item {
            margin-bottom: 2px;
        }
        
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0;
        }
        
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 0;
            padding-top: var(--header-height);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.sidebar-open {
            margin-left: var(--sidebar-width);
        }
        
        /* Header */
        .admin-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            transition: left 0.3s ease;
        }
        
        .admin-header.sidebar-open {
            left: var(--sidebar-width);
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(var(--primary-rgb), 0.1);
        }
        
        .admin-nav .dropdown-toggle::after {
            display: none;
        }
        
        .admin-nav .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Responsive */
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .admin-header {
                left: var(--sidebar-width);
            }
            
            .sidebar-toggle {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }
        }
        
        /* Custom styles */
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: rgba(var(--primary-rgb), 0.9);
            border-color: rgba(var(--primary-rgb), 0.9);
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .border-primary {
            border-color: var(--primary-color) !important;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-color: rgba(28, 200, 138, 0.2);
            color: #1cc88a;
        }
        
        .table th {
            font-weight: 600;
            color: #5a5c69;
            border-top: none;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2">Loading...</div>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-crown"></i>
            <span class="h5 mb-0">Admin Panel</span>
        </a>
        
        <ul class="nav nav-sidebar flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= in_array($currentPage, ['posts', 'post-editor']) ? 'active' : '' ?>" href="posts.php">
                    <i class="fas fa-file-alt"></i>
                    All Posts
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="post-editor.php?type=software">
                    <i class="fas fa-download"></i>
                    Add Software
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="post-editor.php?type=tutorial">
                    <i class="fas fa-book-open"></i>
                    Write Tutorial
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="categories.php">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'comments' ? 'active' : '' ?>" href="comments.php">
                    <i class="fas fa-comments"></i>
                    Comments
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
                        $pendingCount = $stmt->fetchColumn();
                        if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-auto"><?= $pendingCount ?></span>
                        <?php endif;
                    } catch (Exception $e) {} 
                    ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'media' ? 'active' : '' ?>" href="media.php">
                    <i class="fas fa-images"></i>
                    Media Library
                </a>
            </li>
            
            <?php if (hasPermission('*')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'security' ? 'active' : '' ?>" href="security.php">
                    <i class="fas fa-shield-alt"></i>
                    Security & Backup
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM deleted_posts WHERE restored_at IS NULL");
                        $deletedCount = $stmt->fetchColumn();
                        if ($deletedCount > 0): ?>
                            <span class="badge bg-warning text-dark ms-auto"><?= $deletedCount ?></span>
                        <?php endif;
                    } catch (Exception $e) {} 
                    ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item mt-3">
                <a class="nav-link text-light" href="<?= SITE_URL ?>" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    View Website
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Header -->
    <header class="admin-header" id="adminHeader">
        <div class="d-flex align-items-center">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h6 class="mb-0 ms-3 d-none d-md-block text-muted">
                <?= isset($pageTitle) ? esc($pageTitle) : 'Admin Panel' ?>
            </h6>
        </div>
        
        <nav class="admin-nav">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg me-2"></i>
                    <span><?= esc($admin['full_name'] ?: $admin['username']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <h6 class="dropdown-header">
                            <i class="fas fa-user me-2"></i>
                            <?= esc($admin['username']) ?>
                            <small class="d-block text-muted"><?= esc($admin['role']) ?></small>
                        </h6>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent"><?php // Content will be inserted here ?>