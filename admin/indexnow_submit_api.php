<?php
/**
 * IndexNow Submit API
 * Backend endpoint for IndexNow monitor dashboard
 */

require_once __DIR__ . '/../config_modern.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/IndexNowSubmitter.php';

// Check admin authentication using isLoggedIn() function
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get request data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$action = $data['action'] ?? '';

// Initialize IndexNow
$indexNow = new IndexNowSubmitter('localhost');

switch ($action) {
    
    case 'submit_all':
        // Submit all URLs from sitemap
        $sitemapPath = __DIR__ . '/../sitemap.xml';
        $result = $indexNow->submitSitemap($sitemapPath, 'bing');
        
        if ($result['success']) {
            // Count URLs
            $xml = simplexml_load_file($sitemapPath);
            $urlCount = count($xml->url);
            
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'count' => $urlCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        break;
    
    case 'test':
        // Test IndexNow connection with homepage
        $testUrl = 'http://localhost/donan22/';
        $result = $indexNow->submitUrl($testUrl, 'bing');
        
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'endpoint' => 'Bing'
        ]);
        break;
    
    case 'clear_logs':
        // Clear IndexNow logs
        $logFile = __DIR__ . '/../logs/indexnow.log';
        
        if (file_exists($logFile)) {
            if (unlink($logFile)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Logs cleared successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete log file'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No logs to clear'
            ]);
        }
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}
?>
