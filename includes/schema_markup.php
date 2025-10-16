<?php
/**
 * Complete Schema.org Structured Data (JSON-LD) for DONAN22.com
 * Include this file in <head> section: require_once 'includes/schema_markup.php';
 */

// Ensure SITE_URL is defined
$site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/donan22';
$current_url = $site_url . $_SERVER['REQUEST_URI'];

// Organization Schema
$organizationSchema = [
    "@context" => "https://schema.org",
    "@type" => "Organization",
    "name" => "DONAN22",
    "alternateName" => "Donan 22",
    "url" => $site_url,
    "logo" => $site_url . "/assets/images/logo.png",
    "description" => "Platform download software, aplikasi, game, dan tools gratis terpercaya di Indonesia dengan tutorial lengkap",
    "foundingDate" => "2020",
    "sameAs" => [
        "https://www.youtube.com/@Donan22",
        "https://www.pinterest.com/donan22",
        "https://www.facebook.com/donan22",
        "https://twitter.com/donan22"
    ],
    "contactPoint" => [
        "@type" => "ContactPoint",
        "contactType" => "Customer Service",
        "email" => "contact@localhost",
        "availableLanguage" => ["Indonesian", "English"]
    ]
];

// WebSite Schema with SearchAction
$websiteSchema = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "DONAN22",
    "url" => $site_url,
    "description" => "Download software gratis, aplikasi PC, game, dan tutorial IT terlengkap di Indonesia",
    "publisher" => [
        "@type" => "Organization",
        "name" => "DONAN22",
        "logo" => [
            "@type" => "ImageObject",
            "url" => $site_url . "/assets/images/logo.png",
            "width" => 250,
            "height" => 60
        ]
    ],
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => [
            "@type" => "EntryPoint",
            "urlTemplate" => $site_url . "/search.php?q={search_term_string}"
        ],
        "query-input" => "required name=search_term_string"
    ],
    "inLanguage" => "id-ID"
];

// BreadcrumbList Schema - Dynamic based on current page
$breadcrumbItems = [];
$breadcrumbItems[] = [
    "@type" => "ListItem",
    "position" => 1,
    "name" => "Home",
    "item" => $site_url
];

// Add dynamic breadcrumb based on URL
$path = parse_url($current_url, PHP_URL_PATH);
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $breadcrumbItems[] = [
        "@type" => "ListItem",
        "position" => 2,
        "name" => ucwords(str_replace('-', ' ', $_GET['slug'])),
        "item" => $current_url
    ];
}

$breadcrumbSchema = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => $breadcrumbItems
];

// Output all schemas
?>
<script type="application/ld+json">
<?= json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<script type="application/ld+json">
<?= json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<?php if (count($breadcrumbItems) > 1): ?>
<script type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
