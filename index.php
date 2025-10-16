<?php
require_once 'config_modern.php';

// Check maintenance mode
checkMaintenanceMode();

// Get latest uploads for display
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image,
               c.name as category_name, c.slug as category_slug,
               pt.name as post_type_name, pt.slug as post_type_slug,
               c2.name as secondary_category_name, c2.slug as secondary_category_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        LEFT JOIN categories c2 ON p.secondary_category_id = c2.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestSoftware = [];
}

// Get popular software (by views)
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, COALESCE(p.view_count, 0) as views, p.featured_image, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL AND (pt.slug = 'software' OR c.slug IN ('adobe', 'microsoft-office', 'activator', 'video-editors', 'tool-utilities'))
        ORDER BY p.view_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $popularSoftware = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popularSoftware = [];
}

// Get latest software posts
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image,
               c.name as category_name, c.slug as category_slug,
               pt.name as post_type_name, pt.slug as post_type_slug,
               c2.name as secondary_category_name, c2.slug as secondary_category_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        LEFT JOIN categories c2 ON p.secondary_category_id = c2.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
            AND (pt.slug = 'software' OR c.slug IN ('adobe', 'microsoft-office', 'activator', 'video-editors', 'tool-utilities', 'windows-software', 'mac-software'))
        ORDER BY p.created_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $latestSoftware = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestSoftware = [];
}

// Get latest games posts
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image,
               c.name as category_name, c.slug as category_slug,
               pt.name as post_type_name, pt.slug as post_type_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
            AND (pt.slug = 'game' OR c.slug IN ('games', 'pc-games', 'mobile-games') OR p.title LIKE '%game%')
        ORDER BY p.created_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $latestGames = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestGames = [];
}

// Get latest blog/tutorial posts
try {
    // First, try to get blog category ID
    $tutorialCatStmt = $pdo->query("SELECT id FROM categories WHERE slug = 'blog' LIMIT 1");
    $tutorialCat = $tutorialCatStmt->fetch(PDO::FETCH_ASSOC);
    $tutorialCatId = $tutorialCat ? $tutorialCat['id'] : 0;
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image,
               c.name as category_name, c.slug as category_slug,
               pt.name as post_type_name, pt.slug as post_type_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
            AND (
                p.category_id = :tutorialCatId
                OR pt.slug IN ('blog', 'guide') 
                OR c.slug IN ('blog', 'blogs', 'tips-tricks', 'tutorial') 
                OR p.title LIKE '%blog%' 
                OR p.title LIKE '%cara%'
                OR p.title LIKE '%panduan%'
                OR p.title LIKE '%guide%'
                OR p.title LIKE '%belajar%'
            )
        ORDER BY p.created_at DESC
        LIMIT 12
    ");
    $stmt->bindValue(':tutorialCatId', $tutorialCatId, PDO::PARAM_INT);
    $stmt->execute();
    $latestTutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestTutorials = [];
}

// Get featured posts (most viewed posts for featured section)
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image,
               COALESCE(p.view_count, 0) as views,
               c.name as category_name, c.slug as category_slug,
               pt.name as post_type_name, pt.slug as post_type_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
        ORDER BY p.view_count DESC, p.created_at DESC
        LIMIT 4
    ");
    $stmt->execute();
    $featuredPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredPosts = [];
}

// Get popular blogs (by views)
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, COALESCE(p.view_count, 0) as views, p.featured_image, c.name as category_name, c.slug as category_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' 
            AND p.deleted_at IS NULL 
            AND (
                pt.slug IN ('blog', 'guide') 
                OR c.slug IN ('blog', 'blogs', 'tips-tricks') 
                OR p.title LIKE '%blog%' 
                OR p.title LIKE '%cara%'
                OR p.title LIKE '%panduan%'
                OR p.title LIKE '%guide%'
                OR p.title LIKE '%belajar%'
            )
        ORDER BY p.view_count DESC, p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $popularTutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika blog kurang dari 5, ambil posts terbaru sebagai fallback
    if (count($popularTutorials) < 5) {
        $needed = 5 - count($popularTutorials);
        $excludeIds = !empty($popularTutorials) ? implode(',', array_column($popularTutorials, 'id')) : '0';
        
        $stmtFallback = $pdo->prepare("
            SELECT p.id, p.title, p.slug, COALESCE(p.view_count, 0) as views, p.featured_image, c.name as category_name, c.slug as category_slug
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published' 
                AND p.deleted_at IS NULL
                AND p.id NOT IN ($excludeIds)
                AND (c.slug IS NULL OR c.slug NOT IN ('windows', 'mac', 'mobile', 'software', 'design-graphics', 'video-editing', 'office-productivity', 'utilities', 'sistem-operasi'))
            ORDER BY p.created_at DESC
            LIMIT $needed
        ");
        $stmtFallback->execute();
        $fallbackPosts = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        
        $popularTutorials = array_merge($popularTutorials, $fallbackPosts);
    }
} catch (PDOException $e) {
    $popularTutorials = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags - SEO Optimized -->
    <title>DONAN22 - Download Software, Games & Aplikasi PC Terbaru Gratis</title>
    <meta name="title" content="DONAN22 - Download Software, Games & Aplikasi PC Terbaru Gratis">
    <meta name="description" content="Download software full version, games terbaru, dan aplikasi PC gratis di Donan22. Update harian dengan tutorial lengkap dan link download cepat.">
    <meta name="keywords" content="download software, games gratis, aplikasi pc, full version, crack, donan22, software windows, game pc, tutorial, free download">
    <meta name="author" content="DONAN22">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="language" content="Indonesian">
    <meta name="geo.region" content="ID">
    <meta name="geo.placename" content="Indonesia">
    <meta name="revisit-after" content="1 days">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= SITE_URL ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL ?>/">
    <meta property="og:title" content="DONAN22 - Download Software, Games & Aplikasi PC Terbaru Gratis">
    <meta property="og:description" content="Download software full version, games terbaru, dan aplikasi PC gratis di Donan22. Update harian dengan tutorial lengkap dan link download cepat.">
    <meta property="og:image" content="<?= SITE_URL ?>/assets/images/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="DONAN22">
    <meta property="og:locale" content="id_ID">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= SITE_URL ?>/">
    <meta property="twitter:domain" content="localhost">
    <meta name="twitter:title" content="DONAN22 - Download Software, Games & Aplikasi PC Terbaru Gratis">
    <meta name="twitter:description" content="Download software full version, games terbaru, dan aplikasi PC gratis di Donan22. Update harian dengan tutorial lengkap dan link download cepat.">
    <meta name="twitter:image" content="<?= SITE_URL ?>/assets/images/og-image.png">
    <meta name="twitter:site" content="@donan22">
    <meta name="twitter:creator" content="@donan22">
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= SITE_URL ?>/assets/images/logo.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="<?= SITE_URL ?>/site.webmanifest">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="msapplication-config" content="<?= SITE_URL ?>/browserconfig.xml">
    
    <!-- Google Site Verification (Ganti dengan kode verifikasi Anda) -->
    <!-- <meta name="google-site-verification" content="YOUR_VERIFICATION_CODE"> -->
    
    <!-- Bing Site Verification (Ganti dengan kode verifikasi Anda) -->
    <!-- <meta name="msvalidate.01" content="YOUR_VERIFICATION_CODE"> -->
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Responsive Scaling CSS (90% target) -->
    <link href="<?= SITE_URL ?>/assets/css/responsive-scale.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Live Search CSS -->
    <link href="<?= SITE_URL ?>/assets/css/live-search.css" rel="stylesheet">
    
    <!-- Structured Data / JSON-LD for SEO -->
    <!-- Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "DONAN22",
        "alternateName": ["DONAN", "Donan 22", "Donan22.com"],
        "url": "<?= rtrim(SITE_URL, '/') ?>",
        "logo": {
            "@type": "ImageObject",
            "url": "<?= SITE_URL ?>/assets/images/logo.png",
            "width": 250,
            "height": 60
        },
        "description": "DONAN adalah platform download software, aplikasi, game gratis terpercaya di Indonesia. Download di DONAN untuk software Windows, Mac, Android dengan panduan lengkap. 100% aman dan gratis!",
        "foundingDate": "2020",
        "sameAs": [
            "https://www.youtube.com/@Donan22",
            "https://www.pinterest.com/donan22",
            "https://www.facebook.com/donan22",
            "https://twitter.com/donan22"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "email": "contact@localhost",
            "availableLanguage": ["Indonesian", "English"]
        }
    }
    </script>
    
    <!-- WebSite Schema with SearchAction -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "DONAN22",
        "alternateName": "DONAN",
        "url": "<?= rtrim(SITE_URL, '/') ?>",
        "description": "DONAN - Platform download software, aplikasi, game gratis terpercaya. Download di DONAN untuk Windows, Mac, Android dengan panduan lengkap. 100% aman!",
        "publisher": {
            "@type": "Organization",
            "name": "DONAN22",
            "alternateName": "DONAN",
            "logo": {
                "@type": "ImageObject",
                "url": "<?= SITE_URL ?>/assets/images/logo.png",
                "width": 250,
                "height": 60
            }
        },
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?= rtrim(SITE_URL, '/') ?>/search.php?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        },
        "inLanguage": "id-ID"
    }
    </script>
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --muted-color: #6b7280;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            line-height: 1.6;
        }
        
        /* Header - Modern Colored Navbar */
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
            font-size: 1.75rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .navbar-brand img {
            height: 45px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .navbar-brand .brand-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(251, 191, 36, 0.3));
        }
        
        .navbar-brand .brand-text {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -1px;
        }
        
        .navbar-brand:hover {
            color: #f0f9ff !important;
            transform: scale(1.05);
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover .brand-icon {
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.5));
            transform: rotate(10deg);
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
        
        /* Navbar toggler for mobile - white color */
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Search bar in navbar */
        .navbar .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            border-radius: 25px;
            padding: 0.5rem 1rem;
        }
        
        .navbar .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .navbar .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.15);
        }
        
        .navbar .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
            color: #ffffff;
        }
        
        .navbar .btn-outline-light:hover {
            background: #ffffff;
            color: #667eea;
        }
        
        /* Mega Menu */
        .mega-menu {
            min-width: 600px;
        }
        
        /* Category Cards */
        .category-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .category-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Post Cards - Enhanced */
        .post-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            background: white;
            max-width: 100%; /* Prevent oversizing */
            margin: 0 auto;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
            border-color: var(--primary-color);
        }
        
        /* Ensure all cards same height */
        .row.g-3 > [class*='col-'] {
            display: flex;
        }
        
        .row.g-3 > [class*='col-'] > a {
            width: 100%;
            display: flex;
        }
        
        .row.g-3 > [class*='col-'] > a > article,
        .row.g-3 > [class*='col-'] > article {
            width: 100%;
        }
        
        .post-image {
            position: relative;
            width: 100%;
            padding-top: 65%; /* Slightly taller for better thumbnail display */
            border-radius: 0;
            overflow: hidden;
            background: #f3f4f6;
        }
        
        /* Featured Post Cards - Same size as regular */
        .featured-post-card .post-image {
            padding-top: 65%; /* Same as regular cards */
        }
        
        .featured-post-card {
            border: 2px solid #fbbf24 !important; /* Gold border untuk featured */
        }
        
        .featured-post-card:hover {
            border-color: #f59e0b !important;
            box-shadow: 0 15px 40px rgba(251, 191, 36, 0.2) !important;
        }
        
        /* Responsive Grid System */
        /* Extra Large Desktop (>= 1400px) - 6 items per row */
        @media (min-width: 1400px) {
            .col-xxl-2 {
                flex: 0 0 auto;
                width: 16.666667%; /* 100% / 6 = 16.666% */
            }
        }
        
        /* Large Desktop (992px - 1399px) - 4 items per row */
        @media (min-width: 992px) and (max-width: 1399px) {
            .col-lg-3 {
                flex: 0 0 auto;
                width: 25%; /* 100% / 4 = 25% */
            }
        }
        
        /* Tablet (768px - 991px) - 3 items per row */
        @media (min-width: 768px) and (max-width: 991px) {
            .col-md-4 {
                flex: 0 0 auto;
                width: 33.333333%; /* 100% / 3 = 33.333% */
            }
        }
        
        /* Mobile Large (576px - 767px) - 2 items per row */
        @media (min-width: 576px) and (max-width: 767px) {
            .col-sm-6 {
                flex: 0 0 auto;
                width: 50%; /* 100% / 2 = 50% */
            }
        }
        
        /* Mobile Small (< 576px) - 2 items per row */
        @media (max-width: 575px) {
            .col-6 {
                flex: 0 0 auto;
                width: 50%; /* 100% / 2 = 50% */
            }
        }
        
        .post-image img {
            transition: transform 0.3s ease;
        }
        
        .post-card:hover .post-image img {
            transform: scale(1.1);
        }
        
        .category-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .category-badge.badge-download {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        
        .category-badge.badge-tutorial {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            font-size: 1.75rem;
        }
        
        /* Section Spacing */
        section {
            margin-bottom: 3rem;
        }
        
        section:last-child {
            margin-bottom: 2rem;
        }
        
        /* Featured Section Specific */
        .featured-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
        }
        
        @media (max-width: 768px) {
            .featured-section {
                padding: 1rem;
            }
        }
        
        /* View All Button */
        .btn-view-all {
            font-size: 0.9rem;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-view-all:hover {
            transform: translateX(5px);
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: relative;
        }
        
        /* Sidebar tidak sticky - ikut scroll halaman */
        
        .widget {
            /* Remove border karena sudah 2 kolom berdampingan */
            border: none;
            padding-bottom: 0;
        }
        
        .widget-title {
            color: var(--dark-color);
            font-size: 1.1rem;
        }
        
        /* Popular Items with Thumbnails - COMPACT SIZE */
        .popular-item-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
            display: block;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .popular-item-card:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-color: var(--primary-color);
        }
        
        .popular-thumbnail {
            position: relative;
            width: 100%;
            padding-top: 100%; /* 1:1 Aspect Ratio - Square */
            overflow: hidden;
            border-radius: 6px;
            background: #f3f4f6;
        }
        
        .popular-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .popular-item-card:hover .popular-thumbnail img {
            transform: scale(1.05);
        }
        
        .popular-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .popular-overlay i {
            color: white;
            font-size: 14px;
        }
        
        .popular-item-card:hover .popular-overlay {
            opacity: 1;
        }
        
        .popular-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            padding-left: 5px;
        }
        
        .popular-title {
            color: #1f2937;
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 4px;
            transition: color 0.3s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .popular-item-card:hover .popular-title {
            color: var(--primary-color);
        }
        
        /* Card Body Typography */
        .post-card .card-body {
            padding: 0.75rem !important;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .post-card h3, .post-card h6 {
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1.35;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.5rem; /* Ensure consistent height */
        }
        
        .post-card h3 a, .post-card h6 a {
            color: inherit;
            text-decoration: none;
        }
        
        .post-card h3 a:hover, .post-card h6 a:hover {
            color: var(--primary-color);
        }
        
        .post-card .card-text {
            font-size: 0.75rem;
            line-height: 1.5;
            color: #6b7280;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .post-card .btn-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            font-weight: 600;
        }
        
        .popular-meta {
            font-size: 0.7rem;
        }
        
        .popular-meta i {
            font-size: 0.7rem;
        }
        
        /* About Widget Styles - IMPROVED RESPONSIVE */
        .about-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .about-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
        }
        
        .about-logo {
            position: relative;
        }
        
        .about-logo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            opacity: 0.1;
            z-index: -1;
        }
        
        /* Sidebar Statistics - Better Layout */
        .about-stats {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 1rem !important;
        }
        
        .about-stats .stat-item {
            padding: 10px 5px !important;
            text-align: center;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .about-stats .stat-item:hover {
            background: #e0e7ff;
            transform: translateY(-2px);
        }
        
        .about-stats .stat-item i {
            display: block;
            margin-bottom: 5px;
        }
        
        .about-stats .stat-item .fw-bold {
            display: block;
            font-size: 1rem !important;
            font-weight: 700;
            margin-bottom: 2px;
        }
        
        .about-stats .stat-item .text-muted {
            font-size: 0.7rem !important;
            line-height: 1;
        }
        
        .feature-item {
            transition: color 0.2s ease;
        }
        
        .feature-item:hover {
            color: var(--primary-color) !important;
        }
        
        .feature-item:hover .text-muted {
            color: var(--primary-color) !important;
        }
        
        /* Responsive untuk About Stats */
        @media (max-width: 575px) {
            .about-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .about-stats .stat-item {
                padding: 8px 3px !important;
            }
            
            .about-stats .stat-item i {
                font-size: 1rem !important;
            }
            
            .about-stats .stat-item .fw-bold {
                font-size: 0.85rem !important;
            }
            
            .about-stats .stat-item .text-muted {
                font-size: 0.65rem !important;
            }
        }
        
        /* Utilities */
        .text-purple {
            color: #8b5cf6 !important;
        }
        
        .bg-purple {
            background-color: #8b5cf6 !important;
        }
        
        /* Live Search Styles */
        .live-search-container {
            max-width: 400px;
        }
        
        .live-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            border: 1px solid #e5e7eb;
        }
        
        .live-search-results.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .live-search-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
        }
        
        .live-search-item:hover {
            background: #f8fafc;
            color: inherit;
            transform: translateX(3px);
        }
        
        .live-search-item:last-child {
            border-bottom: none;
        }
        
        .live-search-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1rem;
        }
        
        .live-search-content h6 {
            margin: 0 0 3px 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .live-search-content p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--muted-color);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* =============================================
           COMPREHENSIVE RESPONSIVE DESIGN
           ============================================= */
        
        /* Navbar Responsive */
        @media (max-width: 991px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .navbar-brand img {
                height: 38px;
            }
            
            .navbar-brand .brand-icon {
                font-size: 2rem;
            }
            
            .nav-link {
                padding: 0.75rem 1rem !important;
            }
            
            .navbar-collapse {
                background: rgba(79, 107, 235, 0.95);
                margin-top: 15px;
                padding: 15px;
                border-radius: 12px;
            }
        }
        
        @media (max-width: 575px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .navbar-brand img {
                height: 32px;
            }
            
            .navbar-brand .brand-text {
                font-size: 1.3rem;
            }
        }
        
        /* Cards Responsive */
        @media (max-width: 991px) {
            .post-card {
                margin-bottom: 1rem;
            }
            
            .post-image {
                padding-top: 65%;
            }
        }
        
        @media (max-width: 575px) {
            .post-card {
                border-radius: 8px;
            }
            
            .post-image {
                padding-top: 60%;
                border-radius: 8px 8px 0 0;
            }
            
            .category-badge {
                font-size: 0.65rem;
                padding: 2px 8px;
            }
        }
        
        /* Typography Responsive */
        @media (max-width: 991px) {
            h3, .h3 {
                font-size: 1.4rem;
            }
            
            h5, .h5 {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 575px) {
            h3, .h3 {
                font-size: 1.2rem;
            }
            
            h5, .h5 {
                font-size: 1rem;
            }
            
            .widget-title {
                font-size: 0.9rem !important;
            }
        }
        
        /* Container Responsive */
        @media (max-width: 575px) {
            .container, .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            
            main.py-4 {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }
            
            .row {
                margin-left: -4px;
                margin-right: -4px;
            }
            
            .row > * {
                padding-left: 4px;
                padding-right: 4px;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .section-title i {
                font-size: 1.3rem;
            }
            
            .featured-section {
                padding: 1rem;
                margin-bottom: 2rem;
            }
            
            section {
                margin-bottom: 2rem;
            }
            
            .btn-view-all {
                font-size: 0.75rem;
                padding: 0.35rem 0.75rem;
            }
        }
        
        /* Sidebar Responsive */
        @media (max-width: 991px) {
            .sidebar {
                margin-top: 2rem;
                padding: 1.5rem;
                position: relative !important;
                top: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
        }
        
        /* Tablet Specific */
        @media (min-width: 768px) and (max-width: 991px) {
            .section-title {
                font-size: 1.3rem;
            }
            
            .post-card h3, .post-card h6 {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 575px) {
            .sidebar {
                padding: 1rem;
                border-radius: 10px;
            }
            
            .popular-item-card {
                padding: 6px;
                margin-bottom: 8px;
            }
            
            .popular-title {
                font-size: 0.75rem;
            }
            
            .popular-meta {
                font-size: 0.65rem;
            }
        }
        
        /* Mega Menu Responsive */
        @media (max-width: 768px) {
            .mega-menu {
                min-width: auto;
                width: 100%;
            }
            
            .breaking-news {
                font-size: 0.9rem;
            }
        }
        
        /* Search Bar Responsive */
        @media (max-width: 575px) {
            .live-search-container {
                max-width: 100%;
            }
            
            .navbar .form-control {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
        }
        
        /* Optimasi Grid Layout untuk Latest Upload */
        @media (min-width: 1400px) {
            /* Extra large screens - 6 items per row */
            .latest-upload-item {
                flex: 0 0 auto;
                width: 16.666667%; /* 1/6 of container */
            }
        }
        
        @media (min-width: 992px) and (max-width: 1399px) {
            /* Large screens - 5 items per row */
            .latest-upload-item {
                flex: 0 0 auto;
                width: 20%; /* 1/5 of container */
            }
        }
        
        @media (min-width: 768px) and (max-width: 991px) {
            /* Tablet - 3 items per row */
            .latest-upload-item {
                flex: 0 0 auto;
                width: 33.333333%; /* 1/3 of container */
            }
        }
        
        @media (max-width: 767px) {
            /* Mobile - 2 items per row */
            .latest-upload-item {
                flex: 0 0 auto;
                width: 50%; /* 1/2 of container */
            }
        }
        
        /* Button Responsive */
        @media (max-width: 575px) {
            .btn {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
        }
        
        /* Gap and Spacing Improvements */
        .g-3 {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }
        
        @media (max-width: 575px) {
            .g-3 {
                --bs-gutter-x: 0.5rem;
                --bs-gutter-y: 0.5rem;
            }
        }
        
        /* Image Loading State */
        .post-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            z-index: 1;
        }
        
        .post-image img {
            position: relative;
            z-index: 2;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Simple Hero Banner - Clean & Minimal - IMPROVED RESPONSIVE */
        .simple-hero-banner {
            background: linear-gradient(135deg, #4f6bebff 0%, #7a4aaaff 100%);
            border-radius: 16px;
            padding: 35px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(79, 107, 235, 0.25);
            margin-top: 10px;
        }
        
        .hero-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .hero-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 25px;
            line-height: 1.6;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .hero-stats .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 15px 10px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-stats .stat-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .hero-stats .stat-item i {
            font-size: 1.5rem;
            margin-bottom: 2px;
        }
        
        .hero-stats .stat-item span {
            white-space: nowrap;
            text-align: center;
        }
        
        /* Hidden SEO content - only for search engines */
        .seo-hidden-content {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        
        /* RESPONSIVE IMPROVEMENTS */
        /* Desktop Large (>= 1400px) */
        @media (min-width: 1400px) {
            .hero-stats {
                grid-template-columns: repeat(4, 1fr);
                max-width: 800px;
            }
        }
        
        /* Desktop (>= 992px) */
        @media (min-width: 992px) and (max-width: 1399px) {
            .hero-stats {
                grid-template-columns: repeat(4, 1fr);
                max-width: 700px;
            }
        }
        
        /* Tablet (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .simple-hero-banner {
                padding: 30px 25px;
            }
            
            .hero-title {
                font-size: 1.5rem;
            }
            
            .hero-subtitle {
                font-size: 0.95rem;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                max-width: 450px;
            }
            
            .hero-stats .stat-item {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
            
            .hero-stats .stat-item i {
                font-size: 1.3rem;
            }
        }
        
        /* Mobile Large (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            .simple-hero-banner {
                padding: 25px 20px;
                border-radius: 12px;
            }
            
            .hero-title {
                font-size: 1.35rem;
            }
            
            .hero-subtitle {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                max-width: 400px;
            }
            
            .hero-stats .stat-item {
                padding: 12px 8px;
                font-size: 0.85rem;
            }
            
            .hero-stats .stat-item i {
                font-size: 1.2rem;
            }
        }
        
        /* Mobile Small (< 576px) - iPhone, Android */
        @media (max-width: 575px) {
            .simple-hero-banner {
                padding: 20px 15px;
                border-radius: 10px;
                margin-top: 5px;
            }
            
            .hero-title {
                font-size: 1.2rem;
                margin-bottom: 10px;
            }
            
            .hero-subtitle {
                font-size: 0.85rem;
                margin-bottom: 18px;
                line-height: 1.5;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                max-width: 100%;
            }
            
            .hero-stats .stat-item {
                padding: 10px 5px;
                font-size: 0.75rem;
                border-radius: 10px;
            }
            
            .hero-stats .stat-item i {
                font-size: 1.1rem;
            }
            
            .hero-stats .stat-item span {
                font-size: 0.75rem;
                line-height: 1.2;
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
                    <!-- Logo Image -->
                    <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="DONAN22 Logo" style="height: 40px; width: auto;">
                </a>
            
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= SITE_URL ?>/"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/software"><i class="fas fa-laptop-code me-1"></i> Software</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/games"><i class="fas fa-gamepad me-1"></i> Games</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/blog"><i class="fas fa-newspaper me-1"></i> Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/categories.php"><i class="fas fa-th-large me-1"></i> Semua Kategori</a>
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
    
    <!-- Main Content -->
    <main class="py-4">
        <div class="container-fluid px-3 px-lg-4">
            <div class="row g-4">
                <!-- Main Content Area -->
                <div class="col-lg-9 col-xl-9">

                    <!-- Simple SEO Banner - Clean & Minimal -->
                    <div class="simple-hero-banner mb-4">
                        <h1 class="hero-title">Download Software Gratis di DONAN - Platform DONAN22 Terpercaya 2025</h1>
                        <p class="hero-subtitle">Selamat datang di <strong>DONAN</strong> (<strong>DONAN22.COM</strong>), platform download software, aplikasi, game gratis terpercaya di Indonesia. Download di <strong>DONAN</strong> untuk mendapatkan software Windows, Mac, Android dengan panduan lengkap. <strong>DONAN</strong> menyediakan software full version, update terbaru, dan 100% gratis. Kenapa pilih <strong>DONAN</strong>? Karena kami terpercaya, aman, dan lengkap!</p>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>1000+ Software</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-book"></i>
                                <span>500+ Tutorial</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span>10K+ User</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-shield-alt"></i>
                                <span>100% Aman</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden SEO Content (for search engines only) -->
                    <div class="seo-hidden-content" style="font-size: 0; height: 0; overflow: hidden; opacity: 0; position: absolute;">
                        <h2>Mengapa Memilih DONAN22?</h2>
                        <p><strong>DONAN22</strong> adalah pilihan utama ribuan pengguna di Indonesia untuk mendapatkan software berkualitas. Platform <strong>DONAN22</strong> menyediakan download software gratis dengan tutorial lengkap dan aman. Semua software di <strong>DONAN22</strong> telah diverifikasi keamanannya dan dilengkapi panduan instalasi step-by-step.</p>
                        
                        <h2>Kategori Software Populer di DONAN22</h2>
                        <p>Platform <strong>DONAN22</strong> menyediakan berbagai kategori software: Design Software (Adobe Photoshop, CorelDRAW), Video Editing (Premiere Pro, After Effects), Office Productivity (Microsoft Office), Games, Developer Tools, dan Mobile Apps. <strong>DONAN22</strong> selalu update dengan software terbaru.</p>
                        
                        <h3>Tentang Platform DONAN22</h3>
                        <p><strong>DONAN22</strong> didirikan dengan misi menyediakan akses mudah dan aman ke software berkualitas untuk semua orang. Platform <strong>DONAN22</strong> telah melayani lebih dari 10,000+ pengguna aktif dengan koleksi 1000+ software dan aplikasi dari berbagai kategori. Tim <strong>DONAN22</strong> senantiasa memastikan setiap software yang tersedia telah diuji dan aman digunakan. Di <strong>DONAN22</strong>, kami tidak hanya menyediakan link download, tetapi juga tutorial lengkap, tips & tricks, dan panduan troubleshooting untuk membantu Anda memaksimalkan penggunaan software. Bergabunglah dengan komunitas <strong>DONAN22</strong> dan dapatkan akses ke software terbaik untuk meningkatkan produktivitas dan kreativitas Anda!</p>
                    </div>

                    <!-- Featured Posts Section -->
                    <?php if (!empty($featuredPosts)): ?>
                    <section class="featured-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-star text-warning"></i>
                                <span>Artikel Unggulan</span>
                            </h2>
                        </div>
                        <div class="row g-3">
                            <?php foreach (array_slice($featuredPosts, 0, 4) as $index => $post): ?>
                                <?php
                                // Prepare image
                                $imgSrc = $post['featured_image'];
                                if (!empty($imgSrc)) {
                                    $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                    if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                        $imgSrc = SITE_URL . '/' . $imgSrc;
                                    }
                                } else {
                                    $imgSrc = SITE_URL . '/assets/images/placeholder.jpg';
                                }
                                ?>
                                <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                                    <article class="card post-card featured-post-card h-100 border-0 shadow overflow-hidden">
                                        <div class="post-image">
                                            <span class="category-badge badge-download">
                                                <i class="fas fa-star me-1"></i>Featured
                                            </span>
                                            <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="card-body p-3">
                                            <h3 class="h6 fw-bold mb-2 text-dark">
                                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($post['slug']) ?>" 
                                                   class="text-dark text-decoration-none">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </a>
                                            </h3>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <i class="far fa-calendar me-1"></i><?= date('d M Y', strtotime($post['created_at'])) ?>
                                                </small>
                                                <span class="btn btn-sm btn-warning" style="font-size: 0.7rem; padding: 3px 10px;">
                                                    <i class="fas fa-eye me-1"></i>Lihat
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Software Terbaru Section -->
                    <section>
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h2 class="section-title">
                                <i class="fas fa-laptop-code text-primary"></i>
                                <span>📱 Software Terbaru</span>
                            </h2>
                            <a href="<?= SITE_URL ?>/category/software" class="btn btn-sm btn-outline-primary btn-view-all">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        
                        <?php if (!empty($latestSoftware)): ?>
                        <div class="row g-3">
                            <?php 
                            // Function to get appropriate icon based on post type and category
                            function getPostIcon($postType, $category, $title) {
                                // Convert to lowercase with null safety
                                $title = strtolower($title ?? '');
                                $postType = strtolower($postType ?? '');
                                $category = strtolower($category ?? '');
                                
                                // Check specific software types first
                                if (strpos($title, 'photoshop') !== false || strpos($title, 'adobe') !== false) return ['fas fa-paint-brush', 'bg-danger'];
                                if (strpos($title, 'office') !== false || strpos($title, 'word') !== false || strpos($title, 'excel') !== false) return ['fas fa-file-alt', 'bg-primary'];
                                if (strpos($title, 'windows') !== false || strpos($title, 'win') !== false) return ['fab fa-windows', 'bg-info'];
                                if (strpos($title, 'kmspico') !== false || strpos($title, 'activator') !== false) return ['fas fa-key', 'bg-warning'];
                                if (strpos($title, 'game') !== false || strpos($title, 'gta') !== false || strpos($title, 'cyberpunk') !== false) return ['fas fa-gamepad', 'bg-success'];
                                if (strpos($title, 'whatsapp') !== false || strpos($title, 'telegram') !== false) return ['fab fa-whatsapp', 'bg-success'];
                                if (strpos($title, 'tutorial') !== false || strpos($title, 'cara') !== false || strpos($title, 'install') !== false) return ['fas fa-graduation-cap', 'bg-info'];
                                
                                // Default based on post type
                                return match($postType) {
                                    'software' => ['fas fa-desktop', 'bg-primary'],
                                    'game' => ['fas fa-gamepad', 'bg-success'],
                                    'mobile app' => ['fas fa-mobile-alt', 'bg-info'],
                                    'tutorial' => ['fas fa-graduation-cap', 'bg-warning'],
                                    default => ['fas fa-download', 'bg-secondary']
                                };
                            }
                            
                            foreach ($latestSoftware as $post):
                                $iconData = getPostIcon($post['post_type_name'], $post['category_name'], $post['title']);
                                $icon = $iconData[0];
                                $bgColor = $iconData[1];
                                
                                // Determine post type badge
                                $postType = strtolower($post['post_type_name'] ?? 'post');
                                $categoryName = strtolower($post['category_name'] ?? '');
                                
                                // Check if it's a tutorial
                                $isTutorial = (strpos($postType, 'tutorial') !== false || 
                                              strpos($postType, 'guide') !== false ||
                                              strpos($categoryName, 'tutorial') !== false ||
                                              strpos(strtolower($post['title']), 'tutorial') !== false ||
                                              strpos(strtolower($post['title']), 'cara') !== false);
                                
                                // Check if it's software - ANY post should be treated as software unless it's a tutorial
                                // This way we always show secondary category badge if available
                                $isSoftware = !$isTutorial;
                                
                                // Set badge properties - prioritize secondary category for software
                                if (!empty($post['secondary_category_name'])) {
                                    // Use secondary category name if available
                                    $badgeClass = 'badge-download';
                                    $badgeIcon = 'fas fa-tag';
                                    $badgeText = $post['secondary_category_name'];
                                } elseif ($isTutorial) {
                                    $badgeClass = 'badge-tutorial';
                                    $badgeIcon = 'fas fa-rss';
                                    $badgeText = 'Blog';
                                } elseif (!empty($post['category_name'])) {
                                    // Use primary category if no secondary category
                                    $badgeClass = 'badge-download';
                                    $badgeIcon = 'fas fa-folder';
                                    $badgeText = $post['category_name'];
                                } else {
                                    $badgeClass = 'badge-download';
                                    $badgeIcon = 'fas fa-file-alt';
                                    $badgeText = 'Post';
                                }
                            ?>
                            <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xxl-2">
                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                                    <article class="card post-card border-0 shadow-sm bg-white">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <!-- Post with Featured Image (Tutorial or Download) -->
                                            <div class="post-image">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
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
                                                <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                                     onerror="this.style.display='none'; this.parentElement.classList.add('<?= $bgColor ?>'); this.parentElement.innerHTML += '<i class=\'<?= $icon ?> text-white\' style=\'font-size: 2.5rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);\'></i>';">
                                            </div>
                                        <?php else: ?>
                                            <!-- Icon Gradient (for posts without image) -->
                                            <div class="post-image <?= $bgColor ?> d-flex align-items-center justify-content-center">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
                                                <i class="<?= $icon ?> text-white" style="font-size: 2.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body p-2">
                                            <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.85rem; line-height: 1.3;"><?= htmlspecialchars(strlen($post['title']) > 40 ? substr($post['title'], 0, 40) . '...' : $post['title']) ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted" style="font-size: 0.7rem;"><?= timeAgo($post['created_at']) ?></small>
                                                <span class="btn btn-sm btn-primary" style="font-size: 0.7rem; padding: 2px 8px;">View</span>
                                            </div>
                                        </div>
                                    </article>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info text-center mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Belum ada software terbaru. <a href="<?= SITE_URL ?>/categories.php">Lihat kategori lain</a>
                        </div>
                        <?php endif; ?>
                    </section>

                    <!-- Games Terbaru Section -->
                    <?php if (!empty($latestGames)): ?>
                    <section>
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h2 class="section-title">
                                <i class="fas fa-gamepad text-success"></i>
                                <span>🎮 Games Terbaru</span>
                            </h2>
                            <a href="<?= SITE_URL ?>/category/games" class="btn btn-sm btn-outline-success btn-view-all">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($latestGames as $post): 
                                $iconData = getPostIcon($post['post_type_name'], $post['category_name'], $post['title']);
                                $icon = $iconData[0];
                                $bgColor = $iconData[1];
                                
                                $badgeClass = 'badge-download';
                                $badgeIcon = 'fas fa-gamepad';
                                $badgeText = $post['category_name'] ?? 'Game';
                            ?>
                            <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xxl-2">
                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                                    <article class="card post-card border-0 shadow-sm bg-white">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <div class="post-image">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
                                                <?php
                                                $imgSrc = $post['featured_image'];
                                                $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                                if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                                    $imgSrc = SITE_URL . '/' . $imgSrc;
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                                     onerror="this.style.display='none'; this.parentElement.classList.add('<?= $bgColor ?>'); this.parentElement.innerHTML += '<i class=\'<?= $icon ?> text-white\' style=\'font-size: 2.5rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);\'></i>';">
                                            </div>
                                        <?php else: ?>
                                            <div class="post-image <?= $bgColor ?> d-flex align-items-center justify-content-center">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
                                                <i class="<?= $icon ?> text-white" style="font-size: 2.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body p-3">
                                            <h3 class="h6 fw-bold mb-2 text-dark" style="line-height: 1.3;">
                                                <?= htmlspecialchars(strlen($post['title']) > 50 ? substr($post['title'], 0, 50) . '...' : $post['title']) ?>
                                            </h3>
                                            <?php if (!empty($post['excerpt'])): ?>
                                                <p class="card-text text-muted small mb-2" style="font-size: 0.8rem;">
                                                    <?= htmlspecialchars(substr($post['excerpt'], 0, 80)) ?>...
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <i class="far fa-calendar me-1"></i><?= date('d M Y', strtotime($post['created_at'])) ?>
                                                </small>
                                                <span class="btn btn-sm btn-success" style="font-size: 0.75rem; padding: 3px 10px;">
                                                    Download →
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Artikel & Tutorial Section -->
                    <?php if (!empty($latestTutorials)): ?>
                    <section>
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h2 class="section-title">
                                <i class="fas fa-newspaper text-warning"></i>
                                <span>📝 Artikel & Tutorial</span>
                            </h2>
                            <a href="<?= SITE_URL ?>/category/blog" class="btn btn-sm btn-outline-warning btn-view-all">
                                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($latestTutorials as $post): 
                                $badgeClass = 'badge-tutorial';
                                $badgeIcon = 'fas fa-book-open';
                                $badgeText = $post['category_name'] ?? 'Tutorial';
                            ?>
                            <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xxl-2">
                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                                    <article class="card post-card border-0 shadow-sm bg-white">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <div class="post-image">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
                                                <?php
                                                $imgSrc = $post['featured_image'];
                                                $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                                if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                                    $imgSrc = SITE_URL . '/' . $imgSrc;
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                                     onerror="this.style.display='none'; this.parentElement.classList.add('bg-warning'); this.parentElement.innerHTML += '<i class=\'fas fa-book-open text-white\' style=\'font-size: 2.5rem; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);\'></i>';">
                                            </div>
                                        <?php else: ?>
                                            <div class="post-image bg-warning d-flex align-items-center justify-content-center">
                                                <span class="category-badge <?= $badgeClass ?>">
                                                    <i class="<?= $badgeIcon ?> me-1"></i><?= $badgeText ?>
                                                </span>
                                                <i class="fas fa-book-open text-white" style="font-size: 2.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body p-3">
                                            <h3 class="h6 fw-bold mb-2 text-dark" style="line-height: 1.3;">
                                                <?= htmlspecialchars(strlen($post['title']) > 50 ? substr($post['title'], 0, 50) . '...' : $post['title']) ?>
                                            </h3>
                                            <?php if (!empty($post['excerpt'])): ?>
                                                <p class="card-text text-muted small mb-2" style="font-size: 0.8rem;">
                                                    <?= htmlspecialchars(substr($post['excerpt'], 0, 80)) ?>...
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <i class="far fa-calendar me-1"></i><?= date('d M Y', strtotime($post['created_at'])) ?>
                                                </small>
                                                <span class="btn btn-sm btn-warning" style="font-size: 0.75rem; padding: 3px 10px;">
                                                    Baca →
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-3 col-xl-3">
                    <aside class="sidebar p-3">
                        <!-- Software Populer Widget - Full Width -->
                        <div class="widget mb-4">
                            <h5 class="widget-title fw-bold mb-3" style="font-size: 0.95rem;">
                                <i class="fas fa-fire text-danger me-2"></i>Software Populer
                            </h5>
                                    <div class="popular-items">
                                        <?php if (!empty($popularSoftware)): ?>
                                            <?php $softwareCount = 0; ?>
                                            <?php foreach ($popularSoftware as $software): ?>
                                                <?php if ($softwareCount >= 5) break; // Tampilkan 5 item ?>
                                                <?php
                                                // Get featured image or use placeholder
                                                $imgSrc = $software['featured_image'];
                                                if (!empty($imgSrc)) {
                                                    // Remove ../ prefix if exists
                                                    $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                                    // Add SITE_URL if not absolute
                                                    if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                                        $imgSrc = SITE_URL . '/' . $imgSrc;
                                                    }
                                                    $featuredImage = htmlspecialchars($imgSrc);
                                                } else {
                                                    $featuredImage = SITE_URL . '/assets/images/placeholder.svg';
                                                }
                                                ?>
                                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($software['slug']) ?>" class="popular-item-card text-decoration-none d-block mb-2">
                                                    <div class="row g-2">
                                                        <div class="col-4">
                                                            <div class="popular-thumbnail">
                                                                <img src="<?= $featuredImage ?>" alt="<?= htmlspecialchars($software['title']) ?>" class="img-fluid rounded">
                                                                <div class="popular-overlay">
                                                                    <i class="fas fa-eye"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="popular-content">
                                                                <h6 class="popular-title mb-1"><?= htmlspecialchars(substr($software['title'], 0, 30)) ?><?= strlen($software['title']) > 30 ? '...' : '' ?></h6>
                                                                <div class="popular-meta">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-eye me-1"></i><?= number_format($software['views']) ?> views
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                                <?php $softwareCount++; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <a href="<?= SITE_URL ?>/category/software" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-download me-2"></i>Lihat Semua Software
                                            </a>
                                        <?php endif; ?>
                                    </div>
                        </div>
                        
                        <!-- Tutorial Populer Widget - Full Width -->
                        <div class="widget mb-4">
                            <h5 class="widget-title fw-bold mb-3" style="font-size: 0.95rem;">
                                <i class="fas fa-rss text-success me-2"></i>Blog Populer
                            </h5>
                            <div class="popular-items">
                                        <?php if (!empty($popularTutorials)): ?>
                                            <?php $tutorialCount = 0; ?>
                                            <?php foreach ($popularTutorials as $tutorial): ?>
                                                <?php if ($tutorialCount >= 5) break; // Tampilkan 5 item ?>
                                                <?php
                                                // Get featured image or use placeholder
                                                $imgSrc = $tutorial['featured_image'];
                                                if (!empty($imgSrc)) {
                                                    // Remove ../ prefix if exists
                                                    $imgSrc = preg_replace('/^\.\.\//', '', $imgSrc);
                                                    // Add SITE_URL if not absolute
                                                    if (!preg_match('/^(https?:\/\/|\/)/', $imgSrc)) {
                                                        $imgSrc = SITE_URL . '/' . $imgSrc;
                                                    }
                                                    $featuredImage = htmlspecialchars($imgSrc);
                                                } else {
                                                    $featuredImage = SITE_URL . '/assets/images/logo.png';
                                                }
                                                ?>
                                                <a href="<?= SITE_URL ?>/post/<?= htmlspecialchars($tutorial['slug']) ?>" class="popular-item-card text-decoration-none d-block mb-2">
                                                    <div class="row g-2">
                                                        <div class="col-4">
                                                            <div class="popular-thumbnail">
                                                                <img src="<?= $featuredImage ?>" alt="<?= htmlspecialchars($tutorial['title']) ?>" class="img-fluid rounded">
                                                                <div class="popular-overlay">
                                                                    <i class="fas fa-eye"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="popular-content">
                                                                <h6 class="popular-title mb-1"><?= htmlspecialchars(substr($tutorial['title'], 0, 30)) ?><?= strlen($tutorial['title']) > 30 ? '...' : '' ?></h6>
                                                                <div class="popular-meta">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-eye me-1"></i><?= number_format($tutorial['views']) ?> views
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                                <?php $tutorialCount++; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <a href="<?= SITE_URL ?>/category/blog" class="btn btn-outline-success btn-sm w-100">
                                                <i class="fas fa-book-open me-2"></i>Lihat Semua Blog
                                            </a>
                                        <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- About Widget - Full Width -->
                        <div class="widget mb-4">
                            <h5 class="widget-title fw-bold mb-3" style="font-size: 0.95rem;">
                                <i class="fas fa-info-circle text-primary me-2"></i>Tentang DONAN22
                            </h5>
                            <div class="about-content">
                                        <div class="about-card p-3 bg-white rounded shadow-sm">
                                            <div class="text-center mb-3">
                                                <div class="about-logo mb-2">
                                                    <i class="fas fa-rocket text-primary" style="font-size: 2.5rem;"></i>
                                                </div>
                                                <h6 class="fw-bold text-primary mb-2">DONAN22</h6>
                                            </div>
                                            <p class="text-muted small mb-3" style="line-height: 1.5;">
                                                Website DONAN22 menyediakan software, aplikasi, dan blog untuk membantu 
                                                kawan-kawan dalam mengembangkan skill digital, meningkatkan produktivitas, 
                                                dan mempelajari teknologi terkini dengan mudah dan aman.
                                            </p>
                                            <div class="about-stats row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-download text-success mb-1" style="font-size: 1.2rem;"></i>
                                                        <div class="fw-bold small">1000+</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">Software</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-graduation-cap text-warning mb-1" style="font-size: 1.2rem;"></i>
                                                        <div class="fw-bold small">500+</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">Tutorial</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-users text-info mb-1" style="font-size: 1.2rem;"></i>
                                                        <div class="fw-bold small">10K+</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">User</div>
                                                    </div>
                                                </div>
                                            </div>
                                    <div class="about-features">
                                        <div class="feature-item d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" style="font-size: 0.8rem;"></i>
                                            <small class="text-muted">Download Gratis & Aman</small>
                                        </div>
                                        <div class="feature-item d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" style="font-size: 0.8rem;"></i>
                                            <small class="text-muted">Tutorial Lengkap & Mudah</small>
                                        </div>
                                        <div class="feature-item d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" style="font-size: 0.8rem;"></i>
                                            <small class="text-muted">Update Software Terbaru</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <!-- About DONAN Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8 col-md-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="h5 mb-3 text-primary">
                                <i class="fas fa-info-circle me-2"></i>Tentang DONAN22
                            </h3>
                            <p class="mb-3 lh-lg">
                                <strong>DONAN22</strong> adalah platform download software, aplikasi, dan game gratis terpercaya di Indonesia. 
                                Di <strong>DONAN</strong>, Anda bisa download software Windows, Mac, Android dengan mudah dan aman. 
                                <strong>DONAN</strong> menyediakan tutorial lengkap untuk setiap software yang kami bagikan.
                            </p>
                            <p class="mb-3 lh-lg">
                                <strong>Kenapa pilih DONAN?</strong> Karena kami menyediakan software full version, update terbaru, dan 100% gratis. 
                            </p>
                            <a href="<?= SITE_URL ?>/about-donan.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right me-1"></i> Selengkapnya Tentang DONAN
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h3 class="h5 mb-3 text-primary">
                                <i class="fas fa-th me-2"></i>Kategori Populer DONAN
                            </h3>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2 pb-2 border-bottom">
                                    <a href="<?= SITE_URL ?>/category/software" class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-download text-primary me-2"></i> Software
                                    </a>
                                </li>
                                <li class="mb-2 pb-2 border-bottom">
                                    <a href="<?= SITE_URL ?>/category/windows-software" class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fab fa-windows text-primary me-2"></i> Windows Software
                                    </a>
                                </li>
                                <li class="mb-2 pb-2 border-bottom">
                                    <a href="<?= SITE_URL ?>/category/mac-software" class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fab fa-apple text-primary me-2"></i> Mac Software
                                    </a>
                                </li>
                                <li class="mb-2 pb-2 border-bottom">
                                    <a href="<?= SITE_URL ?>/category/mobile-apps" class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-mobile-alt text-primary me-2"></i> Mobile Apps
                                    </a>
                                </li>
                                <li class="mb-2 pb-2 border-bottom">
                                    <a href="<?= SITE_URL ?>/category/game-pc" class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-gamepad text-primary me-2"></i> Game PC
                                    </a>
                                </li>
                                <li class="mb-0">
                                    <a href="<?= SITE_URL ?>/categories.php" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="fas fa-th-large me-1"></i> Lihat Semua Kategori
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <!-- About Section -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-rocket me-2"></i>DONAN22
                    </h5>
                    <p class="mb-3 small">
                        Platform download software, aplikasi, dan game gratis terpercaya untuk 
                        membantu Anda dalam mengembangkan skill digital dan teknologi.
                    </p>
                    <div class="social-links">
                        <a href="https://www.youtube.com/@Donan22" target="_blank" class="text-light me-3" title="YouTube">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a>
                        <a href="https://www.pinterest.com/donan22" target="_blank" class="text-light me-3" title="Pinterest">
                            <i class="fab fa-pinterest fa-lg"></i>
                        </a>
                        <a href="https://www.facebook.com/donan22" target="_blank" class="text-light me-3" title="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Kategori -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Kategori</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/category/software" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-laptop-code me-2"></i>Software
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/category/games" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-gamepad me-2"></i>Games
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/category/blog" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-newspaper me-2"></i>Blog & Tutorial
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/category/windows-software" class="text-light text-decoration-none hover-primary">
                                <i class="fab fa-windows me-2"></i>Windows
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/category/mac-software" class="text-light text-decoration-none hover-primary">
                                <i class="fab fa-apple me-2"></i>Mac
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/categories.php" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-th-large me-2"></i>Semua Kategori
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Menu -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Menu</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/about-donan.php" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-info-circle me-2"></i>Tentang DONAN
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/contact.php" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-envelope me-2"></i>Kontak
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/sitemap.xml" class="text-light text-decoration-none hover-primary" target="_blank">
                                <i class="fas fa-sitemap me-2"></i>Sitemap
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= SITE_URL ?>/search.php" class="text-light text-decoration-none hover-primary">
                                <i class="fas fa-search me-2"></i>Cari
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Kontak & Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">Kontak & Info</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="fas fa-globe me-2 text-primary"></i>
                            <span>localhost/donan22</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <span>contact@localhost</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            <span>Indonesia</span>
                        </li>
                    </ul>
                    <p class="mt-3 small text-muted">
                        Platform download terpercaya sejak 2020. 
                        100% aman dan gratis!
                    </p>
                </div>
            </div>
            
            <hr class="my-4 border-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 small">
                        &copy; <?= date('Y') ?> DONAN22. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 small">
                        Made with <i class="fas fa-heart text-danger"></i> for download community
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
        footer .hover-primary:hover {
            color: #3b82f6 !important;
            padding-left: 5px;
            transition: all 0.3s ease;
        }
        
        footer .social-links a:hover {
            color: #3b82f6 !important;
            transform: translateY(-3px);
            transition: all 0.3s ease;
        }
    </style>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Live Search Implementation for Main Page
        document.addEventListener('DOMContentLoaded', function() {
            let searchTimeout;
            const searchInput = document.getElementById('mainSearchInput');
            const liveResults = document.getElementById('mainLiveResults');

            if (searchInput && liveResults) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    console.log('Search input:', query);
                    
                    clearTimeout(searchTimeout);
                    
                    if (query.length < 2) {
                        liveResults.classList.remove('show');
                        return;
                    }

                    console.log('Showing loading for:', query);
                    // Show loading
                    liveResults.innerHTML = `
                        <div class="live-search-item">
                            <div class="live-search-icon bg-light">
                                <div class="loading-spinner"></div>
                            </div>
                            <div class="live-search-content">
                                <h6>Mencari...</h6>
                                <p>Sedang mencari "${query}"</p>
                            </div>
                        </div>
                    `;
                    liveResults.classList.add('show');

                    searchTimeout = setTimeout(() => {
                        console.log('Making API call for:', query);
                        fetch('search_api_simple.php?q=' + encodeURIComponent(query))
                            .then(response => {
                                console.log('Response received:', response.status);
                                return response.json();
                            })
                            .then(data => {
                                console.log('Data received:', data);
                                if (data.success && data.results.length > 0) {
                                    console.log('Processing results:', data.results.length);
                                    let html = '';
                                    data.results.forEach(item => {
                                        html += `
                                            <a href="${item.url}" class="live-search-item">
                                                <div class="live-search-icon ${item.icon_bg}">
                                                    <i class="${item.icon} text-white"></i>
                                                </div>
                                                <div class="live-search-content">
                                                    <h6>${item.title}</h6>
                                                    <p>${item.category} • ${item.views} views</p>
                                                </div>
                                            </a>
                                        `;
                                    });
                                    
                                    if (data.total > 5) {
                                        html += `
                                            <a href="search.php?q=${encodeURIComponent(query)}" class="live-search-item border-top">
                                                <div class="live-search-icon bg-primary">
                                                    <i class="fas fa-search text-white"></i>
                                                </div>
                                                <div class="live-search-content">
                                                    <h6>Lihat semua ${data.total} hasil</h6>
                                                    <p>Klik untuk hasil lengkap</p>
                                                </div>
                                            </a>
                                        `;
                                    }
                                    
                                    liveResults.innerHTML = html;
                                    console.log('Results displayed successfully');
                                } else {
                                    console.log('No results found');
                                    liveResults.innerHTML = `
                                        <div class="live-search-item">
                                            <div class="live-search-icon bg-light">
                                                <i class="fas fa-search text-muted"></i>
                                            </div>
                                            <div class="live-search-content">
                                                <h6>Tidak ada hasil</h6>
                                                <p>Coba kata kunci lain</p>
                                            </div>
                                        </div>
                                    `;
                                }
                            })
                            .catch(error => {
                                console.error('Search error:', error);
                                liveResults.innerHTML = `
                                    <div class="live-search-item">
                                        <div class="live-search-icon bg-danger">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                        <div class="live-search-content">
                                            <h6>Error</h6>
                                            <p>Terjadi kesalahan: ${error.message}</p>
                                        </div>
                                    </div>
                                `;
                            });
                    }, 500);
                });

                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.live-search-container')) {
                        liveResults.classList.remove('show');
                    }
                });

                // Show results when input focused and has value
                searchInput.addEventListener('focus', function() {
                    if (this.value.trim().length >= 2 && liveResults.innerHTML) {
                        liveResults.classList.add('show');
                    }
                });
            }

            // Other existing interactions
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add hover effects to cards
            document.querySelectorAll('.post-card, .category-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.transition = 'all 0.3s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Search form enhancement
            const searchForm = document.querySelector('form[action*="/search"]');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const input = this.querySelector('input[name="q"]');
                    if (input.value.trim() === '') {
                        e.preventDefault();
                        input.focus();
                    }
                });
            }
        });
    </script>
    
    <!-- Live Search JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/live-search.js"></script>
</body>
</html>