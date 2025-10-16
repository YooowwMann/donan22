<?php
/**
 * Track Social Unlock Event
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
$platform = $input['platform'] ?? '';

if ($link_id && $platform) {
    try {
        $monetization = new MonetizationManager($pdo);
        
        // Track share event
        $monetization->trackEvent($link_id, 'share', 'social_unlock');
        
        // Insert social unlock record
        $unlock_token = bin2hex(random_bytes(16));
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("
            INSERT INTO social_locker_unlocks 
            (link_id, unlock_token, user_ip, social_network, is_verified, unlocked_at, expires_at)
            VALUES (?, ?, ?, ?, 1, NOW(), ?)
        ");
        
        $stmt->execute([$link_id, $unlock_token, $user_ip, $platform, $expires_at]);
        
        echo json_encode(['success' => true, 'token' => $unlock_token]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
