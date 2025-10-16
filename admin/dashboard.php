<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
requireLogin();

$pageTitle = 'Dashboard Admin';
$currentPage = 'dashboard';

// Initialize analytics tracker
require_once 'system/analytics_tracker.php';
$tracker = new AnalyticsTracker($pdo);
$analyticsData = $tracker->getAnalyticsData();
$realtimeStats = $tracker->getRealTimeStats();

// Statistik sederhana
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE deleted_at IS NULL");
    $totalPosts = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalPosts = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
    $totalCategories = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCategories = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM administrators");
    $totalAdmins = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalAdmins = 0;
}

// Get today's analytics summary
$todayViews = $analyticsData['today_views'] ?? 0;
$todayDownloads = $analyticsData['today_downloads'] ?? 0;
$totalViews = $analyticsData['total_views'] ?? 0;
$totalDownloads = $analyticsData['total_downloads'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('dashboard'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar me-1"></i><?= date('d M Y') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm border-start border-primary border-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Total Views</h5>
                                        <span class="h2 font-weight-bold mb-0 text-primary"><?= number_format($totalViews) ?></span>
                                        <small class="text-muted d-block">+<?= number_format($todayViews) ?> today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-eye fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm border-start border-success border-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Downloads</h5>
                                        <span class="h2 font-weight-bold mb-0 text-success"><?= number_format($totalDownloads) ?></span>
                                        <small class="text-muted d-block">+<?= number_format($todayDownloads) ?> today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-download fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm border-start border-warning border-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Total Posts</h5>
                                        <span class="h2 font-weight-bold mb-0 text-warning"><?= number_format($totalPosts) ?></span>
                                        <small class="text-muted d-block"><?= number_format($totalCategories) ?> categories</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-alt fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm border-start border-info border-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">Visitors</h5>
                                        <span class="h2 font-weight-bold mb-0 text-info"><?= number_format($realtimeStats['unique_visitors_today']) ?></span>
                                        <small class="text-muted d-block">unique today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Analytics Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="mb-0 text-white">
                                    <i class="fas fa-chart-line me-2"></i>Today's Performance
                                    <span class="badge bg-light text-dark ms-2">Live</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="border-end">
                                            <h3 class="text-primary mb-1"><?= number_format($realtimeStats['views_24h']) ?></h3>
                                            <p class="text-muted mb-0">Views (24h)</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border-end">
                                            <h3 class="text-success mb-1"><?= number_format($realtimeStats['downloads_24h']) ?></h3>
                                            <p class="text-muted mb-0">Downloads (24h)</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border-end">
                                            <h3 class="text-warning mb-1"><?= number_format($realtimeStats['unique_visitors_today']) ?></h3>
                                            <p class="text-muted mb-0">Unique Visitors</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="text-info mb-1"><?= number_format($totalPosts) ?></h3>
                                        <p class="text-muted mb-0">Total Content</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Analytics -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if (hasPermission('manage_posts')): ?>
                                    <a href="posts.php" class="btn btn-primary">
                                        <i class="fas fa-file-alt me-2"></i>Manage Posts
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('view_analytics')): ?>
                                    <a href="analytics.php" class="btn btn-info">
                                        <i class="fas fa-chart-bar me-2"></i>View Analytics
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('view_analytics')): ?>
                                    <a href="reports.php" class="btn btn-success">
                                        <i class="fas fa-file-chart-column me-2"></i>Generate Reports
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('manage_security')): ?>
                                    <a href="security.php" class="btn btn-warning">
                                        <i class="fas fa-shield-alt me-2"></i>Security Panel
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-2"></i>View Website
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>System Status & Analytics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>PHP Version:</strong>
                                    <span class="badge bg-success float-end"><?= PHP_VERSION ?></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Database:</strong>
                                    <span class="badge bg-success float-end">Connected</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Security System:</strong>
                                    <span class="badge bg-success float-end">Protected</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Analytics:</strong>
                                    <span class="badge bg-info float-end">Active</span>
                                </div>
                                <div class="mb-0">
                                    <strong>Reports:</strong>
                                    <span class="badge bg-primary float-end">Available</span>
                                </div>
                                
                                <hr>
                                <div class="d-grid">
                                    <a href="analytics.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-chart-line me-2"></i>View Full Analytics
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>