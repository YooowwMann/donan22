<?php
/**
 * ====================================
 * MAINTENANCE TOOLS DASHBOARD
 * Gabungan dari: fix_all_counts.php, migrate_view_counts.php,
 * fix_session_data.php, setup_roles.php, import_tutorial.php
 * ====================================
 */

require_once __DIR__ . '/../../config_modern.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$tool = $_GET['tool'] ?? null;
$action = $_GET['action'] ?? null;
$messages = [];
$executionResult = null;

// ====================================
// TOOL EXECUTION
// ====================================

if ($tool && $action === 'execute') {
    ob_start();
    
    switch ($tool) {
        case 'sync':
            // Fix All Counts Tool
            try {
                echo "<div class='result-section'>";
                echo "<h4>🔄 Synchronizing View & Download Counts...</h4>";
                
                // Ensure columns exist
                $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'view_count'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE posts ADD COLUMN view_count INT(11) NOT NULL DEFAULT 0");
                    echo "<p class='text-warning'>✓ Added view_count column</p>";
                }
                
                $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'download_count'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE posts ADD COLUMN download_count INT(11) NOT NULL DEFAULT 0");
                    echo "<p class='text-warning'>✓ Added download_count column</p>";
                }
                
                // Sync view counts
                echo "<h5>Syncing View Counts...</h5>";
                $stmt = $pdo->query("
                    SELECT post_id, COUNT(*) as counted_views
                    FROM page_views
                    GROUP BY post_id
                ");
                
                $updated = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $updateStmt = $pdo->prepare("UPDATE posts SET view_count = ? WHERE id = ?");
                    $updateStmt->execute([$row['counted_views'], $row['post_id']]);
                    $updated++;
                }
                echo "<p class='text-success'>✓ Updated {$updated} posts with view counts</p>";
                
                // Sync download counts
                echo "<h5>Syncing Download Counts...</h5>";
                $stmt = $pdo->query("
                    SELECT post_id, COUNT(*) as counted_downloads
                    FROM downloads
                    WHERE status = 'completed'
                    GROUP BY post_id
                ");
                
                $updated = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $updateStmt = $pdo->prepare("UPDATE posts SET download_count = ? WHERE id = ?");
                    $updateStmt->execute([$row['counted_downloads'], $row['post_id']]);
                    $updated++;
                }
                echo "<p class='text-success'>✓ Updated {$updated} posts with download counts</p>";
                
                // Final summary
                $stmt = $pdo->query("
                    SELECT 
                        SUM(view_count) as total_views,
                        SUM(download_count) as total_downloads,
                        COUNT(*) as total_posts
                    FROM posts
                ");
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<div class='alert alert-success mt-3'>";
                echo "<strong>✅ Synchronization Complete!</strong><br>";
                echo "Total Posts: {$summary['total_posts']}<br>";
                echo "Total Views: " . number_format($summary['total_views']) . "<br>";
                echo "Total Downloads: " . number_format($summary['total_downloads']);
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'migrate':
            // Migrate View Counts Tool
            try {
                echo "<div class='result-section'>";
                echo "<h4>🔄 Migrating Old View Count Data...</h4>";
                
                // Check for old columns
                $hasOldViews = false;
                $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'views'");
                if ($stmt->rowCount() > 0) {
                    $hasOldViews = true;
                    echo "<p class='text-info'>✓ Found old 'views' column</p>";
                    
                    // Migrate data
                    $pdo->exec("UPDATE posts SET view_count = COALESCE(views, 0) WHERE view_count = 0");
                    echo "<p class='text-success'>✓ Migrated data from 'views' to 'view_count'</p>";
                    
                    // Drop old column
                    $pdo->exec("ALTER TABLE posts DROP COLUMN views");
                    echo "<p class='text-success'>✓ Removed old 'views' column</p>";
                } else {
                    echo "<p class='text-muted'>ℹ No old 'views' column found - migration not needed</p>";
                }
                
                // Check for old downloads column
                $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'downloads'");
                if ($stmt->rowCount() > 0) {
                    echo "<p class='text-info'>✓ Found old 'downloads' column</p>";
                    
                    $pdo->exec("UPDATE posts SET download_count = COALESCE(downloads, 0) WHERE download_count = 0");
                    echo "<p class='text-success'>✓ Migrated data from 'downloads' to 'download_count'</p>";
                    
                    $pdo->exec("ALTER TABLE posts DROP COLUMN downloads");
                    echo "<p class='text-success'>✓ Removed old 'downloads' column</p>";
                } else {
                    echo "<p class='text-muted'>ℹ No old 'downloads' column found - migration not needed</p>";
                }
                
                echo "<div class='alert alert-success mt-3'>";
                echo "<strong>✅ Migration Complete!</strong>";
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'sessions':
            // Fix Session Data Tool
            try {
                echo "<div class='result-section'>";
                echo "<h4>🔧 Fixing Session & Geographic Data...</h4>";
                
                // Generate session IDs for views without them
                $stmt = $pdo->query("
                    SELECT id, ip_address, view_date 
                    FROM page_views 
                    WHERE session_id IS NULL OR session_id = ''
                ");
                $viewsToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $sessionIdsFixed = 0;
                
                foreach ($viewsToFix as $view) {
                    $sessionTime = date('Y-m-d H:i', strtotime($view['view_date']) - (strtotime($view['view_date']) % 1800));
                    $generatedSessionId = md5($view['ip_address'] . $sessionTime);
                    
                    $updateStmt = $pdo->prepare("UPDATE page_views SET session_id = ? WHERE id = ?");
                    $updateStmt->execute([$generatedSessionId, $view['id']]);
                    $sessionIdsFixed++;
                }
                
                echo "<p class='text-success'>✓ Fixed {$sessionIdsFixed} page views with generated session IDs</p>";
                
                // Create sessions from page_views
                $stmt = $pdo->query("
                    SELECT DISTINCT 
                        session_id,
                        ip_address,
                        user_agent,
                        country,
                        MIN(view_date) as first_view,
                        MAX(view_date) as last_view
                    FROM page_views
                    WHERE session_id IS NOT NULL
                    GROUP BY session_id
                ");
                
                $sessionsCreated = 0;
                while ($session = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Check if session exists
                    $checkStmt = $pdo->prepare("SELECT id FROM sessions WHERE session_id = ?");
                    $checkStmt->execute([$session['session_id']]);
                    
                    if (!$checkStmt->fetch()) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO sessions (session_id, ip_address, user_agent, country, start_time, last_activity)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $insertStmt->execute([
                            $session['session_id'],
                            $session['ip_address'],
                            $session['user_agent'],
                            $session['country'],
                            $session['first_view'],
                            $session['last_view']
                        ]);
                        $sessionsCreated++;
                    }
                }
                
                echo "<p class='text-success'>✓ Created {$sessionsCreated} new sessions</p>";
                
                echo "<div class='alert alert-success mt-3'>";
                echo "<strong>✅ Session Data Fixed!</strong>";
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'roles':
            // Setup Roles Tool
            try {
                echo "<div class='result-section'>";
                echo "<h4>👥 Setting Up Role-Based Access...</h4>";
                
                // Update role enum
                $pdo->exec("ALTER TABLE administrators MODIFY COLUMN role ENUM('superadmin', 'super_admin', 'admin', 'moderator', 'editor') DEFAULT 'editor'");
                echo "<p class='text-success'>✓ Role enum updated</p>";
                
                // Normalize super_admin to superadmin
                $stmt = $pdo->prepare("UPDATE administrators SET role = 'superadmin' WHERE role = 'super_admin'");
                $stmt->execute();
                echo "<p class='text-success'>✓ Normalized super_admin roles to superadmin</p>";
                
                // Create demo users
                $demoUsers = [
                    ['username' => 'editor1', 'email' => 'editor1@donan22.com', 'role' => 'editor', 'full_name' => 'Demo Editor'],
                    ['username' => 'moderator1', 'email' => 'moderator1@donan22.com', 'role' => 'moderator', 'full_name' => 'Demo Moderator'],
                    ['username' => 'admin1', 'email' => 'admin1@donan22.com', 'role' => 'admin', 'full_name' => 'Demo Admin']
                ];
                
                $created = 0;
                foreach ($demoUsers as $user) {
                    $stmt = $pdo->prepare("SELECT id FROM administrators WHERE username = ? OR email = ?");
                    $stmt->execute([$user['username'], $user['email']]);
                    
                    if (!$stmt->fetch()) {
                        $password = password_hash('demo123', PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO administrators (username, email, password_hash, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                        $stmt->execute([$user['username'], $user['email'], $password, $user['full_name'], $user['role']]);
                        echo "<p class='text-success'>✓ Created demo user: {$user['username']} ({$user['role']})</p>";
                        $created++;
                    } else {
                        echo "<p class='text-muted'>- Demo user already exists: {$user['username']}</p>";
                    }
                }
                
                echo "<div class='alert alert-success mt-3'>";
                echo "<strong>✅ Role-Based Access Setup Complete!</strong><br>";
                echo "Created {$created} new demo users.<br><br>";
                echo "<strong>Demo Login Credentials:</strong><br>";
                echo "• editor1 / demo123 (Editor - can manage posts only)<br>";
                echo "• moderator1 / demo123 (Moderator - can manage posts and comments)<br>";
                echo "• admin1 / demo123 (Admin - can manage most features)";
                echo "</div>";
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        case 'import':
            // Import Tutorial Tool
            try {
                echo "<div class='result-section'>";
                echo "<h4>📚 Importing Tutorial Articles...</h4>";
                
                // Check prerequisites
                $catCheck = $pdo->query("SELECT id FROM categories WHERE slug = 'tutorial' LIMIT 1");
                $category = $catCheck->fetch();
                
                if (!$category) {
                    echo "<div class='alert alert-warning'>";
                    echo "⚠ Category 'Tutorial' tidak ditemukan. Silakan buat kategori Tutorial terlebih dahulu.";
                    echo "</div>";
                } else {
                    echo "<p class='text-success'>✓ Category 'Tutorial' found</p>";
                    
                    // Create sample tutorial post
                    $sampleTitle = "Tutorial HTML & CSS untuk Pemula";
                    $sampleSlug = "tutorial-html-css-untuk-pemula-" . time();
                    $sampleContent = "<h2>Pengenalan HTML &amp; CSS</h2><p>Panduan lengkap untuk memulai belajar web development...</p>";
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())
                    ");
                    
                    $adminId = $_SESSION['admin_id'];
                    $stmt->execute([
                        $sampleTitle,
                        $sampleSlug,
                        $sampleContent,
                        'Tutorial lengkap HTML dan CSS untuk pemula',
                        $category['id'],
                        $adminId
                    ]);
                    
                    echo "<p class='text-success'>✓ Sample tutorial article created</p>";
                    
                    echo "<div class='alert alert-success mt-3'>";
                    echo "<strong>✅ Tutorial Import Complete!</strong><br>";
                    echo "Sample tutorial article has been created.";
                    echo "</div>";
                }
                
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            break;
            
        default:
            echo "<div class='alert alert-warning'>Unknown tool selected.</div>";
    }
    
    $executionResult = ob_get_clean();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Tools - DONAN22</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            border: none;
        }
        .tool-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin: 15px 0;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .tool-card h4 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .btn-tool {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-tool:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
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
        .result-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .badge-warning-tool {
            background: #ffc107;
            color: #000;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .badge-safe-tool {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="mb-0"><i class="fas fa-tools"></i> Maintenance Tools Dashboard</h2>
                <p class="text-muted mb-0">Kumpulan tools untuk maintenance dan perbaikan sistem</p>
            </div>
            <div class="card-body">
                
                <?php if ($executionResult): ?>
                    <div class="mb-4">
                        <?= $executionResult ?>
                        <a href="maintenance_tools.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Tools
                        </a>
                    </div>
                <?php else: ?>
                
                <div class="row">
                    <!-- Sync Counts Tool -->
                    <div class="col-md-6">
                        <div class="tool-card">
                            <h4><i class="fas fa-sync-alt"></i> Sync View & Download Counts</h4>
                            <p class="text-muted">
                                Sinkronisasi ulang semua view count dan download count dari data analytics ke tabel posts.
                                <span class="badge-safe-tool">SAFE</span>
                            </p>
                            <p class="mb-3"><strong>Kapan digunakan:</strong><br>
                            • Ketika jumlah views/downloads tidak sesuai<br>
                            • Setelah import data baru<br>
                            • Maintenance rutin
                            </p>
                            <a href="?tool=sync&action=execute" class="btn-tool" onclick="return confirm('Yakin ingin menjalankan sync counts?')">
                                <i class="fas fa-sync"></i> Run Sync Tool
                            </a>
                        </div>
                    </div>
                    
                    <!-- Migration Tool -->
                    <div class="col-md-6">
                        <div class="tool-card">
                            <h4><i class="fas fa-database"></i> Migrate Old View Counts</h4>
                            <p class="text-muted">
                                Migrate data dari kolom lama (views/downloads) ke kolom baru (view_count/download_count).
                                <span class="badge-warning-tool">RUN ONCE</span>
                            </p>
                            <p class="mb-3"><strong>Kapan digunakan:</strong><br>
                            • Hanya sekali saat upgrade sistem<br>
                            • Jika masih ada kolom 'views' lama<br>
                            • Setelah update database schema
                            </p>
                            <a href="?tool=migrate&action=execute" class="btn-tool" onclick="return confirm('Migration tool hanya perlu dijalankan SEKALI. Lanjutkan?')">
                                <i class="fas fa-arrow-right"></i> Run Migration
                            </a>
                        </div>
                    </div>
                    
                    <!-- Session Fix Tool -->
                    <div class="col-md-6">
                        <div class="tool-card">
                            <h4><i class="fas fa-user-clock"></i> Fix Session Data</h4>
                            <p class="text-muted">
                                Generate session IDs untuk page views yang belum punya dan buat entries di tabel sessions.
                                <span class="badge-safe-tool">SAFE</span>
                            </p>
                            <p class="mb-3"><strong>Kapan digunakan:</strong><br>
                            • Session tracking tidak akurat<br>
                            • Bounce rate tidak muncul<br>
                            • Setelah update tracking system
                            </p>
                            <a href="?tool=sessions&action=execute" class="btn-tool" onclick="return confirm('Yakin ingin fix session data?')">
                                <i class="fas fa-wrench"></i> Run Session Fix
                            </a>
                        </div>
                    </div>
                    
                    <!-- Setup Roles Tool -->
                    <div class="col-md-6">
                        <div class="tool-card">
                            <h4><i class="fas fa-user-shield"></i> Setup Role-Based Access</h4>
                            <p class="text-muted">
                                Setup role permissions dan create demo users untuk testing berbagai level akses.
                                <span class="badge-warning-tool">RUN ONCE</span>
                            </p>
                            <p class="mb-3"><strong>Kapan digunakan:</strong><br>
                            • Setup awal sistem role<br>
                            • Testing multi-level access<br>
                            • Create demo accounts
                            </p>
                            <a href="?tool=roles&action=execute" class="btn-tool" onclick="return confirm('Setup roles dan create demo users?')">
                                <i class="fas fa-users-cog"></i> Setup Roles
                            </a>
                        </div>
                    </div>
                    
                    <!-- Import Tutorial Tool -->
                    <div class="col-md-6">
                        <div class="tool-card">
                            <h4><i class="fas fa-book"></i> Import Tutorial Articles</h4>
                            <p class="text-muted">
                                Import sample tutorial articles untuk testing atau content starter.
                                <span class="badge-safe-tool">SAFE</span>
                            </p>
                            <p class="mb-3"><strong>Kapan digunakan:</strong><br>
                            • Setup awal website<br>
                            • Butuh sample content<br>
                            • Testing tutorial features
                            </p>
                            <a href="?tool=import&action=execute" class="btn-tool" onclick="return confirm('Import sample tutorial articles?')">
                                <i class="fas fa-file-import"></i> Import Tutorials
                            </a>
                        </div>
                    </div>
                    
                    <!-- Info Box -->
                    <div class="col-md-6">
                        <div class="tool-card" style="border-left-color: #17a2b8;">
                            <h4 style="color: #17a2b8;"><i class="fas fa-info-circle"></i> Tool Usage Guide</h4>
                            <p class="mb-2"><strong>Legend:</strong></p>
                            <p class="mb-1">
                                <span class="badge-safe-tool">SAFE</span> = Aman dijalankan berkali-kali
                            </p>
                            <p class="mb-3">
                                <span class="badge-warning-tool">RUN ONCE</span> = Hanya perlu sekali
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <strong>Catatan:</strong> Selalu backup database sebelum menjalankan maintenance tools!
                            </p>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
