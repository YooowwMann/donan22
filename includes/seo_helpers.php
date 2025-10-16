<?php
/**
 * SEO Helpers - Dynamic Meta Tags Generator
 * DONAN22 Platform
 */

/**
 * Generate SEO-optimized title for post pages
 * 
 * @param string $postTitle Post title
 * @param string $postType Type of post (software, game, tutorial)
 * @param string $version Software version (optional)
 * @return string Optimized title
 */
function generatePostTitle($postTitle, $postType = 'software', $version = '') {
    $title = $postTitle;
    
    switch (strtolower($postType)) {
        case 'software':
        case 'aplikasi':
            if (!empty($version)) {
                $title = "Download {$postTitle} v{$version} Full Version Gratis - DONAN22";
            } else {
                $title = "Download {$postTitle} Full Version Gratis - DONAN22";
            }
            break;
            
        case 'game':
        case 'games':
            $title = "Download {$postTitle} Full Version PC - DONAN22";
            break;
            
        case 'tutorial':
        case 'blog':
        case 'guide':
            $title = "{$postTitle} - Tutorial Lengkap | DONAN22";
            break;
            
        default:
            $title = "{$postTitle} - DONAN22";
    }
    
    // Limit to 60 characters for SEO
    if (strlen($title) > 60) {
        $title = substr($title, 0, 57) . '...';
    }
    
    return $title;
}

/**
 * Generate SEO-optimized meta description
 * 
 * @param string $postTitle Post title
 * @param string $excerpt Post excerpt
 * @param string $postType Type of post
 * @return string Optimized description (150-160 characters)
 */
function generatePostDescription($postTitle, $excerpt = '', $postType = 'software') {
    $description = '';
    
    switch (strtolower($postType)) {
        case 'software':
        case 'aplikasi':
            if (!empty($excerpt)) {
                $description = "Download {$postTitle} full version gratis terbaru. {$excerpt}. Link download aman dan cepat.";
            } else {
                $description = "Download {$postTitle} full version gratis terbaru. Software terbaik dengan fitur lengkap. Link download aman dan cepat.";
            }
            break;
            
        case 'game':
        case 'games':
            if (!empty($excerpt)) {
                $description = "Download game {$postTitle} full version untuk PC. {$excerpt}. Link download + cara install lengkap.";
            } else {
                $description = "Download game {$postTitle} full version untuk PC. Game berkualitas dengan grafis terbaik. Link download + cara install lengkap.";
            }
            break;
            
        case 'tutorial':
        case 'blog':
        case 'guide':
            if (!empty($excerpt)) {
                $description = $excerpt;
            } else {
                $description = "Panduan lengkap {$postTitle}. Tutorial step-by-step dengan gambar dan penjelasan detail. Mudah dipahami untuk pemula.";
            }
            break;
            
        default:
            $description = !empty($excerpt) ? $excerpt : "Artikel tentang {$postTitle} di DONAN22. Informasi lengkap dan terpercaya.";
    }
    
    // Limit to 160 characters for SEO
    if (strlen($description) > 160) {
        $description = substr($description, 0, 157) . '...';
    }
    
    return $description;
}

/**
 * Generate keywords for post
 * 
 * @param string $postTitle Post title
 * @param string $category Category name
 * @param string $postType Type of post
 * @return string Comma-separated keywords
 */
function generatePostKeywords($postTitle, $category = '', $postType = 'software') {
    $keywords = [];
    
    // Add post title as keyword
    $keywords[] = strtolower($postTitle);
    
    // Add category
    if (!empty($category)) {
        $keywords[] = strtolower($category);
    }
    
    // Add common keywords based on type
    switch (strtolower($postType)) {
        case 'software':
        case 'aplikasi':
            $keywords = array_merge($keywords, [
                'download software',
                'software gratis',
                'full version',
                'crack',
                'free download',
                'donan22'
            ]);
            break;
            
        case 'game':
        case 'games':
            $keywords = array_merge($keywords, [
                'download game',
                'game pc',
                'game gratis',
                'full version',
                'free download',
                'donan22'
            ]);
            break;
            
        case 'tutorial':
        case 'blog':
        case 'guide':
            $keywords = array_merge($keywords, [
                'tutorial',
                'cara',
                'panduan',
                'tips',
                'guide',
                'donan22'
            ]);
            break;
    }
    
    return implode(', ', array_unique($keywords));
}

/**
 * Output complete meta tags for a post page
 * 
 * @param array $post Post data array
 * @param string $siteUrl Base site URL
 */
function outputPostMetaTags($post, $siteUrl) {
    // Extract data
    $title = $post['title'] ?? 'Untitled';
    $excerpt = $post['excerpt'] ?? '';
    $postType = $post['post_type_name'] ?? 'post';
    $category = $post['category_name'] ?? '';
    $slug = $post['slug'] ?? '';
    $featuredImage = $post['featured_image'] ?? '';
    $createdAt = $post['created_at'] ?? date('Y-m-d');
    $version = $post['version'] ?? '';
    
    // Generate SEO elements
    $seoTitle = generatePostTitle($title, $postType, $version);
    $seoDescription = generatePostDescription($title, $excerpt, $postType);
    $seoKeywords = generatePostKeywords($title, $category, $postType);
    
    // Fix image URL
    if (!empty($featuredImage)) {
        $featuredImage = preg_replace('/^\.\.\//', '', $featuredImage);
        if (!preg_match('/^(https?:\/\/|\/)/', $featuredImage)) {
            $featuredImage = $siteUrl . '/' . $featuredImage;
        }
    } else {
        $featuredImage = $siteUrl . '/assets/images/og-image.png';
    }
    
    $postUrl = $siteUrl . '/post/' . $slug;
    
    // Output meta tags
    ?>
    <!-- Primary Meta Tags - SEO Optimized -->
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywords) ?>">
    <meta name="author" content="DONAN22">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($postUrl) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= htmlspecialchars($postUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($featuredImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="DONAN22">
    <meta property="og:locale" content="id_ID">
    <meta property="article:published_time" content="<?= date('c', strtotime($createdAt)) ?>">
    <meta property="article:section" content="<?= htmlspecialchars($category) ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($postUrl) ?>">
    <meta property="twitter:domain" content="localhost">
    <meta name="twitter:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($featuredImage) ?>">
    <meta name="twitter:site" content="@donan22">
    <meta name="twitter:creator" content="@donan22">
    <?php
}

/**
 * Generate category page meta tags
 * 
 * @param string $categoryName Category name
 * @param string $categorySlug Category slug
 * @param string $siteUrl Base site URL
 */
function outputCategoryMetaTags($categoryName, $categorySlug, $siteUrl) {
    $title = "Download {$categoryName} Gratis Terbaru - DONAN22";
    $description = "Koleksi lengkap download {$categoryName} gratis terbaru. Software, game, dan aplikasi berkualitas dengan tutorial lengkap di DONAN22.";
    $categoryUrl = $siteUrl . '/category/' . $categorySlug;
    
    ?>
    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="title" content="<?= htmlspecialchars($title) ?>">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($categoryName) ?>, download, gratis, free, donan22">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($categoryUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($categoryUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:site_name" content="DONAN22">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <?php
}
