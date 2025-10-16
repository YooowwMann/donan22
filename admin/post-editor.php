<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
require_once '../includes/MonetizationManager.php';
requireLogin();

// Check permissions - all roles can manage posts
requirePermission('manage_posts');

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Initialize MonetizationManager
$monetizationManager = new MonetizationManager($pdo);

// Attractive Title Generator Function
function generateAttractiveTitle($originalTitle, $categorySlug, $postType = 'software') {
    $attractiveTitles = [];
    $title = trim($originalTitle);
    
    if (empty($title)) return [];
    
    // Mobile Apps Category
    if (strpos($categorySlug, 'mobile') !== false || strpos($categorySlug, 'app') !== false) {
        $attractiveTitles[] = "{$title} - Download Gratis Terbaru 2025";
        $attractiveTitles[] = "{$title} Premium MOD APK - Fitur Terlengkap";
        $attractiveTitles[] = "Review {$title}: Aplikasi Wajib Install di HP";
        $attractiveTitles[] = "{$title} Latest Version - Free Download";
        $attractiveTitles[] = "{$title} Pro - Unlock All Features";
        $attractiveTitles[] = "{$title} Terbaru: Update Fitur Amazing!";
    }
    
    // Blog Category  
    else if (strpos($categorySlug, 'blog') !== false || $postType === 'blog') {
        $attractiveTitles[] = "{$title}: Panduan Lengkap & Tutorial 2025";
        $attractiveTitles[] = "{$title} - Tips & Trik yang Wajib Kamu Tahu";
        $attractiveTitles[] = "Cara {$title}: Step by Step untuk Pemula";
        $attractiveTitles[] = "{$title}: Review Honest & Rekomendasi Terbaik";
        $attractiveTitles[] = "Update {$title}: Semua yang Perlu Kamu Ketahui";
        $attractiveTitles[] = "{$title} - Rahasia Sukses yang Jarang Dibahas";
    }
    
    // General Software
    else {
        $attractiveTitles[] = "{$title} Full Version - Download Gratis";
        $attractiveTitles[] = "{$title} Premium - Activated + Crack";
        $attractiveTitles[] = "{$title} Pro - Unlock All Features";
        $attractiveTitles[] = "{$title} Latest 2025 - Free Download";
        $attractiveTitles[] = "{$title} Terbaru: Fitur Terlengkap!";
    }
    
    return $attractiveTitles;
}

// SEO Calculation Function
function calculateSEOScore($title, $content, $metaTitle, $metaDescription, $focusKeyword) {
    $score = 0;
    $maxScore = 100;
    $issues = [];
    $passed = [];
    
    // Title Length Check (20 points)
    $titleToCheck = !empty($metaTitle) ? $metaTitle : $title;
    $titleLength = strlen($titleToCheck);
    if ($titleLength >= 30 && $titleLength <= 60) {
        $score += 20;
        $passed[] = "Title length is optimal ({$titleLength} chars)";
    } else if ($titleLength > 0) {
        $score += 10;
        $issues[] = "Title should be 30-60 characters (currently {$titleLength})";
    } else {
        $issues[] = "Title is required";
    }
    
    // Focus Keyword in Title (15 points)
    if (!empty($focusKeyword) && !empty($titleToCheck)) {
        if (stripos($titleToCheck, $focusKeyword) !== false) {
            $score += 15;
            $passed[] = "Focus keyword found in title";
        } else {
            $issues[] = "Focus keyword not found in title";
        }
    } else if (empty($focusKeyword)) {
        $issues[] = "Focus keyword not set";
    }
    
    // Content Length (25 points)
    $wordCount = str_word_count($content);
    if ($wordCount >= 300) {
        $score += 25;
        $passed[] = "Content has sufficient words ({$wordCount} words)";
    } else if ($wordCount >= 150) {
        $score += 15;
        $issues[] = "Content should be at least 300 words (currently {$wordCount})";
    } else if ($wordCount > 0) {
        $score += 5;
        $issues[] = "Content is too short ({$wordCount} words, need 300+)";
    } else {
        $issues[] = "Content is required";
    }
    
    // Focus Keyword in Content (15 points)
    if (!empty($focusKeyword) && !empty($content)) {
        if (stripos($content, $focusKeyword) !== false) {
            $score += 15;
            $passed[] = "Focus keyword found in content";
        } else {
            $issues[] = "Focus keyword should appear in content";
        }
    }
    
    // Meta Description (20 points)
    if (!empty($metaDescription)) {
        $metaLength = strlen($metaDescription);
        if ($metaLength >= 120 && $metaLength <= 160) {
            $score += 20;
            $passed[] = "Meta description length is optimal ({$metaLength} chars)";
        } else if ($metaLength > 0) {
            $score += 10;
            $issues[] = "Meta description should be 120-160 characters (currently {$metaLength})";
        }
    } else {
        $issues[] = "Meta description is missing";
    }
    
    // Focus Keyword in Meta Description (5 points)
    if (!empty($focusKeyword) && !empty($metaDescription)) {
        if (stripos($metaDescription, $focusKeyword) !== false) {
            $score += 5;
            $passed[] = "Focus keyword found in meta description";
        } else {
            $issues[] = "Focus keyword should appear in meta description";
        }
    }
    
    $percentage = round(($score / $maxScore) * 100);
    
    return [
        'score' => $score,
        'maxScore' => $maxScore,
        'percentage' => $percentage,
        'status' => $percentage >= 80 ? 'Excellent' : ($percentage >= 60 ? 'Good' : 'Needs Work'),
        'issues' => $issues,
        'passed' => $passed
    ];
}

$postId = $_GET['id'] ?? null;
$postType = $_GET['type'] ?? 'software';
$isEdit = (bool)$postId;
$pageTitle = $isEdit ? 'Edit Post' : 'Create New ' . ucfirst($postType);

// Initialize default post data
$post = [
    'title' => '',
    'description' => '',
    'content' => '',
    'status' => 'draft',
    'category_id' => '',
    'featured_image' => '',
    'version' => '',
    'file_size' => '',
    'requirements' => '',
    'download_links' => []
];

// Get all active categories
try {
    $stmt = $pdo->query("
        SELECT 
            c.*,
            COUNT(DISTINCT p.id) as post_count
        FROM categories c 
        LEFT JOIN posts p ON (c.id = p.category_id OR c.id = p.secondary_category_id) 
            AND p.status = 'published' 
            AND p.deleted_at IS NULL
        WHERE c.status != 'deleted' OR c.status IS NULL
        GROUP BY c.id
        ORDER BY post_count DESC, c.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        $error_msg = "Warning: No categories found. Please create categories first.";
    }
} catch (PDOException $e) {
    error_log("Error loading categories: " . $e->getMessage());
    $categories = [];
    $error_msg = "Error loading categories. Please try again.";
}

// Load post data if in edit mode
if ($isEdit && $postId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$postId]);
        $postData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($postData) {
            // Populate $post array with existing data
            $post = $postData;
            
            // Debug: Log featured image
            error_log("Post {$postId} featured_image: " . ($post['featured_image'] ?? 'NULL'));
            
            // Load monetized links for this post
            $monetizedStmt = $pdo->prepare("SELECT * FROM monetized_links WHERE post_id = ? ORDER BY id ASC");
            $monetizedStmt->execute([$postId]);
            $post['monetized_links'] = $monetizedStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_msg = "Post not found or has been deleted.";
            $isEdit = false; // Reset to create mode
            $postId = null;
        }
    } catch (PDOException $e) {
        error_log("Error loading post: " . $e->getMessage());
        $error_msg = "Error loading post data. Please try again.";
        $isEdit = false;
        $postId = null;
    }
} else {
    // Initialize empty monetized links for new post
    $post['monetized_links'] = [];
}

// Post handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX SEO calculation
    if (isset($_POST['action']) && $_POST['action'] === 'calculate_seo') {
        header('Content-Type: application/json');
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $focusKeyword = trim($_POST['focus_keyword'] ?? '');
        
        $seo = calculateSEOScore($title, $content, $metaTitle, $metaDescription, $focusKeyword);
        
        echo json_encode($seo);
        exit;
    }
    
    // Handle AJAX Title Suggestions
    if (isset($_POST['action']) && $_POST['action'] === 'generate_title') {
        header('Content-Type: application/json');
        
        $title = trim($_POST['title'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $postType = trim($_POST['post_type'] ?? 'software');
        
        // Get category slug
        $categorySlug = '';
        if ($categoryId > 0) {
            $stmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            $categorySlug = $category['slug'] ?? '';
        }
        
        $suggestions = generateAttractiveTitle($title, $categorySlug, $postType);
        
        echo json_encode(['success' => true, 'suggestions' => $suggestions]);
        exit;
    }
    
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    try {
        // Re-assign postId from POST if in edit mode
        if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
            $postId = (int)$_POST['post_id'];
            $isEdit = true;
        }
        
        // Debug logging
        error_log("Post submission received at " . date('Y-m-d H:i:s'));
        error_log("POST data: " . print_r($_POST, true));
        
        // Get post type first
        $postType = $_POST['post_type'] ?? 'software';
        error_log("Post type: " . $postType);
        
        // Validate required fields based on post type
        $requiredFields = ['title', 'content'];
        
        // Only require category_id for non-blog posts
        if ($postType !== 'blog') {
            $requiredFields[] = 'category_id';
        }
        
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missingFields[] = ucfirst(str_replace('_', ' ', $field));
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception("Required fields missing: " . implode(", ", $missingFields));
        }
        
        // Process form data
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $content = trim($_POST['content']);
        
        // Auto-assign category for blogs
        if ($postType === 'blog') {
            // Get or create blog category
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = 'blog' LIMIT 1");
            $stmt->execute();
            $tutorialCat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tutorialCat) {
                // Create blog category if not exists
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute(['Blog', 'blog', 'Blog dan panduan']);
                $category_id = (int)$pdo->lastInsertId();
            } else {
                $category_id = (int)$tutorialCat['id'];
            }
            $secondary_category_id = null;
        } else {
            $category_id = (int)$_POST['category_id'];
            $secondary_category_id = !empty($_POST['secondary_category_id']) ? (int)$_POST['secondary_category_id'] : null;
        }
        
        // Set status based on which button was clicked
        if (isset($_POST['publish'])) {
            $status = 'published';
        } elseif (isset($_POST['save_draft'])) {
            $status = 'draft';
        } else {
            $status = $_POST['status'] ?? 'draft';
        }
        
        // Additional fields for software
        $version = trim($_POST['version'] ?? '');
        $file_size = trim($_POST['file_size'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $post_type = $_POST['post_type'] ?? 'software';
        
        // Validation already done above, no need to duplicate
        
        // Handle featured image upload
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
            $upload_dir = '../uploads/';
            $file_ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '_' . time() . '.' . $file_ext;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $file_name)) {
                $featured_image = 'uploads/' . $file_name;
            }
        }
        
        // Process download links if needed
        $download_links = [];
        if (isset($_POST['download_links']) && is_array($_POST['download_links'])) {
            foreach ($_POST['download_links'] as $link) {
                if (!empty($link['title']) && !empty($link['url'])) {
                    $download_links[] = $link;
                }
            }
        }
        
        $download_links_json = json_encode($download_links);
        
        // Get SEO fields
        $meta_title = trim($_POST['meta_title'] ?? $title);
        $meta_description = trim($_POST['meta_description'] ?? $description);
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');
        $focus_keyword = trim($_POST['focus_keyword'] ?? '');
        
        // Insert or update post
        if ($_POST['action'] === 'create') {
            // Prepare slug from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, excerpt, content, status, category_id, 
                                                    secondary_category_id, featured_image, meta_title, meta_description, 
                                                    meta_keywords, focus_keyword, 
                                                    author_id, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $result = $stmt->execute([
                $title,
                $slug,
                $description,  // Using description as excerpt
                $content,
                $status,
                $category_id,
                $secondary_category_id,
                $featured_image,
                $meta_title,
                $meta_description,
                $meta_keywords,
                $focus_keyword,
                $admin['id']
            ]);
                                    
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Database error: " . implode(", ", $errorInfo));
                throw new Exception("Failed to save post. Database error: " . $errorInfo[2]);
            }
            
            $postId = $pdo->lastInsertId();
            if (!$postId) {
                throw new Exception("Failed to get ID of new post. Please check the database logs.");
            }
            
            $success_msg = "Post " . ($status === 'published' ? 'published' : 'saved as draft') . " successfully!";
            error_log("Post {$postId} created successfully with status: {$status}");
            
            // Redirect after successful creation
            $_SESSION['success_msg'] = $success_msg;
            header("Location: posts.php?success=1&msg=" . urlencode($success_msg));
            exit;
            
        } else if ($_POST['action'] === 'update' && $postId) {
            // Prepare slug from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            // Build update query dynamically
            $params = [
                $title,
                $slug,
                $description,  // Using description as excerpt
                $content,
                $status,
                $category_id,
                $secondary_category_id,
                $meta_title,
                $meta_description,
                $meta_keywords,
                $focus_keyword
            ];
            
            $updateFields = "title = ?, slug = ?, excerpt = ?, content = ?, status = ?, 
                           category_id = ?, secondary_category_id = ?, meta_title = ?, meta_description = ?, 
                           meta_keywords = ?, focus_keyword = ?, updated_at = NOW()";
            
            // Add featured_image if uploaded
            if ($featured_image) {
                $updateFields .= ", featured_image = ?";
                $params[] = $featured_image;
            }
            
            // Add post ID as last parameter
            $params[] = $postId;
            
            $stmt = $pdo->prepare("UPDATE posts SET {$updateFields} WHERE id = ?");
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Update error: " . implode(", ", $errorInfo));
                throw new Exception("Failed to update post. Database error: " . $errorInfo[2]);
            }
            
            $success_msg = "Post updated successfully!";
            error_log("Post {$postId} updated successfully" . ($featured_image ? " with new image: {$featured_image}" : ""));
            
            // Update monetized links if provided
            if (isset($_POST['monetized_links']) && is_array($_POST['monetized_links'])) {
                error_log("Processing " . count($_POST['monetized_links']) . " existing monetized links");
                foreach ($_POST['monetized_links'] as $link_id => $link_data) {
                    error_log("Processing link ID: {$link_id} - Title: " . ($link_data['title'] ?? 'N/A') . " - URL: " . ($link_data['url'] ?? 'N/A'));
                    
                    // Check delete flag
                    if (isset($link_data['delete']) && $link_data['delete'] == '1') {
                        // Delete the link
                        $deleteStmt = $pdo->prepare("DELETE FROM monetized_links WHERE id = ? AND post_id = ?");
                        $deleteStmt->execute([intval($link_id), $postId]);
                        error_log("Deleted monetized link {$link_id} from post {$postId}");
                        continue;
                    }
                    
                    // Update existing link (including URL)
                    $updateStmt = $pdo->prepare("
                        UPDATE monetized_links 
                        SET original_url = ?, download_title = ?, file_size = ?, file_password = ?, version = ?
                        WHERE id = ? AND post_id = ?
                    ");
                    $updateStmt->execute([
                        trim($link_data['url']),
                        trim($link_data['title']),
                        trim($link_data['size']),
                        trim($link_data['password']),
                        trim($link_data['version'] ?? ''),
                        intval($link_id),
                        $postId
                    ]);
                    error_log("Updated monetized link {$link_id}: " . trim($link_data['title']));
                }
                error_log("Updated monetized links for post {$postId}");
            }
            
            // Insert new monetized links if provided
            if (isset($_POST['monetized_links_new']) && is_array($_POST['monetized_links_new'])) {
                foreach ($_POST['monetized_links_new'] as $new_link) {
                    if (empty($new_link['title']) || empty($new_link['url'])) {
                        continue; // Skip empty entries
                    }
                    
                    // Create full monetized link via MonetizationManager
                    // This will handle unique short code generation automatically
                    $linkResult = $monetizationManager->createMonetizedLink(
                        trim($new_link['url']),
                        $postId,
                        [
                            'title' => trim($new_link['title']),
                            'file_size' => trim($new_link['size'] ?? ''),
                            'password' => trim($new_link['password'] ?? ''),
                            'version' => trim($new_link['version'] ?? '')
                        ]
                    );
                    
                    if ($linkResult && isset($linkResult['id'])) {
                        error_log("✅ Added monetized link {$linkResult['short_code']} for post {$postId}");
                        error_log("   - Original URL: " . trim($new_link['url']));
                        error_log("   - Monetized URL: " . ($linkResult['monetized_url'] ?? 'N/A'));
                        error_log("   - Local URL: " . $linkResult['local_url']);
                    } else {
                        error_log("❌ Failed to add monetized link for post {$postId}");
                        error_log("   - URL: " . trim($new_link['url']));
                    }
                }
            }
        }
        
        // Auto-regenerate sitemap after post save
        if (isset($success_msg) && !empty($success_msg)) {
            try {
                // Run sitemap generator in background (non-blocking)
                $sitemapScript = __DIR__ . '/../seo/generate_sitemap.php';
                if (file_exists($sitemapScript)) {
                    // Execute in background (Windows compatible)
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        // Windows: use START command
                        pclose(popen("start /B php \"$sitemapScript\"", "r"));
                    } else {
                        // Linux/Mac: use & operator
                        exec("php \"$sitemapScript\" > /dev/null 2>&1 &");
                    }
                    error_log("✅ Sitemap regeneration triggered automatically");
                }
            } catch (Exception $e) {
                // Log error but don't block the save operation
                error_log("⚠️ Failed to auto-regenerate sitemap: " . $e->getMessage());
            }
            
            // Auto-submit to IndexNow for instant indexing (Bing & Yandex)
            try {
                require_once __DIR__ . '/../includes/IndexNowSubmitter.php';
                
                // Get post URL
                $postUrl = '';
                if (isset($post_slug) && !empty($post_slug)) {
                    // Use SITE_URL from config (auto-detects environment from .env)
                    $postUrl = rtrim(SITE_URL, '/') . '/post/' . $post_slug;
                    
                    // Submit to IndexNow (instant indexing)
                    $indexNow = new IndexNowSubmitter('localhost');
                    $result = $indexNow->submitUrl($postUrl, 'bing');
                    
                    if ($result['success']) {
                        error_log("✅ IndexNow: URL submitted successfully - $postUrl");
                    } else {
                        error_log("⚠️ IndexNow: Submission failed - " . $result['message']);
                    }
                }
            } catch (Exception $e) {
                error_log("⚠️ IndexNow submission error: " . $e->getMessage());
            }
            
            $_SESSION['success_msg'] = $success_msg;
            header("Location: posts.php?success=1&msg=" . urlencode($success_msg));
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Error in post operation: " . $e->getMessage());
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Get current page for navigation
$currentPage = 'posts';
if (isset($_GET['type'])) {
    $currentPage = 'add-' . $_GET['type'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Admin Panel - DONAN22</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    
    <style>
    .editor-container {
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .tox-tinymce {
        border: none !important;
    }
    /* Sticky Sidebar to prevent excessive scrolling */
    .sidebar-sticky {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        overflow-x: hidden;
    }
    .sidebar-sticky::-webkit-scrollbar {
        width: 6px;
    }
    .sidebar-sticky::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .sidebar-sticky::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .sidebar-sticky::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    /* Compact card styling */
    .card.mb-3 {
        margin-bottom: 1rem !important;
    }
    .card-body {
        padding: 1rem;
    }
    .card-header {
        padding: 0.75rem 1rem;
    }
    </style>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    
    <!-- NEW FEATURES: Tagify for tags, Flatpickr for datetime, GLightbox for gallery -->
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    window.addEventListener('load', function() {
        if (typeof tinymce === 'undefined') {
            console.error('TinyMCE not loaded');
            return;
        }
        
        try {
            tinymce.init({
            selector: '#content',
            height: 500,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | image media link | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 16px; line-height: 1.6; }',
            images_upload_url: 'upload-image.php',
            automatic_uploads: true,
            file_picker_types: 'image',
            image_title: true,
            image_description: true,
            image_caption: true,
            image_dimensions: true, // Enable dimensions for SEO
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            branding: false,
            promotion: false,
            // SEO: Auto-add attributes to uploaded images
            images_upload_handler: function(blobInfo, success, failure) {
                var xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', 'upload-image.php');
                
                xhr.onload = function() {
                    if (xhr.status != 200) {
                        failure('HTTP Error: ' + xhr.status);
                        return;
                    }
                    var json = JSON.parse(xhr.responseText);
                    if (!json || typeof json.url != 'string') {
                        failure('Invalid JSON: ' + xhr.responseText);
                        return;
                    }
                    // Return image with SEO attributes
                    success(json.url, {
                        alt: json.alt || '',
                        title: json.title || '',
                        width: json.width || '',
                        height: json.height || ''
                    });
                };
                
                var formData = new FormData();
                formData.append('upload', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            },
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('TinyMCE initialized');
                });
                // Auto-calculate SEO when content changes
                editor.on('change keyup', function() {
                    if (typeof autoCalculateSEO === 'function') {
                        autoCalculateSEO();
                    }
                });
            }
        }).then(function() {
            console.log('TinyMCE initialization successful');
        }).catch(function(error) {
            console.error('TinyMCE initialization failed:', error);
        });
    } catch (error) {
        console.error('Error initializing TinyMCE:', error);
        // Fallback to normal textarea
        document.getElementById('content').style.height = '400px';
    }
    });
    </script>
    
    <style>
        .editor-loading-message {
            margin-bottom: 1rem;
        }
        .editor-container {
            position: relative;
        }
        
        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-color: #dee2e6;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 0.75rem;
        }
        
        .select2-container--bootstrap-5 .select2-dropdown {
            border-color: #dee2e6;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .select2-container--bootstrap-5 .select2-search__field {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        
        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.5rem 0.75rem;
        }
        
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: white;
        }
        
        .select2-container--bootstrap-5 .select2-selection__clear {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        
        /* Category icon styling */
        .select2-results__option .fa-folder {
            color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation($currentPage); ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                        <?= htmlspecialchars($pageTitle) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="posts.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Posts
                        </a>
                    </div>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Post Editor Form -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-<?= $postType === 'software' ? 'download' : ($postType === 'game' ? 'gamepad' : ($postType === 'tutorial' ? 'graduation-cap' : 'mobile-alt')) ?> me-2"></i>
                            <?= ucfirst($postType) ?> Post Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="postForm">
                            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
                            <input type="hidden" name="post_type" value="<?= htmlspecialchars($postType) ?>">
                            <?php if ($isEdit): ?>
                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($postId) ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Basic Info -->
                                    <div class="mb-3">
                                        <label class="form-label">Title *</label>
                                        <div class="input-group">
                                            <input type="text" name="title" id="postTitle" class="form-control" required 
                                                   placeholder="Enter post title..." 
                                                   value="<?= htmlspecialchars($post['title'] ?? '') ?>">
                                            <button type="button" class="btn btn-outline-primary" id="generateTitleBtn" title="Generate Attractive Title">
                                                <i class="fas fa-magic"></i> ✨
                                            </button>
                                        </div>
                                        <small class="text-muted">Tip: Click ✨ for attractive title suggestions</small>
                                        
                                        <!-- Title Suggestions -->
                                        <div id="titleSuggestions" class="mt-2" style="display: none;">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white py-2">
                                                    <small><i class="fas fa-lightbulb me-2"></i>Attractive Title Suggestions</small>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div id="titleSuggestionsList"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3" 
                                                  placeholder="Enter short description..."><?= htmlspecialchars($post['excerpt'] ?? $post['description'] ?? '') ?></textarea>
                                        <small class="text-muted">Brief description for post preview (plain text)</small>
                                    </div>

                                    <!-- SEO Content Template Generator -->
                                    <div class="card mb-3 border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-magic me-2"></i>SEO Content Generator</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="small mb-3">Generate SEO-optimized content with proper H1, H2, H3 structure automatically!</p>
                                            <button type="button" class="btn btn-success w-100" id="generateSEOTemplate">
                                                <i class="fas fa-wand-magic me-2"></i>Generate SEO Template
                                            </button>
                                            <div id="seoTemplateStatus" class="mt-2" style="display: none;"></div>
                                            <small class="text-muted d-block mt-2">
                                                ✅ Auto H2/H3 structure<br>
                                                ✅ FAQ section included<br>
                                                ✅ System requirements<br>
                                                ✅ Download & install guide
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Rich Text Editor for Content -->
                                    <div class="mb-3">
                                        <label class="form-label">Content *</label>
                                        <div class="editor-container">
                                            <textarea name="content" id="content" class="form-control" rows="20" required><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                                        </div>
                                        <small class="text-muted">Main content with rich text formatting</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- Sidebar with Sticky Container -->
                                    <div class="sidebar-sticky">
                                    
                                    <?php if ($postType === 'tutorial'): ?>
                                    <!-- Info for Tutorial -->
                                    <div class="alert alert-info mb-3">
                                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Tutorial Mode</h6>
                                        <p class="mb-0">
                                            ✅ Kategori otomatis di-set ke <strong>"Tutorial"</strong><br>
                                            ✅ Tidak perlu upload download links<br>
                                            ✅ Fokus pada konten tutorial yang berkualitas
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Publishing</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" id="statusSelect" class="form-select">
                                                    <option value="published" <?= ($post['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
                                                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                                    <option value="scheduled" <?= ($post['status'] ?? 'draft') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                                </select>
                                            </div>
                                            
                                            <!-- NEW: Scheduled Publishing -->
                                            <div class="mb-3" id="scheduledDateWrapper" style="display: none;">
                                                <label class="form-label"><i class="fas fa-calendar me-1"></i>Publish Date</label>
                                                <input type="text" name="scheduled_at" id="scheduledDatePicker" class="form-control" placeholder="Select date and time...">
                                                <small class="text-muted">Post will be published automatically</small>
                                            </div>
                                            
                                            <!-- NEW: Auto-save Status -->
                                            <div class="mb-3">
                                                <small class="text-muted" id="autosaveStatus">
                                                    <i class="fas fa-clock me-1"></i>Auto-save enabled
                                                </small>
                                            </div>

                                            <?php if ($postType !== 'tutorial'): ?>
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-folder me-1"></i>Kategori Inti *
                                                </label>
                                                <select name="category_id" class="form-select" required>
                                                    <option value="">Select Primary Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= htmlspecialchars($category['id']) ?>" 
                                                                <?= ((string)$post['category_id'] === (string)$category['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                            <?php if (isset($category['post_count'])): ?>
                                                                (<?= (int)$category['post_count'] ?> posts)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> Kategori utama untuk software ini
                                                </small>
                                                <?php if (empty($categories)): ?>
                                                    <div class="text-danger small mt-1">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        No categories available. <a href="categories.php" target="_blank">Create categories first</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-folder-plus me-1"></i>Kategori Pendamping
                                                </label>
                                                <select name="secondary_category_id" class="form-select">
                                                    <option value="">Select Secondary Category (Optional)</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= htmlspecialchars($category['id']) ?>" 
                                                                <?= ((string)($post['secondary_category_id'] ?? '') === (string)$category['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                            <?php if (isset($category['post_count'])): ?>
                                                                (<?= (int)$category['post_count'] ?> posts)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> Kategori tambahan yang akan ditampilkan di halaman download
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- NEW: Tags Input -->
                                            <div class="mb-3">
                                                <label class="form-label"><i class="fas fa-tags me-1"></i>Tags</label>
                                                <input type="text" id="tagsInput" name="tags" class="form-control" placeholder="Add tags...">
                                                <small class="text-muted">Press Enter to add tags</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Featured Image</label>
                                                
                                                <?php if ($isEdit && !empty($post['featured_image'])): ?>
                                                    <div class="mb-2">
                                                        <div class="position-relative d-inline-block">
                                                            <img src="../<?= htmlspecialchars($post['featured_image']) ?>" 
                                                                 alt="Current featured image" 
                                                                 class="img-thumbnail" 
                                                                 style="max-width: 300px; max-height: 200px;">
                                                            <div class="position-absolute top-0 end-0 p-1">
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i> Current
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-info-circle"></i> 
                                                                Upload a new image to replace the current one
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <input type="file" name="featured_image" id="featuredImageInput" class="form-control" accept="image/*">
                                                <small class="text-muted">Recommended: 1200x630px (JPG, PNG, GIF max 5MB)</small>
                                                
                                                <!-- Image Preview for new upload -->
                                                <div id="imagePreview" class="mt-2" style="display: none;">
                                                    <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                                                    <div class="mt-1">
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle"></i> New image ready to upload
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- NEW: SEO Meta Fields -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-search me-2"></i>SEO Settings</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Focus Keyword</label>
                                                <input type="text" name="focus_keyword" id="focusKeyword" class="form-control" placeholder="Main keyword..." value="<?= htmlspecialchars($post['focus_keyword'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta Title</label>
                                                <input type="text" name="meta_title" id="metaTitle" class="form-control" maxlength="60" placeholder="SEO title..." value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>">
                                                <small class="text-muted"><span id="metaTitleCount">0</span>/60 characters</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta Description</label>
                                                <textarea name="meta_description" id="metaDescription" class="form-control" rows="3" maxlength="160" placeholder="SEO description..."><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                                                <small class="text-muted"><span id="metaDescCount">0</span>/160 characters</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta Keywords</label>
                                                <input type="text" name="meta_keywords" class="form-control" placeholder="keyword1, keyword2, keyword3..." value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>">
                                            </div>
                                            
                                            <!-- Google Preview -->
                                            <div class="border rounded p-3 bg-light">
                                                <small class="text-muted d-block mb-2"><i class="fab fa-google me-1"></i>Google Preview</small>
                                                <div id="googlePreview">
                                                    <div class="text-primary" style="font-size: 18px;" id="previewTitle">Your Page Title</div>
                                                    <div class="text-success" style="font-size: 14px;">yoursite.com › page-url</div>
                                                    <div class="text-muted" style="font-size: 14px;" id="previewDesc">Your meta description will appear here...</div>
                                                </div>
                                            </div>
                                            
                                            <!-- SEO Score -->
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="calculateSEO">
                                                    <i class="fas fa-calculator me-1"></i>Calculate SEO Score
                                                </button>
                                                <div id="seoScore" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($postType !== 'tutorial'): ?>
                                    <!-- Monetized Download Links (with Password) -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Monetized Downloads (Password Protected)</h6>
                                            <button type="button" class="btn btn-sm btn-light" onclick="addMonetizedLink()">
                                                <i class="fas fa-plus"></i> Add Link
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            // Use monetized links already loaded
                                            $monetizedLinks = $post['monetized_links'] ?? [];
                                            ?>
                                            
                                            <div id="monetizedLinksContainer">
                                            <?php if (!empty($monetizedLinks)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered" id="monetizedLinksTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="20%">Title</th>
                                                                <th width="25%">Download URL</th>
                                                                <th width="10%">Size</th>
                                                                <th width="12%">Password</th>
                                                                <th width="12%">Short Code</th>
                                                                <th width="8%">Version</th>
                                                                <th width="8%">Stats</th>
                                                                <th width="5%">Del</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($monetizedLinks as $mLink): ?>
                                                            <tr data-link-id="<?= $mLink['id'] ?>">
                                                                <td>
                                                                    <input type="text" 
                                                                           name="monetized_links[<?= $mLink['id'] ?>][title]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="<?= htmlspecialchars($mLink['download_title']) ?>"
                                                                           placeholder="Download title">
                                                                </td>
                                                                <td>
                                                                    <input type="url" 
                                                                           name="monetized_links[<?= $mLink['id'] ?>][url]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="<?= htmlspecialchars($mLink['original_url']) ?>"
                                                                           placeholder="https://dl18.nesabamedia.net/...">
                                                                    <a href="<?= htmlspecialchars($mLink['original_url']) ?>" 
                                                                       target="_blank" 
                                                                       class="btn btn-xs btn-outline-info btn-sm mt-1"
                                                                       title="Open download URL">
                                                                        <i class="fas fa-external-link-alt"></i>
                                                                    </a>
                                                                </td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="monetized_links[<?= $mLink['id'] ?>][size]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="<?= htmlspecialchars($mLink['file_size']) ?>"
                                                                           placeholder="1.1 GB">
                                                                </td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="monetized_links[<?= $mLink['id'] ?>][password]" 
                                                                           class="form-control form-control-sm text-danger fw-bold" 
                                                                           value="<?= htmlspecialchars($mLink['file_password']) ?>"
                                                                           placeholder="donan22.com">
                                                                </td>
                                                                <td>
                                                                    <code class="d-block mb-1"><?= htmlspecialchars($mLink['short_code']) ?></code>
                                                                    <a href="<?= SITE_URL ?>/go/<?= $mLink['short_code'] ?>" 
                                                                       target="_blank" 
                                                                       class="btn btn-xs btn-outline-primary btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="monetized_links[<?= $mLink['id'] ?>][version]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="<?= htmlspecialchars($mLink['version'] ?? '') ?>"
                                                                           placeholder="2024">
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">
                                                                        👁 <?= number_format($mLink['total_clicks']) ?><br>
                                                                        📥 <?= number_format($mLink['total_downloads']) ?>
                                                                    </small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <input type="hidden" name="monetized_links[<?= $mLink['id'] ?>][delete]" value="0" class="delete-flag">
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-danger" 
                                                                            onclick="deleteMonetizedLink(<?= $mLink['id'] ?>)"
                                                                            title="Delete link">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                            </div>
                                            
                                            <div class="alert alert-success mt-2 mb-0">
                                                <small>
                                                    <i class="fas fa-check-circle"></i> 
                                                    <strong><span id="linkCount"><?= count($monetizedLinks) ?></span> download link(s)</strong> tersedia. 
                                                    <br>
                                                    ✏️ Edit <strong>Title, Download URL, Size, Password, Version</strong> langsung di table
                                                    <br>
                                                    🔗 Klik icon <i class="fas fa-external-link-alt"></i> untuk buka Download URL
                                                    <br>
                                                    ➕ Klik <strong>"Add Link"</strong> untuk tambah link baru
                                                    <br>
                                                    🗑️ Klik <strong>Delete</strong> untuk hapus link
                                                    <br>
                                                    💾 Jangan lupa klik <strong>"Update Post"</strong> untuk simpan perubahan!
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; // End if postType !== 'tutorial' ?>
                                        </div>
                                    </div>

                                    <?php if ($postType !== 'tutorial'): ?>
                                    <!-- Download Links (Legacy - Not Recommended) -->
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                            <h6 class="mb-0 text-muted">
                                                <i class="fas fa-download me-2"></i>Download Links (Legacy)
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-secondary" id="addDownloadLink" disabled>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-warning mb-0">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Tidak Direkomendasikan!</strong> Section ini adalah link lama tanpa monetisasi.
                                                <br>
                                                Gunakan <strong>"Monetized Downloads"</strong> di atas untuk link dengan password dan tracking revenue.
                                            </div>
                                            <!--
                                            <div id="downloadLinksContainer" style="display:none;">
                                                <div class="download-link-item border p-2 rounded mb-2">
                                                    <small class="text-muted d-block mb-2">Link 1</small>
                                                    <div class="mb-2">
                                                        <input type="text" name="download_links[0][title]" 
                                                               class="form-control form-control-sm" 
                                                               placeholder="Link title (e.g. MediaFire)">
                                                    </div>
                                                    <div class="mb-2">
                                                        <input type="url" name="download_links[0][url]" 
                                                               class="form-control form-control-sm" 
                                                               placeholder="Download URL">
                                                    </div>
                                                </div>
                                            </div>
                                            -->
                                        </div>
                                    </div>
                                    <?php endif; // End if postType !== 'tutorial' ?>
                                    </div><!-- End .sidebar-sticky -->
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="posts.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <div>
                                            <?php if ($isEdit && $postId): ?>
                                            <a href="../post.php?preview=1&id=<?= $postId ?>" target="_blank" class="btn btn-outline-info me-2">
                                                <i class="fas fa-eye me-1"></i>Preview
                                            </a>
                                            <?php endif; ?>
                                            <button type="submit" name="save_draft" class="btn btn-outline-primary me-2">
                                                <i class="fas fa-save me-1"></i>Save Draft
                                            </button>
                                            <button type="submit" name="publish" class="btn btn-success">
                                                <i class="fas fa-<?= $isEdit ? 'save' : 'paper-plane' ?> me-1"></i>
                                                <?= $isEdit ? 'Update Post' : 'Publish Post' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Role Information -->
                <div class="mt-4">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Role-Based Access Information</h6>
                        <p class="mb-1"><strong>Current Role:</strong> 
                            <span class="badge bg-<?= getCurrentUserRole() === 'superadmin' ? 'danger' : (getCurrentUserRole() === 'admin' ? 'primary' : 'info') ?>">
                                <?= getRoleManager()->getRoleDisplayName(getCurrentUserRole()) ?>
                            </span>
                        </p>
                        <p class="mb-0">
                            <strong>Post Management Permissions:</strong> 
                            <?php if (hasPermission('manage_posts')): ?>
                                <span class="text-success">✓ Can create and edit posts</span>
                            <?php else: ?>
                                <span class="text-danger">✗ Limited access to post management</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>    <!-- NEW FEATURES: Load Select2, Tagify, Flatpickr, GLightbox -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.9/dist/tagify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js"></script>
    
    <script>
    // Enhanced TinyMCE Editor Initialization Script + NEW FEATURES
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing editor and new features...');
        
        // ============================================
        // 0. SELECT2 for Secondary Category with Search
        // ============================================
        $(document).ready(function() {
            // Initialize Select2 for Secondary Category
            $('select[name="secondary_category_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari atau pilih kategori pendamping...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return 'Kategori tidak ditemukan';
                    },
                    searching: function() {
                        return 'Mencari...';
                    },
                    inputTooShort: function() {
                        return 'Ketik untuk mencari kategori';
                    }
                },
                templateResult: formatCategory,
                templateSelection: formatCategorySelection
            });
            
            // Optional: Also add Select2 to Primary Category
            $('select[name="category_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: 'Cari atau pilih kategori inti...',
                width: '100%',
                language: {
                    noResults: function() {
                        return 'Kategori tidak ditemukan';
                    },
                    searching: function() {
                        return 'Mencari...';
                    }
                },
                templateResult: formatCategory,
                templateSelection: formatCategorySelection
            });
            
            // Format category in dropdown (with icon)
            function formatCategory(category) {
                if (!category.id) {
                    return category.text;
                }
                var $category = $(
                    '<span><i class="fas fa-folder text-primary me-2"></i>' + category.text + '</span>'
                );
                return $category;
            }
            
            // Format selected category
            function formatCategorySelection(category) {
                if (!category.id) {
                    return category.text;
                }
                return category.text;
            }
        });
        
        // ============================================
        // 1. TAGS SYSTEM with Tagify
        // ============================================
        const tagsInput = document.getElementById('tagsInput');
        if (tagsInput) {
            const tagify = new Tagify(tagsInput, {
                whitelist: [],
                dropdown: {
                    enabled: 1,
                    maxItems: 20,
                    position: 'text'
                },
                callbacks: {
                    add: () => saveTags(),
                    remove: () => saveTags()
                }
            });
            
            // Load existing tags if in edit mode
            <?php if ($isEdit && $postId): ?>
            fetch('features_api.php?action=tags_get_post&post_id=<?= $postId ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.tags) {
                        tagify.addTags(data.tags.map(t => ({value: t.name, id: t.id})));
                    }
                });
            <?php endif; ?>
            
            // Search tags on input
            let searchTimeout;
            tagify.on('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetch('features_api.php?action=tags_search&q=' + encodeURIComponent(e.detail.value))
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                tagify.whitelist = data.tags.map(t => ({value: t.name, id: t.id}));
                                tagify.dropdown.show(e.detail.value);
                            }
                        });
                }, 300);
            });
            
            function saveTags() {
                <?php if ($isEdit && $postId): ?>
                const tags = tagify.value.map(t => t.id || t.value);
                const formData = new FormData();
                formData.append('action', 'tags_save_post');
                formData.append('post_id', '<?= $postId ?>');
                formData.append('tag_ids', JSON.stringify(tags));
                fetch('features_api.php', {method: 'POST', body: formData});
                <?php endif; ?>
            }
        }
        
        // ============================================
        // 2. SEO META FIELDS with Google Preview & AUTO-CALCULATE
        // ============================================
        const metaTitle = document.getElementById('metaTitle');
        const metaDescription = document.getElementById('metaDescription');
        const focusKeyword = document.getElementById('focusKeyword');
        
        // Auto-calculate SEO score with debounce
        let seoCalculateTimeout;
        
        function autoCalculateSEO() {
            clearTimeout(seoCalculateTimeout);
            seoCalculateTimeout = setTimeout(() => {
                calculateSEOScore();
            }, 1500); // Wait 1.5 seconds after user stops typing
        }
        
        function calculateSEOScore() {
            const formData = new FormData();
            formData.append('action', 'calculate_seo');
            formData.append('title', document.querySelector('[name="title"]').value);
            formData.append('content', tinymce.get('content')?.getContent() || '');
            formData.append('meta_title', metaTitle?.value || '');
            formData.append('meta_description', metaDescription?.value || '');
            formData.append('focus_keyword', focusKeyword?.value || '');
            
            // Show loading indicator
            const seoScoreDiv = document.getElementById('seoScore');
            if (seoScoreDiv) {
                seoScoreDiv.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Calculating SEO Score...</div>';
            }
            
            fetch(window.location.href, {method: 'POST', body: formData})
                .then(r => r.json())
                .then(data => {
                    if (data.percentage !== undefined && seoScoreDiv) {
                        const color = data.percentage >= 80 ? 'success' : (data.percentage >= 50 ? 'warning' : 'danger');
                        const icon = data.percentage >= 80 ? 'check-circle' : (data.percentage >= 50 ? 'exclamation-triangle' : 'times-circle');
                        
                        let html = `<div class="alert alert-${color} mb-0">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-${icon} me-2"></i>
                                <strong>SEO Score: ${data.score}/${data.maxScore} (${data.percentage}%)</strong>
                                <span class="ms-auto badge bg-${color}">${data.status}</span>
                            </div>`;
                        
                        if (data.issues && data.issues.length > 0) {
                            html += '<hr class="my-2"><small><strong>Suggestions:</strong></small><ul class="mb-0 mt-1 small">';
                            data.issues.forEach(issue => html += `<li>${issue}</li>`);
                            html += '</ul>';
                        }
                        
                        if (data.passed && data.passed.length > 0) {
                            html += '<hr class="my-2"><small><strong>✅ Passed:</strong></small><ul class="mb-0 mt-1 small text-success">';
                            data.passed.forEach(pass => html += `<li>${pass}</li>`);
                            html += '</ul>';
                        }
                        
                        html += '</div>';
                        seoScoreDiv.innerHTML = html;
                    }
                })
                .catch(err => {
                    console.error('SEO calculation error:', err);
                    if (seoScoreDiv) {
                        seoScoreDiv.innerHTML = '<div class="alert alert-danger mb-0"><small>Error calculating SEO score</small></div>';
                    }
                });
        }
        
        // Update character counters and Google Preview
        if (metaTitle) {
            metaTitle.addEventListener('input', function() {
                document.getElementById('metaTitleCount').textContent = this.value.length;
                document.getElementById('previewTitle').textContent = this.value || 'Your Page Title';
                autoCalculateSEO(); // Auto-calculate when title changes
            });
        }
        
        if (metaDescription) {
            metaDescription.addEventListener('input', function() {
                document.getElementById('metaDescCount').textContent = this.value.length;
                document.getElementById('previewDesc').textContent = this.value || 'Your meta description will appear here...';
                autoCalculateSEO(); // Auto-calculate when description changes
            });
        }
        
        if (focusKeyword) {
            focusKeyword.addEventListener('input', function() {
                autoCalculateSEO(); // Auto-calculate when keyword changes
            });
        }
        
        // Monitor TinyMCE content changes
        // Note: TinyMCE already initialized above with full config
        // Auto-calculate will be triggered from existing editor instance
        
        // Manual Calculate Button (still available)
        document.getElementById('calculateSEO')?.addEventListener('click', function(e) {
            e.preventDefault();
            calculateSEOScore(); // Immediate calculation
        });
        
        // ============================================
        // ATTRACTIVE TITLE GENERATOR 
        // ============================================
        
        // Title Generation Function
        function generateTitleSuggestions() {
            const titleInput = document.getElementById('postTitle');
            const categorySelect = document.querySelector('select[name="category_id"]');
            const postTypeSelect = document.querySelector('select[name="post_type"]');
            const suggestionsDiv = document.getElementById('titleSuggestions');
            const suggestionsList = document.getElementById('titleSuggestionsList');
            
            const title = titleInput?.value.trim() || '';
            const categoryId = categorySelect?.value || '';
            const postType = postTypeSelect?.value || 'software';
            
            if (!title) {
                alert('⚠️ Masukkan title dasar terlebih dahulu!');
                titleInput?.focus();
                return;
            }
            
            // Tampilkan loading
            suggestionsList.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Generating attractive titles...</div>';
            suggestionsDiv.style.display = 'block';
            
            // Kirim request
            const formData = new FormData();
            formData.append('action', 'generate_title');
            formData.append('title', title);
            formData.append('category_id', categoryId);
            formData.append('post_type', postType);
            
            fetch(window.location.href, {method: 'POST', body: formData})
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.suggestions) {
                        let html = '';
                        data.suggestions.forEach((suggestion, index) => {
                            html += `
                                <div class="mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start title-suggestion-btn" 
                                            data-title="${suggestion.replace(/"/g, '&quot;')}">
                                        <i class="fas fa-arrow-right me-2"></i>${suggestion}
                                    </button>
                                </div>
                            `;
                        });
                        html += '<div class="text-center mt-2"><small class="text-muted">Click any title to use it</small></div>';
                        suggestionsList.innerHTML = html;
                        
                        // Add click handlers
                        document.querySelectorAll('.title-suggestion-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const newTitle = this.getAttribute('data-title');
                                titleInput.value = newTitle;
                                suggestionsDiv.style.display = 'none';
                                
                                // Auto-calculate SEO after title change
                                setTimeout(() => {
                                    autoCalculateSEO();
                                }, 500);
                                
                                // Show success message
                                const successMsg = document.createElement('div');
                                successMsg.className = 'alert alert-success mt-2';
                                successMsg.innerHTML = '<i class="fas fa-check me-2"></i>Title updated! SEO recalculating...';
                                titleInput.parentNode.appendChild(successMsg);
                                
                                setTimeout(() => {
                                    successMsg.remove();
                                }, 3000);
                            });
                        });
                        
                    } else {
                        suggestionsList.innerHTML = '<div class="text-danger text-center">❌ Failed to generate suggestions</div>';
                    }
                })
                .catch(err => {
                    console.error('Title generation error:', err);
                    suggestionsList.innerHTML = '<div class="text-danger text-center">❌ Error generating titles</div>';
                });
        }
        
        // Button click handler
        document.getElementById('generateTitleBtn')?.addEventListener('click', function(e) {
            e.preventDefault();
            generateTitleSuggestions();
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            const suggestionsDiv = document.getElementById('titleSuggestions');
            const generateBtn = document.getElementById('generateTitleBtn');
            
            if (suggestionsDiv && !suggestionsDiv.contains(e.target) && e.target !== generateBtn) {
                if (suggestionsDiv.style.display === 'block') {
                    suggestionsDiv.style.display = 'none';
                }
            }
        });
        
        // ============================================
        // 3. SCHEDULED PUBLISHING with Flatpickr
        // ============================================
        const statusSelect = document.getElementById('statusSelect');
        const scheduledWrapper = document.getElementById('scheduledDateWrapper');
        
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                scheduledWrapper.style.display = this.value === 'scheduled' ? 'block' : 'none';
            });
            // Trigger on load
            if (statusSelect.value === 'scheduled') scheduledWrapper.style.display = 'block';
        }
        
        flatpickr('#scheduledDatePicker', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            minDate: 'today',
            time_24hr: true
        });
        
        // ============================================
        // AUTO-CALCULATE SEO on Page Load (for edit mode)
        // ============================================
        <?php if ($isEdit && $postId): ?>
        // Wait for TinyMCE to fully load, then calculate initial SEO score
        setTimeout(() => {
            if (metaTitle?.value || metaDescription?.value || focusKeyword?.value) {
                calculateSEOScore(); // Auto-calculate on page load if data exists
            }
        }, 2000); // Wait 2 seconds for TinyMCE to initialize
        <?php endif; ?>
        
        // ============================================
        // 4. AUTO-SAVE every 30 seconds
        // ============================================
        <?php if ($isEdit && $postId): ?>
        let autosaveInterval = setInterval(function() {
            const formData = new FormData();
            formData.append('action', 'autosave');
            formData.append('post_id', '<?= $postId ?>');
            formData.append('title', document.querySelector('[name="title"]').value);
            formData.append('content', tinymce.get('content')?.getContent() || '');
            formData.append('description', document.querySelector('[name="description"]')?.value || '');
            formData.append('category_id', document.querySelector('[name="category_id"]')?.value || '');
            formData.append('status', statusSelect?.value || 'draft');
            
            fetch('features_api.php', {method: 'POST', body: formData})
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const now = new Date();
                        document.getElementById('autosaveStatus').innerHTML = 
                            `<i class="fas fa-check-circle text-success me-1"></i>Last saved: ${now.toLocaleTimeString()}`;
                    }
                })
                .catch(err => console.error('Autosave failed:', err));
        }, 30000);
        <?php endif; ?>
        
        // ============================================
        // 5. GALLERY UPLOADER with Preview
        // ============================================
        const galleryUpload = document.getElementById('galleryUpload');
        if (galleryUpload) {
            galleryUpload.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                const preview = document.getElementById('galleryPreview');
                
                files.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'col-3';
                        div.innerHTML = `
                            <div class="border rounded p-1">
                                <img src="${e.target.result}" class="img-fluid" style="max-height: 100px; object-fit: cover;">
                                <small class="text-muted d-block text-truncate">${file.name}</small>
                            </div>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
                
                // Upload to server
                <?php if ($isEdit && $postId): ?>
                const formData = new FormData();
                formData.append('action', 'gallery_upload');
                formData.append('post_id', '<?= $postId ?>');
                files.forEach(file => formData.append('images[]', file));
                
                fetch('features_api.php', {method: 'POST', body: formData})
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Gallery uploaded:', data.uploaded);
                        }
                    });
                <?php endif; ?>
            });
        }
        
        const contentArea = document.getElementById('content');
        const form = document.querySelector('form');
        
        // Show loading message
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'editor-loading-message alert alert-info mb-3';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading rich text editor...';
        contentArea.parentNode.insertBefore(loadingDiv, contentArea);
        
        // Form submission handler
        // REMOVED: Duplicate form validation that was blocking submit
        // The main validation is already handled below
        
        // Error display helper
        function showError(message, element) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-2 error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
            element.parentNode.appendChild(errorDiv);
            element.focus();
        }
        
        // Initialize TinyMCE editor with retry mechanism
        function initializeTinyMCE(attempt = 1) {
            if (typeof tinymce === 'undefined') {
                if (attempt <= 3) {
                    console.log(`TinyMCE not ready, attempt ${attempt}/3`);
                    setTimeout(() => initializeTinyMCE(attempt + 1), 1000);
                    return;
                } else {
                    console.error('TinyMCE failed to load after 3 attempts');
                    showSimpleTextarea();
                    return;
                }
            }
            
            console.log('Initializing TinyMCE editor...');
            
            // Remove any existing TinyMCE instances
            if (tinymce.get('content')) {
                tinymce.get('content').remove();
            }
            
            tinymce.init({
                selector: '#content',
                height: 500,
                menubar: false,
                branding: false,
                promotion: false,
                
                // Complete plugin set
                plugins: 'image link media table code lists fullscreen wordcount help preview searchreplace visualblocks codesample emoticons hr pagebreak nonbreaking anchor insertdatetime advlist autolink charmap directionality paste textcolor colorpicker',
                
                // Complete toolbar as requested
                toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | table | link image media | fullscreen | code',
                
                // Extended toolbar for more features
                toolbar_mode: 'sliding',
                
                // Content styling
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
                
                // Image settings
                image_title: true,
                image_description: false,
                automatic_uploads: true,
                file_picker_types: 'image media',
                
                // File picker callback for uploads
                file_picker_callback: function (callback, value, meta) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    
                    // Set file type filter based on meta.filetype
                    if (meta.filetype === 'image') {
                        input.setAttribute('accept', 'image/*');
                    } else if (meta.filetype === 'media') {
                        input.setAttribute('accept', 'video/*,audio/*');
                    } else {
                        input.setAttribute('accept', '*/*');
                    }
                    
                    input.onchange = function () {
                        var file = this.files[0];
                        if (!file) return;
                        
                        // Show loading notification
                        console.log('Uploading file...');
                        
                        var formData = new FormData();
                        formData.append('file', file);
                        
                        fetch('upload-media.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Return the file URL to TinyMCE
                                callback(data.url, { 
                                    title: file.name,
                                    alt: data.original_name || file.name 
                                });
                                console.log('File uploaded successfully!');
                            } else {
                                console.error('Upload failed: ' + data.message);
                                alert('Upload failed: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Upload error: ' + error.message);
                            alert('Upload error: ' + error.message);
                        });
                    };
                    
                    input.click();
                },
                
                // Images upload handler for drag & drop
                images_upload_handler: function (blobInfo, success, failure) {
                    var formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    
                    fetch('upload-media.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            success(data.url);
                        } else {
                            failure('Upload failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        failure('Upload error: ' + error.message);
                    });
                },
                
                // Setup callback
                setup: function (editor) {
                    editor.on('init', function () {
                        console.log('✅ TinyMCE initialized successfully!');
                        // Hide loading message
                        const loadingMessages = document.querySelectorAll('.editor-loading-message');
                        loadingMessages.forEach(msg => msg.remove());
                    });
                    
                    editor.on('change', function () {
                        editor.save();
                    });
                    
                    // Custom button examples (optional)
                    editor.ui.registry.addButton('customInsertButton', {
                        text: 'Insert Content',
                        onAction: function () {
                            editor.insertContent('<p>Custom inserted content!</p>');
                        }
                    });
                },
                
                // Additional settings
                resize: 'both',
                statusbar: true,
                elementpath: false,
                
                // Valid elements and attributes for security
                valid_elements: '*[*]',
                extended_valid_elements: 'script[src|async|defer|type|charset]',
                
                // Paste settings
                paste_as_text: false,
                paste_auto_cleanup_on_paste: true,
                paste_remove_styles: false,
                paste_remove_spans: false,
                paste_strip_class_attributes: 'none',
                
                // Code sample settings
                codesample_languages: [
                    {text: 'HTML/XML', value: 'markup'},
                    {text: 'JavaScript', value: 'javascript'},
                    {text: 'CSS', value: 'css'},
                    {text: 'PHP', value: 'php'},
                    {text: 'Ruby', value: 'ruby'},
                    {text: 'Python', value: 'python'},
                    {text: 'Java', value: 'java'},
                    {text: 'C', value: 'c'},
                    {text: 'C#', value: 'csharp'},
                    {text: 'C++', value: 'cpp'}
                ]
            });
        }
        
        function showSimpleTextarea() {
            console.log('Falling back to simple textarea');
            const loadingMessages = document.querySelectorAll('.editor-loading-message');
            loadingMessages.forEach(msg => {
                msg.className = 'alert alert-warning';
                msg.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Rich text editor not available. Using simple text area.';
            });
        }
        
        // Start initialization
        setTimeout(() => initializeTinyMCE(), 500);
        
        // Add download link functionality
        let linkCounter = 1;
        
        document.getElementById('addDownloadLink')?.addEventListener('click', function() {
            const container = document.getElementById('downloadLinksContainer');
            const linkHTML = `
                <div class="download-link-item border p-2 rounded mb-2">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">Link ${linkCounter + 1}</small>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-link">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="download_links[${linkCounter}][title]" 
                               class="form-control form-control-sm" 
                               placeholder="Link title">
                    </div>
                    <div class="mb-2">
                        <input type="url" name="download_links[${linkCounter}][url]" 
                               class="form-control form-control-sm" 
                               placeholder="Download URL">
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', linkHTML);
            linkCounter++;
        });

        // Remove download link functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-link') || e.target.closest('.remove-link')) {
                const linkItem = e.target.closest('.download-link-item');
                if (linkItem && document.querySelectorAll('.download-link-item').length > 1) {
                    linkItem.remove();
                } else if (document.querySelectorAll('.download-link-item').length === 1) {
                    alert('At least one download link is required');
                }
            }
        });

        // Form validation
        document.getElementById('postForm')?.addEventListener('submit', function(e) {
            console.log('Form submit triggered');
            
            const title = this.querySelector('[name="title"]').value.trim();
            const postType = this.querySelector('[name="post_type"]').value;
            
            console.log('Title:', title);
            console.log('Post Type:', postType);
            
            // Get content from TinyMCE or textarea
            let content = '';
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                content = tinymce.get('content').getContent();
                console.log('Content from TinyMCE:', content.substring(0, 100) + '...');
            } else {
                const contentField = document.getElementById('content');
                if (contentField) {
                    content = contentField.value;
                    console.log('Content from textarea:', content.substring(0, 100) + '...');
                }
            }
            
            if (!title) {
                e.preventDefault();
                alert('Please enter a post title');
                this.querySelector('[name="title"]').focus();
                console.error('Validation failed: No title');
                return false;
            }
            
            if (!content || content.trim() === '' || content.trim() === '<p></p>' || content.trim() === '<p><br></p>') {
                e.preventDefault();
                alert('Please enter post content');
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').focus();
                } else {
                    document.getElementById('content')?.focus();
                }
                console.error('Validation failed: No content');
                return false;
            }
            
            // Allow form submission
            console.log('Validation passed, submitting form...');
            return true;
        });
        
        // Featured Image Preview
        const featuredImageInput = document.getElementById('featuredImageInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        
        if (featuredImageInput) {
            featuredImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please upload a valid image file (JPG, PNG, GIF, WEBP)');
                        this.value = '';
                        imagePreview.style.display = 'none';
                        return;
                    }
                    
                    // Validate file size (max 5MB)
                    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                    if (file.size > maxSize) {
                        alert('Image size must be less than 5MB');
                        this.value = '';
                        imagePreview.style.display = 'none';
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                }
            });
        }
    });
    
    // Monetized Links Management
    let newLinkCounter = 0;
    
    function addMonetizedLink() {
        const table = document.getElementById('monetizedLinksTable');
        const container = document.getElementById('monetizedLinksContainer');
        
        // Create table if doesn't exist
        if (!table) {
            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="monetizedLinksTable">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Title</th>
                                <th width="25%">Download URL</th>
                                <th width="10%">Size</th>
                                <th width="12%">Password</th>
                                <th width="12%">Short Code</th>
                                <th width="8%">Version</th>
                                <th width="8%">Stats</th>
                                <th width="5%">Del</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `;
        }
        
        const tbody = document.querySelector('#monetizedLinksTable tbody');
        const newId = 'new_' + newLinkCounter++;
        
        const row = document.createElement('tr');
        row.setAttribute('data-link-id', newId);
        row.innerHTML = `
            <td>
                <input type="text" 
                       name="monetized_links_new[${newId}][title]" 
                       class="form-control form-control-sm" 
                       placeholder="Download title"
                       required>
            </td>
            <td>
                <input type="url" 
                       name="monetized_links_new[${newId}][url]" 
                       class="form-control form-control-sm" 
                       placeholder="https://dl18.nesabamedia.net/..."
                       required>
            </td>
            <td>
                <input type="text" 
                       name="monetized_links_new[${newId}][size]" 
                       class="form-control form-control-sm" 
                       placeholder="1.1 GB">
            </td>
            <td>
                <input type="text" 
                       name="monetized_links_new[${newId}][password]" 
                       class="form-control form-control-sm text-danger fw-bold" 
                       placeholder="donan22.com">
            </td>
            <td>
                <small class="text-muted">Auto-generate</small>
            </td>
            <td>
                <input type="text" 
                       name="monetized_links_new[${newId}][version]" 
                       class="form-control form-control-sm" 
                       placeholder="2024">
            </td>
            <td>
                <small class="text-muted">New link</small>
            </td>
            <td class="text-center">
                <button type="button" 
                        class="btn btn-sm btn-danger" 
                        onclick="removeNewLink('${newId}')"
                        title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
        updateLinkCount();
    }
    
    function deleteMonetizedLink(linkId) {
        if (!confirm('Hapus download link ini?')) return;
        
        const row = document.querySelector(`tr[data-link-id="${linkId}"]`);
        if (row) {
            // Set delete flag
            const deleteFlag = row.querySelector('.delete-flag');
            if (deleteFlag) {
                deleteFlag.value = '1';
            }
            // Hide row
            row.style.display = 'none';
            updateLinkCount();
        }
    }
    
    function removeNewLink(linkId) {
        const row = document.querySelector(`tr[data-link-id="${linkId}"]`);
        if (row) {
            row.remove();
            updateLinkCount();
        }
    }
    
    function updateLinkCount() {
        const rows = document.querySelectorAll('#monetizedLinksTable tbody tr');
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const counter = document.getElementById('linkCount');
        if (counter) {
            counter.textContent = visibleRows.length;
        }
    }
    
    // ========================================
    // SEO TEMPLATE GENERATOR
    // ========================================
    document.getElementById('generateSEOTemplate')?.addEventListener('click', async function() {
        const btn = this;
        const statusDiv = document.getElementById('seoTemplateStatus');
        
        // Get form data
        const title = document.querySelector('input[name="title"]')?.value || '';
        const postType = document.querySelector('select[name="post_type_id"]')?.selectedOptions[0]?.text.toLowerCase() || 'software';
        const version = document.querySelector('input[name="version"]')?.value || '';
        const developer = document.querySelector('input[name="developer"]')?.value || '';
        const fileSize = document.querySelector('input[name="file_size"]')?.value || '';
        const categorySlug = document.querySelector('select[name="category_id"]')?.selectedOptions[0]?.dataset.slug || '';
        
        // Validate
        if (!title) {
            alert('❌ Title is required! Please enter a title first.');
            return;
        }
        
        // Confirm
        if (!confirm('Generate SEO template?\n\nThis will REPLACE current content with optimized H2/H3 structure.\n\n✅ Proper heading hierarchy\n✅ FAQ section\n✅ System requirements\n✅ Download guide')) {
            return;
        }
        
        // Show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-spinner fa-spin me-2"></i>Generating SEO template...</div>';
        
        try {
            const response = await fetch('api/generate-seo-template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title,
                    post_type: postType,
                    version,
                    developer,
                    file_size: fileSize,
                    category_slug: categorySlug
                })
            });
            
            if (!response.ok) {
                throw new Error('API request failed');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Insert content to TinyMCE editor
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').setContent(data.content);
                } else {
                    document.getElementById('content').value = data.content;
                }
                
                // Update slug if empty
                const slugInput = document.querySelector('input[name="slug"]');
                if (slugInput && !slugInput.value) {
                    slugInput.value = data.suggested_slug;
                }
                
                // Show success message with stats
                statusDiv.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <h6 class="alert-heading"><i class="fas fa-check-circle me-2"></i>SEO Template Generated!</h6>
                        <hr>
                        <p class="mb-2"><strong>Stats:</strong></p>
                        <ul class="mb-2">
                            <li>H2 Sections: <strong>${data.stats.h2_count}</strong></li>
                            <li>H3 Sub-sections: <strong>${data.stats.h3_count}</strong></li>
                            <li>Word Count: <strong>${data.stats.word_count}</strong></li>
                        </ul>
                        <p class="mb-2"><strong>Suggested H1:</strong></p>
                        <p class="text-primary mb-2"><strong>${data.seo_h1}</strong></p>
                        <p class="mb-2"><strong>Suggested Slug:</strong></p>
                        <p class="text-muted mb-2"><code>${data.suggested_slug}</code></p>
                        <p class="mb-2"><strong>Tips:</strong></p>
                        <ul class="mb-0 small">
                            ${data.tips.map(tip => `<li>${tip}</li>`).join('')}
                        </ul>
                    </div>
                `;
                
                // Auto-calculate SEO score
                setTimeout(() => {
                    if (typeof calculateSEO === 'function') {
                        calculateSEO();
                    }
                }, 1000);
                
            } else {
                throw new Error(data.error || 'Failed to generate template');
            }
            
        } catch (error) {
            console.error('Error generating SEO template:', error);
            statusDiv.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error: ${error.message}
                </div>
            `;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic me-2"></i>Generate SEO Template';
        }
    });
    
    </script>
</body>
</html>
