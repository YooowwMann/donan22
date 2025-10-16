<?php
/**
 * ADMIN API - PARAFRASE ARTIKEL
 * Secure API endpoint inside admin folder
 */

// Define admin access
define('ADMIN_ACCESS', true);

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/paraphrase_errors.log');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Load config
require_once __DIR__ . '/../../config_modern.php';

// Set limits
set_time_limit(300);
ini_set('memory_limit', '512M');

// Set JSON header
header('Content-Type: application/json');

// Load sinonim database
$synonymFile = __DIR__ . '/../../sinonim_gabungan.csv';
$synonyms = [];

if (file_exists($synonymFile)) {
    $handle = fopen($synonymFile, 'r');
    $isFirstRow = true;
    
    while (($data = fgetcsv($handle)) !== false) {
        if ($isFirstRow) {
            $isFirstRow = false;
            continue;
        }
        
        if (isset($data[0]) && isset($data[1])) {
            $kata = strtolower(trim($data[0]));
            $sinonimList = array_map('trim', explode(',', $data[1]));
            
            if (!isset($synonyms[$kata])) {
                $synonyms[$kata] = $sinonimList;
            } else {
                $synonyms[$kata] = array_unique(array_merge($synonyms[$kata], $sinonimList));
            }
        }
    }
    fclose($handle);
}

/**
 * Extract images from HTML content
 */
function extractImages($html) {
    $images = [];
    $placeholder = '___IMAGE_PLACEHOLDER_';
    
    // Extract img tags
    preg_match_all('/<img[^>]+>/i', $html, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $index => $imgTag) {
            $images[$index] = $imgTag;
            $html = str_replace($imgTag, $placeholder . $index . '___', $html);
        }
    }
    
    // Extract figure tags (CKEditor/TinyMCE)
    preg_match_all('/<figure[^>]*>.*?<\/figure>/is', $html, $figureMatches);
    
    if (!empty($figureMatches[0])) {
        foreach ($figureMatches[0] as $figureTag) {
            $figIndex = count($images);
            $images[$figIndex] = $figureTag;
            $html = str_replace($figureTag, $placeholder . $figIndex . '___', $html);
        }
    }
    
    return [
        'images' => $images,
        'content' => $html
    ];
}

/**
 * Restore images to content
 */
function restoreImages($content, $images) {
    foreach ($images as $index => $imgTag) {
        $placeholder = '___IMAGE_PLACEHOLDER_' . $index . '___';
        $content = str_replace($placeholder, $imgTag, $content);
    }
    return $content;
}

/**
 * Paraphrase text only (without HTML)
 */
function paraphraseText($text, $synonyms, $percentage = 60) {
    if (empty($text) || empty($synonyms)) return $text;
    
    $words = preg_split('/(\s+|[.,!?;:"\'\-\(\)\[\]\{\}]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $replacedCount = 0;
    $totalWords = 0;
    
    foreach ($words as $index => $word) {
        if (preg_match('/^(\s+|[.,!?;:"\'\-\(\)\[\]\{\}]+)$/u', $word)) {
            continue;
        }
        
        $totalWords++;
        
        if (rand(1, 100) > $percentage) {
            continue;
        }
        
        $lowerWord = strtolower($word);
        
        if (isset($synonyms[$lowerWord]) && !empty($synonyms[$lowerWord])) {
            $synonym = $synonyms[$lowerWord][array_rand($synonyms[$lowerWord])];
            
            // Preserve capitalization
            if (mb_strtoupper($word) === $word) {
                $synonym = mb_strtoupper($synonym);
            } elseif (mb_strtoupper(mb_substr($word, 0, 1)) === mb_substr($word, 0, 1)) {
                $synonym = mb_strtoupper(mb_substr($synonym, 0, 1)) . mb_substr($synonym, 1);
            }
            
            $words[$index] = $synonym;
            $replacedCount++;
        }
    }
    
    return [
        'text' => implode('', $words),
        'replacedCount' => $replacedCount,
        'totalWords' => $totalWords
    ];
}

/**
 * Paraphrase HTML content (with image protection)
 */
function paraphraseHtmlContent($html, $synonyms, $percentage = 60) {
    $extracted = extractImages($html);
    $cleanContent = $extracted['content'];
    $images = $extracted['images'];
    
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $cleanContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    $totalReplaced = 0;
    $totalWords = 0;
    
    $xpath = new DOMXPath($dom);
    $textNodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style)]');
    
    foreach ($textNodes as $node) {
        $text = $node->nodeValue;
        if (trim($text)) {
            $result = paraphraseText($text, $synonyms, $percentage);
            $node->nodeValue = $result['text'];
            $totalReplaced += $result['replacedCount'];
            $totalWords += $result['totalWords'];
        }
    }
    
    $paraphrasedContent = $dom->saveHTML();
    $finalContent = restoreImages($paraphrasedContent, $images);
    
    return [
        'content' => $finalContent,
        'replacedCount' => $totalReplaced,
        'totalWords' => $totalWords,
        'imageCount' => count($images)
    ];
}

// ============================================
// HANDLE API REQUESTS
// ============================================

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action'])) {
        throw new Exception('Action tidak ditemukan');
    }
    
    $action = $input['action'];
    
    // GET POSTS LIST
    if ($action === 'get_posts') {
        $search = isset($input['search']) ? trim($input['search']) : '';
        $status = isset($input['status']) ? trim($input['status']) : '';
        $paraphraseStatus = isset($input['paraphrase_status']) ? trim($input['paraphrase_status']) : '';
        
        $whereConditions = ["deleted_at IS NULL"];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Check if paraphrase tracking columns exist
        $checkColumns = $pdo->query("SHOW COLUMNS FROM posts LIKE 'is_paraphrased'")->rowCount();
        $hasTrackingColumns = ($checkColumns > 0);
        
        if ($hasTrackingColumns && !empty($paraphraseStatus)) {
            if ($paraphraseStatus === 'paraphrased') {
                $whereConditions[] = "is_paraphrased = 1";
            } elseif ($paraphraseStatus === 'not_paraphrased') {
                $whereConditions[] = "(is_paraphrased = 0 OR is_paraphrased IS NULL)";
            }
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $selectColumns = "id, title, slug, content, status,
                   DATE_FORMAT(created_at, '%d %b %Y %H:%i') as created_at,
                   DATE_FORMAT(updated_at, '%d %b %Y %H:%i') as updated_at";
        
        if ($hasTrackingColumns) {
            $selectColumns .= ",
                   COALESCE(is_paraphrased, 0) as is_paraphrased, 
                   paraphrased_at, 
                   COALESCE(paraphrase_percentage, 0) as paraphrase_percentage, 
                   COALESCE(paraphrase_count, 0) as paraphrase_count,
                   IF(paraphrased_at IS NOT NULL, DATE_FORMAT(paraphrased_at, '%d %b %Y %H:%i'), NULL) as paraphrased_at_formatted";
        }
        
        $sql = "SELECT $selectColumns FROM posts $whereClause ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE deleted_at IS NULL");
        $countStmt->execute();
        $totalCount = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'totalPosts' => $totalCount,
            'posts' => $posts
        ]);
        exit;
    }
    
    // GET SINGLE POST
    if ($action === 'get_post') {
        if (!isset($input['post_id'])) {
            throw new Exception('Post ID tidak ditemukan');
        }
        
        $postId = intval($input['post_id']);
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            throw new Exception('Post tidak ditemukan');
        }
        
        echo json_encode([
            'success' => true,
            'post' => $post
        ]);
        exit;
    }
    
    // PARAPHRASE POST
    if ($action === 'paraphrase') {
        if (!isset($input['post_id'])) {
            throw new Exception('Post ID tidak ditemukan');
        }
        
        if (empty($synonyms)) {
            throw new Exception('File sinonim tidak ditemukan');
        }
        
        $postId = $input['post_id'];
        $percentage = isset($input['percentage']) ? intval($input['percentage']) : 60;
        $preview = isset($input['preview']) ? $input['preview'] : false;
        
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            throw new Exception('Post tidak ditemukan');
        }
        
        // Paraphrase content and title
        $result = paraphraseHtmlContent($post['content'], $synonyms, $percentage);
        $titleResult = paraphraseText($post['title'], $synonyms, $percentage);
        
        // Preview mode
        if ($preview) {
            echo json_encode([
                'success' => true,
                'preview' => true,
                'paraphrased_content' => $result['content'],
                'paraphrased_title' => $titleResult['text'],
                'stats' => [
                    'replaced_words' => $result['replacedCount'] + $titleResult['replacedCount'],
                    'total_words' => $result['totalWords'] + $titleResult['totalWords'],
                    'images_protected' => $result['imageCount'],
                    'percentage' => $percentage
                ]
            ]);
            exit;
        }
        
        // Apply to database
        $checkColumns = $pdo->query("SHOW COLUMNS FROM posts LIKE 'is_paraphrased'")->rowCount();
        $hasTrackingColumns = ($checkColumns > 0);
        
        if ($hasTrackingColumns) {
            $updateStmt = $pdo->prepare("
                UPDATE posts 
                SET title = ?, content = ?, updated_at = NOW(),
                    is_paraphrased = 1, paraphrased_at = NOW(),
                    paraphrase_percentage = ?,
                    paraphrase_count = COALESCE(paraphrase_count, 0) + 1
                WHERE id = ?
            ");
            $updateStmt->execute([$titleResult['text'], $result['content'], $percentage, $postId]);
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE posts 
                SET title = ?, content = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$titleResult['text'], $result['content'], $postId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Artikel berhasil diparafrase!',
            'stats' => [
                'replaced_words' => $result['replacedCount'] + $titleResult['replacedCount'],
                'total_words' => $result['totalWords'] + $titleResult['totalWords'],
                'images_protected' => $result['imageCount'],
                'percentage' => $percentage
            ]
        ]);
        exit;
    }
    
    throw new Exception('Action tidak valid');
    
} catch (Exception $e) {
    error_log('Paraphrase API Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
