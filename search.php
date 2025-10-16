<?php
require_once 'config_modern.php';

// Get search query
$searchQuery = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12; // Posts per page
$offset = ($page - 1) * $limit;

// Initialize results
$posts = [];
$totalResults = 0;
$searchExecuted = false;

// Perform search if query is provided
if (!empty($searchQuery) && strlen($searchQuery) >= 1) {
    $searchExecuted = true;
    
    try {
        // Simplified query - just search in title and make sure it's published
        $searchParam = "%{$searchQuery}%";
        
        // Count total results
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM posts p
            WHERE p.status = 'published' AND p.deleted_at IS NULL
            AND p.title LIKE ?
        ");
        $countStmt->execute([$searchParam]);
        $totalResults = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get search results
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image, COALESCE(p.view_count, 0) as views,
                   COALESCE(c.name, 'Uncategorized') as category_name, 
                   COALESCE(c.slug, 'uncategorized') as category_slug
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published' AND p.deleted_at IS NULL
            AND p.title LIKE ?
            ORDER BY p.view_count DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$searchParam, $limit, $offset]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        $posts = [];
        $totalResults = 0;
    }
}

// Calculate pagination
$totalPages = ceil($totalResults / $limit);

// Function to highlight search terms
function highlightSearchTerms($text, $search) {
    if (empty($search)) return $text;
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $text);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $searchExecuted ? "Hasil Pencarian: " . htmlspecialchars($searchQuery) . " - " : "Pencarian - " ?>DONAN22</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <meta name="description" content="<?= $searchExecuted ? "Hasil pencarian untuk: " . htmlspecialchars($searchQuery) : "Cari software dan blog di DONAN22" ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Live Search CSS -->
    <link href="<?= SITE_URL ?>/assets/css/live-search.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .main-header {
            background: linear-gradient(135deg, #4f6bebff 0%, #7a4aaaff 100%);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        
        .navbar-brand:hover {
            color: #f0f9ff !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }
        
        .nav-link {
            font-weight: 500;
            color: #ffffff !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .search-header {
            background: linear-gradient(135deg, #4f6bebff 0%, #7a4aaaff 100%);
            color: white;
            padding: 2.5rem 0 2rem;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .search-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        
        .search-box {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .search-box .input-group {
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 50px;
            overflow: hidden;
        }
        
        .search-box .form-control {
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            background: white;
        }
        
        .search-box .form-control:focus {
            box-shadow: none;
            outline: none;
        }
        
        .search-box .btn {
            padding: 1rem 2rem;
            font-weight: 600;
            border: none;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            transition: all 0.3s ease;
        }
        
        .search-box .btn:hover {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            transform: scale(1.02);
        }
        
        .search-stats {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .search-stats h5 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .search-stats strong {
            color: #667eea;
        }
        
        .post-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .post-image {
            position: relative;
            width: 100%;
            padding-top: 60%; /* 5:3 Aspect Ratio - More rectangular */
            overflow: hidden;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 0;
        }
        
        .post-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .post-card:hover .post-image img {
            transform: scale(1.08);
        }
        
        .post-image-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            opacity: 0.9;
        }
        
        .post-image-placeholder i {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .post-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 180px; /* Fixed minimum height */
        }
        
        .post-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 0.75rem;
            line-height: 1.5;
            height: 3rem; /* Fixed height - exactly 2 lines */
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .post-title a {
            color: #2d3436 !important;
            text-decoration: none !important;
            transition: color 0.3s ease;
        }
        
        .post-title a:hover {
            color: #667eea !important;
        }
        
        .post-excerpt {
            color: #636e72;
            font-size: 0.875rem;
            margin-bottom: auto;
            line-height: 1.6;
            height: 2.8rem; /* Fixed height - exactly 2 lines */
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid #f1f3f5;
        }
        
        .post-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .category-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .post-card:hover .category-badge {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
        }
        
        .category-badge.badge-download {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        
        .category-badge.badge-tutorial {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .no-results i {
            font-size: 5rem;
            color: #e5e7eb;
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .no-results h3 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .no-results p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        mark {
            background: linear-gradient(120deg, #fef3c7 0%, #fde68a 100%);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
            color: #92400e;
        }
        
        /* Grid Adjustments */
        .row.g-4 {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 1.5rem;
        }
        
        /* Responsive Design */
        @media (max-width: 1400px) {
            .post-content {
                padding: 1.25rem;
                min-height: 170px;
            }
        }
        
        @media (max-width: 992px) {
            .post-image {
                padding-top: 65%; /* Slightly taller on tablets */
            }
            
            .post-content {
                padding: 1.25rem;
                min-height: 160px;
            }
        }
        
        @media (max-width: 768px) {
            .search-header {
                padding: 2rem 0 1.5rem;
            }
            
            .search-header h1 {
                font-size: 1.5rem;
            }
            
            .post-image {
                padding-top: 70%; /* Better ratio on mobile */
            }
            
            .post-content {
                padding: 1rem;
                min-height: 150px;
            }
            
            .post-title {
                font-size: 0.95rem;
                min-height: 2.8rem;
            }
            
            .post-excerpt {
                font-size: 0.85rem;
                min-height: 2.6rem;
            }
            
            .search-box .form-control {
                font-size: 0.9rem;
                padding: 0.875rem 1.25rem;
            }
            
            .search-box .btn {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .category-badge {
                font-size: 0.7rem;
                padding: 4px 10px;
            }
            
            .row.g-4 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .post-image {
                padding-top: 75%; /* Square-ish on small mobile */
            }
            
            .post-image-placeholder i {
                font-size: 3rem;
            }
            
            .post-content {
                min-height: 140px;
            }
            
            .search-stats {
                padding: 1rem;
            }
            
            .page-link {
                padding: 0.4rem 0.75rem;
                font-size: 0.875rem;
            }
        }
        
        .pagination {
            justify-content: center;
            margin: 3rem 0;
            gap: 0.5rem;
        }
        
        .page-item {
            margin: 0 0.25rem;
        }
        
        .page-link {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            color: #667eea;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .page-item.disabled .page-link {
            background-color: #f3f4f6;
            border-color: #e5e7eb;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?= SITE_URL ?>/">
                    <i class="fas fa-rocket me-2"></i>
                    <span>DONAN22</span>
                </a>
            
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto me-4">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/software"><i class="fas fa-download me-1"></i> Software</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/blog"><i class="fas fa-graduation-cap me-1"></i> Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/mobile-apps"><i class="fas fa-mobile-alt me-1"></i> Mobile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/windows-software"><i class="fab fa-windows me-1"></i> Windows</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/mac-software"><i class="fab fa-apple me-1"></i> Mac</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/categories.php"><i class="fas fa-th-large me-1"></i> Kategori</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <div class="text-center">
                <h1 class="mb-3">
                    <i class="fas fa-search me-2"></i>
                    Cari Software & Blog
                </h1>
                <p class="mb-4">Temukan software dan blog terbaik</p>
                
                <!-- Search Form -->
                <div class="search-box">
                    <form method="GET" action="">
                        <div class="input-group input-group-lg">
                            <input 
                                type="text" 
                                class="form-control" 
                                name="q" 
                                value="<?= htmlspecialchars($searchQuery) ?>" 
                                placeholder="Ketik kata kunci pencarian..."
                                required
                            >
                            <button class="btn btn-warning" type="submit">
                                <i class="fas fa-search me-1"></i>
                                Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        
        <?php if ($searchExecuted): ?>
            <!-- Search Statistics -->
            <div class="search-stats">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">Hasil Pencarian untuk: <strong>"<?= htmlspecialchars($searchQuery) ?>"</strong></h5>
                        <p class="mb-0 text-muted">
                            Ditemukan <?= number_format($totalResults) ?> hasil
                            <?php if ($totalPages > 1): ?>
                                (Halaman <?= $page ?> dari <?= $totalPages ?>)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($totalResults > 0): ?>
                <!-- Search Results -->
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                            <div class="post-card">
                                <!-- Post Image -->
                                <div class="post-image">
                                    <?php if (!empty($post['featured_image'])): ?>
                                        <!-- Badge overlay for category -->
                                        <span class="category-badge badge-download">
                                            <i class="fas fa-folder me-1"></i><?= htmlspecialchars($post['category_name']) ?>
                                        </span>
                                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                             alt="<?= htmlspecialchars($post['title']) ?>"
                                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'post-image-placeholder\'><i class=\'fas fa-image\'></i></div>';">
                                    <?php else: ?>
                                        <div class="post-image-placeholder">
                                            <!-- Badge overlay for category -->
                                            <span class="category-badge badge-download">
                                                <i class="fas fa-folder me-1"></i><?= htmlspecialchars($post['category_name']) ?>
                                            </span>
                                            <?php
                                            // Icon detection based on title
                                            $title_lower = strtolower($post['title']);
                                            if (strpos($title_lower, 'indesign') !== false || strpos($title_lower, 'adobe') !== false) {
                                                echo '<i class="fab fa-adobe"></i>';
                                            } elseif (strpos($title_lower, 'photoshop') !== false) {
                                                echo '<i class="fas fa-paint-brush"></i>';
                                            } elseif (strpos($title_lower, 'office') !== false || strpos($title_lower, 'microsoft') !== false) {
                                                echo '<i class="fas fa-file-alt"></i>';
                                            } elseif (strpos($title_lower, 'windows') !== false) {
                                                echo '<i class="fab fa-windows"></i>';
                                            } elseif (strpos($title_lower, 'kmspico') !== false || strpos($title_lower, 'kms') !== false || strpos($title_lower, 'activator') !== false) {
                                                echo '<i class="fas fa-key"></i>';
                                            } elseif (strpos($title_lower, 'game') !== false) {
                                                echo '<i class="fas fa-gamepad"></i>';
                                            } elseif (strpos($title_lower, 'linux') !== false) {
                                                echo '<i class="fab fa-linux"></i>';
                                            } elseif (strpos($title_lower, 'mac') !== false) {
                                                echo '<i class="fab fa-apple"></i>';
                                            } else {
                                                echo '<i class="fas fa-download"></i>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Post Content -->
                                <div class="post-content">
                                    <h3 class="post-title">
                                        <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none text-dark">
                                            <?= highlightSearchTerms(htmlspecialchars($post['title']), $searchQuery) ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($post['excerpt'])): ?>
                                        <div class="post-excerpt">
                                            <?= highlightSearchTerms(htmlspecialchars(substr(strip_tags($post['excerpt']), 0, 100)), $searchQuery) ?>
                                            <?= strlen(strip_tags($post['excerpt'])) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-meta">
                                        <span class="text-muted small">
                                            <i class="fas fa-eye me-1"></i>
                                            <?= number_format($post['views']) ?> views
                                        </span>
                                        <span class="text-muted small">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('d M Y', strtotime($post['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Search Results Pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?q=<?= urlencode($searchQuery) ?>&page=<?= $page - 1 ?>">
                                        <i class="fas fa-chevron-left me-1"></i>
                                        Sebelumnya
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?q=<?= urlencode($searchQuery) ?>&page=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?q=<?= urlencode($searchQuery) ?>&page=<?= $page + 1 ?>">
                                        Selanjutnya
                                        <i class="fas fa-chevron-right ms-1"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Tidak Ada Hasil Ditemukan</h3>
                    <p class="text-muted">
                        Tidak ditemukan hasil untuk "<strong><?= htmlspecialchars($searchQuery) ?></strong>". 
                        <br>Coba gunakan kata kunci yang berbeda.
                    </p>
                    
                    <div class="mt-4">
                        <h6>Contoh Pencarian Populer:</h6>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                            <a href="?q=KMSpico" class="btn btn-outline-primary btn-sm">KMSpico Final</a>
                            <a href="?q=Adobe" class="btn btn-outline-primary btn-sm">Adobe Photoshop</a>
                            <a href="?q=Microsoft" class="btn btn-outline-primary btn-sm">Microsoft Office</a>
                            <a href="?q=Windows" class="btn btn-outline-primary btn-sm">Windows Activator</a>
                            <a href="?q=Video" class="btn btn-outline-primary btn-sm">Video Editor</a>
                            <a href="?q=Antivirus" class="btn btn-outline-primary btn-sm">Antivirus</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Initial Search State -->
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Mulai Pencarian</h3>
                <p class="text-muted">
                    Masukkan kata kunci di atas untuk mulai mencari software atau blog.
                </p>
                
                <div class="mt-4">
                    <h6>Contoh Pencarian Populer:</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                        <a href="?q=KMSpico" class="btn btn-outline-primary btn-sm">KMSpico Final</a>
                        <a href="?q=Adobe" class="btn btn-outline-primary btn-sm">Adobe Photoshop</a>
                        <a href="?q=Microsoft" class="btn btn-outline-primary btn-sm">Microsoft Office</a>
                        <a href="?q=Windows" class="btn btn-outline-primary btn-sm">Windows Activator</a>
                        <a href="?q=Video" class="btn btn-outline-primary btn-sm">Video Editor</a>
                        <a href="?q=Antivirus" class="btn btn-outline-primary btn-sm">Antivirus</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-rocket me-2"></i>DONAN22</h5>
                    <p class="mb-3">Platform download software dan blog IT terlengkap dan terpercaya. Semua konten gratis dan aman untuk digunakan.</p>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Menu</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="categories.php" class="text-light text-decoration-none">Categories</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Kontak</h5>
                    <p class="mb-2"><i class="fas fa-globe me-2"></i>localhost/donan22</p>
                    <p class="mb-0">Download software gratis dengan aman dan terpercaya.</p>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 DONAN22. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Made with <i class="fas fa-heart text-danger"></i> for download community</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Live Search JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/live-search.js"></script>
</body>
</html>