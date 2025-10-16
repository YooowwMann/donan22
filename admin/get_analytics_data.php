<?php
require_once __DIR__ . '/../config_modern.php';

header('Content-Type: application/json');

// Get actual view trend data
$stmt = $pdo->query("
    SELECT 
        DATE(view_date) as date, 
        COUNT(*) as views,
        COUNT(DISTINCT ip_address) as unique_visitors
    FROM page_views 
    WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(view_date) 
    ORDER BY date
");

$viewTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get download trend
$stmt = $pdo->query("
    SELECT 
        DATE(download_date) as date, 
        COUNT(*) as downloads
    FROM downloads 
    WHERE download_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(download_date) 
    ORDER BY date
");

$downloadTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get traffic sources
$stmt = $pdo->query("
    SELECT 
        traffic_source,
        COUNT(*) as visits
    FROM page_views 
    WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY traffic_source
    ORDER BY visits DESC
");

$trafficSources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total counts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM page_views");
$totalViews = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM downloads");
$totalDownloads = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'viewTrend' => $viewTrend,
    'downloadTrend' => $downloadTrend,
    'trafficSources' => $trafficSources,
    'totalViews' => $totalViews,
    'totalDownloads' => $totalDownloads
], JSON_PRETTY_PRINT);
?>
