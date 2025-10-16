<?php
/**
 * Export Analytics Data
 * Supports CSV, Excel (HTML), and PDF formats
 */

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'system/analytics_tracker.php';
requireLogin();

$format = $_GET['format'] ?? 'csv';
$type = $_GET['type'] ?? 'overview';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Validate dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Initialize tracker
$tracker = new AnalyticsTracker($pdo);

// Get data based on type
$data = [];
$filename = '';

try {
    switch ($type) {
        case 'overview':
            $filename = "analytics_overview_{$startDate}_to_{$endDate}";
            $data = getOverviewData($pdo, $startDate, $endDate);
            break;
            
        case 'views':
            $filename = "views_report_{$startDate}_to_{$endDate}";
            $data = getViewsData($pdo, $startDate, $endDate);
            break;
            
        case 'downloads':
            $filename = "downloads_report_{$startDate}_to_{$endDate}";
            $data = getDownloadsData($pdo, $startDate, $endDate);
            break;
            
        case 'posts':
            $filename = "posts_performance_{$startDate}_to_{$endDate}";
            $data = getPostsPerformance($pdo, $startDate, $endDate);
            break;
            
        case 'traffic':
            $filename = "traffic_sources_{$startDate}_to_{$endDate}";
            $data = getTrafficSources($pdo, $startDate, $endDate);
            break;
            
        case 'devices':
            $filename = "device_analytics_{$startDate}_to_{$endDate}";
            $data = getDeviceAnalytics($pdo, $startDate, $endDate);
            break;
            
        default:
            die("Invalid export type");
    }
    
    // Export based on format
    switch ($format) {
        case 'csv':
            exportCSV($data, $filename);
            break;
            
        case 'excel':
            exportExcel($data, $filename);
            break;
            
        case 'pdf':
            exportPDF($data, $filename, $type, $startDate, $endDate);
            break;
            
        default:
            die("Invalid export format");
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    die("Export failed: " . $e->getMessage());
}

// ===== DATA COLLECTION FUNCTIONS =====

function getOverviewData($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(view_date) as date,
            COUNT(*) as total_views,
            COUNT(DISTINCT ip_address) as unique_visitors,
            COUNT(DISTINCT post_id) as posts_viewed
        FROM page_views 
        WHERE DATE(view_date) BETWEEN ? AND ?
        GROUP BY DATE(view_date)
        ORDER BY date ASC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getViewsData($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            p.title as post_title,
            c.name as category,
            DATE(pv.view_date) as date,
            COUNT(*) as views,
            COUNT(DISTINCT pv.ip_address) as unique_views
        FROM page_views pv
        LEFT JOIN posts p ON pv.post_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE DATE(pv.view_date) BETWEEN ? AND ? AND pv.post_id IS NOT NULL
        GROUP BY pv.post_id, DATE(pv.view_date)
        ORDER BY date DESC, views DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDownloadsData($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            p.title as post_title,
            c.name as category,
            d.file_name,
            DATE(d.download_date) as date,
            COUNT(*) as downloads,
            COUNT(DISTINCT d.ip_address) as unique_downloads
        FROM downloads d
        LEFT JOIN posts p ON d.post_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE DATE(d.download_date) BETWEEN ? AND ?
        GROUP BY d.post_id, d.file_name, DATE(d.download_date)
        ORDER BY date DESC, downloads DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPostsPerformance($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            p.title,
            p.slug,
            c.name as category,
            u.username as author,
            p.status,
            p.view_count,
            p.download_count,
            (SELECT COUNT(*) FROM page_views WHERE post_id = p.id AND DATE(view_date) BETWEEN ? AND ?) as period_views,
            (SELECT COUNT(*) FROM downloads WHERE post_id = p.id AND DATE(download_date) BETWEEN ? AND ?) as period_downloads,
            p.created_at
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.deleted_at IS NULL
        ORDER BY period_views DESC
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTrafficSources($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN referer IS NULL OR referer = '' THEN 'Direct'
                WHEN referer LIKE '%google%' THEN 'Google'
                WHEN referer LIKE '%facebook%' THEN 'Facebook'
                WHEN referer LIKE '%twitter%' OR referer LIKE '%t.co%' THEN 'Twitter'
                WHEN referer LIKE '%instagram%' THEN 'Instagram'
                WHEN referer LIKE '%youtube%' THEN 'YouTube'
                WHEN referer LIKE '%linkedin%' THEN 'LinkedIn'
                ELSE 'Other Referrals'
            END as source,
            COUNT(*) as visits,
            COUNT(DISTINCT ip_address) as unique_visitors
        FROM page_views 
        WHERE DATE(view_date) BETWEEN ? AND ?
        GROUP BY source
        ORDER BY visits DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDeviceAnalytics($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                ELSE 'Desktop'
            END as device_type,
            CASE 
                WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edg%' THEN 'Chrome'
                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                WHEN user_agent LIKE '%Edg%' THEN 'Edge'
                WHEN user_agent LIKE '%Opera%' OR user_agent LIKE '%OPR%' THEN 'Opera'
                ELSE 'Other'
            END as browser,
            COUNT(*) as visits,
            COUNT(DISTINCT ip_address) as unique_visitors
        FROM page_views 
        WHERE DATE(view_date) BETWEEN ? AND ?
        GROUP BY device_type, browser
        ORDER BY visits DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ===== EXPORT FUNCTIONS =====

function exportCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($data)) {
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

function exportExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
    echo '<x:Name>Analytics Report</x:Name>';
    echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '</head>';
    echo '<body>';
    echo '<table border="1">';
    
    if (!empty($data)) {
        // Headers
        echo '<thead><tr style="background-color: #4CAF50; color: white;">';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
        }
        echo '</tr></thead>';
        
        // Data
        echo '<tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

function exportPDF($data, $filename, $type, $startDate, $endDate) {
    // Simple HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // For simple PDF, we'll use HTML output that can be printed to PDF
    // In production, use libraries like TCPDF, mPDF, or Dompdf
    
    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta charset="utf-8">';
    echo '<title>Analytics Report</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .info { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #4CAF50; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f5f5f5; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>';
    echo '</head><body>';
    
    echo '<h1>DONAN22 Analytics Report</h1>';
    echo '<div class="info">';
    echo '<strong>Report Type:</strong> ' . ucwords(str_replace('_', ' ', $type)) . '<br>';
    echo '<strong>Period:</strong> ' . date('F d, Y', strtotime($startDate)) . ' - ' . date('F d, Y', strtotime($endDate)) . '<br>';
    echo '<strong>Generated:</strong> ' . date('F d, Y H:i:s') . '<br>';
    echo '<strong>Total Records:</strong> ' . count($data);
    echo '</div>';
    
    if (!empty($data)) {
        echo '<table>';
        echo '<thead><tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
        }
        echo '</tr></thead>';
        
        echo '<tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
    
    echo '<div class="footer">';
    echo '<p>&copy; ' . date('Y') . ' DONAN22 - Analytics Report</p>';
    echo '<p>This is an automated report. For questions, contact the administrator.</p>';
    echo '</div>';
    
    echo '</body></html>';
    
    // Note: For actual PDF generation, implement TCPDF or similar library
    exit;
}
?>
