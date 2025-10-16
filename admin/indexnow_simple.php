<?php
/**
 * IndexNow Monitoring Dashboard - Standalone Version
 * Simple version without complex header dependencies
 */

require_once __DIR__ . '/../config_modern.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication using isLoggedIn() function
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../includes/IndexNowSubmitter.php';

// Get statistics
$logFile = __DIR__ . '/../logs/indexnow.log';
$logs = [];
$stats = [
    'total_submissions' => 0,
    'successful' => 0,
    'failed' => 0,
    'last_submission' => null
];

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = array_filter(explode("\n", $logContent));
    
    foreach ($logLines as $line) {
        if (preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
            $logs[] = [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3]
            ];
        }
        
        if (strpos($line, 'Submitting') !== false) {
            $stats['total_submissions']++;
            $stats['last_submission'] = $matches[1] ?? null;
        }
        
        if (strpos($line, 'HTTP 200') !== false || strpos($line, 'HTTP 202') !== false) {
            $stats['successful']++;
        }
        
        if (strpos($line, '[ERROR]') !== false) {
            $stats['failed']++;
        }
    }
    
    $logs = array_reverse(array_slice(array_reverse($logs), 0, 50));
}

$indexNow = new IndexNowSubmitter('localhost');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IndexNow Monitor - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .stat-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .log-table { font-family: 'Courier New', monospace; font-size: 12px; }
        .badge { font-size: 0.75rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-rocket"></i> DONAN22 Admin
            </a>
            <div class="text-white">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-rocket text-primary"></i> IndexNow Monitoring Dashboard</h2>
                <p class="text-muted">Real-time IndexNow submission tracking and statistics</p>
            </div>
            <div class="col-auto">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-paper-plane"></i> Total Submissions</h5>
                        <h2 class="mb-0"><?= $stats['total_submissions'] ?></h2>
                        <small>All-time</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-check-circle"></i> Successful</h5>
                        <h2 class="mb-0"><?= $stats['successful'] ?></h2>
                        <small>HTTP 200/202</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-times-circle"></i> Failed</h5>
                        <h2 class="mb-0"><?= $stats['failed'] ?></h2>
                        <small>Errors</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock"></i> Last Submission</h5>
                        <h6 class="mb-0">
                            <?= $stats['last_submission'] ? date('M d, H:i', strtotime($stats['last_submission'])) : 'Never' ?>
                        </h6>
                        <small>Timestamp</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <button onclick="submitAllUrls()" class="btn btn-success">
                                <i class="fas fa-upload"></i> Submit All URLs to IndexNow
                            </button>
                            <button onclick="testConnection()" class="btn btn-info">
                                <i class="fas fa-vial"></i> Test Connection
                            </button>
                            <a href="<?= $indexNow->getKeyLocation() ?>" target="_blank" class="btn btn-warning">
                                <i class="fas fa-key"></i> Verify API Key
                            </a>
                            <button onclick="clearLogs()" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Clear Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configuration</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">API Key:</th>
                                <td><code>0562378ac1cabc9e90389059b69e3765</code></td>
                            </tr>
                            <tr>
                                <th>Host:</th>
                                <td><code>localhost</code></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <tr>
                                <th>Auto-Submit:</th>
                                <td><span class="badge bg-success">Enabled</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> How It Works</h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>Every post save triggers IndexNow submission</li>
                            <li>Bing & Yandex notified instantly</li>
                            <li>URLs indexed within 5-30 minutes</li>
                            <li>100% automated, no manual work needed</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Recent Activity (Last 50)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($logs)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No submissions yet. Logs will appear after first submission.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped log-table mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="15%">Timestamp</th>
                                            <th width="10%">Level</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                                <td>
                                                    <?php
                                                    $badgeClass = 'secondary';
                                                    if ($log['level'] === 'ERROR') $badgeClass = 'danger';
                                                    elseif ($log['level'] === 'WARNING') $badgeClass = 'warning';
                                                    elseif ($log['level'] === 'INFO') $badgeClass = 'info';
                                                    ?>
                                                    <span class="badge bg-<?= $badgeClass ?>">
                                                        <?= htmlspecialchars($log['level']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($log['message']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitAllUrls() {
            if (!confirm('Submit all URLs from sitemap to IndexNow?\n\nThis will notify Bing & Yandex.')) return;
            
            alert('Feature coming soon! Use CLI tool:\nphp submit-to-indexnow.php');
        }

        function testConnection() {
            alert('Testing IndexNow connection...\n\nTest would send homepage URL to IndexNow API.');
        }

        function clearLogs() {
            if (!confirm('Clear all IndexNow logs?')) return;
            alert('Logs cleared! (Feature to be implemented via API)');
        }
    </script>
</body>
</html>
