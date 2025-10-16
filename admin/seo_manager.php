<?php
/**
 * DONAN22.COM - SEO & Sitemap Manager
 * ====================================
 * Admin page for managing sitemap and SEO settings
 */

require_once __DIR__ . '/../config_modern.php';
require_once __DIR__ . '/../includes/sitemap_hooks.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication using isLoggedIn() function
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'regenerate_sitemap':
            $result = regenerateSitemap();
            $message = $result ? 'Sitemap berhasil di-generate!' : 'Gagal generate sitemap!';
            $messageType = $result ? 'success' : 'error';
            break;
            
        case 'ping_google':
            $sitemapUrl = SITE_URL . '/sitemap.xml';
            $pingUrl = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
            
            $ch = curl_init($pingUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $message = $httpCode === 200 ? 'Google berhasil di-ping!' : 'Gagal ping Google (HTTP ' . $httpCode . ')';
            $messageType = $httpCode === 200 ? 'success' : 'error';
            break;
            
        case 'ping_bing':
            $sitemapUrl = SITE_URL . '/sitemap.xml';
            $pingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
            
            $ch = curl_init($pingUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $message = $httpCode === 200 ? 'Bing berhasil di-ping!' : 'Gagal ping Bing (HTTP ' . $httpCode . ')';
            $messageType = $httpCode === 200 ? 'success' : 'error';
            break;
    }
}

// Get sitemap info
$sitemapPath = __DIR__ . '/../sitemap.xml';
$sitemapExists = file_exists($sitemapPath);
$sitemapSize = $sitemapExists ? filesize($sitemapPath) : 0;
$sitemapModified = $sitemapExists ? filemtime($sitemapPath) : 0;

// Count URLs in sitemap
$urlCount = 0;
if ($sitemapExists) {
    $content = file_get_contents($sitemapPath);
    preg_match_all('/<url>/', $content, $matches);
    $urlCount = count($matches[0]);
}

// Get sitemap generation log
$logPath = __DIR__ . '/../seo/sitemap_generation.log';
$recentLogs = [];
if (file_exists($logPath)) {
    $logs = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentLogs = array_slice(array_reverse($logs), 0, 10);
}

// Get stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $totalPosts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $totalCategories = $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalPosts = 0;
    $totalCategories = 0;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO & Sitemap Manager - DONAN22 Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold; }
        .stat-card { text-align: center; padding: 20px; }
        .stat-value { font-size: 2.5rem; font-weight: bold; color: #667eea; }
        .stat-label { color: #6c757d; font-size: 0.9rem; }
        .btn-action { margin: 5px; }
        .log-entry { font-family: monospace; font-size: 0.85rem; padding: 5px; background: #f8f9fa; border-left: 3px solid #667eea; margin-bottom: 5px; }
        .alert-custom { border-left: 4px solid; }
        .sitemap-url { font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="bi bi-speedometer2"></i> DONAN22 Admin</a>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4"><i class="bi bi-diagram-3"></i> SEO & Sitemap Manager</h1>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <div class="stat-value"><?= number_format($urlCount) ?></div>
                        <div class="stat-label"><i class="bi bi-link-45deg"></i> URLs in Sitemap</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <div class="stat-value"><?= number_format($totalPosts) ?></div>
                        <div class="stat-label"><i class="bi bi-file-text"></i> Published Posts</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <div class="stat-value"><?= number_format($totalCategories) ?></div>
                        <div class="stat-label"><i class="bi bi-folder"></i> Categories</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body stat-card">
                        <div class="stat-value"><?= number_format($sitemapSize / 1024, 1) ?> KB</div>
                        <div class="stat-label"><i class="bi bi-file-earmark-code"></i> Sitemap Size</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sitemap Info -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Sitemap Information
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th width="150">Status:</th>
                                <td>
                                    <?php if ($sitemapExists): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Not Generated</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td>
                                    <?php if ($sitemapModified): ?>
                                        <?= date('Y-m-d H:i:s', $sitemapModified) ?>
                                        <small class="text-muted">(<?= human_time_diff($sitemapModified) ?> ago)</small>
                                    <?php else: ?>
                                        Never
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>File Size:</th>
                                <td><?= number_format($sitemapSize / 1024, 2) ?> KB</td>
                            </tr>
                            <tr>
                                <th>Total URLs:</th>
                                <td><?= number_format($urlCount) ?></td>
                            </tr>
                            <tr>
                                <th>Public URL:</th>
                                <td>
                                    <div class="sitemap-url">
                                        <a href="<?= SITE_URL ?>/sitemap.xml" target="_blank">
                                            <?= SITE_URL ?>/sitemap.xml
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-lightning"></i> Actions
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <button type="submit" name="action" value="regenerate_sitemap" class="btn btn-primary btn-action">
                                <i class="bi bi-arrow-clockwise"></i> Regenerate Sitemap
                            </button>
                            <button type="submit" name="action" value="ping_google" class="btn btn-success btn-action">
                                <i class="bi bi-google"></i> Ping Google
                            </button>
                            <button type="submit" name="action" value="ping_bing" class="btn btn-info btn-action text-white">
                                <i class="bi bi-bing"></i> Ping Bing
                            </button>
                        </form>
                        <hr>
                        <a href="<?= SITE_URL ?>/sitemap.xml" target="_blank" class="btn btn-outline-secondary btn-action">
                            <i class="bi bi-eye"></i> View Sitemap
                        </a>
                        <a href="<?= SITE_URL ?>/seo/sitemap_dynamic.php?debug=1" target="_blank" class="btn btn-outline-warning btn-action">
                            <i class="bi bi-bug"></i> Debug Mode
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history"></i> Recent Generation Log
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentLogs)): ?>
                            <p class="text-muted">No generation logs yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="log-entry"><?= htmlspecialchars($log) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SEO Tips -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-lightbulb"></i> SEO Tips
                    </div>
                    <div class="card-body">
                        <ul class="small">
                            <li>Regenerate sitemap setiap kali ada perubahan konten besar</li>
                            <li>Ping Google & Bing setelah update sitemap</li>
                            <li>Submit sitemap di <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                            <li>Submit sitemap di <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster Tools</a></li>
                            <li>Maksimal 50,000 URLs per sitemap</li>
                            <li>Sitemap akan auto-update saat ada post baru</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit to Search Engines -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-search"></i> Submit to Search Engines
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Google Search Console</h5>
                        <ol class="small">
                            <li>Kunjungi <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                            <li>Pilih property website Anda</li>
                            <li>Pergi ke Sitemaps</li>
                            <li>Tambahkan URL: <code>https://donan22.com/sitemap.xml</code></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h5>Bing Webmaster Tools</h5>
                        <ol class="small">
                            <li>Kunjungi <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster Tools</a></li>
                            <li>Pilih website Anda</li>
                            <li>Pergi ke Sitemaps</li>
                            <li>Submit URL: <code>https://donan22.com/sitemap.xml</code></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function human_time_diff($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return $diff . ' seconds';
    if ($diff < 3600) return floor($diff / 60) . ' minutes';
    if ($diff < 86400) return floor($diff / 3600) . ' hours';
    if ($diff < 604800) return floor($diff / 86400) . ' days';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks';
    
    return floor($diff / 2592000) . ' months';
}
?>
