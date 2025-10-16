<?php
/**
 * SEO Meta Tags for DONAN22.com
 * Optimized meta tags untuk semua halaman
 * Usage: require_once 'includes/seo_meta_tags.php';
 */

// Default values
$page_title = $page_title ?? 'DONAN22 - Download Software & Aplikasi Gratis Terpercaya';
$page_description = $page_description ?? 'Platform download software, aplikasi PC, game, dan tools gratis terlengkap. DONAN22 menyediakan software Windows, Mac, Android dengan tutorial lengkap dan aman.';
$page_keywords = $page_keywords ?? 'DONAN22, download software gratis, aplikasi pc gratis, download game gratis, software windows, aplikasi android, tutorial IT, adobe photoshop, microsoft office, video editor';
$page_image = $page_image ?? SITE_URL . '/assets/images/og-image.png';
$page_url = $page_url ?? SITE_URL . $_SERVER['REQUEST_URI'];
$page_type = $page_type ?? 'website';
?>

<!-- Primary Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- SEO Meta Tags -->
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="title" content="<?= htmlspecialchars($page_title) ?>">
<meta name="description" content="<?= htmlspecialchars($page_description) ?>">
<meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
<meta name="author" content="DONAN22">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow">
<meta name="bingbot" content="index, follow">
<meta name="language" content="Indonesian">
<meta name="geo.region" content="ID">
<meta name="geo.placename" content="Indonesia">
<meta name="revisit-after" content="1 days">

<!-- Canonical URL -->
<link rel="canonical" href="<?= htmlspecialchars($page_url) ?>">

<!-- Open Graph / Facebook Meta Tags -->
<meta property="og:type" content="<?= htmlspecialchars($page_type) ?>">
<meta property="og:url" content="<?= htmlspecialchars($page_url) ?>">
<meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
<meta property="og:image" content="<?= htmlspecialchars($page_image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="DONAN22">
<meta property="og:locale" content="id_ID">
<meta property="fb:app_id" content="">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= htmlspecialchars($page_url) ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($page_image) ?>">
<meta name="twitter:creator" content="@donan22">
<meta name="twitter:site" content="@donan22">

<!-- Additional Meta Tags -->
<meta name="format-detection" content="telephone=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DONAN22">

<!-- Favicon and App Icons -->
<link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= SITE_URL ?>/assets/images/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/images/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_URL ?>/assets/images/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="<?= SITE_URL ?>/assets/images/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="<?= SITE_URL ?>/assets/images/android-chrome-512x512.png">

<!-- Web App Manifest -->
<link rel="manifest" href="<?= SITE_URL ?>/site.webmanifest">

<!-- Theme Color -->
<meta name="theme-color" content="#3b82f6">
<meta name="msapplication-TileColor" content="#3b82f6">
<meta name="msapplication-config" content="<?= SITE_URL ?>/browserconfig.xml">

<!-- Google Site Verification -->
<meta name="google-site-verification" content="b735655a83d6bddd">

<!-- Preconnect to external resources -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

<!-- DNS Prefetch -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
