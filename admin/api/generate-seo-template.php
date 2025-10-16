<?php
/**
 * API: Generate SEO Content Template
 * Generate structured content with proper heading hierarchy
 */

define('ADMIN_ACCESS', true);
require_once '../../config_modern.php';
require_once '../../includes/seo_content_template.php';
require_once '../../includes/seo_heading_helper.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? '';
$postType = $input['post_type'] ?? 'software';
$version = $input['version'] ?? '';
$developer = $input['developer'] ?? '';
$fileSize = $input['file_size'] ?? '';
$categorySlug = $input['category_slug'] ?? '';

// Validate
if (empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Title is required']);
    exit;
}

// Extract software/game name
$softwareName = preg_replace('/^(download|free|gratis|full|version)/i', '', $title);
$softwareName = trim($softwareName);

// Default features based on category
$defaultFeatures = [];
if (stripos($categorySlug, 'adobe') !== false || stripos($title, 'adobe') !== false) {
    $defaultFeatures = [
        'AI-powered Neural Filters untuk editing cerdas',
        'Cloud Document Sync - akses file dari mana saja',
        'Advanced Layer Management dengan Smart Objects',
        'Content-Aware Fill untuk hapus objek secara otomatis',
        'Camera RAW support untuk edit foto profesional',
        'Batch processing untuk edit multiple files',
        'Integration dengan Adobe Creative Cloud'
    ];
} elseif (stripos($categorySlug, 'microsoft') !== false || stripos($title, 'office') !== false || stripos($title, 'windows') !== false) {
    $defaultFeatures = [
        'Interface modern dan user-friendly',
        'Support berbagai format file (docx, xlsx, pptx)',
        'Cloud integration dengan OneDrive',
        'Real-time collaboration untuk kerja tim',
        'Template profesional siap pakai',
        'Advanced formulas dan macros',
        'Compatibility dengan semua versi Office'
    ];
} elseif (stripos($postType, 'game') !== false) {
    $defaultFeatures = [
        'Graphics HD dengan efek visual memukau',
        'Gameplay smooth dengan FPS tinggi',
        'Multiple game modes (Single/Multiplayer)',
        'Storyline menarik dan challenging',
        'Customizable characters dan weapons',
        'Regular updates dengan konten baru',
        'Support controller dan keyboard'
    ];
} else {
    // Generic software features
    $defaultFeatures = [
        'Interface yang mudah digunakan',
        'Performa cepat dan stabil',
        'Support berbagai format file',
        'Regular updates dan bug fixes',
        'Kompatibel dengan Windows 10/11',
        'Lightweight dan tidak berat',
        'Free lifetime updates'
    ];
}

// Default system requirements
$defaultRequirements = [
    'os' => 'Windows 10/11 (64-bit)',
    'processor' => 'Intel Core i3 atau AMD equivalent',
    'ram' => '4GB minimum, 8GB recommended',
    'storage' => '500MB - 2GB available space',
    'graphics' => 'DirectX 11 compatible',
    'additional' => 'Internet connection untuk aktivasi'
];

// For games, use higher requirements
if (stripos($postType, 'game') !== false) {
    $defaultRequirements = [
        'os' => 'Windows 10/11 (64-bit)',
        'processor' => 'Intel Core i5 atau AMD Ryzen 5',
        'ram' => '8GB minimum, 16GB recommended',
        'storage' => '50GB - 100GB available space (SSD recommended)',
        'graphics' => 'NVIDIA GTX 1050 Ti / AMD RX 560 (4GB VRAM)',
        'directx' => 'Version 12',
        'additional' => 'Internet connection untuk multiplayer'
    ];
}

// Default description
$defaultDescription = "**{$softwareName}** adalah software/aplikasi yang powerful dan mudah digunakan untuk membantu pekerjaan Anda lebih efisien. Dengan berbagai fitur canggih dan interface yang user-friendly, {$softwareName} menjadi pilihan terbaik untuk kebutuhan Anda.";

if (stripos($postType, 'game') !== false) {
    $defaultDescription = "**{$softwareName}** adalah game yang seru dan menantang dengan graphics HD yang memukau. Nikmati gameplay yang smooth, storyline yang menarik, dan berbagai mode permainan yang tidak akan membuat Anda bosan!";
}

// Prepare data for template
$templateData = [
    'software_name' => $softwareName,
    'title' => $title,
    'version' => $version,
    'developer' => $developer,
    'file_size' => $fileSize,
    'description' => $defaultDescription,
    'features' => $defaultFeatures,
    'requirements' => $defaultRequirements,
    'screenshots' => [] // Will be added manually
];

// Generate content based on post type
if (stripos($postType, 'game') !== false) {
    $templateData['game_name'] = $softwareName;
    $templateData['genre'] = 'Action'; // Default
    $content = generateGameContentTemplate($templateData);
} else {
    $content = generateSoftwareContentTemplate($templateData);
}

// Generate SEO slug
$seoSlug = generateSEOSlug($title);

// Validate slug
$slugValidation = validateSEOSlug($seoSlug);

// Generate H1
$seoH1 = generateSEOH1($title, $postType, $version);

// Count headings
preg_match_all('/<h2/', $content, $h2Matches);
preg_match_all('/<h3/', $content, $h3Matches);

// Response
$response = [
    'success' => true,
    'content' => $content,
    'seo_h1' => $seoH1,
    'suggested_slug' => $seoSlug,
    'slug_validation' => $slugValidation,
    'stats' => [
        'h2_count' => count($h2Matches[0]),
        'h3_count' => count($h3Matches[0]),
        'word_count' => str_word_count(strip_tags($content)),
        'char_count' => strlen(strip_tags($content))
    ],
    'message' => 'SEO template generated successfully!',
    'tips' => [
        'Upload featured image (1200x630px recommended)',
        'Add real screenshots di section "Screenshot / Preview"',
        'Update system requirements sesuai software',
        'Add more features if needed',
        'Proofread dan customize content'
    ]
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
