<?php
/**
 * Newsletter Unsubscribe Handler
 * Handle unsubscribe requests
 */

require_once 'config_modern.php';
require_once 'includes/RateLimiter.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: Rate limiting to prevent abuse
    $rateLimiter = new RateLimiter($pdo);
    $clientIP = RateLimiter::getClientIP();
    
    if (!$rateLimiter->checkRateLimit('unsubscribe', $clientIP, 5, 300)) {
        $message = 'Too many requests. Please try again later.';
        $messageType = 'danger';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid email address.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE newsletter_subscribers 
                    SET status = 'unsubscribed', unsubscribed_at = NOW() 
                    WHERE email = ? AND status = 'active'
                ");
                $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                // Get subscriber ID
                $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                $subscriber = $stmt->fetch();
                
                // Log action
                if ($subscriber) {
                    $logStmt = $pdo->prepare("
                        INSERT INTO newsletter_logs 
                        (subscriber_id, action, ip_address, user_agent, created_at) 
                        VALUES (?, 'unsubscribe', ?, ?, NOW())
                    ");
                    $logStmt->execute([
                        $subscriber['id'],
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                }
                
                $message = 'You have been successfully unsubscribed from our newsletter.';
                $messageType = 'success';
            } else {
                $message = 'Email not found or already unsubscribed.';
                $messageType = 'warning';
            }
        } catch (PDOException $e) {
            error_log("Unsubscribe error: " . $e->getMessage());
            $message = 'An error occurred. Please try again later.';
            $messageType = 'danger';
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - DONAN22</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-envelope-open-text fa-4x text-muted"></i>
                        </div>
                        
                        <h3 class="mb-3">Unsubscribe from Newsletter</h3>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?>" role="alert">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($messageType !== 'success'): ?>
                            <p class="text-muted mb-4">
                                We're sorry to see you go. Enter your email address below to unsubscribe from our newsletter.
                            </p>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control form-control-lg" 
                                        placeholder="Your email address"
                                        value="<?= htmlspecialchars($email) ?>"
                                        required
                                    >
                                </div>
                                
                                <button type="submit" class="btn btn-danger btn-lg w-100 mb-3">
                                    <i class="fas fa-sign-out-alt me-2"></i>Unsubscribe
                                </button>
                                
                                <a href="<?= SITE_URL ?>/index.php" class="btn btn-link text-muted">
                                    Go back to homepage
                                </a>
                            </form>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Go to Homepage
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
