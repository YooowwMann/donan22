<?php
/**
 * SEO Heading Helper - DONAN22
 * Generate SEO-friendly heading structure for posts
 */

if (!defined('ADMIN_ACCESS') && !function_exists('getSettings')) {
    if (!file_exists(__DIR__ . '/../config_modern.php')) {
        die('Unauthorized access');
    }
}

/**
 * Generate H1 title with SEO optimization
 * Format: Download [Software Name] v[Version] Full Version Gratis
 */
function generateSEOH1($postTitle, $postType = 'software', $version = null) {
    $h1 = '';
    
    // Jika sudah ada kata "Download" di title, gunakan apa adanya
    if (stripos($postTitle, 'download') !== false) {
        $h1 = $postTitle;
    } else {
        // Tambahkan prefix berdasarkan post type
        switch (strtolower($postType)) {
            case 'software':
                $h1 = "Download " . $postTitle;
                if ($version) {
                    $h1 .= " v" . $version;
                }
                $h1 .= " Full Version Gratis";
                break;
            case 'game':
                $h1 = "Download " . $postTitle . " Full Version PC Gratis";
                break;
            case 'tutorial':
                $h1 = $postTitle; // Tutorial biasanya sudah format lengkap
                break;
            default:
                $h1 = $postTitle;
        }
    }
    
    return htmlspecialchars($h1);
}

/**
 * Generate H2 sections for software post
 */
function getSoftwareH2Sections($softwareName) {
    return [
        'about' => "Tentang " . $softwareName,
        'features' => "Fitur Utama " . $softwareName,
        'screenshots' => "Screenshot / Preview " . $softwareName,
        'requirements' => "Spesifikasi & System Requirements",
        'how_to' => "Cara Download dan Install " . $softwareName,
        'download' => "Link Download " . $softwareName,
        'faq' => "FAQ (Frequently Asked Questions)"
    ];
}

/**
 * Generate clean URL slug from title
 * SEO-friendly: lowercase, dash-separated, no stop words
 */
function generateSEOSlug($title) {
    // Convert to lowercase
    $slug = strtolower($title);
    
    // Remove Indonesian stop words
    $stopWords = [
        ' dan ', ' atau ', ' yang ', ' di ', ' ke ', ' dari ', ' untuk ',
        ' dengan ', ' pada ', ' adalah ', ' oleh ', ' ini ', ' itu ',
        ' akan ', ' telah ', ' sudah ', ' masih ', ' dapat ', ' bisa ',
        ' juga ', ' saja ', ' hanya ', ' karena ', ' jika ', ' maka ',
        ' sebagai ', ' antara ', ' dalam ', ' menjadi ', ' memiliki ',
        ' tersebut ', ' tersedia ', ' terbaru ', ' lengkap ', ' gratis ',
        ' terbaik ', ' mudah ', ' cepat ', ' aman '
    ];
    
    foreach ($stopWords as $word) {
        $slug = str_replace($word, '-', $slug);
    }
    
    // Replace special characters
    $slug = str_replace(['&', '+', '|'], '-', $slug);
    
    // Remove version numbers and years from slug (optional - for cleaner URL)
    // $slug = preg_replace('/\b(v?\d+(\.\d+){0,3}|20\d{2})\b/', '', $slug);
    
    // Replace spaces and underscores with dash
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    
    // Remove non-alphanumeric characters (except dash)
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple dashes
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim dashes from start and end
    $slug = trim($slug, '-');
    
    // Limit to 5 words maximum (ideal for SEO)
    $words = explode('-', $slug);
    if (count($words) > 5) {
        $slug = implode('-', array_slice($words, 0, 5));
    }
    
    return $slug;
}

/**
 * Validate slug format
 */
function validateSEOSlug($slug) {
    $errors = [];
    
    // Check if slug is lowercase
    if ($slug !== strtolower($slug)) {
        $errors[] = "Slug harus huruf kecil semua (lowercase)";
    }
    
    // Check if slug uses dash not underscore
    if (strpos($slug, '_') !== false) {
        $errors[] = "Gunakan dash (-) bukan underscore (_)";
    }
    
    // Check slug length (3-5 words ideal)
    $wordCount = count(explode('-', $slug));
    if ($wordCount < 2) {
        $errors[] = "Slug terlalu pendek (minimal 2 kata)";
    }
    if ($wordCount > 7) {
        $errors[] = "Slug terlalu panjang (maksimal 7 kata, ideal 3-5 kata)";
    }
    
    // Check if slug has stop words
    $stopWords = ['dan', 'atau', 'yang', 'dengan', 'untuk', 'dari', 'pada'];
    foreach ($stopWords as $word) {
        if (strpos($slug, $word) !== false) {
            $errors[] = "Hindari stop words: " . $word;
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'score' => empty($errors) ? 100 : max(0, 100 - (count($errors) * 20))
    ];
}

/**
 * Generate breadcrumb with proper heading hierarchy
 */
function generateSEOBreadcrumb($category, $postTitle) {
    $breadcrumb = [
        ['name' => 'Home', 'url' => SITE_URL],
        ['name' => ucfirst($category), 'url' => SITE_URL . '/' . strtolower($category)],
        ['name' => $postTitle, 'url' => null] // Current page
    ];
    
    return $breadcrumb;
}

/**
 * Check if content has proper heading structure
 */
function validateHeadingStructure($content) {
    $issues = [];
    
    // Count H1 tags
    preg_match_all('/<h1[^>]*>/i', $content, $h1Matches);
    $h1Count = count($h1Matches[0]);
    
    if ($h1Count === 0) {
        $issues[] = "Tidak ada H1 - harus ada 1 H1 per halaman";
    } elseif ($h1Count > 1) {
        $issues[] = "Terlalu banyak H1 (" . $h1Count . ") - harus hanya 1 H1 per halaman";
    }
    
    // Check for skipped heading levels
    if (preg_match('/<h1[^>]*>.*?<h3/is', $content)) {
        $issues[] = "Skip heading level terdeteksi (H1 → H3 tanpa H2)";
    }
    
    // Check if headings are descriptive (not just numbers or too short)
    preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $content, $headings);
    foreach ($headings[1] as $heading) {
        $headingText = strip_tags($heading);
        if (strlen(trim($headingText)) < 10) {
            $issues[] = "Heading terlalu pendek: '" . $headingText . "' (minimal 10 karakter)";
        }
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues,
        'h1_count' => $h1Count
    ];
}

/**
 * Display SEO heading tips in admin
 */
function showHeadingTips() {
    ?>
    <div class="seo-heading-tips card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-lightbulb"></i> Tips SEO Heading Structure
        </div>
        <div class="card-body">
            <h6 class="fw-bold">✅ BENAR:</h6>
            <ul>
                <li><strong>H1:</strong> Download Adobe Photoshop 2025 v24.0 Full Version Gratis</li>
                <li><strong>H2:</strong> Tentang Adobe Photoshop 2025</li>
                <li><strong>H2:</strong> Fitur Utama Adobe Photoshop 2025</li>
                <li><strong>H3:</strong> Langkah 1: Download File</li>
                <li><strong>H3:</strong> Langkah 2: Extract File</li>
            </ul>
            
            <h6 class="fw-bold text-danger">❌ SALAH:</h6>
            <ul>
                <li>Lebih dari 1 H1 per halaman</li>
                <li>Skip heading level (H1 → H3 tanpa H2)</li>
                <li>Heading tidak deskriptif ("Download", "Info", dll)</li>
            </ul>
            
            <h6 class="fw-bold">Checklist:</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check1">
                <label class="form-check-label" for="check1">
                    Hanya 1 H1 per halaman
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check2">
                <label class="form-check-label" for="check2">
                    H1 mengandung keyword utama
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check3">
                <label class="form-check-label" for="check3">
                    Tidak ada skip heading level
                </label>
            </div>
        </div>
    </div>
    <?php
}
