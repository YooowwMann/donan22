<?php
/**
 * Software/Post Article Schema Template for DONAN22.com
 * Include this in post.php to add rich Article schema
 * 
 * Required variables before including:
 * - $post: Array with post data (title, excerpt, featured_image, created_at, updated_at, author, slug)
 * - $categoryName: Category name
 */

// Ensure required data is available
if (!isset($post) || empty($post)) {
    return;
}

// Prepare data
$postTitle = $post['title'] ?? 'Untitled';
$postExcerpt = $post['excerpt'] ?? $post['description'] ?? '';
$postImage = $post['featured_image'] ?? SITE_URL . '/assets/images/default-post.png';
$postUrl = SITE_URL . '/post/' . ($post['slug'] ?? '');
$postDatePublished = $post['created_at'] ?? date('c');
$postDateModified = $post['updated_at'] ?? $postDatePublished;
$authorName = $post['author_name'] ?? 'DONAN22';
$categoryName = $categoryName ?? 'Software';

// Ensure full URL for image
if (strpos($postImage, 'http') !== 0) {
    $postImage = SITE_URL . '/' . ltrim($postImage, '/');
}

// Build Article Schema
$articleSchema = [
    "@context" => "https://schema.org",
    "@type" => "Article",
    "headline" => $postTitle,
    "description" => strip_tags($postExcerpt),
    "image" => [
        "@type" => "ImageObject",
        "url" => $postImage,
        "width" => 1200,
        "height" => 630
    ],
    "datePublished" => date('c', strtotime($postDatePublished)),
    "dateModified" => date('c', strtotime($postDateModified)),
    "author" => [
        "@type" => "Person",
        "name" => $authorName
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => "DONAN22",
        "logo" => [
            "@type" => "ImageObject",
            "url" => SITE_URL . "/assets/images/logo.png",
            "width" => 250,
            "height" => 60
        ]
    ],
    "mainEntityOfPage" => [
        "@type" => "WebPage",
        "@id" => $postUrl
    ],
    "articleSection" => $categoryName,
    "keywords" => "DONAN22, download software gratis, " . strtolower($postTitle),
    "inLanguage" => "id-ID"
];

// Add SoftwareApplication schema if this is software
if (stripos($categoryName, 'software') !== false || 
    stripos($categoryName, 'aplikasi') !== false || 
    stripos($categoryName, 'app') !== false) {
    
    $softwareSchema = [
        "@context" => "https://schema.org",
        "@type" => "SoftwareApplication",
        "name" => $postTitle,
        "description" => strip_tags($postExcerpt),
        "applicationCategory" => $categoryName,
        "offers" => [
            "@type" => "Offer",
            "price" => "0",
            "priceCurrency" => "IDR"
        ],
        "operatingSystem" => "Windows, Mac, Android",
        "url" => $postUrl
    ];
}

// Add BreadcrumbList Schema
$breadcrumbSchema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Home",
            "item" => SITE_URL
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => $categoryName,
            "item" => SITE_URL . "/category/" . strtolower(str_replace(' ', '-', $categoryName))
        ],
        [
            "@type" => "ListItem",
            "position" => 3,
            "name" => $postTitle,
            "item" => $postUrl
        ]
    ]
];
?>

<!-- Article Schema Markup -->
<script type="application/ld+json">
<?= json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>

<?php if (isset($softwareSchema)): ?>
<!-- Software Application Schema -->
<script type="application/ld+json">
<?= json_encode($softwareSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
