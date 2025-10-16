<?php
/**
 * DONAN22 - Features API
 * API endpoint for advanced post features (SEO, Tags, etc.)
 */

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once '../includes/enhancements.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ============================================
        // SEO SCORE CALCULATOR
        // ============================================
        case 'calculate_seo':
            $post = [
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'focus_keyword' => $_POST['focus_keyword'] ?? ''
            ];
            
            $result = calculateSEOScore($post);
            
            echo json_encode([
                'success' => true,
                'score' => $result['score'],
                'issues' => $result['issues']
            ]);
            break;
            
        // ============================================
        // TAG SUGGESTIONS (Auto-complete)
        // ============================================
        case 'tags_search':
            $query = $_POST['query'] ?? '';
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'tags' => []]);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, slug, COUNT(*) as usage_count
                FROM tags
                WHERE name LIKE ? OR slug LIKE ?
                GROUP BY id, name, slug
                ORDER BY usage_count DESC, name ASC
                LIMIT 10
            ");
            $stmt->execute(["%{$query}%", "%{$query}%"]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'tags' => $tags]);
            break;
            
        // ============================================
        // SAVE POST TAGS
        // ============================================
        case 'tags_save_post':
            $post_id = (int)($_POST['post_id'] ?? 0);
            $tag_ids = json_decode($_POST['tag_ids'] ?? '[]', true);
            
            if (!$post_id) {
                throw new Exception('Invalid post ID');
            }
            
            // Delete existing tags for this post
            $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $stmt->execute([$post_id]);
            
            // Insert new tags
            if (!empty($tag_ids)) {
                $insertStmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($tag_ids as $tag_id) {
                    // If tag_id is a string (new tag), create it first
                    if (!is_numeric($tag_id)) {
                        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_id)));
                        
                        // Check if tag already exists
                        $checkStmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                        $checkStmt->execute([$slug]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            $tag_id = $existing['id'];
                        } else {
                            // Create new tag
                            $createStmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                            $createStmt->execute([$tag_id, $slug]);
                            $tag_id = $pdo->lastInsertId();
                        }
                    }
                    
                    $insertStmt->execute([$post_id, $tag_id]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Tags saved successfully']);
            break;
            
        // ============================================
        // DEFAULT: Invalid action
        // ============================================
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
