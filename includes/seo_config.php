<?php
/**
 * FIXED SEO CONFIGURATION - DONAN22.COM
 * ===================================
 * LEGAL & SAFE VERSION - Optimized for Legal Software Downloads
 * 
 * @version 2.0
 * @date 2025-10-12
 * @status PRODUCTION READY
 */

// ============================================
// SITE INFORMATION
// ============================================

define('SEO_SITE_NAME', 'DONAN22');
define('SEO_SITE_URL', 'http://localhost/donan22');
define('SEO_SITE_DOMAIN', 'localhost');

// ============================================
// DEFAULT SEO VALUES (LEGAL & SAFE)
// ============================================

define('SEO_DEFAULT_TITLE', 'DONAN22 - Download Software & Aplikasi Gratis Terpercaya 2025');
define('SEO_DEFAULT_DESCRIPTION', 'Platform download software, aplikasi PC, game, dan tools gratis resmi terpercaya di Indonesia. Download legal dan aman dengan tutorial lengkap. Freeware, open source, dan software trial terbaru 2025.');
define('SEO_DEFAULT_KEYWORDS', 'download software gratis, aplikasi pc gratis legal, freeware terbaik, software open source, tutorial software, download legal indonesia, aplikasi terpercaya, software trial, donan22');

// ============================================
// SOCIAL MEDIA
// ============================================

define('SEO_FACEBOOK_PAGE', 'https://www.facebook.com/donan22');
define('SEO_TWITTER_HANDLE', '@donan22');
define('SEO_TWITTER_CREATOR', '@donan22');

// ============================================
// BRANDING
// ============================================

define('SEO_LOGO_URL', 'http://localhost/donan22/assets/images/logo.png');
define('SEO_LOGO_WIDTH', 600);
define('SEO_LOGO_HEIGHT', 60);

define('SEO_OG_IMAGE_DEFAULT', 'http://localhost/donan22/assets/images/og-default.jpg');
define('SEO_OG_IMAGE_WIDTH', 1200);
define('SEO_OG_IMAGE_HEIGHT', 630);

// ============================================
// HIGH-VALUE KEYWORDS (LEGAL & SAFE)
// ============================================

$SEO_HIGH_VALUE_KEYWORDS = [
    // Legal Software
    'download software gratis resmi',
    'free software download',
    'software legal indonesia',
    'freeware terbaik',
    'open source software',
    'software trial gratis',
    
    // Applications
    'aplikasi pc gratis',
    'download aplikasi legal',
    'software windows gratis',
    'aplikasi mac gratis',
    'tools productivity',
    
    // Tutorials & Guides
    'tutorial software',
    'panduan aplikasi',
    'cara install software',
    'review software terpercaya',
    
    // Categories
    'video editor gratis',
    'photo editor free',
    'office software',
    'antivirus gratis',
    'download manager',
    
    // Year-based (for freshness)
    'software terbaru 2025',
    'aplikasi 2025',
    'freeware 2025',
    
    // Modifiers (LEGAL ONLY)
    'full version legal',
    'gratis resmi',
    'official download',
    'terpercaya',
    'aman digunakan',
];

// ============================================
// KEYWORD MODIFIERS (LEGAL ONLY)
// ============================================

$SEO_KEYWORD_MODIFIERS = [
    'Download Gratis Resmi',
    'Free Download Legal',
    'Full Version Trial',
    'Official Version',
    'Terbaru 2025',
    'Latest Update',
    'Gratis Terpercaya',
    'Aman & Legal',
    'Panduan Lengkap',
    'Review Terbaru',
];

// ============================================
// CATEGORY-SPECIFIC SEO (LEGAL FOCUS)
// ============================================

$SEO_CATEGORY_META = [
    'software' => [
        'title_suffix' => 'Download Gratis Resmi & Legal',
        'keywords' => 'software download, free software legal, freeware, trial version, software gratis resmi',
        'description_template' => 'Download %s gratis dan legal! Versi trial/freeware resmi dengan panduan lengkap di DONAN22.com'
    ],
    'aplikasi' => [
        'title_suffix' => 'Download Aplikasi Gratis Terpercaya',
        'keywords' => 'aplikasi gratis, download aplikasi legal, apps terpercaya, aplikasi pc gratis',
        'description_template' => 'Download aplikasi %s gratis dan terpercaya. Panduan instalasi lengkap hanya di DONAN22.com'
    ],
    'tutorial' => [
        'title_suffix' => 'Panduan Lengkap & Tips',
        'keywords' => 'tutorial software, panduan aplikasi, cara menggunakan, tips tricks, guide',
        'description_template' => 'Tutorial %s lengkap dengan panduan step-by-step. Belajar mudah di DONAN22.com'
    ],
    'tools' => [
        'title_suffix' => 'Download Tools Gratis Terbaik',
        'keywords' => 'download tools, utilities gratis, tools terbaik, free tools',
        'description_template' => 'Download %s gratis! Tools terbaik dan terpercaya untuk produktivitas Anda.'
    ],
    'game' => [
        'title_suffix' => 'Download Game Gratis Legal',
        'keywords' => 'game gratis, free games, download game legal, indie games, free to play',
        'description_template' => 'Download game %s gratis dan legal! Free-to-play, indie games, dan demo resmi.'
    ],
    'freeware' => [
        'title_suffix' => 'Freeware Terbaik & Gratis Selamanya',
        'keywords' => 'freeware, software gratis permanen, aplikasi gratis, free forever',
        'description_template' => 'Download %s freeware terbaik! Gratis selamanya dan legal untuk digunakan.'
    ],
    'open-source' => [
        'title_suffix' => 'Open Source Software Terbaik',
        'keywords' => 'open source, software bebas, FOSS, free open source software',
        'description_template' => 'Download %s open source gratis! Software bebas dengan kode terbuka dan legal.'
    ],
];

// ============================================
// STRUCTURED DATA TEMPLATES
// ============================================

$SEO_ORGANIZATION_SCHEMA = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'DONAN22',
    'alternateName' => 'Donan 22',
    'url' => 'http://localhost/donan22',
    'logo' => 'http://localhost/donan22/assets/images/logo.png',
    'description' => 'Platform download software dan aplikasi gratis resmi terpercaya di Indonesia',
    'foundingDate' => '2020',
    'sameAs' => [
        'https://www.facebook.com/donan22',
        'https://twitter.com/donan22',
        'https://www.youtube.com/@Donan22',
        'https://www.pinterest.com/donan22',
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'contactType' => 'Customer Service',
        'email' => 'contact@localhost',
        'availableLanguage' => ['Indonesian', 'English']
    ],
];

// ============================================
// CONTENT GUIDELINES
// ============================================

$SEO_CONTENT_GUIDELINES = [
    'min_word_count' => 800,
    'optimal_word_count' => 1500,
    'max_keyword_density' => 2.5, // percent
    'min_images' => 1,
    'recommended_images' => 3,
    'internal_links_min' => 2,
    'internal_links_recommended' => 5,
];

// ============================================
// BLACKLISTED KEYWORDS (DO NOT USE)
// ============================================

$SEO_BLACKLISTED_KEYWORDS = [
    // Illegal/Risky keywords - NEVER USE
    'crack',
    'keygen',
    'activator',
    'pirate',
    'pirated',
    'nulled',
    'warez',
    'torrent crack',
    'serial key',
    'license key generator',
    'full crack',
    'cracked version',
    'patch',
    'loader',
];

// ============================================
// SAFE ALTERNATIVE KEYWORDS
// ============================================

$SEO_SAFE_ALTERNATIVES = [
    'crack' => ['trial version', 'free version', 'freeware', 'demo version'],
    'full version' => ['complete edition', 'full features', 'standard version'],
    'free download' => ['download gratis resmi', 'official free download', 'legal download'],
    'activator' => ['official installer', 'setup file', 'installation guide'],
];

// ============================================
// SEO FUNCTIONS
// ============================================

/**
 * Check if content contains blacklisted keywords
 */
function seo_check_blacklisted_content($content) {
    global $SEO_BLACKLISTED_KEYWORDS;
    
    $content_lower = strtolower($content);
    $found_keywords = [];
    
    foreach ($SEO_BLACKLISTED_KEYWORDS as $keyword) {
        if (strpos($content_lower, strtolower($keyword)) !== false) {
            $found_keywords[] = $keyword;
        }
    }
    
    return [
        'is_safe' => empty($found_keywords),
        'blacklisted_keywords' => $found_keywords
    ];
}

/**
 * Suggest safe alternatives for risky keywords
 */
function seo_suggest_alternatives($keyword) {
    global $SEO_SAFE_ALTERNATIVES;
    
    $keyword_lower = strtolower($keyword);
    
    foreach ($SEO_SAFE_ALTERNATIVES as $risky => $alternatives) {
        if (strpos($keyword_lower, $risky) !== false) {
            return $alternatives;
        }
    }
    
    return [];
}

/**
 * Generate SEO-friendly title
 */
function seo_generate_title($base_title, $category = 'software', $year = null) {
    global $SEO_CATEGORY_META;
    
    $year = $year ?? date('Y');
    $suffix = $SEO_CATEGORY_META[$category]['title_suffix'] ?? 'Download Gratis';
    
    return "{$base_title} - {$suffix} {$year}";
}

/**
 * Generate SEO-friendly description
 */
function seo_generate_description($software_name, $category = 'software') {
    global $SEO_CATEGORY_META;
    
    $template = $SEO_CATEGORY_META[$category]['description_template'] ?? 
                'Download %s gratis dan legal di DONAN22.com';
    
    return sprintf($template, $software_name);
}

/**
 * Validate content for SEO
 */
function seo_validate_content($title, $description, $content, $keywords = '') {
    $issues = [];
    $warnings = [];
    $score = 100;
    
    // Check title length
    $title_length = strlen($title);
    if ($title_length < 30 || $title_length > 60) {
        $warnings[] = "Title length should be 30-60 characters (current: {$title_length})";
        $score -= 5;
    }
    
    // Check description length
    $desc_length = strlen($description);
    if ($desc_length < 120 || $desc_length > 160) {
        $warnings[] = "Meta description should be 120-160 characters (current: {$desc_length})";
        $score -= 5;
    }
    
    // Check content length
    $word_count = str_word_count(strip_tags($content));
    if ($word_count < 500) {
        $issues[] = "Content is too short (current: {$word_count} words, minimum: 500)";
        $score -= 15;
    } elseif ($word_count < 800) {
        $warnings[] = "Content could be longer for better SEO (current: {$word_count} words, recommended: 800+)";
        $score -= 5;
    }
    
    // Check for blacklisted keywords
    $full_content = $title . ' ' . $description . ' ' . $content . ' ' . $keywords;
    $blacklist_check = seo_check_blacklisted_content($full_content);
    
    if (!$blacklist_check['is_safe']) {
        $issues[] = "CRITICAL: Contains blacklisted keywords: " . implode(', ', $blacklist_check['blacklisted_keywords']);
        $score -= 30;
    }
    
    return [
        'score' => max(0, $score),
        'is_safe' => empty($issues),
        'issues' => $issues,
        'warnings' => $warnings
    ];
}

// ============================================
// LEGAL DISCLAIMER
// ============================================

define('SEO_LEGAL_DISCLAIMER', 'DONAN22 hanya menyediakan link download ke software legal, freeware, open source, dan versi trial resmi dari developer. Kami tidak mendistribusikan, menyediakan, atau mendukung software bajakan, crack, keygen, atau aktivator ilegal.');

?>
