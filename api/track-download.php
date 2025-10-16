<?php
/**
 * Track Download Event
 */

header('Content-Type: application/json');
require_once '../config_modern.php';
require_once '../includes/MonetizationManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$link_id = $input['link_id'] ?? 0;

if ($link_id) {
    try {
        $monetization = new MonetizationManager($pdo);
        
        // Get link info
        $stmt = $pdo->prepare("SELECT monetizer_service FROM monetized_links WHERE id = ?");
        $stmt->execute([$link_id]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($link) {
            $monetization->trackEvent($link_id, 'download', $link['monetizer_service']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
