<?php
/**
 * Cron Job for Automated Analytics Reports
 * 
 * Schedule this file to run:
 * - Daily: at 8:00 AM
 * - Weekly: every Monday at 8:00 AM
 * - Monthly: first day of month at 8:00 AM
 * 
 * Example cron entries:
 * Daily: 0 8 * * * /usr/bin/php /path/to/cron_analytics_reports.php daily
 * Weekly: 0 8 * * 1 /usr/bin/php /path/to/cron_analytics_reports.php weekly
 * Monthly: 0 8 1 * * /usr/bin/php /path/to/cron_analytics_reports.php monthly
 */

// Define admin access
define('ADMIN_ACCESS', true);

// Get report type from command line argument
$reportType = $argv[1] ?? 'weekly';

// Include configuration
require_once dirname(__FILE__) . '/../config_modern.php';
require_once dirname(__FILE__) . '/system/analytics_tracker.php';

// Get admin emails from settings or use default
$adminEmails = getAdminEmails($pdo);

if (empty($adminEmails)) {
    error_log("No admin emails configured for analytics reports");
    exit(1);
}

// Send report to each admin
foreach ($adminEmails as $email) {
    $result = sendAnalyticsReport($email, $reportType);
    
    if ($result['success']) {
        echo "Report sent successfully to: {$email}\n";
    } else {
        error_log("Failed to send report to {$email}: " . $result['error']);
        echo "Failed to send report to: {$email}\n";
    }
}

/**
 * Get admin emails from database
 */
function getAdminEmails($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT email FROM users 
            WHERE role IN ('admin', 'superadmin') 
            AND email IS NOT NULL 
            AND email != ''
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error fetching admin emails: " . $e->getMessage());
        return [];
    }
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
        
        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: DONAN22 Analytics <noreply@donan22.com>\r\n";
        $headers .= "Reply-To: noreply@donan22.com\r\n";
        
        $subject = "DONAN22 {$period} Analytics Report - " . date('F d, Y');
        
        // Send email
        $sent = mail($emailTo, $subject, $emailBody, $headers);
        
        if ($sent) {
            // Log the sent report
            $stmt = $pdo->prepare("
                INSERT INTO email_reports (email_to, report_type, sent_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$emailTo, $reportType]);
            
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Mail function failed'];
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

echo "Analytics report cron job completed.\n";
?>
