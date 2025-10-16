<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/security_system.php';
require_once 'system/soft_delete_system.php';
require_once 'system/security_scanner.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$pageTitle = 'Security System';
$currentPage = 'security';

$security = new SecurityManager($pdo);

try {
    $softDelete = new SoftDeleteManager($pdo);
} catch (Exception $e) {
    error_log('Failed to initialize: ' . $e->getMessage());
    $softDelete = null;
}

$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = 'Token keamanan tidak valid';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'restore_post':
                if ($softDelete && isset($_POST['post_id'])) {
                    $result = $softDelete->restorePost(intval($_POST['post_id']));
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
                break;
            case 'permanent_delete':
                if ($softDelete && isset($_POST['post_id'])) {
                    $result = $softDelete->permanentDeletePost(intval($_POST['post_id']));
                    $_SESSION['flash_message'] = $result['message'];
                    $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                }
                break;
            case 'block_ip':
                $ip = $security->sanitizeInput($_POST['ip_address'] ?? '');
                $reason = $security->sanitizeInput($_POST['reason'] ?? '');
                if ($ip) {
                    $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason, blocked_by) VALUES (?, ?, ?)");
                    $_SESSION['flash_message'] = $stmt->execute([$ip, $reason, $_SESSION['admin_id']]) ? 'IP berhasil diblokir' : 'Gagal memblokir IP';
                    $_SESSION['flash_type'] = $stmt->rowCount() ? 'success' : 'danger';
                }
                break;
            case 'unblock_ip':
                $id = intval($_POST['ip_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE id = ?");
                    $_SESSION['flash_message'] = $stmt->execute([$id]) ? 'IP berhasil di-unblock' : 'Gagal unblock IP';
                    $_SESSION['flash_type'] = $stmt->rowCount() ? 'success' : 'danger';
                }
                break;
            case 'save_autoban_settings':
                $enabled = isset($_POST['auto_ban_enabled']) ? '1' : '0';
                $maxAttempts = intval($_POST['max_login_attempts'] ?? 5);
                $timeWindow = intval($_POST['time_window'] ?? 15);
                $banDuration = intval($_POST['ban_duration'] ?? 15);
                
                try {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()")
                        ->execute(['auto_ban_enabled', $enabled, $enabled]);
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()")
                        ->execute(['auto_ban_max_login_attempts', $maxAttempts, $maxAttempts]);
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()")
                        ->execute(['auto_ban_time_window', $timeWindow, $timeWindow]);
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()")
                        ->execute(['auto_ban_ban_duration', $banDuration, $banDuration]);
                    
                    $_SESSION['flash_message'] = 'Auto-ban settings berhasil disimpan';
                    $_SESSION['flash_type'] = 'success';
                } catch (Exception $e) {
                    $_SESSION['flash_message'] = 'Gagal menyimpan settings: ' . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                }
                break;
            case 'run_security_scan':
                try {
                    $scanner = new SecurityScanner($pdo);
                    $scanResults = $scanner->runFullScan();
                    $_SESSION['scan_results'] = $scanResults;
                    $_SESSION['flash_message'] = 'Security scan completed. Found ' . $scanResults['summary']['total'] . ' issues.';
                    $_SESSION['flash_type'] = 'info';
                } catch (Exception $e) {
                    $_SESSION['flash_message'] = 'Scan error: ' . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                }
                break;
        }
    }
    
    // PRG Pattern: Redirect after POST to prevent form resubmission on refresh
    header('Location: security.php?tab=' . $activeTab);
    exit;
}

// Get flash message from session
$message = $_SESSION['flash_message'] ?? '';
$messageType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$csrfToken = generateCSRFToken();

$stats = ['security_high' => 0, 'security_medium' => 0, 'security_low' => 0, 'security_total' => 0, 'deleted_posts' => 0, 'blocked_ips' => 0];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, severity FROM security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY severity");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['security_high'] = $logs['high'] ?? 0;
    $stats['security_medium'] = $logs['medium'] ?? 0;
    $stats['security_low'] = $logs['low'] ?? 0;
    $stats['security_total'] = array_sum($logs);
    
    if ($softDelete) {
        $stats['deleted_posts'] = count($softDelete->getDeletedPosts());
    }
    $stats['blocked_ips'] = $pdo->query("SELECT COUNT(*) FROM blocked_ips")->fetchColumn();
} catch (Exception $e) {
    error_log('Stats error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <style>
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link:hover { 
            border-bottom-color: #dee2e6; 
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background: none;
            font-weight: 500;
        }
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('security'); ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-shield-alt me-2"></i><?= $pageTitle ?></h1>
                    <span class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-calendar me-1"></i><?= date('d M Y') ?>
                    </span>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-uppercase text-muted mb-1" style="font-size: 0.75rem; font-weight: 600;">HIGH THREATS</p>
                                        <h2 class="mb-0 fw-bold text-danger"><?= $stats['security_high'] ?></h2>
                                        <small class="text-muted">Last 24h</small>
                                    </div>
                                    <div class="text-danger" style="font-size: 2rem;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm" style="border-left: 4px solid #ffc107 !important;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-uppercase text-muted mb-1" style="font-size: 0.75rem; font-weight: 600;">SECURITY LOGS</p>
                                        <h2 class="mb-0 fw-bold text-warning"><?= $stats['security_total'] ?></h2>
                                        <small class="text-muted">Total events</small>
                                    </div>
                                    <div class="text-warning" style="font-size: 2rem;">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm" style="border-left: 4px solid #17a2b8 !important;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-uppercase text-muted mb-1" style="font-size: 0.75rem; font-weight: 600;">BLOCKED IPS</p>
                                        <h2 class="mb-0 fw-bold text-info"><?= $stats['blocked_ips'] ?></h2>
                                        <small class="text-muted">Addresses</small>
                                    </div>
                                    <div class="text-info" style="font-size: 2rem;">
                                        <i class="fas fa-ban"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" href="?tab=overview">
                            <i class="fas fa-chart-line me-2"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'recovery' ? 'active' : '' ?>" href="?tab=recovery">
                            <i class="fas fa-trash-restore me-2"></i>Recovery
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'logs' ? 'active' : '' ?>" href="?tab=logs">
                            <i class="fas fa-list me-2"></i>Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'ip-blocking' ? 'active' : '' ?>" href="?tab=ip-blocking">
                            <i class="fas fa-ban me-2"></i>IP Blocking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'firewall' ? 'active' : '' ?>" href="?tab=firewall">
                            <i class="fas fa-fire me-2"></i>Firewall
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'scanner' ? 'active' : '' ?>" href="?tab=scanner">
                            <i class="fas fa-search me-2"></i>Security Scanner
                        </a>
                    </li>
                </ul>

                <?php if ($activeTab === 'overview'): ?>
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2 text-success"></i>System Protection</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <span><i class="fas fa-check-circle text-success me-2"></i>CSRF Protection</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <span><i class="fas fa-check-circle text-success me-2"></i>SQL Injection Shield</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <span><i class="fas fa-check-circle text-success me-2"></i>XSS Protection</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <span><i class="fas fa-check-circle text-success me-2"></i>Session Security</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <span><i class="fas fa-info-circle text-info me-2"></i>Backup by NiagaHoster</span>
                                        <span class="badge bg-info">Hosting Provider</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>System Info</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong>Deleted Posts:</strong>
                                        <span class="badge bg-warning"><?= $stats['deleted_posts'] ?></span>
                                    </div>
                                </div>
                                <div class="mb-0 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong>Blocked IPs:</strong>
                                        <span class="badge bg-danger"><?= $stats['blocked_ips'] ?></span>
                                    </div>
                                </div>
                                <div class="alert alert-info mb-0 mt-3">
                                    <small>
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <strong>Backup Protection:</strong><br>
                                        Database backup dikelola oleh <strong>NiagaHoster</strong> (hosting provider).<br>
                                        • Backup otomatis setiap hari<br>
                                        • Retention: 7-30 hari<br>
                                        • Restore via cPanel atau hubungi support
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'recovery'): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-trash-restore me-2"></i>Content Recovery</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($softDelete): 
                            $deletedPosts = $softDelete->getDeletedPosts();
                            if (!empty($deletedPosts)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Post Title</th>
                                        <th>Deleted</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deletedPosts as $p): ?>
                                    <tr>
                                        <td><i class="fas fa-file-alt text-muted me-2"></i><?= htmlspecialchars($p['title']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($p['deleted_at'])) ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="restore_post">
                                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo me-1"></i>Restore
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="permanent_delete">
                                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete permanent?')">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-3x mb-3 d-block"></i>
                            <p class="text-muted">No deleted posts</p>
                        </div>
                        <?php endif; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'logs'): ?>
                
                <!-- Failed Logins Section -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Failed Login Attempts</h5>
                        <span class="badge bg-danger" id="failedLoginCount">Loading...</span>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get failed logins from security_logs
                        $dateFilter = $_GET['date_filter'] ?? 'today';
                        $ipFilter = $_GET['ip_filter'] ?? '';
                        
                        $dateCondition = "DATE(sl.created_at) = CURDATE()"; // Default: today
                        if ($dateFilter === 'week') {
                            $dateCondition = "sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        } elseif ($dateFilter === 'month') {
                            $dateCondition = "sl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        } elseif ($dateFilter === 'all') {
                            $dateCondition = "1=1";
                        }
                        
                        $ipCondition = $ipFilter ? "AND sl.ip_address LIKE ?" : "";
                        
                        $query = "SELECT sl.*, a.username 
                                  FROM security_logs sl 
                                  LEFT JOIN administrators a ON sl.details LIKE CONCAT('%', a.username, '%')
                                  WHERE sl.event_type IN ('failed_login', 'login_failed', 'blocked_login') 
                                  AND $dateCondition $ipCondition
                                  ORDER BY sl.created_at DESC LIMIT 100";
                        
                        $stmt = $pdo->prepare($query);
                        if ($ipFilter) {
                            $stmt->execute(['%' . $ipFilter . '%']);
                        } else {
                            $stmt->execute();
                        }
                        $failedLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Get statistics - fix: remove sl alias in $dateCondition for this query
                        $statsDateCondition = str_replace('sl.', '', $dateCondition);
                        $statsQuery = "SELECT 
                                        COUNT(*) as total,
                                        COUNT(DISTINCT ip_address) as unique_ips,
                                        ip_address,
                                        COUNT(*) as ip_count
                                       FROM security_logs 
                                       WHERE event_type IN ('failed_login', 'login_failed', 'blocked_login')
                                       AND $statsDateCondition
                                       GROUP BY ip_address
                                       ORDER BY ip_count DESC
                                       LIMIT 5";
                        $statsStmt = $pdo->query($statsQuery);
                        $topIPs = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
                        $totalFailed = array_sum(array_column($topIPs, 'ip_count'));
                        ?>
                        
                        <!-- Filter Form -->
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="tab" value="logs">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="date_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                                        <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                                        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="ip_filter" class="form-control form-control-sm" placeholder="Filter by IP..." value="<?= htmlspecialchars($ipFilter) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Statistics -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="alert alert-danger mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-times-circle me-2"></i>Total Failures</span>
                                        <strong><?= number_format($totalFailed) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-warning mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-network-wired me-2"></i>Unique IPs</span>
                                        <strong><?= count($topIPs) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-calendar me-2"></i>Period</span>
                                        <strong><?= ucfirst(str_replace('_', ' ', $dateFilter)) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Offending IPs -->
                        <?php if (!empty($topIPs)): ?>
                        <div class="alert alert-light mb-3">
                            <strong><i class="fas fa-crown me-2"></i>Top Offending IPs:</strong>
                            <div class="mt-2">
                                <?php foreach ($topIPs as $tip): ?>
                                <span class="badge bg-danger me-2 mb-1">
                                    <code class="text-white"><?= htmlspecialchars($tip['ip_address']) ?></code> 
                                    (<?= $tip['ip_count'] ?>x)
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Failed Logins Table -->
                        <?php if ($failedLogins): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>IP Address</th>
                                        <th>Username Attempt</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($failedLogins as $fl): 
                                        // Extract username from details
                                        preg_match('/username[:\s]+([^\s,\)]+)/i', $fl['details'], $matches);
                                        $attemptedUsername = $matches[1] ?? 'Unknown';
                                    ?>
                                    <tr>
                                        <td><?= date('d M H:i:s', strtotime($fl['created_at'])) ?></td>
                                        <td><code class="text-danger"><?= htmlspecialchars($fl['ip_address']) ?></code></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($attemptedUsername) ?></span></td>
                                        <td class="small"><?= htmlspecialchars(substr($fl['details'], 0, 80)) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3 d-block"></i>
                            <p class="text-muted">No failed login attempts in selected period</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- General Security Logs -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Security Logs</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 100");
                        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($logs): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>Event</th>
                                        <th>IP</th>
                                        <th>Severity</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= date('d M H:i', strtotime($log['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($log['event_type']) ?></td>
                                        <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                        <td>
                                            <span class="badge bg-<?= $log['severity'] === 'high' ? 'danger' : ($log['severity'] === 'medium' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($log['severity']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($log['details'], 0, 50)) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt text-success fa-3x mb-3 d-block"></i>
                            <p class="text-muted">No security logs</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'ip-blocking'): ?>
                
                <!-- Auto-Ban Settings -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Auto-Ban Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $autoBanSettings = getAutoBanSettings($pdo);
                        ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="save_autoban_settings">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="auto_ban_enabled" id="autoBanEnabled" <?= $autoBanSettings['auto_ban_enabled'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="autoBanEnabled">
                                                <strong>Enable Auto-Ban System</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Otomatis blokir IP dengan too many failed login attempts</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max Login Attempts</label>
                                        <input type="number" name="max_login_attempts" class="form-control" value="<?= $autoBanSettings['max_login_attempts'] ?>" min="3" max="20">
                                        <small class="text-muted">Failed attempts before ban</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Time Window (minutes)</label>
                                        <input type="number" name="time_window" class="form-control" value="<?= $autoBanSettings['time_window'] ?>" min="5" max="60">
                                        <small class="text-muted">Time frame to count attempts</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Ban Duration (minutes)</label>
                                        <input type="number" name="ban_duration" class="form-control" value="<?= $autoBanSettings['ban_duration'] ?>" min="5" max="1440">
                                        <small class="text-muted">How long to ban IP</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mb-0">
                                <strong><i class="fas fa-exclamation-triangle me-2"></i>Example:</strong> 
                                With settings "<?= $autoBanSettings['max_login_attempts'] ?> attempts in <?= $autoBanSettings['time_window'] ?> minutes", 
                                an IP with <?= $autoBanSettings['max_login_attempts'] ?> failed logins within <?= $autoBanSettings['time_window'] ?> minutes 
                                will be auto-banned for <?= $autoBanSettings['ban_duration'] ?> minutes.
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Manual IP Blocking -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-ban me-2"></i>Manual IP Blocking</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="block_ip">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" name="ip_address" class="form-control" placeholder="IP Address" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="reason" class="form-control" placeholder="Reason (optional)">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-ban me-1"></i>Block IP
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php
                        $blockedIPs = $pdo->query("SELECT * FROM blocked_ips ORDER BY blocked_at DESC")->fetchAll();
                        if ($blockedIPs): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Reason</th>
                                        <th>Blocked At</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blockedIPs as $ip): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($ip['ip_address']) ?></code></td>
                                        <td><?= htmlspecialchars($ip['reason']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($ip['blocked_at'])) ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="unblock_ip">
                                                <input type="hidden" name="ip_id" value="<?= $ip['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Unblock IP ini?')">
                                                    <i class="fas fa-check me-1"></i>Unblock
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt text-success fa-3x mb-3 d-block"></i>
                            <p class="text-muted">No blocked IPs</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'firewall'): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Firewall Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Firewall rules are managed through server configuration. This section displays current status.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="card-title text-success">
                                            <i class="fas fa-check-circle me-2"></i>Active Protection
                                        </h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-shield-alt text-success me-2"></i>DDoS Protection</li>
                                            <li><i class="fas fa-shield-alt text-success me-2"></i>Rate Limiting</li>
                                            <li><i class="fas fa-shield-alt text-success me-2"></i>SQL Injection Filter</li>
                                            <li><i class="fas fa-shield-alt text-success me-2"></i>XSS Filter</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6 class="card-title text-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Monitoring
                                        </h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-eye text-warning me-2"></i>Login Attempts</li>
                                            <li><i class="fas fa-eye text-warning me-2"></i>Failed Requests</li>
                                            <li><i class="fas fa-eye text-warning me-2"></i>Suspicious Activity</li>
                                            <li><i class="fas fa-eye text-warning me-2"></i>IP Reputation</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($activeTab === 'scanner'): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Security Scanner</h5>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="run_security_scan">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-play me-2"></i>Run Security Scan
                            </button>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php
                        $scanResults = $_SESSION['scan_results'] ?? null;
                        unset($_SESSION['scan_results']);
                        
                        if ($scanResults): 
                            $summary = $scanResults['summary'];
                            $score = $scanResults['score'];
                            $issues = $scanResults['issues'];
                            
                            // Score color
                            $scoreColor = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                        ?>
                        
                        <!-- Security Score -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-<?= $scoreColor ?>">
                                    <div class="card-body text-center">
                                        <h1 class="display-3 text-<?= $scoreColor ?> mb-0"><?= $score ?></h1>
                                        <p class="text-muted mb-0">Security Score</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="alert alert-danger mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['critical'] ?></h4>
                                            <small>Critical</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="alert alert-warning mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['high'] ?></h4>
                                            <small>High</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="alert alert-warning mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['medium'] ?></h4>
                                            <small>Medium</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="alert alert-info mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['low'] ?></h4>
                                            <small>Low</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="alert alert-light mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['info'] ?></h4>
                                            <small>Info</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="alert alert-secondary mb-0 text-center">
                                            <h4 class="mb-0"><?= $summary['total'] ?></h4>
                                            <small>Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Issues List -->
                        <div class="accordion" id="scanIssues">
                            <?php foreach ($issues as $index => $issue): 
                                $badgeClass = [
                                    'critical' => 'danger',
                                    'high' => 'warning',
                                    'medium' => 'warning',
                                    'low' => 'info',
                                    'info' => 'light'
                                ][$issue['severity']];
                                
                                $icon = [
                                    'critical' => 'times-circle',
                                    'high' => 'exclamation-triangle',
                                    'medium' => 'exclamation-circle',
                                    'low' => 'info-circle',
                                    'info' => 'check-circle'
                                ][$issue['severity']];
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#issue<?= $index ?>">
                                        <span class="badge bg-<?= $badgeClass ?> me-2">
                                            <i class="fas fa-<?= $icon ?> me-1"></i><?= ucfirst($issue['severity']) ?>
                                        </span>
                                        <strong class="me-2"><?= htmlspecialchars($issue['category']) ?>:</strong>
                                        <?= htmlspecialchars($issue['description']) ?>
                                    </button>
                                </h2>
                                <div id="issue<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#scanIssues">
                                    <div class="accordion-body">
                                        <div class="alert alert-light mb-0">
                                            <strong><i class="fas fa-lightbulb me-2"></i>Recommendation:</strong>
                                            <p class="mb-0"><?= htmlspecialchars($issue['recommendation']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt fa-4x text-muted mb-3 d-block"></i>
                            <h4>Security Scanner</h4>
                            <p class="text-muted mb-4">Run a comprehensive security audit to identify potential vulnerabilities</p>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="run_security_scan">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Start Security Scan
                                </button>
                            </form>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <strong><i class="fas fa-info-circle me-2"></i>What this scan checks:</strong>
                            <ul class="mb-0 mt-2">
                                <li>PHP version and configuration</li>
                                <li>File and directory permissions</li>
                                <li>Security headers implementation</li>
                                <li>Weak or default passwords</li>
                                <li>Database security configuration</li>
                                <li>Sensitive files exposure</li>
                                <li>Required PHP extensions</li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
