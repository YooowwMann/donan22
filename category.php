<?php
/**
 * Category Page - DONAN22
 * Menampilkan posts berdasarkan kategori yang dipilih
 */

require_once 'config_modern.php';
// checkMaintenanceMode(); // Already auto-called in config_modern.php

// Get category slug from URL
$categorySlug = $_GET['slug'] ?? '';
if (empty($categorySlug)) {
    header('Location: categories.php');
    exit;
}

// Pagination settings
$page = max(1, (int)($_GET['page'] ?? 1));
$postsPerPage = 12; // 4 kolom x 3 baris = 12 items (with sidebar)
$offset = ($page - 1) * $postsPerPage;

// Sort options
$sortBy = $_GET['sort'] ?? 'latest';
$allowedSorts = ['latest', 'popular', 'oldest', 'title'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'latest';
}

try {
    // Get category information
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(p.id) as total_posts
        FROM categories c
        LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published' AND p.deleted_at IS NULL
        WHERE c.slug = ? AND c.status = 'active'
        GROUP BY c.id
    ");
    $stmt->execute([$categorySlug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        header('HTTP/1.0 404 Not Found');
        include '404.php';
        exit;
    }
    
    // Build sort order
    $orderBy = match($sortBy) {
        'popular' => 'p.view_count DESC, p.created_at DESC',
        'oldest' => 'p.created_at ASC',
        'title' => 'p.title ASC',
        default => 'p.featured DESC, p.created_at DESC'
    };
    
    // Get posts in this category
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.title,
            p.slug,
            p.excerpt,
            p.content,
            p.featured,
            p.featured_image,
            COALESCE(p.view_count, 0) as views,
            p.created_at,
            p.updated_at,
            pt.name as post_type_name,
            pt.slug as post_type_slug,
            c.name as category_name,
            c.slug as category_slug,
            c2.name as secondary_category_name,
            c2.slug as secondary_category_slug,
            COALESCE(sd.downloads_count, 0) as downloads_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'approved') as comment_count
        FROM posts p
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN categories c2 ON p.secondary_category_id = c2.id
        LEFT JOIN software_details sd ON p.id = sd.post_id
        WHERE p.category_id = ? AND p.status = 'published' AND p.deleted_at IS NULL
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$category['id'], $postsPerPage, $offset]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total posts for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM posts 
        WHERE category_id = ? AND status = 'published'
    ");
    $stmt->execute([$category['id']]);
    $totalPosts = $stmt->fetchColumn();
    $totalPages = ceil($totalPosts / $postsPerPage);
    
    // Get related categories
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
        WHERE c.status = 'active' AND c.id != ?
        GROUP BY c.id
        HAVING post_count > 0
        ORDER BY post_count DESC
        LIMIT 6
    ");
    $stmt->execute([$category['id']]);
    $relatedCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Page meta
    $pageTitle = htmlspecialchars($category['name']) . ' - DONAN22';
    $pageDescription = htmlspecialchars($category['description'] ?: 'Browse ' . $category['name'] . ' category with ' . $totalPosts . ' posts available for download.');
    
} catch (PDOException $e) {
    error_log("Database error in category.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    die('Database error occurred.');
}

// Helper function for post icons
function getPostIcon($postType, $title) {
    $title_lower = strtolower($title ?? '');
    
    // Check by post type first
    switch (strtolower($postType ?? '')) {
        case 'software':
            if (strpos($title_lower, 'adobe') !== false) return 'fab fa-adobe';
            if (strpos($title_lower, 'office') !== false || strpos($title_lower, 'microsoft') !== false) return 'fab fa-microsoft';
            if (strpos($title_lower, 'photoshop') !== false) return 'fas fa-image';
            if (strpos($title_lower, 'activator') !== false || strpos($title_lower, 'kmspico') !== false) return 'fas fa-key';
            return 'fas fa-download';
        case 'tutorial':
            return 'fas fa-graduation-cap';
        case 'mobile apps':
            if (strpos($title_lower, 'whatsapp') !== false) return 'fab fa-whatsapp';
            if (strpos($title_lower, 'android') !== false) return 'fab fa-android';
            return 'fas fa-mobile-alt';
        default:
            return 'fas fa-file-alt';
    }
}

// Helper function for post colors
function getPostColor($postType) {
    return match(strtolower($postType ?? '')) {
        'software' => '#3b82f6', 
        'tutorial' => '#8b5cf6',
        'mobile apps' => '#10b981',
        default => '#6b7280'
    };
}

// Check if this is a blog category
$isTutorialCategory = in_array(strtolower($categorySlug), ['tutorial', 'blog', 'guide', 'panduan']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <meta name="description" content="<?= $pageDescription ?>">
    <meta name="keywords" content="<?= htmlspecialchars($category['name']) ?>, download, software, blog">
    
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
    
    <?php if ($isTutorialCategory): ?>
    <!-- Blog Style CSS for Blog Category -->
    <link href="<?= SITE_URL ?>/assets/css/blog-style.css" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        /* COMMON STYLES - For Both Blog & Download */
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--light-color);
            line-height: 1.6;
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
        
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 0;
            position: relative;
            overflow: hidden;
        }
        
        .category-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
            z-index: 1;
        }
        
        .category-header-content {
            position: relative;
            z-index: 2;
        }
        
        .category-stats {
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .category-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .category-description {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            margin: 0;
            border-radius: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #6b7280;
        }
        
        .sort-controls {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .form-select {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .category-header {
                padding: 2rem 0;
                text-align: center;
            }
        }
        
        @media (max-width: 576px) {
            .category-title {
                font-size: 1.75rem;
            }
            
            .category-description {
                font-size: 1rem;
            }
        }
    </style>
    
    <?php if (!$isTutorialCategory): ?>
    <style>
        /* DOWNLOAD/SOFTWARE SPECIFIC STYLES - Match Homepage */
        
        .post-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .post-image-link {
            text-decoration: none;
            display: block;
        }
        
        .post-image {
            position: relative;
            width: 100%;
            padding-top: 60%; /* 5:3 ratio - lebih proporsional untuk card kecil */
            overflow: hidden;
            background: #f3f4f6;
            border-radius: 10px 10px 0 0;
        }
        
        /* Custom grid for 5 columns - compact layout */
        @media (min-width: 992px) {
            .col-lg-3 {
                flex: 0 0 auto !important;
                width: 25% !important; /* 4 columns = 25% each */
            }
            
            .col-lg-2-4 {
                flex: 0 0 auto !important;
                width: 20% !important; /* 5 columns = 20% each */
            }
            
            /* Remove extra margin/padding that causes right space */
            .row.g-2 {
                --bs-gutter-x: 0.75rem;
                --bs-gutter-y: 0.5rem;
                margin-right: -0.375rem;
                margin-left: -0.375rem;
            }
            
            .row.g-2 > * {
                padding-right: 0.375rem;
                padding-left: 0.375rem;
                padding-bottom: 0.5rem;
            }
        }
        
        /* Tablet: 3 columns */
        @media (min-width: 768px) and (max-width: 991px) {
            .col-md-4 {
                flex: 0 0 auto !important;
                width: 33.333333% !important; /* 3 columns */
            }
        }
        
        /* Mobile: 2 columns */
        @media (max-width: 767px) {
            .col-6 {
                flex: 0 0 auto !important;
                width: 50% !important; /* 2 columns */
            }
        }
        
        .post-thumbnail {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .post-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .post-card:hover .post-thumbnail img {
            transform: scale(1.1);
        }
        
        /* Blog Grid Layout (4 columns for sidebar layout) */
        .tutorial-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .tutorial-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 991px) {
            .tutorial-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (min-width: 992px) {
            .tutorial-grid {
                grid-template-columns: repeat(4, 1fr) !important; /* 4 columns for sidebar layout */
            }
        }
        
        /* Blog Card Styling */
        .tutorial-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .tutorial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .tutorial-card-image {
            position: relative;
            width: 100%;
            height: 150px; /* Reduced from 180px - lebih compact */
            overflow: hidden;
        }
        
        .tutorial-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .tutorial-card:hover .tutorial-card-image img {
            transform: scale(1.05);
        }
        
        .tutorial-featured-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .tutorial-card-content {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .tutorial-card-title {
            margin: 0 0 8px 0;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .tutorial-card-title a {
            text-decoration: none;
            color: #1f2937;
            transition: color 0.3s ease;
        }
        
        .tutorial-card-title a:hover {
            color: #667eea;
        }
        
        .tutorial-card-excerpt {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.5;
            margin: 0 0 12px 0;
            flex: 1;
        }
        
        .tutorial-card-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: auto;
        }
        
        .tutorial-meta-item {
            color: #9ca3af;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .tutorial-meta-item i {
            font-size: 0.7rem;
        }
        
        .post-thumbnail i {
            font-size: 2.5rem;
            color: white;
            opacity: 0.9;
            position: absolute;
        }
        
        .category-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .post-type-badge {
            /* Hidden - we use category-badge instead */
            display: none;
        }
        
        .post-content {
            padding: 0.4rem 0.5rem;
        }
        
        .post-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 0.4rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .post-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .post-title a:hover {
            color: #667eea;
        }
        
        .post-excerpt {
            /* Hidden untuk menghemat ruang */
            display: none;
        }
        
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.65rem;
            color: #6b7280;
            padding-top: 0.3rem;
            border-top: 1px solid #f1f3f5;
        }
        
        .post-stats {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .post-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #4f46e5;
            transition: all 0.3s ease;
        }
        
        .post-stat:hover {
            background: rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }
        
        .post-stat i {
            font-size: 0.9rem;
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-primary-custom:hover::before {
            left: 100%;
        }
        
        .pagination-custom {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .pagination .page-link {
            border-radius: 12px;
            margin: 0 0.25rem;
            border: 1px solid #e5e7eb;
            color: #374151;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .pagination .page-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .sidebar {
            background: white !important;
            padding: 1.5rem !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
            margin-bottom: 2rem !important;
            border: 1px solid #e5e7eb;
        }
        
        .sidebar h5 {
            color: #1f2937 !important;
            font-weight: 600 !important;
            margin-bottom: 1rem !important;
            padding-bottom: 0.5rem !important;
            border-bottom: 2px solid #667eea !important;
            font-size: 1.125rem !important;
        }
        
        .related-category {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 0.75rem 0 !important;
            border-bottom: 1px solid #f3f4f6 !important;
            text-decoration: none !important;
            color: #374151 !important;
            transition: all 0.3s ease !important;
        }
        
        .related-category:hover {
            color: #667eea;
            padding-left: 0.5rem;
        }
        
        .related-category:last-child {
            border-bottom: none;
        }
        
        .related-category span {
            font-weight: 600 !important;
            font-size: 0.9rem !important;
            color: #374151 !important;
        }
        
        .related-category small {
            font-size: 0.8rem !important;
            color: #9ca3af !important;
            font-weight: 500 !important;
            background: #f3f4f6 !important;
            padding: 2px 8px !important;
            border-radius: 12px !important;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        /* Enhanced Sidebar Styling */
        .stats-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 12px;
            border-left: 4px solid #6366f1;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .related-categories-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .related-category-card {
            display: block;
            padding: 1rem;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .related-category-card:hover {
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
            transform: translateX(5px);
            color: inherit;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .quick-action-btn:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            color: inherit;
        }
        
        .quick-action-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
        }
        
        .quick-action-content {
            flex: 1;
        }

        @media (max-width: 768px) {
            .category-header {
                padding: 2rem 0;
                text-align: center;
            }
            
            .post-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .post-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .stats-grid {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .stat-item {
                flex: 1;
                min-width: 140px;
            }
            
            .quick-actions {
                gap: 0.5rem;
            }
            
            .quick-action-btn {
                padding: 0.75rem;
                flex-direction: column;
                text-align: center;
            }
            
            .quick-action-icon {
                margin: 0 0 0.5rem 0;
                width: 40px;
                height: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .category-title {
                font-size: 1.75rem;
            }
            
            .category-description {
                font-size: 1rem;
            }
            
            .post-card {
                margin-bottom: 1.5rem;
            }
            
            .post-stats {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .stats-grid {
                flex-direction: column;
            }
            
            .sort-controls {
                flex-direction: column;
                text-align: center;
            }
            
            .sort-controls .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .pagination .page-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .scroll-to-top {
                bottom: 1rem !important;
                right: 1rem !important;
                width: 45px !important;
                height: 45px !important;
            }
        }
        
        /* Print styles */
        @media print {
            .main-header,
            .sort-controls,
            .pagination-custom,
            .sidebar,
            .scroll-to-top {
                display: none !important;
            }
            
            .post-card {
                break-inside: avoid;
                margin-bottom: 1rem;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .category-header {
                background: none !important;
                color: black !important;
            }
        }
        
        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* High contrast mode */
        @media (prefers-contrast: high) {
            .post-card {
                border: 2px solid #000;
            }
            
            .btn-primary-custom {
                background: #000;
                color: #fff;
            }
        }
        
    </style>
    <?php endif; ?>
    
    <!-- DEBUG: Force 4 columns - <?= date('Y-m-d H:i:s') ?> -->
    <style>
        /* Force 4 columns layout - DEBUG - UPDATED */
        .tutorial-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1rem !important;
        }
        
        @media (min-width: 992px) {
            .col-lg-3 {
                flex: 0 0 auto !important;
                width: 25% !important;
                max-width: 25% !important;
            }
        }
        
        @media (max-width: 991px) {
            .tutorial-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 768px) {
            .tutorial-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-light py-3">
            <div class="container">
                <a class="navbar-brand" href="<?= SITE_URL ?>/">
                    <i class="fas fa-rocket brand-icon"></i>
                    <span class="brand-text">DONAN22</span>
                </a>
            
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
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

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="categories.php">Kategori</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($category['name']) ?></li>
            </ol>
        </div>
    </nav>

    <!-- Category Header -->
    <section class="category-header">
        <div class="container">
            <div class="category-header-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="category-title"><?= htmlspecialchars($category['name']) ?></h1>
                        <p class="category-description">
                            <?= htmlspecialchars($category['description'] ?: 'Koleksi lengkap ' . $category['name'] . ' terbaru dan terpopuler untuk kebutuhan Anda.') ?>
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="category-stats text-center">
                            <div class="row">
                                <div class="col-6">
                                    <h3 class="fw-bold mb-1"><?= number_format($totalPosts) ?></h3>
                                    <small class="text-white-50">Total Posts</small>
                                </div>
                                <div class="col-6">
                                    <h3 class="fw-bold mb-1"><?= $page ?>/<?= $totalPages ?></h3>
                                    <small class="text-white-50">Halaman</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <!-- Sort Controls -->
                <div class="sort-controls">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0"><?= number_format($totalPosts) ?> items ditemukan</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars($categorySlug) ?>">
                                <input type="hidden" name="page" value="1">
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="latest" <?= $sortBy === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                                    <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Terpopuler</option>
                                    <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>A-Z</option>
                                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Posts Grid -->
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum Ada Konten</h3>
                        <p class="text-muted mb-4">Kategori ini belum memiliki konten. Silakan kembali lagi nanti atau jelajahi kategori lain.</p>
                        <a href="categories.php" class="btn-primary-custom">
                            Lihat Kategori Lain
                        </a>
                    </div>
                <?php else: ?>
                    
                    <?php if ($isTutorialCategory): ?>
                    <!-- BLOG GRID LAYOUT (Blog Style) -->
                    <div class="tutorial-grid">
                        <?php foreach ($posts as $post): 
                            // Calculate reading time
                            $wordCount = str_word_count(strip_tags($post['content']));
                            $readingTime = ceil($wordCount / 200);
                        ?>
                            <article class="tutorial-card">
                                <div class="tutorial-card-image">
                                    <?php 
                                    // Get image with multiple fallbacks
                                    $postImage = null;
                                    
                                    // 1. Try featured_image first
                                    if (!empty($post['featured_image'])) {
                                        $postImage = $post['featured_image'];
                                        // Fix path if relative
                                        if (!preg_match('/^(https?:\/\/|\/)/', $postImage)) {
                                            $postImage = SITE_URL . '/' . $postImage;
                                        }
                                    }
                                    
                                    // 2. Extract first image from content
                                    if (!$postImage && !empty($post['content'])) {
                                        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post['content'], $matches)) {
                                            $postImage = $matches[1];
                                            // Fix path if relative
                                            if (!preg_match('/^(https?:\/\/|\/)/', $postImage)) {
                                                $postImage = SITE_URL . '/' . $postImage;
                                            }
                                        }
                                    }
                                    
                                    // 3. Use placeholder as final fallback
                                    if (!$postImage) {
                                        $postImage = 'https://via.placeholder.com/400x200/' . 
                                                     substr(md5($post['title']), 0, 6) . '/ffffff?text=' . 
                                                     urlencode(substr($post['title'], 0, 30));
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($postImage) ?>" 
                                         alt="<?= htmlspecialchars($post['title']) ?>"
                                         onerror="this.src='https://via.placeholder.com/400x200/667eea/ffffff?text=Blog'">
                                    <?php if ($post['featured']): ?>
                                        <span class="tutorial-featured-badge">
                                            <i class="fas fa-star"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="tutorial-card-content">
                                    <h3 class="tutorial-card-title">
                                        <a href="<?= SITE_URL ?>/post/<?= urlencode($post['slug']) ?>">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="tutorial-card-excerpt">
                                        <?= htmlspecialchars(substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 150)) ?>...
                                    </p>
                                    
                                    <div class="tutorial-card-meta">
                                        <span class="tutorial-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d M Y', strtotime($post['created_at'])) ?>
                                        </span>
                                        <span class="tutorial-meta-item">
                                            <i class="fas fa-clock"></i>
                                            <?= $readingTime ?> min read
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php else: ?>
                    <!-- DOWNLOAD/SOFTWARE GRID LAYOUT (5 columns for full width layout) -->
                    <div class="row g-2 justify-content-start">
                        <?php foreach ($posts as $index => $post): ?>
                            <div class="col-6 col-md-4 col-lg-2-4 mb-1">
                                <article class="post-card fade-in" style="animation-delay: <?= $index * 0.1 ?>s;">
                                    <a href="<?= SITE_URL ?>/post/<?= urlencode($post['slug']) ?>" class="post-image-link">
                                        <div class="post-image">
                                            <?php if (!empty($post['featured_image'])): ?>
                                                <?php
                                                // Fix featured image path
                                                $imgSrc = $post['featured_image'];
                                                // Remove ../ prefix if exists
                                                $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                                // Add SITE_URL if not absolute
                                                if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                                    $imgSrc = SITE_URL . '/' . $imgSrc;
                                                }
                                                ?>
                                                <div class="post-thumbnail">
                                                    <!-- Badge overlay for category -->
                                                    <span class="category-badge">
                                                        <?php
                                                        // Determine badge text - prioritize secondary category
                                                        if (!empty($post['secondary_category_name'])) {
                                                            echo htmlspecialchars($post['secondary_category_name']);
                                                        } else {
                                                            echo htmlspecialchars($post['category_name'] ?? 'Post');
                                                        }
                                                        ?>
                                                    </span>
                                                    
                                                    <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                         alt="<?= htmlspecialchars($post['title']) ?>"
                                                         onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, <?= getPostColor($post['post_type_name']) ?>, <?= getPostColor($post['post_type_name']) ?>99)'; this.parentElement.innerHTML += '<i class=\'<?= getPostIcon($post['post_type_name'], $post['title']) ?>\'></i>';">
                                                </div>
                                            <?php else: ?>
                                                <div class="post-thumbnail" style="background: linear-gradient(135deg, <?= getPostColor($post['post_type_name']) ?>, <?= getPostColor($post['post_type_name']) ?>99);">
                                                    <!-- Badge overlay for category -->
                                                    <span class="category-badge">
                                                        <?php
                                                        // Determine badge text - prioritize secondary category
                                                        if (!empty($post['secondary_category_name'])) {
                                                            echo htmlspecialchars($post['secondary_category_name']);
                                                        } else {
                                                            echo htmlspecialchars($post['category_name'] ?? 'Post');
                                                        }
                                                        ?>
                                                    </span>
                                                    
                                                    <i class="<?= getPostIcon($post['post_type_name'], $post['title']) ?>"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    
                                    <div class="post-content">
                                        <h2 class="post-title">
                                            <a href="<?= SITE_URL ?>/post/<?= urlencode($post['slug']) ?>">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                        </h2>
                                        
                                        <div class="post-meta">
                                            <span class="text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?= date('d M Y', strtotime($post['created_at'])) ?>
                                            </span>
                                            <span class="text-muted small">
                                                <i class="fas fa-eye me-1"></i>
                                                <?= number_format($post['views']) ?> views
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; // End tutorial vs download layout ?>
                    
                <?php endif; // End posts check ?>
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-custom mt-4">
                            <nav aria-label="Category pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?slug=<?= urlencode($categorySlug) ?>&page=<?= $page - 1 ?>&sort=<?= urlencode($sortBy) ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?slug=<?= urlencode($categorySlug) ?>&page=<?= $i ?>&sort=<?= urlencode($sortBy) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Page -->
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?slug=<?= urlencode($categorySlug) ?>&page=<?= $page + 1 ?>&sort=<?= urlencode($sortBy) ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Categories -->
                <?php if (!empty($relatedCategories)): ?>
                    <div class="sidebar">
                        <h5>Kategori Lainnya</h5>
                        <?php foreach ($relatedCategories as $relatedCategory): ?>
                            <a href="<?= SITE_URL ?>/category/<?= urlencode($relatedCategory['slug']) ?>" class="related-category" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: #374151; transition: all 0.3s ease;">
                                <span style="font-weight: 600; font-size: 0.9rem; color: #374151;"><?= htmlspecialchars($relatedCategory['name']) ?></span>
                                <small style="font-size: 0.8rem; color: #9ca3af; font-weight: 500; background: #f3f4f6; padding: 2px 8px; border-radius: 12px;"><?= $relatedCategory['post_count'] ?> items</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Enhanced Interactions and Animations
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for pagination links
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function() {
                    setTimeout(() => {
                        document.querySelector('.sort-controls').scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                    }, 100);
                });
            });
            
            // Add loading state to sort dropdown
            const sortSelect = document.querySelector('select[name="sort"]');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    this.disabled = true;
                    const option = this.selectedOptions[0];
                    const originalText = option.textContent;
                    option.textContent = '⏳ Loading...';
                    
                    // Re-enable after form submission (fallback)
                    setTimeout(() => {
                        this.disabled = false;
                        option.textContent = originalText;
                    }, 3000);
                });
            }
            
            // Parallax effect for category header
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.category-header');
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                
                if (header) {
                    header.style.transform = `translateY(${rate}px)`;
                }
            });
            
            // Animate stats counters
            function animateCounter(element, target) {
                let current = 0;
                const increment = target / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    element.textContent = Math.floor(current).toLocaleString();
                }, 20);
            }
            
            // Intersection Observer for animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Animate counters
                        if (entry.target.classList.contains('stat-number')) {
                            const target = parseInt(entry.target.textContent.replace(/,/g, ''));
                            animateCounter(entry.target, target);
                            observer.unobserve(entry.target);
                        }
                        
                        // Add visible class for animations
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            // Observe stat numbers
            document.querySelectorAll('.stat-number').forEach(el => {
                observer.observe(el);
            });
            
            // Observe post cards for stagger animation
            document.querySelectorAll('.post-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Add ripple effect to buttons
            document.querySelectorAll('.btn-primary-custom, .quick-action-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Enhanced hover effects for post cards
            document.querySelectorAll('.post-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.querySelector('.post-image img')?.style.setProperty('transform', 'scale(1.1)');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.querySelector('.post-image img')?.style.setProperty('transform', 'scale(1)');
                });
            });
            
            // Smooth scroll to top functionality
            const scrollTopBtn = document.createElement('button');
            scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollTopBtn.className = 'scroll-to-top';
            scrollTopBtn.style.cssText = `
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 50px;
                height: 50px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                opacity: 0;
                transform: translateY(100px);
                transition: all 0.3s ease;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            `;
            
            document.body.appendChild(scrollTopBtn);
            
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollTopBtn.style.opacity = '1';
                    scrollTopBtn.style.transform = 'translateY(0)';
                } else {
                    scrollTopBtn.style.opacity = '0';
                    scrollTopBtn.style.transform = 'translateY(100px)';
                }
            });
        });
        
        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            .ripple {
                position: absolute;
                background: rgba(255, 255, 255, 0.6);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .animate-in {
                animation: slideInUp 0.6s ease-out;
            }
            
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .scroll-to-top:hover {
                transform: translateY(-2px) scale(1.1) !important;
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <!-- Live Search JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/live-search.js"></script>
    
    <?php if ($isTutorialCategory): ?>
    <!-- Blog Features JavaScript for Tutorial Category -->
    <script src="<?= SITE_URL ?>/assets/js/blog.js"></script>
    <?php endif; ?>
</body>
</html>