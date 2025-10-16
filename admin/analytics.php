<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/analytics_tracker.php';
requireLogin();

$pageTitle = 'Analytics Dashboard';
$currentPage = 'analytics';

// Initialize analytics tracker
$tracker = new AnalyticsTracker($pdo);
$analyticsData = $tracker->getAnalyticsData();
$realtimeStats = $tracker->getRealTimeStats();

// Get enhanced analytics data
$bounceRateData = $tracker->getBounceRate(7);
$geographicStats = $tracker->getGeographicStats(7);
$trafficSourcesData = $tracker->getTrafficSourcesBreakdown(7);
$downloadSuccessRate = $tracker->getDownloadSuccessRate(7);

// Get analytics data
try {
    // Posts statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE deleted_at IS NULL");
    $totalPosts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as published FROM posts WHERE status = 'published' AND deleted_at IS NULL");
    $publishedPosts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as drafts FROM posts WHERE status = 'draft' AND deleted_at IS NULL");
    $draftPosts = $stmt->fetchColumn();
    
    // VIEW TREND - Last 30 days
    $stmt = $pdo->query("SELECT 
        DATE(view_date) as date,
        COUNT(*) as views,
        COUNT(DISTINCT ip_address) as unique_visitors
        FROM page_views 
        WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(view_date)
        ORDER BY date ASC");
    $viewTrend = $stmt->fetchAll();
    
    // DOWNLOAD TREND - Last 30 days
    $stmt = $pdo->query("SELECT 
        DATE(download_date) as date,
        COUNT(*) as downloads
        FROM downloads 
        WHERE download_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(download_date)
        ORDER BY date ASC");
    $downloadTrend = $stmt->fetchAll();
    
    // Conversion Rate (View to Download)
    $totalViewsCount = $pdo->query("SELECT COUNT(*) FROM page_views WHERE page_type = 'post'")->fetchColumn();
    $totalDownloadsCount = $pdo->query("SELECT COUNT(*) FROM downloads")->fetchColumn();
    $conversionRate = $totalViewsCount > 0 ? ($totalDownloadsCount / $totalViewsCount) * 100 : 0;
    
    // Peak Hours Analytics
    $stmt = $pdo->query("SELECT 
        HOUR(view_date) as hour,
        COUNT(*) as views
        FROM page_views 
        WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY HOUR(view_date)
        ORDER BY hour ASC");
    $peakHours = $stmt->fetchAll();
    
    // Device Analytics (from user_agent)
    $stmt = $pdo->query("SELECT 
        CASE 
            WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
            ELSE 'Desktop'
        END as device_type,
        COUNT(*) as count
        FROM page_views 
        WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY device_type");
    $deviceStats = $stmt->fetchAll();
    
    // Browser Analytics
    $stmt = $pdo->query("SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edg%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN user_agent LIKE '%Edg%' THEN 'Edge'
            WHEN user_agent LIKE '%Opera%' OR user_agent LIKE '%OPR%' THEN 'Opera'
            ELSE 'Other'
        END as browser,
        COUNT(*) as count
        FROM page_views 
        WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY browser
        ORDER BY count DESC");
    $browserStats = $stmt->fetchAll();
    
    // Recent posts by month
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
        FROM posts 
        WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6");
    $monthlyPosts = $stmt->fetchAll();
    
    // Categories with post counts
    $stmt = $pdo->query("SELECT 
        c.name,
        COUNT(DISTINCT p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON (c.id = p.category_id OR c.id = p.secondary_category_id) 
            AND p.deleted_at IS NULL
        GROUP BY c.id, c.name
        ORDER BY post_count DESC
        LIMIT 10");
    $categoryStats = $stmt->fetchAll();
    
    // Recent activity with analytics
    $stmt = $pdo->query("SELECT 
        p.title, 
        p.status, 
        p.created_at,
        p.updated_at,
        COALESCE(p.view_count, 0) as view_count,
        COALESCE(p.download_count, 0) as download_count
        FROM posts p
        WHERE p.deleted_at IS NULL
        ORDER BY p.updated_at DESC 
        LIMIT 10");
    $recentPosts = $stmt->fetchAll();
    
    // Returning vs New Visitors (based on session tracking)
    $stmt = $pdo->query("SELECT 
        COUNT(DISTINCT CASE WHEN visit_count = 1 THEN session_id END) as new_visitors,
        COUNT(DISTINCT CASE WHEN visit_count > 1 THEN session_id END) as returning_visitors
        FROM (
            SELECT session_id, COUNT(*) as visit_count
            FROM page_views
            WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY session_id
        ) as visitor_stats");
    $visitorType = $stmt->fetch();
    
} catch (Exception $e) {
    $totalPosts = $publishedPosts = $draftPosts = 0;
    $monthlyPosts = $categoryStats = $recentPosts = [];
    $viewTrend = $downloadTrend = $peakHours = $deviceStats = $browserStats = [];
    $conversionRate = 0;
    $visitorType = ['new_visitors' => 0, 'returning_visitors' => 0];
}

// Get system info
$phpVersion = PHP_VERSION;
$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
$diskSpace = disk_free_space('.') / (1024 * 1024 * 1024); // GB
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Compact Analytics Dashboard */
        .card-header {
            padding: 0.75rem 1rem;
        }
        .card-body {
            padding: 1rem;
        }
        .table-sm td, .table-sm th {
            padding: 0.3rem;
            font-size: 0.875rem;
        }
        .progress {
            height: 18px !important;
        }
        h5, h6 {
            margin-bottom: 0.5rem;
        }
        .border-bottom {
            padding-bottom: 0.75rem !important;
            margin-bottom: 1rem !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('analytics'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-bar me-2"></i>Analytics Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <!-- Date Range Picker -->
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <span id="currentDateRange"><?= date('d M Y') ?></span>
                            </button>
                        </div>
                        
                        <!-- Export Dropdown -->
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Overview Reports</h6></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv', 'overview')">
                                    <i class="fas fa-file-csv me-2"></i>CSV - Overview
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('excel', 'overview')">
                                    <i class="fas fa-file-excel me-2"></i>Excel - Overview
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('pdf', 'overview')">
                                    <i class="fas fa-file-pdf me-2"></i>PDF - Overview
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Detailed Reports</h6></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv', 'views')">
                                    <i class="fas fa-eye me-2"></i>Views Report
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv', 'downloads')">
                                    <i class="fas fa-download me-2"></i>Downloads Report
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('excel', 'posts')">
                                    <i class="fas fa-file-alt me-2"></i>Posts Performance
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv', 'traffic')">
                                    <i class="fas fa-share-alt me-2"></i>Traffic Sources
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportData('csv', 'devices')">
                                    <i class="fas fa-mobile-alt me-2"></i>Device Analytics
                                </a></li>
                            </ul>
                        </div>
                        
                        <!-- Email Report Button -->
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#emailReportModal">
                                <i class="fas fa-envelope me-1"></i>Email Report
                            </button>
                        </div>
                        
                        <!-- Refresh Button -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-3">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm border-start border-primary border-4">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="card-title text-uppercase text-muted mb-1">Total Views</h6>
                                        <span class="h3 font-weight-bold mb-0 text-primary"><?= number_format($analyticsData['total_views']) ?></span>
                                        <small class="text-muted d-block">+<?= number_format($realtimeStats['views_24h']) ?> today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-eye fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm border-start border-success border-4">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="card-title text-uppercase text-muted mb-1">Downloads</h6>
                                        <span class="h3 font-weight-bold mb-0 text-success"><?= number_format($analyticsData['total_downloads']) ?></span>
                                        <small class="text-muted d-block">+<?= number_format($realtimeStats['downloads_24h']) ?> today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-download fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm border-start border-warning border-4">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="card-title text-uppercase text-muted mb-1">Visitors</h6>
                                        <span class="h3 font-weight-bold mb-0 text-warning"><?= number_format($realtimeStats['unique_visitors_today']) ?></span>
                                        <small class="text-muted d-block">unique today</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm border-start border-danger border-4">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col">
                                        <h6 class="card-title text-uppercase text-muted mb-1">Conversion Rate</h6>
                                        <span class="h3 font-weight-bold mb-0 text-danger"><?= number_format($conversionRate, 2) ?>%</span>
                                        <small class="text-muted d-block">view to download</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW TREND CHART - NEW SECTION -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-white">
                                        <i class="fas fa-chart-area me-2"></i>Views & Downloads Trend (Last 30 Days)
                                    </h5>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="updateTrendChart(7)">7 Days</button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="updateTrendChart(14)">14 Days</button>
                                        <button type="button" class="btn btn-light btn-sm active" onclick="updateTrendChart(30)">30 Days</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="viewTrendChart" height="60"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Analytics Row: Bounce Rate & Traffic Sources -->
                <div class="row mb-3">
                    <!-- Bounce Rate & Session Metrics -->
                    <div class="col-lg-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Session Metrics (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Bounce Rate</span>
                                        <strong class="text-danger"><?= number_format($bounceRateData['bounce_rate'] ?? 0, 1) ?>%</strong>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-danger" style="width: <?= $bounceRateData['bounce_rate'] ?? 0 ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= number_format($bounceRateData['bounce_sessions'] ?? 0) ?> of <?= number_format($bounceRateData['total_sessions'] ?? 0) ?> sessions</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Avg. Session Duration</span>
                                        <strong class="text-primary"><?= gmdate("i:s", $bounceRateData['avg_duration'] ?? 0) ?></strong>
                                    </div>
                                    <small class="text-muted">Minutes:Seconds per session</small>
                                </div>
                                
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Avg. Pages/Session</span>
                                        <strong class="text-success"><?= number_format($bounceRateData['avg_pages_per_session'] ?? 0, 1) ?></strong>
                                    </div>
                                    <small class="text-muted">Pages viewed per session</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Traffic Sources -->
                    <div class="col-lg-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-share-alt me-2"></i>Traffic Sources (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($trafficSourcesData)): ?>
                                    <?php 
                                    $totalTraffic = array_sum(array_column($trafficSourcesData, 'visits'));
                                    $sourceIcons = [
                                        'direct' => 'fa-arrow-right',
                                        'google' => 'fa-google',
                                        'facebook' => 'fa-facebook',
                                        'twitter' => 'fa-twitter',
                                        'instagram' => 'fa-instagram',
                                        'youtube' => 'fa-youtube',
                                        'referral' => 'fa-link'
                                    ];
                                    foreach (array_slice($trafficSourcesData, 0, 5) as $source): 
                                        $percentage = ($source['visits'] / $totalTraffic) * 100;
                                        $icon = $sourceIcons[$source['traffic_source']] ?? 'fa-globe';
                                    ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>
                                                <i class="fab <?= $icon ?> me-1"></i>
                                                <?= ucfirst($source['traffic_source']) ?>
                                            </span>
                                            <span><strong><?= number_format($source['visits']) ?></strong></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">No traffic data</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Geographic Stats -->
                    <div class="col-lg-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white py-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-globe me-2"></i>Top Countries (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($geographicStats)): ?>
                                    <?php 
                                    $totalSessions = array_sum(array_column($geographicStats, 'sessions'));
                                    foreach (array_slice($geographicStats, 0, 6) as $geo): 
                                        $percentage = ($geo['sessions'] / $totalSessions) * 100;
                                    ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-truncate">
                                                <i class="fas fa-flag me-1"></i>
                                                <?= htmlspecialchars($geo['country']) ?>
                                            </span>
                                            <span><strong><?= number_format($geo['sessions']) ?></strong></span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">No geographic data</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Download Success Rate -->
                <?php if (!empty($downloadSuccessRate)): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-gradient py-2" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-check-circle me-2"></i>Download Success Rate (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <?php foreach ($downloadSuccessRate as $stat): ?>
                                    <div class="col-md-4">
                                        <div class="border-end">
                                            <h4 class="mb-0 text-<?= $stat['status'] === 'success' ? 'success' : ($stat['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                                <?= number_format($stat['count']) ?>
                                            </h4>
                                            <small class="text-muted">
                                                <?= ucfirst($stat['status']) ?> (<?= number_format($stat['percentage'], 1) ?>%)
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Analytics Row -->
                <div class="row mb-3">
                    <!-- Peak Hours -->
                    <div class="col-lg-8 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2 text-warning"></i>Peak Traffic Hours
                                </h5>
                                <small class="text-muted">Last 7 days activity by hour</small>
                            </div>
                            <div class="card-body">
                                <canvas id="peakHoursChart" height="60"></canvas>
                            </div>
                        </div>
                        
                        <!-- Visitor Type Analytics - Moved Below -->
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-header py-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-users me-2 text-success"></i>Visitor Type
                                </h6>
                                <small class="text-muted">Last 7 days</small>
                            </div>
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <canvas id="visitorTypeChart" height="50"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <h5 class="text-success mb-1"><?= number_format($visitorType['new_visitors'] ?? 0) ?></h5>
                                                <small class="text-muted">New Visitors</small>
                                            </div>
                                            <div class="col-6">
                                                <h5 class="text-primary mb-1"><?= number_format($visitorType['returning_visitors'] ?? 0) ?></h5>
                                                <small class="text-muted">Returning</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device & Browser Analytics -->
                    <div class="col-lg-4 mb-3">
                        <div class="row">
                            <!-- Device Distribution -->
                            <div class="col-12 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0">
                                            <i class="fas fa-mobile-alt me-2 text-info"></i>Device Analytics
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <canvas id="deviceChart" height="150"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Browser Distribution -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header py-2">
                                        <h6 class="mb-0">
                                            <i class="fas fa-browser me-2 text-primary"></i>Browser Analytics
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <?php if (!empty($browserStats)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tbody>
                                                    <?php 
                                                    $totalBrowsers = array_sum(array_column($browserStats, 'count'));
                                                    foreach ($browserStats as $browser): 
                                                        $percentage = ($browser['count'] / $totalBrowsers) * 100;
                                                    ?>
                                                    <tr>
                                                        <td width="100">
                                                            <i class="fab fa-<?= strtolower($browser['browser']) ?> me-1"></i>
                                                            <?= $browser['browser'] ?>
                                                        </td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar" style="width: <?= $percentage ?>%">
                                                                    <?= number_format($percentage, 1) ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td width="80" class="text-end"><?= number_format($browser['count']) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted text-center">No data available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Activity -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-gradient py-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-pulse me-2"></i>Real-Time Analytics
                                    <span class="badge bg-light text-dark ms-2">Live</span>
                                </h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="border-end">
                                            <h4 class="text-primary mb-0"><?= number_format($realtimeStats['views_24h']) ?></h4>
                                            <small class="text-muted">Views (24h)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border-end">
                                            <h4 class="text-success mb-0"><?= number_format($realtimeStats['downloads_24h']) ?></h4>
                                            <small class="text-muted">Downloads (24h)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="text-warning mb-0"><?= number_format($realtimeStats['unique_visitors_today']) ?></h4>
                                        <small class="text-muted">Unique Visitors Today</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-3">
                    <!-- Monthly Posts Chart -->
                    <div class="col-lg-8 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Posts Trend (6 Months)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="60"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Category Distribution
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Content Analytics -->
                <div class="row mb-3">
                    <!-- Top Viewed Posts -->
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-eye me-2 text-primary"></i>Most Viewed Posts
                                </h5>
                                <small class="text-muted">Posts ranked by total views</small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($analyticsData['top_posts'])): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($analyticsData['top_posts'], 0, 8) as $index => $post): ?>
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                <strong class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <?= htmlspecialchars(substr($post['title'], 0, 40)) ?>
                                                    <?= strlen($post['title']) > 40 ? '...' : '' ?>
                                                </strong>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary fs-6"><?= number_format($post['view_count']) ?> views</span>
                                                <?php if ($post['download_count'] > 0): ?>
                                                <span class="badge bg-success fs-6 ms-1"><?= number_format($post['download_count']) ?> DL</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center py-3">
                                    <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                    No view data available yet
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Downloads -->
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-download me-2 text-success"></i>Popular Downloads
                                </h5>
                                <small class="text-muted">Posts with most downloads</small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($analyticsData['popular_downloads'])): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($analyticsData['popular_downloads'], 0, 8) as $index => $post): ?>
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <span class="badge bg-success me-2"><?= $index + 1 ?></span>
                                                <strong class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <?= htmlspecialchars(substr($post['title'], 0, 40)) ?>
                                                    <?= strlen($post['title']) > 40 ? '...' : '' ?>
                                                </strong>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-success fs-6"><?= number_format($post['download_count']) ?> downloads</span>
                                                <span class="badge bg-primary fs-6 ms-1"><?= number_format($post['view_count']) ?> views</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center py-3">
                                    <i class="fas fa-download fa-2x mb-2 d-block"></i>
                                    No download data available yet
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & System Info -->
                <div class="row">
                    <!-- Recent Posts -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Content Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Post Title</th>
                                                <th class="text-center">Views</th>
                                                <th class="text-center">Downloads</th>
                                                <th>Status</th>
                                                <th>Last Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPosts as $post): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file-alt me-2 text-muted"></i>
                                                    <?= htmlspecialchars(substr($post['title'], 0, 40)) ?>
                                                    <?= strlen($post['title']) > 40 ? '...' : '' ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= isset($post['view_count']) ? number_format($post['view_count']) : '0' ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= isset($post['download_count']) ? number_format($post['download_count']) : '0' ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($post['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    <?= date('M d, Y', strtotime($post['updated_at'])) ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-server me-2"></i>System Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>PHP Version:</strong>
                                    <span class="badge bg-success float-end"><?= $phpVersion ?></span>
                                </div>
                                <div class="mb-3">
                                    <strong>MySQL Version:</strong>
                                    <span class="badge bg-info float-end"><?= substr($mysqlVersion, 0, 6) ?></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Free Disk Space:</strong>
                                    <span class="badge bg-primary float-end"><?= number_format($diskSpace, 1) ?> GB</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Security Status:</strong>
                                    <span class="badge bg-success float-end">Protected</span>
                                </div>
                                <div class="mb-0">
                                    <strong>Backup System:</strong>
                                    <span class="badge bg-success float-end">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Email Report Modal -->
    <div class="modal fade" id="emailReportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope me-2"></i>Send Analytics Report
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="send_email_report.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email_to" 
                                   placeholder="admin@example.com" required>
                            <small class="text-muted">Enter the recipient's email address</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type" required>
                                <option value="daily">Daily Report</option>
                                <option value="weekly" selected>Weekly Report</option>
                                <option value="monthly">Monthly Report</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            The report will include analytics data, top posts, traffic sources, and geographic statistics.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_report" class="btn btn-warning">
                            <i class="fas fa-paper-plane me-1"></i>Send Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Date Range Modal -->
    <div class="modal fade" id="dateRangeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt me-2"></i>Select Date Range
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="dateRangeForm" method="get">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="startDate" 
                                       value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="endDate" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quick Select</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(7)">7 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(14)">14 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(30)">30 Days</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setDateRange(90)">90 Days</button>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="comparePeriod" name="compare">
                            <label class="form-check-label" for="comparePeriod">
                                Compare with previous period
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyDateRange()">
                        <i class="fas fa-check me-1"></i>Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // === VIEW TREND CHART (30 DAYS) ===
    const viewTrendData = <?= json_encode($viewTrend) ?>;
    const downloadTrendData = <?= json_encode($downloadTrend) ?>;
    
    // Only show dates that have actual data (not empty 30 days)
    const viewTrendLabels = viewTrendData.map(item => {
        const d = new Date(item.date);
        return d.toLocaleDateString('id-ID', { month: 'short', day: 'numeric' });
    });
    
    const viewTrendValues = viewTrendData.map(item => parseInt(item.views));
    
    // Match downloads with view dates
    const downloadTrendValues = viewTrendData.map(viewItem => {
        const found = downloadTrendData.find(dlItem => dlItem.date === viewItem.date);
        return found ? parseInt(found.downloads) : 0;
    });
    
    let viewTrendChart = new Chart(document.getElementById('viewTrendChart'), {
        type: 'line',
        data: {
            labels: viewTrendLabels,
            datasets: [{
                label: 'Views',
                data: viewTrendValues,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }, {
                label: 'Downloads',
                data: downloadTrendValues,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Function to update trend chart based on days
    function updateTrendChart(days) {
        const buttons = document.querySelectorAll('.btn-group button');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        const dataToShow = viewTrendValues.slice(-days);
        const labelsToShow = viewTrendLabels.slice(-days);
        const downloadsToShow = downloadTrendValues.slice(-days);
        
        viewTrendChart.data.labels = labelsToShow;
        viewTrendChart.data.datasets[0].data = dataToShow;
        viewTrendChart.data.datasets[1].data = downloadsToShow;
        viewTrendChart.update();
    }

    // === PEAK HOURS CHART ===
    const peakHoursData = <?= json_encode($peakHours) ?>;
    const allHours = Array.from({length: 24}, (_, i) => i);
    const peakHoursValues = allHours.map(hour => {
        const found = peakHoursData.find(item => parseInt(item.hour) === hour);
        return found ? parseInt(found.views) : 0;
    });
    const peakHoursLabels = allHours.map(h => h.toString().padStart(2, '0') + ':00');
    
    new Chart(document.getElementById('peakHoursChart'), {
        type: 'bar',
        data: {
            labels: peakHoursLabels,
            datasets: [{
                label: 'Traffic',
                data: peakHoursValues,
                backgroundColor: 'rgba(255, 193, 7, 0.6)',
                borderColor: 'rgb(255, 193, 7)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // === DEVICE CHART ===
    const deviceData = <?= json_encode($deviceStats) ?>;
    const deviceLabels = deviceData.map(item => item.device_type);
    const deviceValues = deviceData.map(item => item.count);
    
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: deviceLabels,
            datasets: [{
                data: deviceValues,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // === VISITOR TYPE CHART ===
    const visitorTypeData = <?= json_encode($visitorType) ?>;
    new Chart(document.getElementById('visitorTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['New Visitors', 'Returning Visitors'],
            datasets: [{
                data: [
                    visitorTypeData.new_visitors || 0,
                    visitorTypeData.returning_visitors || 0
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(54, 162, 235, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // === MONTHLY POSTS CHART ===
    const monthlyData = <?= json_encode(array_reverse($monthlyPosts)) ?>;
    const monthLabels = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
    });
    const monthValues = monthlyData.map(item => item.count);

    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Posts Created',
                data: monthValues,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // === CATEGORY CHART ===
    const categoryData = <?= json_encode($categoryStats) ?>;
    const categoryLabels = categoryData.map(item => item.name);
    const categoryValues = categoryData.map(item => item.post_count);

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        fontSize: 12,
                        padding: 10
                    }
                }
            }
        }
    });
    
    // ===== EXPORT FUNCTIONS =====
    function exportData(format, type) {
        const startDate = document.getElementById('startDate')?.value || '<?= date('Y-m-d', strtotime('-30 days')) ?>';
        const endDate = document.getElementById('endDate')?.value || '<?= date('Y-m-d') ?>';
        
        const url = `export_analytics.php?format=${format}&type=${type}&start_date=${startDate}&end_date=${endDate}`;
        window.open(url, '_blank');
    }
    
    // ===== DATE RANGE FUNCTIONS =====
    function setDateRange(days) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);
        
        document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
        document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
    }
    
    function applyDateRange() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const compare = document.getElementById('comparePeriod').checked;
        
        let url = `analytics.php?start_date=${startDate}&end_date=${endDate}`;
        if (compare) {
            url += '&compare=1';
        }
        
        window.location.href = url;
    }
    
    // Update current date range display
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const startDate = urlParams.get('start_date');
        const endDate = urlParams.get('end_date');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const display = start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + 
                          ' - ' + 
                          end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            document.getElementById('currentDateRange').textContent = display;
        }
    });
    </script>
</body>
</html>
