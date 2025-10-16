<?php
/**
 * ====================================
 * SYSTEM DIAGNOSTIC DASHBOARD
 * Gabungan dari: check_analytics.php, check_database.php, 
 * check_posts.php, check_sessions.php, check_table_structure.php
 * ====================================
 */

require_once '../config_modern.php';
require_once 'system/analytics_tracker.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$activeTab = $_GET['tab'] ?? 'analytics';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostic - DONAN22</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            border: none;
        }
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .status-ok { background: #d4edda; color: #155724; padding: 8px 16px; border-radius: 5px; display: inline-block; }
        .status-warning { background: #fff3cd; color: #856404; padding: 8px 16px; border-radius: 5px; display: inline-block; }
        .status-error { background: #f8d7da; color: #721c24; padding: 8px 16px; border-radius: 5px; display: inline-block; }
        .check-item {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 10px 0;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .btn-back {
            background: white;
            color: #667eea;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="mb-0"><i class="fas fa-stethoscope"></i> System Diagnostic Dashboard</h2>
                <p class="text-muted mb-0">Pemeriksaan kesehatan sistem secara menyeluruh</p>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'analytics' ? 'active' : '' ?>" href="?tab=analytics">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'database' ? 'active' : '' ?>" href="?tab=database">
                            <i class="fas fa-database"></i> Database
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'posts' ? 'active' : '' ?>" href="?tab=posts">
                            <i class="fas fa-file-alt"></i> Posts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'sessions' ? 'active' : '' ?>" href="?tab=sessions">
                            <i class="fas fa-users"></i> Sessions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab === 'structure' ? 'active' : '' ?>" href="?tab=structure">
                            <i class="fas fa-table"></i> Table Structure
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-4">
                    <?php if ($activeTab === 'analytics'): ?>
                        <!-- ANALYTICS CHECK -->
                        <h3 class="mb-4"><i class="fas fa-chart-line"></i> Analytics System Health Check</h3>
                        
                        <?php
                        $checks = [];
                        $overallStatus = 'ok';
                        
                        try {
                            // Check 1: Database Tables
                            echo "<div class='check-item'>";
                            echo "<h5><i class='fas fa-check-circle'></i> Check 1: Database Tables</h5>";
                            
                            $tables = ['page_views', 'downloads', 'sessions', 'daily_stats', 'posts'];
                            $missingTables = [];
                            
                            foreach ($tables as $table) {
                                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                                if ($stmt->rowCount() == 0) {
                                    $missingTables[] = $table;
                                }
                            }
                            
                            if (empty($missingTables)) {
                                echo "<span class='status-ok'>✓ OK</span> Semua tabel yang diperlukan ada<br>";
                                echo "<small class='text-muted'>Tables: " . implode(', ', $tables) . "</small>";
                            } else {
                                echo "<span class='status-error'>✗ ERROR</span> Tabel yang hilang: " . implode(', ', $missingTables);
                                $overallStatus = 'error';
                            }
                            echo "</div>";
                            
                            // Check 2: Posts Table Columns
                            echo "<div class='check-item'>";
                            echo "<h5><i class='fas fa-check-circle'></i> Check 2: Posts Table Columns</h5>";
                            
                            $requiredColumns = ['view_count', 'download_count'];
                            $missingColumns = [];
                            
                            foreach ($requiredColumns as $col) {
                                $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE '$col'");
                                if ($stmt->rowCount() == 0) {
                                    $missingColumns[] = $col;
                                }
                            }
                            
                            if (empty($missingColumns)) {
                                echo "<span class='status-ok'>✓ OK</span> Semua kolom yang diperlukan ada<br>";
                                echo "<small class='text-muted'>Columns: " . implode(', ', $requiredColumns) . "</small>";
                            } else {
                                echo "<span class='status-error'>✗ ERROR</span> Kolom yang hilang: " . implode(', ', $missingColumns);
                                $overallStatus = 'error';
                            }
                            echo "</div>";
                            
                            // Check 3: Analytics Tracker
                            echo "<div class='check-item'>";
                            echo "<h5><i class='fas fa-check-circle'></i> Check 3: Analytics Tracker</h5>";
                            
                            try {
                                $tracker = new AnalyticsTracker($pdo);
                                echo "<span class='status-ok'>✓ OK</span> Analytics Tracker berhasil diinisialisasi";
                            } catch (Exception $e) {
                                echo "<span class='status-error'>✗ ERROR</span> Gagal inisialisasi: " . htmlspecialchars($e->getMessage());
                                $overallStatus = 'error';
                            }
                            echo "</div>";
                            
                            // Check 4: Data Counts
                            echo "<div class='check-item'>";
                            echo "<h5><i class='fas fa-check-circle'></i> Check 4: Data Availability</h5>";
                            
                            echo "<div class='row'>";
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM page_views");
                            $pageViewsCount = $stmt->fetchColumn();
                            echo "<div class='col-md-3'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($pageViewsCount) . "</div>";
                            echo "<div class='stat-label'>Page Views</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM downloads");
                            $downloadsCount = $stmt->fetchColumn();
                            echo "<div class='col-md-3'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($downloadsCount) . "</div>";
                            echo "<div class='stat-label'>Downloads</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
                            $sessionsCount = $stmt->fetchColumn();
                            echo "<div class='col-md-3'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($sessionsCount) . "</div>";
                            echo "<div class='stat-label'>Sessions</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
                            $postsCount = $stmt->fetchColumn();
                            echo "<div class='col-md-3'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($postsCount) . "</div>";
                            echo "<div class='stat-label'>Posts</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            echo "</div>";
                            echo "</div>";
                            
                            // Check 5: Sync Issues
                            echo "<div class='check-item'>";
                            echo "<h5><i class='fas fa-check-circle'></i> Check 5: Data Synchronization</h5>";
                            
                            $stmt = $pdo->query("
                                SELECT COUNT(*) as sync_issues
                                FROM posts p
                                LEFT JOIN (
                                    SELECT post_id, COUNT(*) as counted_views
                                    FROM page_views
                                    GROUP BY post_id
                                ) pv ON p.id = pv.post_id
                                WHERE COALESCE(pv.counted_views, 0) != p.view_count
                            ");
                            $syncIssues = $stmt->fetchColumn();
                            
                            if ($syncIssues == 0) {
                                echo "<span class='status-ok'>✓ OK</span> Semua data tersinkronisasi dengan baik";
                            } else {
                                echo "<span class='status-warning'>⚠ WARNING</span> {$syncIssues} posts memiliki masalah sinkronisasi. ";
                                echo "Jalankan <a href='tools/maintenance_tools.php?tool=sync'>Sync Tool</a> untuk memperbaiki.";
                            }
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                    <?php elseif ($activeTab === 'database'): ?>
                        <!-- DATABASE CHECK -->
                        <h3 class="mb-4"><i class="fas fa-database"></i> Database Structure Check</h3>
                        
                        <?php
                        $required_tables = [
                            'administrators' => 'Tabel admin/pengelola sistem',
                            'users' => 'Tabel user/anggota',
                            'posts' => 'Tabel konten/artikel/download',
                            'categories' => 'Tabel kategori',
                            'comments' => 'Tabel komentar',
                            'page_views' => 'Tracking views halaman',
                            'downloads' => 'Tracking downloads',
                            'daily_stats' => 'Statistik harian',
                            'sessions' => 'Session tracking',
                            'email_reports' => 'Log email reports',
                            'analytics_settings' => 'Settings analytics',
                            'newsletter_subscribers' => 'Subscribers newsletter',
                            'security_logs' => 'Log aktivitas keamanan',
                            'blocked_ips' => 'IP yang diblokir',
                            'login_attempts' => 'Log percobaan login gagal',
                        ];
                        
                        try {
                            $stmt = $pdo->query("SHOW TABLES");
                            $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            echo "<div class='check-item'>";
                            echo "<h5>Status Tabel Database</h5>";
                            echo "<table>";
                            echo "<thead><tr><th>#</th><th>Nama Tabel</th><th>Deskripsi</th><th>Status</th></tr></thead>";
                            echo "<tbody>";
                            
                            $no = 1;
                            $existCount = 0;
                            $missingCount = 0;
                            
                            foreach ($required_tables as $table => $desc) {
                                $exists = in_array($table, $existing_tables);
                                $statusClass = $exists ? 'status-ok' : 'status-error';
                                $statusText = $exists ? '✓ Exists' : '✗ Missing';
                                
                                if ($exists) $existCount++;
                                else $missingCount++;
                                
                                echo "<tr>";
                                echo "<td>{$no}</td>";
                                echo "<td><strong>{$table}</strong></td>";
                                echo "<td>{$desc}</td>";
                                echo "<td><span class='{$statusClass}'>{$statusText}</span></td>";
                                echo "</tr>";
                                $no++;
                            }
                            
                            echo "</tbody>";
                            echo "</table>";
                            
                            echo "<div class='alert alert-info mt-3'>";
                            echo "<strong>Summary:</strong> {$existCount} tabel ada, {$missingCount} tabel hilang";
                            echo "</div>";
                            
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                    <?php elseif ($activeTab === 'posts'): ?>
                        <!-- POSTS CHECK -->
                        <h3 class="mb-4"><i class="fas fa-file-alt"></i> Posts Overview</h3>
                        
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT 
                                    p.id,
                                    p.title,
                                    p.slug,
                                    p.status,
                                    p.view_count,
                                    p.download_count,
                                    p.created_at,
                                    c.name as category_name,
                                    a.username as author,
                                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'approved') as comment_count
                                FROM posts p
                                LEFT JOIN categories c ON p.category_id = c.id
                                LEFT JOIN administrators a ON p.author_id = a.id
                                ORDER BY p.created_at DESC
                                LIMIT 50
                            ");
                            
                            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo "<div class='check-item'>";
                            echo "<p><strong>Total Posts: " . count($posts) . "</strong></p>";
                            
                            if (count($posts) > 0) {
                                echo "<div class='table-responsive'>";
                                echo "<table>";
                                echo "<thead>";
                                echo "<tr>";
                                echo "<th>ID</th><th>Title</th><th>Category</th><th>Author</th>";
                                echo "<th>Status</th><th>Views</th><th>Downloads</th><th>Comments</th>";
                                echo "<th>Created</th><th>Actions</th>";
                                echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                
                                foreach ($posts as $post) {
                                    $statusColor = $post['status'] == 'published' ? 'text-success' : 
                                                  ($post['status'] == 'draft' ? 'text-warning' : 'text-secondary');
                                    
                                    echo "<tr>";
                                    echo "<td>{$post['id']}</td>";
                                    echo "<td><strong>" . htmlspecialchars($post['title']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($post['category_name'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($post['author'] ?? '-') . "</td>";
                                    echo "<td class='{$statusColor}'>" . strtoupper($post['status']) . "</td>";
                                    echo "<td class='text-center'>{$post['view_count']}</td>";
                                    echo "<td class='text-center'>{$post['download_count']}</td>";
                                    echo "<td class='text-center'>{$post['comment_count']}</td>";
                                    echo "<td>" . date('d M Y', strtotime($post['created_at'])) . "</td>";
                                    echo "<td>";
                                    echo "<a href='" . SITE_URL . "/post/{$post['slug']}' target='_blank' class='btn btn-sm btn-primary'>View</a> ";
                                    echo "<a href='post-editor.php?id={$post['id']}' class='btn btn-sm btn-warning'>Edit</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                
                                echo "</tbody>";
                                echo "</table>";
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-warning'>Tidak ada posts yang ditemukan.</div>";
                            }
                            
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                    <?php elseif ($activeTab === 'sessions'): ?>
                        <!-- SESSIONS CHECK -->
                        <h3 class="mb-4"><i class="fas fa-users"></i> Session Data Check</h3>
                        
                        <?php
                        try {
                            echo "<div class='check-item'>";
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
                            $totalSessions = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM page_views WHERE session_id IS NOT NULL");
                            $viewsWithSession = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM page_views WHERE session_id IS NULL");
                            $viewsWithoutSession = $stmt->fetchColumn();
                            
                            echo "<div class='row'>";
                            echo "<div class='col-md-4'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($totalSessions) . "</div>";
                            echo "<div class='stat-label'>Total Sessions</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            echo "<div class='col-md-4'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($viewsWithSession) . "</div>";
                            echo "<div class='stat-label'>Views with Session ID</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            echo "<div class='col-md-4'>";
                            echo "<div class='stat-card'>";
                            echo "<div class='stat-value'>" . number_format($viewsWithoutSession) . "</div>";
                            echo "<div class='stat-label'>Views without Session ID</div>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                            
                            echo "<h5 class='mt-4'>Sample Page Views (10 terbaru)</h5>";
                            $stmt = $pdo->query("
                                SELECT id, post_id, ip_address, session_id, traffic_source, country, view_date 
                                FROM page_views 
                                ORDER BY view_date DESC
                                LIMIT 10
                            ");
                            
                            echo "<div class='table-responsive'>";
                            echo "<table>";
                            echo "<thead><tr><th>ID</th><th>Post ID</th><th>IP</th><th>Session ID</th><th>Traffic Source</th><th>Country</th><th>Date</th></tr></thead>";
                            echo "<tbody>";
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['post_id']}</td>";
                                echo "<td>{$row['ip_address']}</td>";
                                echo "<td>" . ($row['session_id'] ? substr($row['session_id'], 0, 10) . '...' : '<em class="text-muted">NULL</em>') . "</td>";
                                echo "<td>" . ($row['traffic_source'] ?? '<em class="text-muted">NULL</em>') . "</td>";
                                echo "<td>" . ($row['country'] ?? '<em class="text-muted">NULL</em>') . "</td>";
                                echo "<td>" . date('d M Y H:i', strtotime($row['view_date'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "</tbody>";
                            echo "</table>";
                            echo "</div>";
                            
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                    <?php elseif ($activeTab === 'structure'): ?>
                        <!-- TABLE STRUCTURE CHECK -->
                        <h3 class="mb-4"><i class="fas fa-table"></i> Table Structure Details</h3>
                        
                        <?php
                        try {
                            $tables_to_check = ['page_views', 'sessions', 'downloads', 'posts'];
                            
                            foreach ($tables_to_check as $table) {
                                echo "<div class='check-item'>";
                                echo "<h5>{$table} Table Structure</h5>";
                                
                                $stmt = $pdo->query("DESCRIBE {$table}");
                                
                                echo "<div class='table-responsive'>";
                                echo "<table>";
                                echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
                                echo "<tbody>";
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td><strong>{$row['Field']}</strong></td>";
                                    echo "<td>{$row['Type']}</td>";
                                    echo "<td>{$row['Null']}</td>";
                                    echo "<td>{$row['Key']}</td>";
                                    echo "<td>" . ($row['Default'] ?? '<em class="text-muted">NULL</em>') . "</td>";
                                    echo "<td>" . ($row['Extra'] ?? '-') . "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody>";
                                echo "</table>";
                                echo "</div>";
                                
                                echo "</div>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
