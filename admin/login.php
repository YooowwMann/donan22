<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'system/security_system.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$clientIP = $security->getClientIP();

// Auto-unlock expired bans
autoUnlockExpiredBans($pdo);

// Check if IP is banned
if (isIPBanned($pdo, $clientIP)) {
    $error = "IP Anda telah diblokir karena terlalu banyak percobaan login yang gagal. Silakan hubungi administrator.";
}

// Check if should auto-ban
if (!$error) {
    $autoBanSettings = getAutoBanSettings($pdo);
    if ($autoBanSettings['auto_ban_enabled']) {
        $banCheck = checkAutoBan(
            $pdo, 
            $clientIP, 
            $autoBanSettings['max_login_attempts'], 
            $autoBanSettings['time_window'] * 60
        );
        
        if ($banCheck['should_ban']) {
            // Auto-ban this IP
            autoBanIP($pdo, $clientIP, $autoBanSettings['ban_duration']);
            $error = "Terlalu banyak percobaan login gagal. IP Anda telah diblokir untuk {$autoBanSettings['ban_duration']} menit.";
        }
    }
}

// Check rate limiting (existing check)
if (!$error && !$security->checkLoginAttempts($clientIP)) {
    $error = "Terlalu banyak percobaan login dari IP ini. Coba lagi dalam 15 menit.";
}

if ($_POST && !$error) {
    $username = $security->sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (!$username || !$password) {
        $error = 'Username dan password harus diisi';
    } elseif (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid';
        $security->logSecurityEvent('csrf_violation', "Invalid CSRF token in login form", 'medium');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM administrators WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && $security->verifyPassword($password, $admin['password_hash'])) {
                // Log successful login
                $security->logLoginAttempt($username, true, $clientIP);
                
                // Set session data
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
                $_SESSION['login_time'] = time();
                
                // Update last login with timestamp and IP
                $stmt = $pdo->prepare("UPDATE administrators SET last_login = NOW(), last_login_at = NOW(), last_login_ip = ?, login_attempts = 0, failed_login_attempts = 0 WHERE id = ?");
                $stmt->execute([$clientIP, $admin['id']]);
                
                // Set remember me cookie
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                }
                
                // Redirect to requested page or dashboard
                $redirectTo = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirectTo);
                exit;
                
            } else {
                // Log failed login
                $security->logLoginAttempt($username, false, $clientIP);
                $error = "Username atau password salah";
                
                // Update failed attempts in database
                if ($admin) {
                    $stmt = $pdo->prepare("UPDATE administrators SET login_attempts = login_attempts + 1 WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                }
            }
            
        } catch (PDOException $e) {
            $error = DEBUG_MODE ? $e->getMessage() : 'Terjadi kesalahan sistem';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= esc($siteSettings['site_name']) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
        
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">   <!--  Responsive Scaling System -->   <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: <?= $siteSettings['theme_color'] ?>;
            --primary-rgb: <?= implode(',', sscanf($siteSettings['theme_color'], "#%02x%02x%02x")) ?>;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, rgba(var(--primary-rgb), 0.8) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .btn-login {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: rgba(var(--primary-rgb), 0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-check {
            margin: 20px 0;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-site a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-card {
                border-radius: 0;
                min-height: 100vh;
            }
            
            .login-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h3 class="mb-0">Admin Login</h3>
                <p class="mb-0 opacity-75"><?= esc($siteSettings['site_name']) ?></p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= esc($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Username" required autocomplete="username"
                               value="<?= esc($_POST['username'] ?? '') ?>">
                        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required autocomplete="current-password">
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">
                            Ingat saya selama 30 hari
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-login" <?= $error && strpos($error, 'dikunci') !== false ? 'disabled' : '' ?>>
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <?= $error && strpos($error, 'dikunci') !== false ? 'Akun Terkunci' : 'Masuk' ?>
                    </button>
                </form>
                
                <div class="back-to-site">
                    <a href="<?= SITE_URL ?>">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali ke Website
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto focus and form validation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus on username field
            document.getElementById('username').focus();
            
            // Add loading state to login button
            document.querySelector('form').addEventListener('submit', function() {
                const btn = document.querySelector('.btn-login');
                if (!btn.disabled) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                    btn.disabled = true;
                }
            });
            
            // Remove error message after 5 seconds
            const alert = document.querySelector('.alert-danger');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>
