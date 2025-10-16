<?php
/**
 * DONAN22 - Export Posts
 * Export semua post yang sudah dipublish beserta URL-nya
 */

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
requireLogin();

// Check permissions
requirePermission('manage_posts');

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Handle export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $format = $_GET['format'] ?? 'csv';
    
    // Get all posts with their monetized links
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.title,
            p.slug,
            c.name as category_name,
            p.status,
            p.created_at,
            p.updated_at,
            COALESCE(p.view_count, 0) as views,
            COALESCE(p.download_count, 0) as downloads
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL
        ORDER BY COALESCE(p.updated_at, p.created_at) DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all monetized links
    $linkStmt = $pdo->prepare("
        SELECT 
            post_id,
            download_title,
            original_url,
            short_code,
            file_size,
            file_password,
            version,
            monetized_url,
            total_clicks,
            total_downloads
        FROM monetized_links
        WHERE monetized_url IS NOT NULL AND monetized_url != ''
        ORDER BY post_id, id
    ");
    $linkStmt->execute();
    $allLinks = $linkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group links by post_id
    $linksByPost = [];
    foreach ($allLinks as $link) {
        $linksByPost[$link['post_id']][] = $link;
    }
    
    if ($format === 'csv') {
        // Export as simple CSV: ID, Post Title, Category, Status, Download Title, Download URL, post url
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="donan22_posts_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        // Header dengan Short URL
        fputcsv($output, ['ID', 'Post Title', 'Category', 'Status', 'Download Title', 'Download URL', 'Short URL', 'Short Code', 'File Size', 'Password', 'Clicks', 'Downloads', 'Post URL']);
        foreach ($posts as $post) {
            $postUrl = SITE_URL . '/post/' . $post['slug'];
            $postLinks = $linksByPost[$post['id']] ?? [];
            if (empty($postLinks)) {
                // Post tanpa download link
                fputcsv($output, [
                    $post['id'],
                    $post['title'],
                    $post['category_name'],
                    strtoupper($post['status']),
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    0,
                    0,
                    $postUrl
                ]);
            } else {
                foreach ($postLinks as $link) {
                    fputcsv($output, [
                        $post['id'],
                        $post['title'],
                        $post['category_name'],
                        strtoupper($post['status']),
                        $link['download_title'] ?? '-',
                        $link['original_url'] ?? '-',
                        $link['monetized_url'] ?? '-',
                        $link['short_code'] ?? '-',
                        $link['file_size'] ?? '-',
                        $link['file_password'] ?? '-',
                        $link['total_clicks'] ?? 0,
                        $link['total_downloads'] ?? 0,
                        $postUrl
                    ]);
                }
            }
        }
        fclose($output);
        exit;
        
    } elseif ($format === 'json') {
        // Export as JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="donan22_posts_downloads_' . date('Y-m-d') . '.json"');
        
        $exportData = [];
        foreach ($posts as $post) {
            $postLinks = $linksByPost[$post['id']] ?? [];
            
            $downloadLinks = [];
            foreach ($postLinks as $link) {
                $downloadLinks[] = [
                    'title' => $link['download_title'],
                    'download_url' => $link['original_url'],
                    'short_url' => SITE_URL . '/go/' . $link['short_code'],
                    'short_code' => $link['short_code'],
                    'file_size' => $link['file_size'],
                    'version' => $link['version'] ?? null,
                    'password' => $link['file_password'],
                    'clicks' => (int)$link['total_clicks'],
                    'downloads' => (int)$link['total_downloads']
                ];
            }
            
            $exportData[] = [
                'id' => $post['id'],
                'title' => $post['title'],
                'url' => SITE_URL . '/post/' . $post['slug'],
                'slug' => $post['slug'],
                'category' => $post['category_name'],
                'status' => $post['status'],
                'views' => (int)$post['views'],
                'total_downloads' => (int)$post['downloads'],
                'download_links' => $downloadLinks,
                'download_links_count' => count($downloadLinks),
                'created_at' => $post['created_at'],
                'updated_at' => $post['updated_at'] ?? null
            ];
        }
        
        echo json_encode([
            'title' => 'DATA SUDAH POSTING DONAN22',
            'exported_at' => date('Y-m-d H:i:s'),
            'total_posts' => count($exportData),
            'site_url' => SITE_URL,
            'posts' => $exportData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
        
    } elseif ($format === 'excel') {
        // Export as Excel XLS - Simple format
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="donan22_posts_' . date('Y-m-d') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Start Excel XML
        echo '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
        <Title>DONAN22 Posts Export</Title>
        <Author>DONAN22</Author>
        <Created>' . date('Y-m-d\TH:i:s') . '</Created>
    </DocumentProperties>
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Font ss:FontName="Calibri" ss:Size="11"/>
        </Style>
        <Style ss:ID="Title">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="14"/>
            <Interior ss:Color="#4CAF50" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
            </Borders>
        </Style>
        <Style ss:ID="Header">
            <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/>
            <Interior ss:Color="#667EEA" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="Data">
            <Alignment ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E0E0E0"/>
            </Borders>
        </Style>
    </Styles>
    <Worksheet ss:Name="Posts">
        <Table>
            <Column ss:AutoFitWidth="1" ss:Width="50"/>
            <Column ss:AutoFitWidth="1" ss:Width="250"/>
            <Column ss:AutoFitWidth="1" ss:Width="120"/>
            <Column ss:AutoFitWidth="1" ss:Width="80"/>
            <Column ss:AutoFitWidth="1" ss:Width="200"/>
            <Column ss:AutoFitWidth="1" ss:Width="350"/>
            <Column ss:AutoFitWidth="1" ss:Width="300"/>
            
            <Row ss:Height="35">
                <Cell ss:MergeAcross="6" ss:StyleID="Title"><Data ss:Type="String">DATA SUDAH POSTING DONAN22</Data></Cell>
            </Row>
            <Row ss:Height="5">
                <Cell><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row ss:Height="30">
                <Cell ss:StyleID="Header"><Data ss:Type="String">ID</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Post Title</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Category</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Status</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Download Title</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Download URL</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">post url</Data></Cell>
            </Row>';
        
        foreach ($posts as $post) {
            $postUrl = SITE_URL . '/post/' . $post['slug'];
            $postLinks = $linksByPost[$post['id']] ?? [];
            
            if (empty($postLinks)) {
                // Post tanpa download link
                echo '<Row>
                    <Cell ss:StyleID="Data"><Data ss:Type="Number">' . $post['id'] . '</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($post['title']) . '</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($post['category_name']) . '</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">' . strtoupper($post['status']) . '</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">-</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">-</Data></Cell>
                    <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($postUrl) . '</Data></Cell>
                </Row>';
            } else {
                foreach ($postLinks as $link) {
                    echo '<Row>
                        <Cell ss:StyleID="Data"><Data ss:Type="Number">' . $post['id'] . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($post['title']) . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($post['category_name']) . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . strtoupper($post['status']) . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($link['download_title']) . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($link['original_url']) . '</Data></Cell>
                        <Cell ss:StyleID="Data"><Data ss:Type="String">' . htmlspecialchars($postUrl) . '</Data></Cell>
                    </Row>';
                }
            }
        }
        
        echo '</Table>
    </Worksheet>
</Workbook>';
        exit;
        
    } elseif ($format === 'html') {
        // Export as HTML
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="donan22_posts_downloads_' . date('Y-m-d') . '.html"');
        
        // Count total links
        $totalLinks = count($allLinks);
        
        echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DONAN22 Posts & Downloads Export - ' . date('Y-m-d') . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; font-size: 13px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 13px; }
        tr:hover { background: #f5f5f5; }
        a { color: #667eea; text-decoration: none; word-break: break-all; }
        a:hover { text-decoration: underline; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .badge-published { background: #4caf50; color: white; }
        .badge-draft { background: #ff9800; color: white; }
        .post-header { background: #f8f9fa; font-weight: bold; }
        .download-row { background: #fafafa; }
        .password { color: #e53935; font-weight: bold; }
        .version { color: #1976d2; font-weight: bold; }
    </style>
</head>
<body>
    <h1>📊 DONAN22 Posts & Downloads Export</h1>
    <div class="info">
        <strong>Exported:</strong> ' . date('Y-m-d H:i:s') . '<br>
        <strong>Total Posts:</strong> ' . count($posts) . '<br>
        <strong>Total Download Links:</strong> ' . $totalLinks . '<br>
        <strong>Site URL:</strong> ' . SITE_URL . '
    </div>
    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="15%">Post Title</th>
                <th width="12%">Category</th>
                <th width="15%">Download Title</th>
                <th width="20%">Download URL</th>
                <th width="8%">Size</th>
                <th width="8%">Version</th>
                <th width="10%">Password</th>
                <th width="7%">Stats</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($posts as $post) {
            $postUrl = SITE_URL . '/post/' . $post['slug'];
            $statusClass = $post['status'] === 'published' ? 'badge-published' : 'badge-draft';
            $postLinks = $linksByPost[$post['id']] ?? [];
            
            if (empty($postLinks)) {
                // Post without download links
                echo '<tr class="post-header">
                    <td>' . $post['id'] . '</td>
                    <td><a href="' . htmlspecialchars($postUrl) . '" target="_blank">' . htmlspecialchars($post['title']) . '</a></td>
                    <td>' . htmlspecialchars($post['category_name']) . '</td>
                    <td colspan="6"><em>No download links</em></td>
                </tr>';
            } else {
                $isFirst = true;
                foreach ($postLinks as $link) {
                    if ($isFirst) {
                        // First row shows post info
                        echo '<tr class="post-header">
                            <td rowspan="' . count($postLinks) . '">' . $post['id'] . '</td>
                            <td rowspan="' . count($postLinks) . '"><a href="' . htmlspecialchars($postUrl) . '" target="_blank">' . htmlspecialchars($post['title']) . '</a><br><span class="badge ' . $statusClass . '">' . ucfirst($post['status']) . '</span></td>
                            <td rowspan="' . count($postLinks) . '">' . htmlspecialchars($post['category_name']) . '</td>
                            <td>' . htmlspecialchars($link['download_title']) . '</td>
                            <td><a href="' . htmlspecialchars($link['original_url']) . '" target="_blank" style="font-size:11px;">' . htmlspecialchars(substr($link['original_url'], 0, 50)) . '...</a></td>
                            <td>' . htmlspecialchars($link['file_size']) . '</td>
                            <td><span class="version">' . htmlspecialchars($link['version'] ?? '-') . '</span></td>
                            <td><span class="password">' . htmlspecialchars($link['file_password']) . '</span></td>
                            <td>👁 ' . number_format($link['total_clicks']) . '<br>📥 ' . number_format($link['total_downloads']) . '</td>
                        </tr>';
                        $isFirst = false;
                    } else {
                        // Subsequent rows only show download info
                        echo '<tr class="download-row">
                            <td>' . htmlspecialchars($link['download_title']) . '</td>
                            <td><a href="' . htmlspecialchars($link['original_url']) . '" target="_blank" style="font-size:11px;">' . htmlspecialchars(substr($link['original_url'], 0, 50)) . '...</a></td>
                            <td>' . htmlspecialchars($link['file_size']) . '</td>
                            <td><span class="version">' . htmlspecialchars($link['version'] ?? '-') . '</span></td>
                            <td><span class="password">' . htmlspecialchars($link['file_password']) . '</span></td>
                            <td>👁 ' . number_format($link['total_clicks']) . '<br>📥 ' . number_format($link['total_downloads']) . '</td>
                        </tr>';
                    }
                }
            }
        }
        
        echo '</tbody>
    </table>
</body>
</html>';
        exit;
    }
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE deleted_at IS NULL");
$totalPosts = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'published' AND deleted_at IS NULL");
$publishedPosts = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE status = 'draft' AND deleted_at IS NULL");
$draftPosts = $stmt->fetch()['total'];

$currentPage = 'posts';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Posts - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .export-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .export-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .export-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('posts'); ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-export me-2"></i>Export Posts
                    </h1>
                    <a href="posts.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Posts
                    </a>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h3 class="mb-0"><?= number_format($totalPosts) ?></h3>
                            <p class="mb-0">Total Posts</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);">
                            <h3 class="mb-0"><?= number_format($publishedPosts) ?></h3>
                            <p class="mb-0">Published</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                            <h3 class="mb-0"><?= number_format($draftPosts) ?></h3>
                            <p class="mb-0">Drafts</p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Export akan mencakup:</strong> 
                    <ul class="mb-0 mt-2">
                        <li><strong>Post Data:</strong> ID, Title, Post URL, Category, Status, Views, Created/Updated</li>
                        <li><strong>Download Links:</strong> Title, Download URL, Short URL, File Size, Version, Password, Clicks, Downloads</li>
                    </ul>
                </div>

                <!-- Export Options -->
                <div class="row g-4">
                    <!-- Excel Export (NEW - WITH MERGED CELLS) -->
                    <div class="col-md-3">
                        <a href="?action=export&format=excel" class="text-decoration-none">
                            <div class="card export-card text-center p-4" style="border: 3px solid #4caf50;">
                                <div class="export-icon text-success">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <h4>Excel (XLS)</h4>
                                <span class="badge bg-success mb-2">RECOMMENDED ⭐</span>
                                <p class="text-muted small">With merged cells</p>
                                <ul class="list-unstyled text-start small">
                                    <li><i class="fas fa-check text-success me-2"></i>Merged cells untuk post yang sama</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Styling & warna</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Enak dilihat!</li>
                                </ul>
                                <button class="btn btn-success mt-2">
                                    <i class="fas fa-download me-2"></i>Download Excel
                                </button>
                            </div>
                        </a>
                    </div>
                    
                    <!-- CSV Export -->
                    <div class="col-md-3">
                        <a href="?action=export&format=csv" class="text-decoration-none">
                            <div class="card export-card text-center p-4">
                                <div class="export-icon text-info">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <h4>CSV</h4>
                                <p class="text-muted small">Simple format</p>
                                <ul class="list-unstyled text-start small">
                                    <li><i class="fas fa-check text-success me-2"></i>Bisa dibuka di Excel</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Easy to analyze</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Lightweight file</li>
                                </ul>
                                <button class="btn btn-info mt-2">
                                    <i class="fas fa-download me-2"></i>Download CSV
                                </button>
                            </div>
                        </a>
                    </div>

                    <!-- JSON Export -->
                    <div class="col-md-3">
                        <a href="?action=export&format=json" class="text-decoration-none">
                            <div class="card export-card text-center p-4">
                                <div class="export-icon text-primary">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <h4>JSON</h4>
                                <p class="text-muted small">Developer format</p>
                                <ul class="list-unstyled text-start small">
                                    <li><i class="fas fa-check text-success me-2"></i>API integration ready</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Structured data</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Easy to parse</li>
                                </ul>
                                <button class="btn btn-primary mt-2">
                                    <i class="fas fa-download me-2"></i>Download JSON
                                </button>
                            </div>
                        </a>
                    </div>

                    <!-- HTML Export -->
                    <div class="col-md-3">
                        <a href="?action=export&format=html" class="text-decoration-none">
                            <div class="card export-card text-center p-4">
                                <div class="export-icon text-warning">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h4>HTML</h4>
                                <p class="text-muted small">Printable format</p>
                                <ul class="list-unstyled text-start small">
                                    <li><i class="fas fa-check text-success me-2"></i>Bisa dibuka di browser</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Ready to print</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Styled table view</li>
                                </ul>
                                <button class="btn btn-warning mt-2">
                                    <i class="fas fa-download me-2"></i>Download HTML
                                </button>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sample Preview -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Sample Preview (Latest 5 Posts with Download Links)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Post Title</th>
                                        <th>Category</th>
                                        <th>Download Title</th>
                                        <th>Download URL</th>
                                        <th>Size</th>
                                        <th>Version</th>
                                        <th>Password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT p.id, p.title, p.slug, c.name as category_name, p.status
                                        FROM posts p
                                        LEFT JOIN categories c ON p.category_id = c.id
                                        WHERE p.deleted_at IS NULL
                                        ORDER BY COALESCE(p.updated_at, p.created_at) DESC
                                        LIMIT 5
                                    ");
                                    $stmt->execute();
                                    $samplePosts = $stmt->fetchAll();
                                    
                                    foreach ($samplePosts as $post):
                                        // Get download links for this post
                                        $linkStmt = $pdo->prepare("
                                            SELECT download_title, original_url, file_size, version, file_password
                                            FROM monetized_links
                                            WHERE post_id = ?
                                            ORDER BY id
                                        ");
                                        $linkStmt->execute([$post['id']]);
                                        $postLinks = $linkStmt->fetchAll();
                                        
                                        $url = SITE_URL . '/post/' . $post['slug'];
                                        
                                        if (empty($postLinks)):
                                    ?>
                                    <tr>
                                        <td><?= $post['id'] ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($post['category_name']) ?></td>
                                        <td colspan="5" class="text-muted"><em>No download links</em></td>
                                    </tr>
                                    <?php
                                        else:
                                            $isFirst = true;
                                            foreach ($postLinks as $link):
                                                if ($isFirst):
                                    ?>
                                    <tr style="background: #f8f9fa;">
                                        <td rowspan="<?= count($postLinks) ?>"><?= $post['id'] ?></td>
                                        <td rowspan="<?= count($postLinks) ?>">
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                        </td>
                                        <td rowspan="<?= count($postLinks) ?>"><?= htmlspecialchars($post['category_name']) ?></td>
                                        <td><?= htmlspecialchars($link['download_title']) ?></td>
                                        <td style="font-size: 11px;">
                                            <a href="<?= htmlspecialchars($link['original_url']) ?>" target="_blank">
                                                <?= htmlspecialchars(substr($link['original_url'], 0, 40)) ?>...
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($link['file_size']) ?></td>
                                        <td><span class="text-primary fw-bold"><?= htmlspecialchars($link['version'] ?? '-') ?></span></td>
                                        <td><span class="text-danger fw-bold"><?= htmlspecialchars($link['file_password']) ?></span></td>
                                    </tr>
                                    <?php
                                                    $isFirst = false;
                                                else:
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($link['download_title']) ?></td>
                                        <td style="font-size: 11px;">
                                            <a href="<?= htmlspecialchars($link['original_url']) ?>" target="_blank">
                                                <?= htmlspecialchars(substr($link['original_url'], 0, 40)) ?>...
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($link['file_size']) ?></td>
                                        <td><span class="text-primary fw-bold"><?= htmlspecialchars($link['version'] ?? '-') ?></span></td>
                                        <td><span class="text-danger fw-bold"><?= htmlspecialchars($link['file_password']) ?></span></td>
                                    </tr>
                                    <?php
                                                endif;
                                            endforeach;
                                        endif;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
