<?php
/**
 * Email Report System
 * Automated analytics reports via email
 */

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'system/analytics_tracker.php';
require_once '../EmailSender.php';
requireLogin();

// Check if this is a manual send or scheduled
$manualSend = isset($_POST['send_report']);
$emailTo = $_POST['email_to'] ?? '';
$reportType = $_POST['report_type'] ?? 'weekly';

if ($manualSend && !empty($emailTo)) {
    $result = sendAnalyticsReport($emailTo, $reportType);
    $_SESSION['message'] = $result['success'] ? 'Report sent successfully!' : 'Failed to send report: ' . $result['error'];
    $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
    header('Location: analytics.php');
    exit;
}

/**
 * Send analytics report via email
 */
function sendAnalyticsReport($emailTo, $reportType = 'weekly') {
    global $pdo;
    
    try {
        $tracker = new AnalyticsTracker($pdo);
        
        // Determine date range based on report type
        switch ($reportType) {
            case 'daily':
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
                $period = 'Daily';
                break;
            case 'weekly':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                $endDate = date('Y-m-d');
                $period = 'Weekly';
                break;
            case 'monthly':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $period = 'Monthly';
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-7 days'));
                $endDate = date('Y-m-d');
                $period = 'Weekly';
        }
        
        // Get analytics data
        $analyticsData = $tracker->getAnalyticsData();
        $bounceRate = $tracker->getBounceRate(7);
        $trafficSources = $tracker->getTrafficSourcesBreakdown(7);
        $geoStats = $tracker->getGeographicStats(7);
        
        // Build email HTML
        $emailBody = buildEmailReport($period, $startDate, $endDate, $analyticsData, $bounceRate, $trafficSources, $geoStats);
        
        $subject = "DONAN22 {$period} Analytics Report - " . date('F d, Y');
        
        // Send email using PHPMailer
        try {
            $emailSender = new EmailSender();
            $result = $emailSender->send($emailTo, $subject, $emailBody);
            
            if ($result['success']) {
                // Log the sent report
                $stmt = $pdo->prepare("
                    INSERT INTO email_reports (email_to, report_type, sent_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$emailTo, $reportType]);
                
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                return ['success' => false, 'error' => $result['message']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Email error: ' . $e->getMessage()];
        }
        
    } catch (Exception $e) {
        error_log("Email report error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Build HTML email report
 */
function buildEmailReport($period, $startDate, $endDate, $analytics, $bounce, $traffic, $geo) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; }
            .stat-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .stat-title { font-size: 14px; color: #666; margin-bottom: 5px; }
            .stat-value { font-size: 32px; font-weight: bold; color: #667eea; }
            .stat-small { font-size: 12px; color: #999; }
            .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .table th { background: #667eea; color: white; padding: 10px; text-align: left; }
            .table td { padding: 10px; border-bottom: 1px solid #ddd; }
            .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
            .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
            .badge-success { background: #28a745; color: white; }
            .badge-warning { background: #ffc107; color: #333; }
            .badge-danger { background: #dc3545; color: white; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎯 DONAN22 Analytics Report</h1>
                <p>' . $period . ' Report: ' . date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)) . '</p>
            </div>
            
            <div class="content">
                <h2>📊 Overview</h2>
                
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; padding: 10px;">
                            <div class="stat-box">
                                <div class="stat-title">Total Views</div>
                                <div class="stat-value">' . number_format($analytics['total_views']) . '</div>
                                <div class="stat-small">+' . number_format($analytics['today_views']) . ' today</div>
                            </div>
                        </td>
                        <td style="width: 50%; padding: 10px;">
                            <div class="stat-box">
                                <div class="stat-title">Total Downloads</div>
                                <div class="stat-value">' . number_format($analytics['total_downloads']) . '</div>
                                <div class="stat-small">+' . number_format($analytics['today_downloads']) . ' today</div>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <h2>📈 Session Metrics</h2>
                <div class="stat-box">
                    <p><strong>Bounce Rate:</strong> <span class="badge badge-' . ($bounce['bounce_rate'] > 70 ? 'danger' : ($bounce['bounce_rate'] > 50 ? 'warning' : 'success')) . '">' . number_format($bounce['bounce_rate'], 1) . '%</span></p>
                    <p><strong>Avg. Session Duration:</strong> ' . gmdate("i:s", $bounce['avg_duration'] ?? 0) . ' minutes</p>
                    <p><strong>Avg. Pages per Session:</strong> ' . number_format($bounce['avg_pages_per_session'], 1) . '</p>
                </div>
                
                <h2>🔥 Top Posts</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>Views</th>
                            <th>Downloads</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach (array_slice($analytics['top_posts'], 0, 5) as $post) {
        $html .= '<tr>
                    <td>' . htmlspecialchars(substr($post['title'], 0, 40)) . '</td>
                    <td>' . number_format($post['view_count']) . '</td>
                    <td>' . number_format($post['download_count']) . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
                </table>
                
                <h2>🌍 Traffic Sources</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Visits</th>
                            <th>Unique Visitors</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach (array_slice($traffic, 0, 5) as $source) {
        $html .= '<tr>
                    <td>' . ucfirst($source['traffic_source']) . '</td>
                    <td>' . number_format($source['visits']) . '</td>
                    <td>' . number_format($source['unique_visitors']) . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
                </table>
                
                <h2>🌏 Top Countries</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th>Sessions</th>
                            <th>Page Views</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach (array_slice($geo, 0, 5) as $country) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($country['country']) . '</td>
                    <td>' . number_format($country['sessions']) . '</td>
                    <td>' . number_format($country['page_views']) . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
                </table>
            </div>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' DONAN22 - Automated Analytics Report</p>
                <p>This email was sent automatically. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Initialize email_reports table if doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `email_reports` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `email_to` varchar(255) NOT NULL,
          `report_type` enum('daily','weekly','monthly') NOT NULL,
          `sent_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_sent_at` (`sent_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {
    error_log("Error creating email_reports table: " . $e->getMessage());
}
?>
