<?php
/**
 * Live Search API - AJAX Endpoint
 * Returns search results with thumbnails for autocomplete
 */

require_once '../config_modern.php';

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

try {
    // Search in posts (title, excerpt, content)
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.title,
            p.slug,
            p.excerpt,
            p.featured_image,
            COALESCE(p.view_count, 0) as views,
            c.name as category_name,
            c.slug as category_slug,
            pt.name as post_type_name,
            pt.slug as post_type_slug
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN post_types pt ON p.post_type_id = pt.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
          AND (
              p.title LIKE ? OR 
              p.excerpt LIKE ? OR 
              p.content LIKE ? OR
              c.name LIKE ?
          )
        ORDER BY p.view_count DESC, p.created_at DESC
        LIMIT 8
    ");
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $formattedResults = [];
    foreach ($results as $post) {
        $formattedResults[] = [
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'url' => '/donan22/post/' . $post['slug'],
            'excerpt' => substr(strip_tags($post['excerpt']), 0, 100) . '...',
            'image' => $post['featured_image'] ?: 'assets/images/placeholder.svg',
            'category' => $post['category_name'],
            'category_slug' => $post['category_slug'],
            'type' => $post['post_type_name'],
            'type_slug' => $post['post_type_slug'],
            'views' => number_format($post['views'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $formattedResults,
        'total' => count($formattedResults)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
