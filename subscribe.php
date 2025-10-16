<?php
/**
 * Newsletter Subscription Handler
 * Handle newsletter subscription requests
 */

require_once 'config_modern.php';
require_once 'includes/RateLimiter.php';

header('Content-Type: application/json');

// SECURITY: Rate limiting to prevent spam
$rateLimiter = new RateLimiter($pdo);
$clientIP = RateLimiter::getClientIP();

if (!$rateLimiter->checkRateLimit('subscribe', $clientIP, 3, 300)) {
    $timeLeft = $rateLimiter->getTimeUntilReset('subscribe', $clientIP, 300);
    echo json_encode([
        'success' => false,
        'message' => "Too many subscription attempts. Please try again in " . ceil($timeLeft / 60) . " minutes."
    ]);
    exit;
}

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode([
                'success' => false,
                'message' => 'This email is already subscribed to our newsletter.'
            ]);
            exit;
        } elseif ($existing['status'] === 'pending') {
            echo json_encode([
                'success' => false,
                'message' => 'This email is pending verification. Please check your inbox.'
            ]);
            exit;
        } elseif ($existing['status'] === 'unsubscribed') {
            // Reactivate unsubscribed email
            $stmt = $pdo->prepare("
                UPDATE newsletter_subscribers 
                SET status = 'active', 
                    name = ?, 
                    subscribed_at = NOW(),
                    unsubscribed_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$name, $existing['id']]);
            
            // Log action
            logNewsletterAction($pdo, $existing['id'], 'subscribe');
            
            echo json_encode([
                'success' => true,
                'message' => 'Welcome back! You have been re-subscribed successfully.'
            ]);
            exit;
        }
    }
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    
    // Insert new subscriber
    $stmt = $pdo->prepare("
        INSERT INTO newsletter_subscribers 
        (email, name, status, verification_token, ip_address, user_agent, subscribed_at) 
        VALUES (?, ?, 'active', ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $email,
        $name,
        $verificationToken,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    $subscriberId = $pdo->lastInsertId();
    
    // Log action
    logNewsletterAction($pdo, $subscriberId, 'subscribe');
    
    // Send welcome email (optional - implement later with PHPMailer)
    // sendWelcomeEmail($email, $name, $verificationToken);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing! You will receive our latest updates.'
    ]);
    
} catch (PDOException $e) {
    error_log("Newsletter subscription error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}

/**
 * Log newsletter action
 */
function logNewsletterAction($pdo, $subscriberId, $action) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_logs 
            (subscriber_id, action, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $subscriberId,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Newsletter log error: " . $e->getMessage());
    }
}
