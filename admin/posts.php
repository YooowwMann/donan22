<?php
// Start output buffering IMMEDIATELY to catch any output
ob_start();

define('ADMIN_ACCESS', true);

// Handle AJAX bulk actions FIRST - before any output
if (isset($_POST['bulk_action']) && !empty($_POST['bulk_action'])) {
    try {
        require_once '../config_modern.php';
        
        // Simple authentication check (session already started in config_modern.php)
        if (!isset($_SESSION['admin_id'])) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        // Load required classes
        require_once 'system/soft_delete_system.php';
        
        // Clear any buffered output and set JSON header
        ob_end_clean();
        header('Content-Type: application/json');
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Missing CSRF token']);
            exit;
        }
        
        $action = $_POST['bulk_action'];
        $postIdsJson = $_POST['post_ids'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        // Parse post IDs
        $postIds = json_decode($postIdsJson, true);
        
        if (!is_array($postIds) || empty($postIds)) {
            echo json_encode(['success' => false, 'message' => 'No posts selected']);
            exit;
        }
        
        // Sanitize post IDs
        $postIds = array_map('intval', $postIds);
        $postIds = array_filter($postIds, function($id) { return $id > 0; });
        
        if (empty($postIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid post IDs']);
            exit;
        }
        
        // Get PDO connection (should be available from config_modern.php)
        /** @var PDO $pdo */
        global $pdo;
        if (!isset($pdo)) {
            echo json_encode(['success' => false, 'message' => 'Database connection error']);
            exit;
        }
        
        $softDelete = new SoftDeleteManager($pdo);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        switch ($action) {
            case 'publish':
                foreach ($postIds as $postId) {
                    $stmt = $pdo->prepare("UPDATE posts SET status = 'published', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                    if ($stmt->execute([$postId])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                $message = "$successCount post(s) berhasil dipublish";
                if ($errorCount > 0) $message .= ", $errorCount gagal";
                break;
                
            case 'draft':
                foreach ($postIds as $postId) {
                    $stmt = $pdo->prepare("UPDATE posts SET status = 'draft', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                    if ($stmt->execute([$postId])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                $message = "$successCount post(s) berhasil diubah ke draft";
                if ($errorCount > 0) $message .= ", $errorCount gagal";
                break;
                
            case 'trash':
                foreach ($postIds as $postId) {
                    $result = $softDelete->deletePost($postId, $reason);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $result['message'];
                    }
                }
                $message = "$successCount post(s) berhasil dipindahkan ke trash";
                if ($errorCount > 0) $message .= ", $errorCount gagal";
                break;
                
            case 'restore':
                foreach ($postIds as $postId) {
                    $result = $softDelete->restorePost($postId);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $result['message'];
                    }
                }
                $message = "$successCount post(s) berhasil di-restore";
                if ($errorCount > 0) $message .= ", $errorCount gagal";
                break;
                
            case 'permanent_delete':
                foreach ($postIds as $postId) {
                    $result = $softDelete->permanentDeletePost($postId);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $result['message'];
                    }
                }
                $message = "$successCount post(s) berhasil dihapus permanen";
                if ($errorCount > 0) $message .= ", $errorCount gagal";
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
        
        echo json_encode([
            'success' => $successCount > 0,
            'message' => $message,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    }
}

// Normal page load - end output buffering
ob_end_flush();

// Load remaining files for normal page load
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
require_once 'system/security_system.php';
require_once 'system/soft_delete_system.php';
require_once '../includes/MonetizationManager.php';
requireLogin();
requirePermission('manage_posts');

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Handle success/error messages from sessions
if (isset($_SESSION['success_message'])) {
    $success_msg = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_msg = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle success message from post-editor delete
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_msg = "Post berhasil dihapus ke trash!";
}

// Handle post operations
if ($_POST && isset($_POST['action'])) {
    // Validate CSRF token using modern method
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generateCSRFToken()) {
        $error_msg = "Token keamanan tidak valid";
    } else {
        try {
            switch ($_POST['action']) {
                case 'soft_delete':
                    $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
                    $reason = $security->sanitizeInput($_POST['delete_reason'] ?? '');
                    
                    if ($id > 0) {
                        $result = $softDelete->deletePost($id, $reason);
                        if ($result['success']) {
                            $success_msg = $result['message'];
                        } else {
                            $error_msg = $result['message'];
                        }
                    } else {
                        $error_msg = "Invalid post ID";
                    }
                    break;
                    
                case 'permanent_delete':
                    $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
                    if ($id > 0) {
                        $result = $softDelete->permanentDeletePost($id);
                        if ($result['success']) {
                            $success_msg = $result['message'];
                        } else {
                            $error_msg = $result['message'];
                        }
                    } else {
                        $error_msg = "Invalid post ID";
                    }
                    break;
                    
                case 'restore':
                    $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
                    if ($id > 0) {
                        $result = $softDelete->restorePost($id);
                        if ($result['success']) {
                            $success_msg = $result['message'];
                        } else {
                            $error_msg = $result['message'];
                        }
                    } else {
                        $error_msg = "Invalid post ID";
                    }
                    break;
                    
                case 'toggle_status':
                    $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE posts SET status = CASE WHEN status = 'published' THEN 'draft' ELSE 'published' END, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                        $stmt->execute([$id]);
                        $success_msg = "Status post berhasil diupdate!";
                    } else {
                        $error_msg = "Invalid post ID";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Initialize MonetizationManager for shortlink search
$monetization = new MonetizationManager($pdo);

// Handle shortlink search
$shortlinkSearchResult = null;
$shortlinkQuery = $_GET['shortlink'] ?? '';

if (!empty($shortlinkQuery)) {
    $shortCode = $monetization->extractShortCodeFromUrl($shortlinkQuery);
    if ($shortCode) {
        $shortlinkSearchResult = $monetization->searchPostByShortlinkForAdmin($shortCode);
    }
}

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get filter
$filter = $_GET['filter'] ?? 'active';

// Get posts based on filter
switch ($filter) {
    case 'deleted':
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug, p.delete_reason, p.deleted_at as soft_deleted_at,
                   a.username as deleted_by_name,
                   COALESCE(p.view_count, 0) as views,
                   COALESCE(p.download_count, 0) as download_count
            FROM posts p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN administrators a ON p.deleted_by = a.id
            WHERE p.deleted_at IS NOT NULL
            ORDER BY p.deleted_at DESC
        ");
        break;
    default:
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug,
                   COALESCE(p.view_count, 0) as views,
                   COALESCE(p.download_count, 0) as download_count
            FROM posts p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.deleted_at IS NULL
            ORDER BY COALESCE(p.updated_at, p.created_at) DESC
        ");
        break;
}
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?php require_once 'includes/navigation.php'; ?>
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('posts'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt me-2"></i>
                        <?= $filter == 'deleted' ? 'Post Terhapus' : 'Manage Posts' ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?filter=active" class="btn btn-outline-primary <?= $filter != 'deleted' ? 'active' : '' ?>">
                                <i class="fas fa-list me-2"></i>Active Posts
                            </a>
                            <a href="?filter=deleted" class="btn btn-outline-warning <?= $filter == 'deleted' ? 'active' : '' ?>">
                                <i class="fas fa-trash me-2"></i>Trash
                            </a>
                        </div>
                        <?php if ($filter != 'deleted'): ?>
                        <div class="btn-group me-2">
                            <a href="export_posts.php" class="btn btn-outline-info">
                                <i class="fas fa-file-export me-2"></i>Export Posts
                            </a>
                        </div>
                        <div class="btn-group">
                            <a href="post-editor.php?type=software" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Add Software
                            </a>
                            <a href="post-editor.php?type=blog" class="btn btn-primary">
                                <i class="fas fa-pen me-2"></i>Write Blog
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filters Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>Filters & Search
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Search -->
                                    <div class="col-md-4">
                                        <label class="form-label">Search Posts</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="searchInput" 
                                                   placeholder="Search by title, content...">
                                        </div>
                                    </div>
                                    
                                    <!-- Shortlink Search -->
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-link me-1"></i>Search by Shortlink
                                            <small class="text-muted">(untuk debug download error)</small>
                                        </label>
                                        <form method="GET" action="">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </span>
                                                <input type="text" class="form-control" name="shortlink" 
                                                       value="<?= htmlspecialchars($shortlinkQuery) ?>"
                                                       placeholder="Masukkan shortlink (5F053521 atau URL lengkap)">
                                                <button class="btn btn-outline-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <?php if (!empty($shortlinkQuery)): ?>
                                            <div class="mt-1">
                                                <a href="?" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-times me-1"></i>Clear
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    
                                    <!-- Status Filter -->
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="published">Published</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Type Filter -->
                                    <div class="col-md-2">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" id="typeFilter">
                                            <option value="">All Types</option>
                                            <option value="software">Software</option>
                                            <option value="blog">Blog</option>
                                            <option value="mobile-apps">Mobile Apps</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Category Filter with Search -->
                                    <div class="col-md-2">
                                        <label class="form-label">Category</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="categorySearchInput" 
                                                   placeholder="Search categories..." autocomplete="off">
                                            <div class="category-dropdown" id="categoryDropdown" style="
                                                position: absolute;
                                                top: 100%;
                                                left: 0;
                                                right: 0;
                                                background: white;
                                                border: 1px solid #dee2e6;
                                                border-top: none;
                                                border-radius: 0 0 0.375rem 0.375rem;
                                                max-height: 200px;
                                                overflow-y: auto;
                                                z-index: 1050;
                                                display: none;
                                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                            ">
                                                <div class="category-item px-3 py-2" data-value="" data-name="All Categories" style="cursor: pointer; border-bottom: 1px solid #f8f9fa;">All Categories</div>
                                                <?php foreach ($categories as $category): ?>
                                                    <?php
                                                    // Count posts for this category
                                                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL");
                                                    $countStmt->execute([$category['id']]);
                                                    $postCount = $countStmt->fetchColumn();
                                                    ?>
                                                    <div class="category-item px-3 py-2" data-value="<?= htmlspecialchars($category['slug']) ?>" data-name="<?= htmlspecialchars($category['name']) ?>" style="cursor: pointer; border-bottom: 1px solid #f8f9fa;">
                                                        <?= htmlspecialchars($category['name']) ?> (<?= $postCount ?> posts)
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <!-- Hidden select for form compatibility -->
                                            <select class="form-select" id="categoryFilter" style="display: none;">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= htmlspecialchars($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- NEW: Tags Filter -->
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-tags me-1"></i>Tags</label>
                                        <select class="form-select" id="tagsFilter">
                                            <option value="">All Tags</option>
                                            <?php
                                            $tagsStmt = $pdo->query("SELECT DISTINCT t.* FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id ORDER BY t.name");
                                            while ($tag = $tagsStmt->fetch()): ?>
                                            <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Date Filter -->
                                    <div class="col-md-2">
                                        <label class="form-label">Date</label>
                                        <select class="form-select" id="dateFilter">
                                            <option value="">All Dates</option>
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                            <option value="year">This Year</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <!-- View Range Filter -->
                                    <div class="col-md-3">
                                        <label class="form-label">Views Range</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="minViews" placeholder="Min">
                                            <span class="input-group-text">-</span>
                                            <input type="number" class="form-control" id="maxViews" placeholder="Max">
                                        </div>
                                    </div>
                                    
                                    <!-- Sort Options -->
                                    <div class="col-md-3">
                                        <label class="form-label">Sort By</label>
                                        <select class="form-select" id="sortFilter">
                                            <option value="created_at_desc">Newest First</option>
                                            <option value="created_at_asc">Oldest First</option>
                                            <option value="views_desc">Most Views</option>
                                            <option value="views_asc">Least Views</option>
                                            <option value="downloads_desc">Most Downloads</option>
                                            <option value="downloads_asc">Least Downloads</option>
                                            <option value="title_asc">Title A-Z</option>
                                            <option value="title_desc">Title Z-A</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary me-2" id="applyFilters">
                                            <i class="fas fa-filter me-1"></i>Apply
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                            <i class="fas fa-times me-1"></i>Clear
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm ms-2" onclick="console.log('Test sort clicked'); document.getElementById('sortFilter').value='title_asc'; applyFilters();">
                                            Test Sort
                                        </button>
                                    </div>
                                    
                                    <!-- Results Count -->
                                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                                        <small class="text-muted" id="resultsCount">
                                            Showing <span id="visibleCount"><?= count($posts) ?></span> of <span id="totalCount"><?= count($posts) ?></span> posts
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortlink Search Result -->
                <?php if (!empty($shortlinkQuery)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-link me-2"></i>Hasil Pencarian Shortlink: <?= htmlspecialchars($shortlinkQuery) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($shortlinkSearchResult): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>Post ditemukan dari shortlink!
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <h4><?= htmlspecialchars($shortlinkSearchResult['title']) ?></h4>
                                <p class="text-muted mb-2">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?= $shortlinkSearchResult['status'] == 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($shortlinkSearchResult['status']) ?>
                                    </span>
                                    <?php if ($shortlinkSearchResult['deleted_at']): ?>
                                    <span class="badge bg-danger ms-1">DELETED</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <strong>Category:</strong> <?= htmlspecialchars($shortlinkSearchResult['category_name'] ?? 'Uncategorized') ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <strong>Author:</strong> <?= htmlspecialchars($shortlinkSearchResult['author_name'] ?? 'Unknown') ?>
                                </p>
                                <p class="text-muted mb-3">
                                    <strong>Created:</strong> <?= date('d M Y H:i', strtotime($shortlinkSearchResult['created_at'])) ?>
                                </p>
                                <div class="mb-3">
                                    <a href="post-editor.php?id=<?= $shortlinkSearchResult['post_id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i>Edit Post
                                    </a>
                                    <a href="<?= SITE_URL ?>/post/<?= $shortlinkSearchResult['slug'] ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-external-link-alt me-1"></i>View Post
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Download Info</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Short Code:</strong> <code><?= $shortlinkSearchResult['short_code'] ?></code></p>
                                        <p><strong>Download Title:</strong> <?= htmlspecialchars($shortlinkSearchResult['download_title'] ?? 'N/A') ?></p>
                                        <p><strong>File Size:</strong> <?= htmlspecialchars($shortlinkSearchResult['file_size'] ?? 'N/A') ?></p>
                                        <p><strong>Version:</strong> <?= htmlspecialchars($shortlinkSearchResult['version'] ?? 'N/A') ?></p>
                                        <p><strong>Password:</strong> <?= htmlspecialchars($shortlinkSearchResult['file_password'] ?? 'No password') ?></p>
                                        <hr>
                                        <p><strong>Total Clicks:</strong> <?= number_format($shortlinkSearchResult['total_clicks']) ?></p>
                                        <p><strong>Total Downloads:</strong> <?= number_format($shortlinkSearchResult['total_downloads']) ?></p>
                                        <p><strong>Est. Revenue:</strong> $<?= number_format($shortlinkSearchResult['estimated_revenue'], 2) ?></p>
                                        <hr>
                                        <div class="d-grid gap-2">
                                            <a href="<?= SITE_URL ?>/go/<?= $shortlinkSearchResult['short_code'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>Test Shortlink
                                            </a>
                                            <?php if ($shortlinkSearchResult['original_url']): ?>
                                            <a href="<?= htmlspecialchars($shortlinkSearchResult['original_url']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download me-1"></i>Direct Download
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Tidak ada post ditemukan untuk shortlink: <strong><?= htmlspecialchars($shortlinkQuery) ?></strong>
                            <br><small class="text-muted">Pastikan shortlink valid atau masih aktif</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Posts Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($posts)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No posts found</h5>
                            <p class="text-muted">Start by creating your first post!</p>
                            <div class="btn-group">
                                <a href="post-editor.php?type=software" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>Add Software
                                </a>
                                <a href="post-editor.php?type=blog" class="btn btn-primary">
                                    <i class="fas fa-pen me-2"></i>Write Blog
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        
                        <!-- ✅ Bulk Actions Toolbar - Active Posts -->
                        <?php if ($filter != 'deleted'): ?>
                        <div class="mb-3 p-3 bg-light rounded" id="bulkActionsToolbar" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong id="selectedCount">0</strong> item(s) selected
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm" id="bulkPublishBtn">
                                        <i class="fas fa-check me-1"></i>Publish
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="bulkDraftBtn">
                                        <i class="fas fa-file me-1"></i>Set as Draft
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" id="bulkTrashBtn">
                                        <i class="fas fa-trash me-1"></i>Move to Trash
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkCancelBtn">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- ✅ Bulk Actions Toolbar - Deleted Posts -->
                        <div class="mb-3 p-3 bg-light rounded" id="bulkActionsToolbar" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong id="selectedCount">0</strong> item(s) selected
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm" id="bulkRestoreBtn">
                                        <i class="fas fa-undo me-1"></i>Restore
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" id="bulkPermanentDeleteBtn">
                                        <i class="fas fa-times me-1"></i>Hapus Permanen
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkCancelBtn">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-all" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="postsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="30">
                                                    <input type="checkbox" class="form-check-input" id="selectAllPosts" title="Select All">
                                                </th>
                                                <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th><i class="fas fa-download me-1"></i>Downloads</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                    <tr class="post-row" 
                                        data-post-id="<?= $post['id'] ?>"
                                        data-post-title="<?= htmlspecialchars($post['title']) ?>"
                                        data-post-content="<?= htmlspecialchars(strtolower(strip_tags($post['content']))) ?>"
                                        data-post-type="<?= strtolower($post['post_type_slug'] ?? 'post') ?>"
                                        data-post-category="<?= htmlspecialchars($post['category_slug'] ?? '') ?>"
                                        data-post-status="<?= $post['status'] ?>"
                                        data-post-views="<?= $post['views'] ?>"
                                        data-post-downloads="<?= $post['download_count'] ?? 0 ?>"
                                        data-post-date="<?= $post['created_at'] ?>"
                                        data-post-featured="<?= $post['featured'] ? '1' : '0' ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input post-checkbox" value="<?= $post['id'] ?>">
                                        </td>
                                        <td><?php echo $post['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                            <?php if ($post['featured']): ?>
                                            <span class="badge bg-warning text-dark">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $postType = strtolower($post['post_type_name'] ?? 'post');
                                            $badgeClass = 'bg-secondary';
                                            $icon = 'fas fa-file-alt';
                                            
                                            if (strpos($postType, 'software') !== false) {
                                                $badgeClass = 'bg-success';
                                                $icon = 'fas fa-download';
                                            } elseif (strpos($postType, 'blog') !== false) {
                                                $badgeClass = 'bg-info';
                                                $icon = 'fas fa-book';
                                            } elseif (strpos($postType, 'game') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                                $icon = 'fas fa-gamepad';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <i class="<?php echo $icon; ?> me-1"></i><?php echo htmlspecialchars($post['post_type_name'] ?? 'Post'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($post['status'] == 'published'): ?>
                                            <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-eye me-1"></i><?php echo number_format($post['views'] ?? 0); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-download me-1 text-success"></i>
                                            <strong><?php echo number_format($post['download_count'] ?? 0); ?></strong>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($filter == 'deleted'): ?>
                                                    <!-- Actions for deleted posts -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Restore post ini?')">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="restore">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" class="btn btn-success" title="Restore">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus permanen post ini? Tidak bisa di-restore lagi!')">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="permanent_delete">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" title="Hapus Permanen">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                    <small class="text-muted d-block mt-1">
                                                        Dihapus: <?= date('d/m/Y H:i', strtotime($post['soft_deleted_at'])) ?><br>
                                                        Alasan: <?= htmlspecialchars($post['delete_reason'] ?: 'Tidak ada alasan') ?>
                                                    </small>
                                                <?php else: ?>
                                                    <!-- Actions for active posts -->
                                                    <a href="post-editor.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-info quick-edit-btn" 
                                                            data-post-id="<?= $post['id'] ?>" 
                                                            data-post-title="<?= htmlspecialchars($post['title']) ?>" 
                                                            data-post-status="<?= $post['status'] ?>" 
                                                            data-post-category="<?= $post['category_id'] ?>" 
                                                            title="Quick Edit">
                                                        <i class="fas fa-bolt"></i>
                                                    </button>
                                                    <a href="<?= SITE_URL ?>/post/<?php echo $post['slug']; ?>" class="btn btn-outline-secondary" title="View" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-warning" title="Toggle Status">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-outline-danger" title="Delete to Trash" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-post-id="<?php echo $post['id']; ?>" 
                                                            data-post-title="<?php echo htmlspecialchars($post['title']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success paraphrase-btn" title="Paraphrase Content" 
                                                            data-bs-toggle="modal" data-bs-target="#paraphraseModal"
                                                            data-post-id="<?php echo $post['id']; ?>" 
                                                            data-post-title="<?php echo htmlspecialchars($post['title']); ?>">
                                                        <i class="fas fa-language"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count($posts); ?></h4>
                                        <p class="mb-0">Total Posts</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count(array_filter($posts, function($p) { return $p['status'] == 'published'; })); ?></h4>
                                        <p class="mb-0">Published</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count(array_filter($posts, function($p) { return $p['status'] == 'draft'; })); ?></h4>
                                        <p class="mb-0">Drafts</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-edit fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo count(array_filter($posts, function($p) { return $p['featured']; })); ?></h4>
                                        <p class="mb-0">Featured</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Advanced Posts Filter System
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing filters...');
            
            // Get all filter elements
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const dateFilter = document.getElementById('dateFilter');
            const minViews = document.getElementById('minViews');
            const maxViews = document.getElementById('maxViews');
            const sortFilter = document.getElementById('sortFilter');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const visibleCount = document.getElementById('visibleCount');
            const totalCount = document.getElementById('totalCount');
            
            const tableBody = document.querySelector('#postsTable tbody');
            const allRows = Array.from(document.querySelectorAll('.post-row'));
            
            console.log('Found elements:', {
                searchInput: searchInput ? 'OK' : 'NOT FOUND',
                statusFilter: statusFilter ? 'OK' : 'NOT FOUND',
                typeFilter: typeFilter ? 'OK' : 'NOT FOUND',
                categoryFilter: categoryFilter ? 'OK' : 'NOT FOUND',
                sortFilter: sortFilter ? 'OK' : 'NOT FOUND',
                tableBody: tableBody ? 'OK' : 'NOT FOUND', 
                rowCount: allRows.length
            });
            
            // Check if essential elements exist
            if (!searchInput) {
                console.error('Search input not found!');
                return;
            }
            if (!tableBody) {
                console.error('Table body not found!');
                return;
            }
            
            // Apply filters function
            function applyFilters() {
                try {
                    console.log('Applying filters...'); // Debug log
                    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
                    const statusValue = statusFilter ? statusFilter.value : '';
                    const typeValue = typeFilter ? typeFilter.value : '';
                const categoryValue = categoryFilter.value;
                const dateValue = dateFilter.value;
                const minViewsValue = minViews.value ? parseInt(minViews.value) : null;
                const maxViewsValue = maxViews.value ? parseInt(maxViews.value) : null;
                const sortValue = sortFilter.value;
                
                console.log('Applying filters with sort:', sortValue);
                console.log('Total rows:', allRows.length);
                
                let filteredRows = allRows.filter(row => {
                    // Search filter
                    if (searchTerm) {
                        const title = row.dataset.postTitle || '';
                        const content = row.dataset.postContent || '';
                        if (!title.includes(searchTerm) && !content.includes(searchTerm)) {
                            return false;
                        }
                    }
                    
                    // Status filter
                    if (statusValue && row.dataset.postStatus !== statusValue) {
                        return false;
                    }
                    
                    // Type filter
                    if (typeValue && row.dataset.postType !== typeValue) {
                        return false;
                    }
                    
                    // Category filter
                    if (categoryValue && row.dataset.postCategory !== categoryValue) {
                        return false;
                    }
                    
                    // Date filter
                    if (dateValue) {
                        const postDate = new Date(row.dataset.postDate);
                        const now = new Date();
                        let startDate;
                        
                        switch (dateValue) {
                            case 'today':
                                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                                break;
                            case 'week':
                                startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                                break;
                            case 'month':
                                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                                break;
                            case 'year':
                                startDate = new Date(now.getFullYear(), 0, 1);
                                break;
                        }
                        
                        if (startDate && postDate < startDate) {
                            return false;
                        }
                    }
                    
                    // Views filter
                    const views = parseInt(row.dataset.postViews) || 0;
                    if (minViewsValue !== null && views < minViewsValue) {
                        return false;
                    }
                    if (maxViewsValue !== null && views > maxViewsValue) {
                        return false;
                    }
                    
                    return true;
                });
                
                console.log('Filtered rows:', filteredRows.length, 'Sort by:', sortValue);
                
                // Function to get sort value for comparison
                function getSortValue(row, sortType) {
                    switch (sortType) {
                        case 'created_at_desc':
                        case 'created_at_asc':
                            return new Date(row.dataset.postDate).getTime();
                        case 'views_desc':
                        case 'views_asc':
                            return parseInt(row.dataset.postViews) || 0;
                        case 'downloads_desc':
                        case 'downloads_asc':
                            return parseInt(row.dataset.postDownloads) || 0;
                        case 'title_asc':
                        case 'title_desc':
                            return (row.dataset.postTitle || '').toLowerCase();
                        default:
                            return 0;
                    }
                }
                
                // Sort ALL rows (not just filtered)
                const allRowsSorted = [...allRows].sort((a, b) => {
                    const valueA = getSortValue(a, sortValue);
                    const valueB = getSortValue(b, sortValue);
                    
                    let comparison = 0;
                    if (typeof valueA === 'string') {
                        comparison = valueA.localeCompare(valueB);
                    } else {
                        comparison = valueA - valueB;
                    }
                    
                    // Reverse for desc sorts
                    if (sortValue.includes('_desc')) {
                        comparison = -comparison;
                    }
                    
                    return comparison;
                });
                
                console.log('Sorting complete, reordering DOM...');
                
                // Clear table and re-append in sorted order
                tableBody.innerHTML = '';
                allRowsSorted.forEach(row => {
                    tableBody.appendChild(row);
                    
                    // Show filtered rows, hide others
                    if (filteredRows.includes(row)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update count
                visibleCount.textContent = filteredRows.length;
                
                // Show empty state if no results
                showEmptyState(filteredRows.length === 0);
                
                console.log(`Filtered ${filteredRows.length} rows, sorted by: ${sortValue}`);
                } catch (error) {
                    console.error('Error in applyFilters:', error);
                }
            }
            
            // Clear filters function
            function clearFilters() {
                searchInput.value = '';
                statusFilter.value = '';
                typeFilter.value = '';
                categoryFilter.value = '';
                dateFilter.value = '';
                minViews.value = '';
                maxViews.value = '';
                sortFilter.value = 'created_at_desc';
                
                // Reset table to original order and show all rows
                while (tableBody.firstChild) {
                    tableBody.removeChild(tableBody.firstChild);
                }
                
                // Re-append all rows in original order
                allRows.forEach(row => {
                    row.style.display = '';
                    tableBody.appendChild(row);
                });
                
                visibleCount.textContent = allRows.length;
                showEmptyState(false);
                
                // Apply default sort (newest first)
                applyFilters();
            }
            
            // Show/hide empty state
            function showEmptyState(show) {
                let emptyRow = document.querySelector('.empty-state-row');
                
                if (show && !emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'empty-state-row';
                    emptyRow.innerHTML = `
                        <td colspan="10" class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No posts found</h5>
                            <p class="text-muted">Try adjusting your filters or search terms</p>
                        </td>
                    `;
                    tableBody.appendChild(emptyRow);
                } else if (emptyRow) {
                    emptyRow.style.display = show ? '' : 'none';
                }
            }
            
            // Real-time search with debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300); // 300ms debounce
            });
            
            // Also trigger on Enter key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    applyFilters();
                }
            });
            
            // Event listeners for all filters
            applyFiltersBtn.addEventListener('click', applyFilters);
            clearFiltersBtn.addEventListener('click', clearFilters);
            
            // Add change listeners for immediate filtering
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
            if (typeFilter) typeFilter.addEventListener('change', applyFilters);
            if (dateFilter) dateFilter.addEventListener('change', applyFilters);
            if (sortFilter) sortFilter.addEventListener('change', applyFilters);
            if (minViews) minViews.addEventListener('input', applyFilters);
            if (maxViews) maxViews.addEventListener('input', applyFilters);
            
            // Category Search Functionality
            const categorySearchInput = document.getElementById('categorySearchInput');
            const categoryDropdown = document.getElementById('categoryDropdown');
            const categoryItems = document.querySelectorAll('.category-item');
            let selectedCategoryValue = '';
            let selectedCategoryText = '';
            
            // Show dropdown on focus and clear input for easy typing
            categorySearchInput.addEventListener('focus', function() {
                // Clear input when focused to allow easy typing
                if (this.value === selectedCategoryText || selectedCategoryValue === '') {
                    this.value = '';
                }
                categoryDropdown.style.display = 'block';
                filterCategoryItems('');
            });
            
            // Filter categories while typing
            categorySearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                filterCategoryItems(searchTerm);
                categoryDropdown.style.display = 'block';
            });
            
            // Filter category items
            function filterCategoryItems(searchTerm) {
                categoryItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
            
            // Handle category item clicks
            categoryItems.forEach(item => {
                // Hover effects
                item.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
                
                // Click handler
                item.addEventListener('click', function() {
                    selectedCategoryValue = this.dataset.value;
                    selectedCategoryText = this.dataset.name || this.textContent;
                    
                    // Update input display
                    categorySearchInput.value = selectedCategoryText;
                    
                    // Update hidden select for compatibility
                    categoryFilter.value = selectedCategoryValue;
                    
                    // Hide dropdown
                    categoryDropdown.style.display = 'none';
                    
                    // Apply filters
                    applyFilters();
                });
            });
            
            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.position-relative')) {
                    categoryDropdown.style.display = 'none';
                    // Show selected category or clear input if none selected
                    if (selectedCategoryValue && selectedCategoryText) {
                        categorySearchInput.value = selectedCategoryText;
                    } else {
                        categorySearchInput.value = '';
                        categorySearchInput.placeholder = 'Search categories...';
                    }
                }
            });
            
            // Handle clear filters
            const originalClearFilters = clearFilters;
            function clearFilters() {
                selectedCategoryValue = '';
                selectedCategoryText = '';
                categorySearchInput.value = '';
                categorySearchInput.placeholder = 'Search categories...';
                categoryFilter.value = '';
                originalClearFilters();
            }
            
            // Update the global clearFilters function
            window.clearFilters = clearFilters;
            
            // ✅ NEW: Bulk Actions JavaScript
            const selectAllCheckbox = document.getElementById('selectAllPosts');
            const postCheckboxes = document.querySelectorAll('.post-checkbox');
            const bulkActionsToolbar = document.getElementById('bulkActionsToolbar');
            const selectedCountSpan = document.getElementById('selectedCount');
            const bulkPublishBtn = document.getElementById('bulkPublishBtn');
            const bulkDraftBtn = document.getElementById('bulkDraftBtn');
            const bulkTrashBtn = document.getElementById('bulkTrashBtn');
            const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
            const bulkPermanentDeleteBtn = document.getElementById('bulkPermanentDeleteBtn');
            const bulkCancelBtn = document.getElementById('bulkCancelBtn');
            
            // Select All functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const visibleCheckboxes = Array.from(postCheckboxes).filter(cb => {
                        return cb.closest('tr').style.display !== 'none';
                    });
                    
                    visibleCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    
                    updateBulkActionsToolbar();
                });
            }
            
            // Individual checkbox change
            postCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActionsToolbar);
            });
            
            // Update bulk actions toolbar visibility
            function updateBulkActionsToolbar() {
                const checkedBoxes = Array.from(postCheckboxes).filter(cb => cb.checked);
                const count = checkedBoxes.length;
                
                if (count > 0) {
                    bulkActionsToolbar.style.display = 'block';
                    selectedCountSpan.textContent = count;
                } else {
                    bulkActionsToolbar.style.display = 'none';
                }
                
                // Update Select All checkbox state
                const visibleCheckboxes = Array.from(postCheckboxes).filter(cb => {
                    return cb.closest('tr').style.display !== 'none';
                });
                const allVisibleChecked = visibleCheckboxes.length > 0 && 
                    visibleCheckboxes.every(cb => cb.checked);
                selectAllCheckbox.checked = allVisibleChecked;
            }
            
            // Bulk Publish
            if (bulkPublishBtn) {
                bulkPublishBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedPostIds();
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`Publish ${selectedIds.length} post(s)?`)) {
                        bulkAction('publish', selectedIds);
                    }
                });
            }
            
            // Bulk Draft
            if (bulkDraftBtn) {
                bulkDraftBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedPostIds();
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`Set ${selectedIds.length} post(s) as draft?`)) {
                        bulkAction('draft', selectedIds);
                    }
                });
            }
            
            // Bulk Trash
            if (bulkTrashBtn) {
                bulkTrashBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedPostIds();
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`Move ${selectedIds.length} post(s) to trash?`)) {
                        const reason = prompt('Alasan penghapusan (optional):') || '';
                        bulkAction('trash', selectedIds, reason);
                    }
                });
            }
            
            // Bulk Restore (for deleted posts)
            if (bulkRestoreBtn) {
                bulkRestoreBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedPostIds();
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`Restore ${selectedIds.length} post(s)?`)) {
                        bulkAction('restore', selectedIds);
                    }
                });
            }
            
            // Bulk Permanent Delete (for deleted posts)
            if (bulkPermanentDeleteBtn) {
                bulkPermanentDeleteBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedPostIds();
                    if (selectedIds.length === 0) return;
                    
                    if (confirm(`PERINGATAN! Hapus permanen ${selectedIds.length} post(s)? Tindakan ini tidak dapat dibatalkan!`)) {
                        const confirmText = prompt('Ketik "HAPUS" untuk konfirmasi:');
                        if (confirmText === 'HAPUS') {
                            bulkAction('permanent_delete', selectedIds);
                        } else {
                            alert('Penghapusan dibatalkan.');
                        }
                    }
                });
            }
            
            // Cancel bulk selection
            if (bulkCancelBtn) {
                bulkCancelBtn.addEventListener('click', function() {
                    postCheckboxes.forEach(cb => cb.checked = false);
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    updateBulkActionsToolbar();
                });
            }
            
            // Get selected post IDs
            function getSelectedPostIds() {
                return Array.from(postCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
            }
            
            // Perform bulk action
            function bulkAction(action, postIds, reason = '') {
                const formData = new FormData();
                formData.append('csrf_token', '<?= generateCSRFToken() ?>');
                formData.append('bulk_action', action);
                formData.append('post_ids', JSON.stringify(postIds));
                if (reason) formData.append('reason', reason);
                
                fetch('posts.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Bulk action error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    </script>

    <!-- NEW: Quick Edit Modal -->
    <div class="modal fade" id="quickEditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bolt me-2"></i>Quick Edit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="qePostId">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" id="qeTitle" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="qeStatus" class="form-select">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="qeCategory" class="form-select">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveQuickEdit">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Hapus Post
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="soft_delete">
                        <input type="hidden" name="post_id" id="deletePostId">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Post akan dipindahkan ke trash dan dapat di-restore kapan saja.
                        </div>
                        
                        <p>Anda yakin ingin menghapus post: <strong id="deletePostTitle"></strong>?</p>
                        
                        <div class="mb-3">
                            <label for="deleteReason" class="form-label">Alasan Penghapusan (opsional)</label>
                            <textarea class="form-control" id="deleteReason" name="delete_reason" rows="3" 
                                      placeholder="Masukkan alasan penghapusan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Hapus ke Trash
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Paraphrase Modal -->
    <div class="modal fade" id="paraphraseModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-language me-2"></i>Paraphrase Database Content
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="paraphrasePostId">
                    
                    <!-- Search & Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchPostsInput" placeholder="Cari artikel berdasarkan judul atau konten...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="text-muted">Total: <strong id="totalPostsCount">0</strong> artikel | Terpilih: <strong id="selectedCount">0</strong></small>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option value="all" selected>Semua Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterParaphraseStatus">
                                <option value="" selected>Semua Artikel</option>
                                <option value="paraphrased">✅ Sudah Diparafrase</option>
                                <option value="not_paraphrased">⚠️ Belum Diparafrase</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Bulk Action Toolbar -->
                    <div class="alert alert-info" id="bulkActionToolbar" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong id="bulkSelectedCount">0</strong> artikel terpilih
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success" id="bulkParaphraseTopBtn" style="display: none;">
                                    <i class="fas fa-language me-1"></i>Parafrase <span class="bulk-count-top">0</span> Terpilih
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn" style="display: none;">
                                    <i class="fas fa-times me-1"></i>Batal Pilihan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Posts List -->
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <input type="checkbox" class="form-check-input me-2" id="selectAllParaphrasePosts">
                                        <strong>Pilih Artikel untuk Diparafrase</strong>
                                    </div>
                                    <small class="text-muted" id="paraphraseStatsHeader"></small>
                                </div>
                                <div class="card-body p-0">
                                    <div id="postsListContainer" style="max-height: 400px; overflow-y: auto;">
                                        <div class="text-center p-4">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="mt-2 text-muted">Loading posts...</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bulk Paraphrase Button -->
                                <div id="bulkParaphraseBtn" class="card-footer bg-warning p-3" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label text-dark fw-bold mb-2">
                                            <i class="fas fa-sliders-h me-2"></i>Tingkat Perubahan Massal: <span id="bulkPercentageValue" class="text-primary">60</span>%
                                        </label>
                                        <input type="range" class="form-range" id="bulkParaphrasePercentage" min="30" max="90" value="60" step="10">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-dark">30% (Minim)</small>
                                            <small class="text-dark">60% (Normal)</small>
                                            <small class="text-dark">90% (Maksimal)</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-dark w-100 fw-bold" id="startBulkParaphrase">
                                        <i class="fas fa-magic me-2"></i>Parafrase <span class="bulk-count">0</span> Artikel Sekaligus
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview & Controls -->
                        <div class="col-md-7">
                            <div id="noPostSelected" class="text-center p-5">
                                <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Pilih artikel dari daftar untuk melihat preview dan melakukan paraphrase</p>
                            </div>
                            
                            <div id="postPreviewContainer" style="display: none;">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong id="selectedPostTitle"></strong>
                                        <div class="small text-muted" id="selectedPostMeta"></div>
                                    </div>
                                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                        <div id="selectedPostContent" class="small"></div>
                                    </div>
                                </div>
                                
                                <!-- Paraphrase Settings -->
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-cog me-2"></i>Pengaturan Paraphrase
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Persentase Perubahan: <strong id="percentageValue">50</strong>%</label>
                                            <input type="range" class="form-range" id="paraphrasePercentage" min="30" max="90" value="50" step="10">
                                            <small class="text-muted">30% = Minim, 90% = Maksimal</small>
                                        </div>
                                        
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-shield-alt me-2"></i>
                                            <strong>Proteksi Otomatis:</strong> Gambar, link, dan format HTML akan dilindungi
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary btn-lg" id="previewParaphraseBtn">
                                        <i class="fas fa-eye me-2"></i>Preview Paraphrase
                                    </button>
                                    <button type="button" class="btn btn-success btn-lg" id="applyParaphraseBtn" style="display: none;">
                                        <i class="fas fa-check me-2"></i>Terapkan ke Database
                                    </button>
                                </div>
                                
                                <!-- Progress -->
                                <div id="paraphraseProgress" style="display: none;" class="mt-3">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                             role="progressbar" style="width: 0%" id="progressBar">0%</div>
                                    </div>
                                    <p class="text-center text-muted mt-2">
                                        <i class="fas fa-spinner fa-spin me-2"></i><span id="progressText">Memproses...</span>
                                    </p>
                                </div>
                                
                                <!-- Preview Result -->
                                <div id="previewResultContainer" style="display: none;" class="mt-3">
                                    <div class="card">
                                        <div class="card-header bg-warning">
                                            <strong>Preview Hasil Paraphrase</strong>
                                            <button type="button" class="btn btn-sm btn-outline-dark float-end" id="closePreview">
                                                <i class="fas fa-times"></i> Tutup
                                            </button>
                                        </div>
                                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                            <div id="previewContent"></div>
                                        </div>
                                        <div class="card-footer">
                                            <div id="paraphraseStats" class="small"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Success Message -->
                                <div id="paraphraseSuccess" style="display: none;" class="mt-3">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Berhasil!</strong> Artikel telah diparafrase dan disimpan ke database.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle delete modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            const deletePostId = document.getElementById('deletePostId');
            const deletePostTitle = document.getElementById('deletePostTitle');
            
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const postId = button.getAttribute('data-post-id');
                const postTitle = button.getAttribute('data-post-title');
                
                deletePostId.value = postId;
                deletePostTitle.textContent = postTitle;
            });
        });
        
        // NEW: Quick Edit functionality
        const quickEditModal = new bootstrap.Modal(document.getElementById('quickEditModal'));
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('.quick-edit-btn')) {
                const btn = e.target.closest('.quick-edit-btn');
                document.getElementById('qePostId').value = btn.dataset.postId;
                document.getElementById('qeTitle').value = btn.dataset.postTitle;
                document.getElementById('qeStatus').value = btn.dataset.postStatus;
                document.getElementById('qeCategory').value = btn.dataset.postCategory;
                quickEditModal.show();
            }
        });
        
        document.getElementById('saveQuickEdit')?.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'quick_edit');
            formData.append('post_id', document.getElementById('qePostId').value);
            formData.append('title', document.getElementById('qeTitle').value);
            formData.append('status', document.getElementById('qeStatus').value);
            formData.append('category_id', document.getElementById('qeCategory').value);
            
            fetch('features_api.php', {method: 'POST', body: formData})
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        quickEditModal.hide();
                        alert('Post updated!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => alert('Failed to save'));
        });
        
        // Paraphrase functionality - NEW INTEGRATED SYSTEM
        const paraphraseModal = document.getElementById('paraphraseModal');
        let allPosts = [];
        let filteredPosts = [];
        let currentSelectedPost = null;
        let currentPreviewData = null;
        let searchTimeout = null;
        let selectedPostIds = []; // For bulk selection
        let selectAllCheckboxInitialized = false;
        
        // Initialize Select All checkbox when modal opens
        function initSelectAllCheckbox() {
            if (selectAllCheckboxInitialized) return;
            
            const selectAllCb = document.getElementById('selectAllParaphrasePosts');
            if (!selectAllCb) return;
            
            selectAllCb.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.paraphrase-checkbox');
                
                if (this.checked) {
                    // Select all
                    selectedPostIds = [];
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                        selectedPostIds.push(parseInt(cb.value));
                    });
                } else {
                    // Deselect all
                    selectedPostIds = [];
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                }
                
                updateBulkButtons();
            });
            
            selectAllCheckboxInitialized = true;
        }
        
        // Toggle post selection
        function togglePostSelection(postId, isChecked) {
            if (isChecked) {
                if (!selectedPostIds.includes(postId)) {
                    selectedPostIds.push(postId);
                }
            } else {
                selectedPostIds = selectedPostIds.filter(id => id !== postId);
            }
            updateBulkButtons();
            updateSelectAllCheckbox();
        }
        
        // Update Select All checkbox state
        function updateSelectAllCheckbox() {
            const selectAllCb = document.getElementById('selectAllParaphrasePosts');
            if (!selectAllCb) return; // Guard clause
            
            const checkboxes = document.querySelectorAll('.paraphrase-checkbox');
            if (checkboxes.length === 0) {
                selectAllCb.checked = false;
                selectAllCb.indeterminate = false;
                return;
            }
            
            const checkedCount = selectedPostIds.length;
            const totalCount = checkboxes.length;
            
            // Update checkbox state
            selectAllCb.checked = checkedCount === totalCount && checkedCount > 0;
            selectAllCb.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        }
        
        // Update bulk action buttons
        function updateBulkButtons() {
            const count = selectedPostIds.length;
            const statsHeader = document.getElementById('paraphraseStatsHeader');
            if (statsHeader) {
                statsHeader.textContent = count > 0 ? `${count} artikel dipilih` : '';
            }
            
            // Update bulk selected count
            const bulkCountEl = document.getElementById('bulkSelectedCount');
            if (bulkCountEl) {
                bulkCountEl.textContent = count;
            }
            
            // Show/hide top bulk buttons
            const topBtn = document.getElementById('bulkParaphraseTopBtn');
            const clearBtn = document.getElementById('clearSelectionBtn');
            if (topBtn) {
                topBtn.style.display = count > 0 ? 'inline-block' : 'none';
                const topCountSpan = topBtn.querySelector('.bulk-count-top');
                if (topCountSpan) {
                    topCountSpan.textContent = count;
                }
            }
            if (clearBtn) {
                clearBtn.style.display = count > 0 ? 'inline-block' : 'none';
            }
            
            // Show/hide bottom bulk paraphrase button
            const bulkBtn = document.getElementById('bulkParaphraseBtn');
            if (bulkBtn) {
                bulkBtn.style.display = count > 0 ? 'block' : 'none';
                const bulkCountSpan = bulkBtn.querySelector('.bulk-count');
                if (bulkCountSpan) {
                    bulkCountSpan.textContent = count;
                }
            }
        }
        
        // Bulk Paraphrase Handler
        document.getElementById('startBulkParaphrase')?.addEventListener('click', async function() {
            if (selectedPostIds.length === 0) {
                alert('Pilih artikel terlebih dahulu');
                return;
            }
            
            // Get percentage from BULK slider
            const percentage = parseInt(document.getElementById('bulkParaphrasePercentage')?.value || 60);
            
            // Show detail confirmation
            const articleList = selectedPostIds.map((id, idx) => {
                const post = allPosts.find(p => p.id == id);
                return `${idx + 1}. ${post ? post.title : 'ID: ' + id}`;
            }).join('\n');
            
            const confirmMsg = `🔄 PARAFRASE MASSAL\n\n` +
                `Artikel yang dipilih: ${selectedPostIds.length}\n` +
                `Persentase perubahan: ${percentage}%\n\n` +
                `Daftar artikel:\n${articleList}\n\n` +
                `⚠️ PERINGATAN:\n` +
                `• Proses ini TIDAK BISA DIBATALKAN\n` +
                `• Artikel akan langsung diupdate ke database\n` +
                `• Gambar dan format HTML akan tetap aman\n\n` +
                `Lanjutkan parafrase ${selectedPostIds.length} artikel sekaligus?`;
            
            const confirmed = confirm(confirmMsg);
            
            if (!confirmed) return;
            
            // Disable button
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            
            let successCount = 0;
            let failCount = 0;
            const totalPosts = selectedPostIds.length;
            
            // Process each post sequentially
            for (let i = 0; i < selectedPostIds.length; i++) {
                const postId = selectedPostIds[i];
                const currentNum = i + 1;
                
                this.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>Memparafrase ${currentNum}/${totalPosts}...`;
                
                try {
                    const response = await fetch('api/paraphrase_api.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'paraphrase',
                            post_id: postId,
                            percentage: percentage,
                            preview: false
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        successCount++;
                    } else {
                        failCount++;
                        console.error(`Failed to paraphrase post ${postId}:`, data.message);
                    }
                } catch (err) {
                    failCount++;
                    console.error(`Error paraphrasing post ${postId}:`, err);
                }
            }
            
            // Reset
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-magic me-2"></i>Parafrase <span class="bulk-count">' + selectedPostIds.length + '</span> Artikel Sekaligus';
            
            // Clear selection
            selectedPostIds = [];
            updateBulkButtons();
            document.getElementById('selectAllParaphrasePosts').checked = false;
            
            // Reload list
            loadPostsList();
            
            // Show result
            alert(`Selesai!\nBerhasil: ${successCount}\nGagal: ${failCount}`);
        });
        
        // Top bulk paraphrase button
        document.getElementById('bulkParaphraseTopBtn')?.addEventListener('click', function() {
            // Trigger the main bulk paraphrase button
            document.getElementById('startBulkParaphrase')?.click();
        });
        
        // Clear selection button
        document.getElementById('clearSelectionBtn')?.addEventListener('click', function() {
            selectedPostIds = [];
            document.querySelectorAll('.paraphrase-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllParaphrasePosts').checked = false;
            updateBulkButtons();
            updateSelectAllCheckbox();
        });
        
        // Load posts when modal opens
        paraphraseModal.addEventListener('show.bs.modal', function(event) {
            initSelectAllCheckbox(); // Initialize checkbox handler
            loadPostsList();
        });
        
        // Load all posts with search
        function loadPostsList(searchQuery = '') {
            const status = document.getElementById('filterStatus')?.value || 'all';
            const paraphraseStatus = document.getElementById('filterParaphraseStatus')?.value || '';
            
            console.log('Loading posts with:', { status, searchQuery, paraphraseStatus });
            
            fetch('api/paraphrase_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'get_posts',
                    status: status === 'all' ? '' : status,
                    search: searchQuery,
                    paraphrase_status: paraphraseStatus
                })
            })
            .then(r => {
                console.log('Response status:', r.status);
                if (!r.ok) {
                    throw new Error('HTTP error ' + r.status);
                }
                return r.json();
            })
            .then(data => {
                console.log('Posts loaded:', data);
                if (data.success) {
                    allPosts = data.posts || [];
                    filteredPosts = allPosts;
                    
                    const totalPostsEl = document.getElementById('totalPostsCount');
                    if (totalPostsEl) {
                        totalPostsEl.textContent = data.totalPosts || 0;
                    }
                    
                    // Calculate paraphrase statistics
                    const paraphrasedCount = allPosts.filter(p => p.is_paraphrased == 1).length;
                    const notParaphrasedCount = allPosts.length - paraphrasedCount;
                    
                    // Update stats header
                    const statsHeader = document.getElementById('paraphraseStatsHeader');
                    if (statsHeader) {
                        statsHeader.innerHTML = `
                            <span class="badge bg-success me-1">${paraphrasedCount} Sudah</span>
                            <span class="badge bg-warning">${notParaphrasedCount} Belum</span>
                        `;
                    }
                    
                    // Update filter dropdown with counts
                    const filterDropdown = document.getElementById('filterParaphraseStatus');
                    if (filterDropdown) {
                        const currentValue = filterDropdown.value;
                        filterDropdown.innerHTML = `
                            <option value="">Semua Artikel (${allPosts.length})</option>
                            <option value="paraphrased">✅ Sudah Diparafrase (${paraphrasedCount})</option>
                            <option value="not_paraphrased">⚠️ Belum Diparafrase (${notParaphrasedCount})</option>
                        `;
                        filterDropdown.value = currentValue; // Restore selected value
                    }
                    
                    renderPostsList();
                } else {
                    showError('Gagal memuat posts: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error loading posts:', err);
                showError('Error loading posts');
            });
        }
        
        // Render posts list
        function renderPostsList() {
            const container = document.getElementById('postsListContainer');
            
            if (filteredPosts.length === 0) {
                container.innerHTML = `
                    <div class="text-center p-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted">Tidak ada artikel ditemukan</p>
                    </div>
                `;
                updateBulkButtons();
                return;
            }
            
            let html = '<div class="list-group list-group-flush">';
            filteredPosts.forEach(post => {
                const excerpt = stripHtml(post.content).substring(0, 100) + '...';
                const isActive = currentSelectedPost && currentSelectedPost.id === post.id ? 'active' : '';
                const isChecked = selectedPostIds.includes(post.id) ? 'checked' : '';
                
                // Check if paraphrased
                const paraphraseBadge = post.is_paraphrased == 1 
                    ? `<span class="badge bg-success ms-2" title="Diparafrase pada ${post.paraphrased_at_formatted || 'N/A'}">
                         <i class="fas fa-check-circle me-1"></i>Sudah Diparafrase ${post.paraphrase_percentage || 0}%
                       </span>`
                    : '';
                
                const paraphraseCount = post.paraphrase_count > 0 
                    ? `<span class="badge bg-info ms-1" title="Jumlah kali diparafrase">${post.paraphrase_count}x</span>`
                    : '';
                
                html += `
                    <a href="#" class="list-group-item list-group-item-action ${isActive} d-flex align-items-start" data-post-id="${post.id}">
                        <input type="checkbox" class="form-check-input me-2 mt-1 paraphrase-checkbox" 
                               value="${post.id}" ${isChecked}>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${highlightText(post.title)}</h6>
                                <small class="text-muted">#${post.id}</small>
                            </div>
                            <p class="mb-1 small text-muted">${highlightText(excerpt)}</p>
                            <small class="text-muted">
                                <i class="far fa-calendar me-1"></i>${formatDate(post.created_at)}
                                <span class="badge bg-secondary ms-2">${post.status}</span>
                                ${paraphraseBadge}
                                ${paraphraseCount}
                            </small>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
            
            // Add click handlers for list items
            container.querySelectorAll('.list-group-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (e.target.classList.contains('paraphrase-checkbox')) return;
                    if (e.target.closest('.paraphrase-checkbox')) return; // Also check parent
                    e.preventDefault();
                    const postId = this.dataset.postId;
                    loadPostDetails(postId);
                });
            });
            
            // Add checkbox change handlers using event delegation
            container.querySelectorAll('.paraphrase-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function(e) {
                    e.stopPropagation(); // Prevent triggering item click
                    togglePostSelection(parseInt(this.value), this.checked);
                });
            });
            
            // Also update Select All checkbox state after loading
            updateSelectAllCheckbox();
            
            updateBulkButtons();
        }
        
        // Load post details
        function loadPostDetails(postId) {
            fetch('api/paraphrase_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'get_post',
                    post_id: postId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.post) {
                    currentSelectedPost = data.post;
                    displayPostPreview(data.post);
                    renderPostsList(); // Re-render to update active state
                } else {
                    showError('Gagal memuat detail post');
                }
            })
            .catch(err => {
                console.error('Error loading post details:', err);
                showError('Error loading post details');
            });
        }
        
        // Display post preview
        function displayPostPreview(post) {
            document.getElementById('noPostSelected').style.display = 'none';
            document.getElementById('postPreviewContainer').style.display = 'block';
            
            document.getElementById('selectedPostTitle').textContent = post.title;
            document.getElementById('selectedPostMeta').innerHTML = `
                ID: ${post.id} | Dibuat: ${formatDate(post.created_at)} | Status: <span class="badge bg-secondary">${post.status}</span>
            `;
            document.getElementById('selectedPostContent').innerHTML = post.content.substring(0, 500) + '...';
            
            // Reset states
            document.getElementById('previewResultContainer').style.display = 'none';
            document.getElementById('paraphraseSuccess').style.display = 'none';
            document.getElementById('applyParaphraseBtn').style.display = 'none';
            currentPreviewData = null;
        }
        
        // Search functionality
        document.getElementById('searchPostsInput')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadPostsList(e.target.value);
            }, 300);
        });
        
        document.getElementById('clearSearch')?.addEventListener('click', function() {
            document.getElementById('searchPostsInput').value = '';
            loadPostsList('');
        });
        
        document.getElementById('filterStatus')?.addEventListener('change', function() {
            loadPostsList(document.getElementById('searchPostsInput').value);
        });
        
        document.getElementById('filterParaphraseStatus')?.addEventListener('change', function() {
            loadPostsList(document.getElementById('searchPostsInput').value);
        });
        
        // Percentage slider
        document.getElementById('paraphrasePercentage')?.addEventListener('input', function(e) {
            document.getElementById('percentageValue').textContent = e.target.value;
        });
        
        // Bulk percentage slider
        document.getElementById('bulkParaphrasePercentage')?.addEventListener('input', function(e) {
            document.getElementById('bulkPercentageValue').textContent = e.target.value;
        });
        
        // Preview paraphrase
        document.getElementById('previewParaphraseBtn')?.addEventListener('click', function() {
            if (!currentSelectedPost) {
                alert('Pilih artikel terlebih dahulu');
                return;
            }
            
            const percentage = document.getElementById('paraphrasePercentage').value;
            
            // Show progress
            showProgress('Membuat preview paraphrase...', 0);
            this.disabled = true;
            
            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.random() * 20;
                    if (progress > 90) progress = 90;
                    updateProgress(progress);
                }
            }, 300);
            
            fetch('api/paraphrase_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'paraphrase',
                    post_id: currentSelectedPost.id,
                    percentage: parseInt(percentage),
                    preview: true
                })
            })
            .then(r => r.json())
            .then(data => {
                clearInterval(progressInterval);
                updateProgress(100);
                
                setTimeout(() => {
                    hideProgress();
                    this.disabled = false;
                    
                    if (data.success) {
                        currentPreviewData = data;
                        showPreviewResult(data);
                    } else {
                        showError('Preview gagal: ' + (data.message || 'Unknown error'));
                    }
                }, 500);
            })
            .catch(err => {
                clearInterval(progressInterval);
                hideProgress();
                this.disabled = false;
                console.error('Preview error:', err);
                showError('Error saat preview');
            });
        });
        
        // Apply paraphrase
        document.getElementById('applyParaphraseBtn')?.addEventListener('click', function() {
            if (!currentPreviewData || !currentSelectedPost) {
                alert('Preview terlebih dahulu sebelum apply');
                return;
            }
            
            if (!confirm('Yakin ingin menerapkan paraphrase ini ke database? Proses ini tidak dapat di-undo.')) {
                return;
            }
            
            const percentage = document.getElementById('paraphrasePercentage').value;
            
            showProgress('Menerapkan paraphrase ke database...', 0);
            this.disabled = true;
            
            let progress = 0;
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.random() * 20;
                    if (progress > 90) progress = 90;
                    updateProgress(progress);
                }
            }, 300);
            
            fetch('api/paraphrase_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'paraphrase',
                    post_id: currentSelectedPost.id,
                    percentage: parseInt(percentage),
                    preview: false
                })
            })
            .then(r => r.json())
            .then(data => {
                clearInterval(progressInterval);
                updateProgress(100);
                
                setTimeout(() => {
                    hideProgress();
                    this.disabled = false;
                    
                    if (data.success) {
                        document.getElementById('previewResultContainer').style.display = 'none';
                        document.getElementById('paraphraseSuccess').style.display = 'block';
                        
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showError('Apply gagal: ' + (data.message || 'Unknown error'));
                    }
                }, 500);
            })
            .catch(err => {
                clearInterval(progressInterval);
                hideProgress();
                this.disabled = false;
                console.error('Apply error:', err);
                showError('Error saat apply');
            });
        });
        
        // Show preview result
        function showPreviewResult(data) {
            document.getElementById('previewResultContainer').style.display = 'block';
            document.getElementById('applyParaphraseBtn').style.display = 'block';
            
            document.getElementById('previewContent').innerHTML = data.paraphrased_content || '';
            
            const stats = data.stats || {};
            document.getElementById('paraphraseStats').innerHTML = `
                <div class="row text-center">
                    <div class="col">
                        <strong>${stats.replaced_words || 0}</strong>
                        <div class="text-muted">Kata Diganti</div>
                    </div>
                    <div class="col">
                        <strong>${stats.total_words || 0}</strong>
                        <div class="text-muted">Total Kata</div>
                    </div>
                    <div class="col">
                        <strong>${stats.images_protected || 0}</strong>
                        <div class="text-muted">Gambar Dilindungi</div>
                    </div>
                    <div class="col">
                        <strong>${stats.percentage || 0}%</strong>
                        <div class="text-muted">Perubahan</div>
                    </div>
                </div>
            `;
        }
        
        // Close preview
        document.getElementById('closePreview')?.addEventListener('click', function() {
            document.getElementById('previewResultContainer').style.display = 'none';
            document.getElementById('applyParaphraseBtn').style.display = 'none';
            currentPreviewData = null;
        });
        
        // Helper functions
        function showProgress(text, percent) {
            document.getElementById('paraphraseProgress').style.display = 'block';
            document.getElementById('progressText').textContent = text;
            updateProgress(percent);
        }
        
        function updateProgress(percent) {
            const bar = document.getElementById('progressBar');
            bar.style.width = percent + '%';
            bar.textContent = Math.round(percent) + '%';
        }
        
        function hideProgress() {
            document.getElementById('paraphraseProgress').style.display = 'none';
        }
        
        function showError(message) {
            alert('Error: ' + message);
        }
        
        function stripHtml(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        
        function highlightText(text) {
            const searchQuery = document.getElementById('searchPostsInput')?.value || '';
            if (!searchQuery) return text;
            
            const regex = new RegExp('(' + searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }
    </script>

</body>
</html>