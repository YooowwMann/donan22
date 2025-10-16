<?php
/**
 * Halaman Categories - Menampilkan semua kategori
 */

require_once 'config_modern.php';

// Check maintenance mode
checkMaintenanceMode();

// Include config
$configFile = __DIR__ . '/config_modern.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Fallback config
    $host = 'localhost';
    $dbname = 'donan22';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Ambil semua kategori dari database
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON (c.id = p.category_id OR c.id = p.secondary_category_id) 
            AND p.status = 'published' 
            AND p.deleted_at IS NULL
        GROUP BY c.id
        ORDER BY post_count DESC, c.name ASC
    ");
    $stmt->execute();
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format categories dengan icon dan color default jika tidak ada
    $mainCategories = [];
    $defaultIcons = [
        'software' => 'fas fa-laptop-code',
        'tool' => 'fas fa-tools',
        'tutorial' => 'fas fa-graduation-cap',
        'adobe' => 'fab fa-adobe',
        'microsoft' => 'fab fa-microsoft',
        'windows' => 'fab fa-windows',
        'mac' => 'fab fa-apple',
        'android' => 'fab fa-android',
        'mobile' => 'fas fa-mobile-alt',
        'web' => 'fas fa-globe',
        'design' => 'fas fa-palette',
        'video' => 'fas fa-video',
        'audio' => 'fas fa-music',
        'office' => 'fas fa-file-alt',
        'security' => 'fas fa-shield-alt',
        'network' => 'fas fa-network-wired'
    ];
    
    $defaultColors = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
    ];
    
    foreach ($allCategories as $index => $category) {
        $slug = strtolower($category['slug']);
        
        // Tentukan icon berdasarkan slug
        $icon = $category['icon'] ?? 'fas fa-folder';
        if (empty($category['icon'])) {
            foreach ($defaultIcons as $keyword => $defaultIcon) {
                if (strpos($slug, $keyword) !== false) {
                    $icon = $defaultIcon;
                    break;
                }
            }
        }
        
        // Tentukan color
        $color = $category['color'] ?? $defaultColors[$index % count($defaultColors)];
        
        // Tentukan type berdasarkan nama kategori
        $type = 'General';
        $name_lower = strtolower($category['name']);
        if (strpos($name_lower, 'software') !== false || strpos($name_lower, 'tool') !== false) {
            $type = 'Software';
        } elseif (strpos($name_lower, 'tutorial') !== false) {
            $type = 'Tutorial';
        } elseif (strpos($name_lower, 'mobile') !== false || strpos($name_lower, 'android') !== false) {
            $type = 'Mobile';
        }
        
        $mainCategories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?: 'Koleksi konten ' . $category['name'],
            'icon' => $icon,
            'color' => $color,
            'type' => $type,
            'actual_post_count' => $category['post_count']
        ];
    }
    
} catch (PDOException $e) {
    error_log("Database error in categories.php: " . $e->getMessage());
    $mainCategories = [];
}

// Kelompokkan berdasarkan type untuk tampilan
$categoriesByType = [];
foreach ($mainCategories as $category) {
    $type = $category['type'];
    if (!isset($categoriesByType[$type])) {
        $categoriesByType[$type] = [];
    }
    $categoriesByType[$type][] = $category;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Explore All Categories | DONAN22</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <meta name="description" content="Jelajahi semua kategori software dan blog di DONAN22. Temukan konten sesuai kebutuhan Anda dengan mudah dan cepat.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Live Search CSS -->
    <link href="<?= SITE_URL ?>/assets/css/live-search.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
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
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        /* Categories Grid */
        .categories-container {
            padding: 80px 0;
        }
        
        .category-type-section {
            margin-bottom: 60px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        .section-title p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        /* Category Cards */
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
            height: 100%;
            cursor: pointer;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--category-color, linear-gradient(135deg, var(--primary-color), var(--secondary-color)));
        }
        
        .category-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            border-color: var(--category-color, var(--primary-color));
        }
        
        /* Category Color Variations */
        .category-card.windows { --category-color: #0078d4; }
        .category-card.mac { --category-color: #000000; }
        .category-card.software { --category-color: #6366f1; }
        .category-card.android { --category-color: #3ddc84; }
        .category-card.tutorials { --category-color: #8b5cf6; }
        
        .category-card.windows:hover { box-shadow: 0 25px 50px rgba(0, 120, 212, 0.3); }
        .category-card.mac:hover { box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3); }
        .category-card.software:hover { box-shadow: 0 25px 50px rgba(99, 102, 241, 0.3); }
        .category-card.android:hover { box-shadow: 0 25px 50px rgba(61, 220, 132, 0.3); }
        .category-card.tutorials:hover { box-shadow: 0 25px 50px rgba(139, 92, 246, 0.3); }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--category-color, linear-gradient(135deg, var(--primary-color), var(--secondary-color)));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .category-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .category-description {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }
        
        .post-count {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: var(--dark-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .view-category {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .view-category:hover {
            color: var(--secondary-color);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin: 40px 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .empty-state p {
            color: #6b7280;
            margin-bottom: 30px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .category-card {
                padding: 30px 20px;
            }
            
            .categories-container {
                padding: 40px 0;
            }
        }
        
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .page-header p {
            font-size: 1.1rem;
            color: #6b7280;
        }
        
        .category-section {
            margin-bottom: 50px;
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .section-header h2 {
            font-weight: 700;
            margin: 0;
        }
        
        .category-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        
        .category-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
        }
        
        .category-card h4 {
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .category-card p {
            color: #6b7280;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .category-count {
            background: var(--accent-color);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .category-link {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .category-link:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #6b7280;
            margin-bottom: 25px;
        }
        
        .setup-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .setup-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        /* Category Icons by Type */
        .software-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
        .tutorial-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .mobile-icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }
            
            .category-card {
                padding: 20px;
            }
            
            .category-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <i class="fas fa-rocket me-2"></i>
                    <span>DONAN22</span>
                </a>
            
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto me-4">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
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
                            <a class="nav-link active" href="categories.php"><i class="fas fa-th-large me-1"></i> Kategori</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Search Bar Below Nav -->
        <div class="container py-2">
            <form action="<?= SITE_URL ?>/search.php" method="GET" id="searchForm" class="live-search-container position-relative" style="max-width: 400px; margin: 0 auto;">
                <input 
                    type="search" 
                    name="q"
                    class="form-control form-control-sm" 
                    id="live-search-input"
                    placeholder="Cari software..." 
                    autocomplete="off"
                    style="padding-right: 35px; border-radius: 20px;"
                >
                <button class="btn btn-sm position-absolute" type="submit" id="searchButton" style="right: 5px; top: 50%; transform: translateY(-50%); border: none; background: transparent;">
                    <i class="fas fa-search text-primary"></i>
                </button>
                
                <!-- Live Search Results Dropdown -->
                <div class="live-search-results" id="live-search-results"></div>
            </form>
        </div>
    </header>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1><i class="fas fa-th-large me-3"></i>Main Categories</h1>
                <p>Jelajahi kategori utama kami: Windows, Mac, Software, Android Apps, dan Blog</p>
                <div class="mt-4">
                    <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                        <i class="fab fa-windows me-1"></i>Windows Software
                    </span>
                    <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                        <i class="fab fa-apple me-1"></i>Mac Software
                    </span>
                    <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                        <i class="fas fa-tools me-1"></i>Software Tools
                    </span>
                    <span class="badge bg-light text-dark me-2 fs-6 px-3 py-2">
                        <i class="fab fa-android me-1"></i>Android Apps
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Container -->
    <div class="categories-container">
        <div class="container">
            <!-- 5 Main Categories -->
            <div class="category-type-section">
                <div class="section-title">
                    <h2><i class="fas fa-star me-2"></i>Main Categories</h2>
                    <p>Pilih kategori yang Anda butuhkan untuk menemukan konten terbaik</p>
                </div>
                
                <div class="row g-4 justify-content-center">
                    <?php foreach ($mainCategories as $category): ?>
                        <div class="col-lg-4 col-md-6" onclick="window.location.href='<?= SITE_URL ?>/category/<?= urlencode($category['slug']) ?>'">
                            <div class="category-card <?= strtolower(str_replace([' ', '-'], '', $category['name'])) ?>" 
                                 style="--category-color: <?= $category['color'] ?>;">
                                <div class="category-icon">
                                    <i class="<?= $category['icon'] ?>"></i>
                                </div>
                                
                                <h4><?= htmlspecialchars($category['name']) ?></h4>
                                
                                <div class="category-description">
                                    <?= htmlspecialchars($category['description']) ?>
                                </div>
                                
                                <div class="category-stats">
                                    <span class="post-count">
                                        <i class="fas fa-file-alt me-1"></i><?= number_format($category['actual_post_count']) ?> items
                                    </span>
                                    
                                    <a href="<?= SITE_URL ?>/category/<?= urlencode($category['slug']) ?>" class="view-category">
                                        Explore <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        
            <!-- Statistics Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 20px; padding: 50px; color: white; text-align: center; box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3);">
                        <h3 class="fw-bold mb-4">Platform Statistics</h3>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-th-large fs-1 mb-2 opacity-75"></i>
                                    <h2 class="fw-bold mb-1"><?= count($mainCategories) ?></h2>
                                    <p class="mb-0 opacity-90">Main Categories</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-file-alt fs-1 mb-2 opacity-75"></i>
                                    <h2 class="fw-bold mb-1"><?= number_format(array_sum(array_column($mainCategories, 'actual_post_count'))) ?></h2>
                                    <p class="mb-0 opacity-90">Total Content</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-download fs-1 mb-2 opacity-75"></i>
                                    <h2 class="fw-bold mb-1">100%</h2>
                                    <p class="mb-0 opacity-90">Free Access</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-clock fs-1 mb-2 opacity-75"></i>
                                    <h2 class="fw-bold mb-1">24/7</h2>
                                    <p class="mb-0 opacity-90">Available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    
    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add hover effects and smooth transitions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate category cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe all category cards
            document.querySelectorAll('.category-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });
    </script>
    
    <!-- Live Search JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/live-search.js"></script>
</body>
</html>