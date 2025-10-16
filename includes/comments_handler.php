<?php
/**
 * Comments & Rating System Handler
 * DONAN22 - Modern Comment System with Rating
 */

// Prevent duplicate inclusion
if (defined('COMMENTS_HANDLER_LOADED')) {
    return;
}
define('COMMENTS_HANDLER_LOADED', true);

if (!defined('ADMIN_ACCESS') && !isset($pdo)) {
    require_once '../config_modern.php';
}

/**
 * Submit new comment with rating
 */
function submitComment($postId, $name, $email, $comment, $rating = null, $parentId = null) {
    global $pdo;
    
    try {
        // Validate inputs
        if (empty($name) || empty($email) || empty($comment)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }
        
        // Sanitize inputs
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $comment = htmlspecialchars(trim($comment), ENT_QUOTES, 'UTF-8');
        
        // Check if comments table exists and has required columns
        $stmt = $pdo->prepare("INSERT INTO comments 
            (post_id, user_name, user_email, content, rating, parent_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())");
        
        $result = $stmt->execute([
            $postId, 
            $name, 
            $email, 
            $comment, 
            $rating, 
            $parentId
        ]);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'Comment posted successfully!',
                'comment_id' => $pdo->lastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to post comment'];
        
    } catch (PDOException $e) {
        error_log("Comment submission error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Get comments for a post
 */
function getComments($postId, $status = 'approved') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.post_id, c.parent_id, 
                   COALESCE(c.user_name, '') as name,
                   COALESCE(c.user_email, '') as email,
                   COALESCE(c.content, '') as comment,
                   c.rating, c.status, c.created_at,
                   COUNT(r.id) as reply_count
            FROM comments c
            LEFT JOIN comments r ON r.parent_id = c.id AND r.status = 'approved'
            WHERE c.post_id = ? 
                AND c.status = ? 
                AND c.parent_id IS NULL
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$postId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get comments error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get replies for a comment
 */
function getCommentReplies($commentId, $status = 'approved') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, post_id, parent_id,
                   COALESCE(user_name, '') as name,
                   COALESCE(user_email, '') as email,
                   COALESCE(content, '') as comment,
                   rating, status, created_at
            FROM comments 
            WHERE parent_id = ? AND status = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$commentId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get replies error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get average rating for a post
 */
function getAverageRating($postId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                AVG(rating) as average,
                COUNT(rating) as count
            FROM comments 
            WHERE post_id = ? 
                AND rating IS NOT NULL 
                AND status = 'approved'
        ");
        $stmt->execute([$postId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'average' => round($result['average'] ?? 0, 1),
            'count' => (int)($result['count'] ?? 0)
        ];
    } catch (PDOException $e) {
        error_log("Get rating error: " . $e->getMessage());
        return ['average' => 0, 'count' => 0];
    }
}

/**
 * Get rating distribution (1-5 stars count)
 */
function getRatingDistribution($postId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT rating, COUNT(*) as count 
            FROM comments 
            WHERE post_id = ? 
                AND rating IS NOT NULL 
                AND status = 'approved'
            GROUP BY rating
            ORDER BY rating DESC
        ");
        $stmt->execute([$postId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialize all ratings to 0
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        
        foreach ($results as $row) {
            $distribution[(int)$row['rating']] = (int)$row['count'];
        }
        
        return $distribution;
    } catch (PDOException $e) {
        error_log("Get rating distribution error: " . $e->getMessage());
        return [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    }
}

/**
 * Delete comment (soft delete)
 */
function deleteComment($commentId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE comments SET status = 'deleted' WHERE id = ?");
        $result = $stmt->execute([$commentId]);
        
        return [
            'success' => $result,
            'message' => $result ? 'Comment deleted successfully' : 'Failed to delete comment'
        ];
    } catch (PDOException $e) {
        error_log("Delete comment error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

/**
 * Render star rating HTML
 */
function renderStarRating($rating, $maxStars = 5, $showNumber = true) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = $maxStars - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '<div class="star-rating" data-rating="' . $rating . '">';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-warning"></i>';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-warning"></i>';
    }
    
    if ($showNumber) {
        $html .= ' <span class="rating-number ms-1">(' . number_format($rating, 1) . ')</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render comment form HTML
 */
function renderCommentForm($postId, $parentId = null) {
    $formId = $parentId ? "replyForm{$parentId}" : "commentForm";
    $title = $parentId ? "Reply to Comment" : "Leave a Comment";
    
    ob_start();
    ?>
    <div class="comment-form-container mb-3" id="<?= $formId ?>">
        <h5 class="mb-2"><i class="fas fa-pen me-2"></i><?= $title ?></h5>
        <form method="POST" action="" class="comment-form compact-form" data-post-id="<?= $postId ?>" data-parent-id="<?= $parentId ?>">
            <input type="hidden" name="action" value="submit_comment">
            <input type="hidden" name="post_id" value="<?= $postId ?>">
            <?php if ($parentId): ?>
                <input type="hidden" name="parent_id" value="<?= $parentId ?>">
            <?php endif; ?>
            
            <?php if (!$parentId): ?>
            <!-- Rating (only for main comments, not replies) -->
            <div class="mb-2">
                <label class="form-label fw-bold mb-1" style="font-size: 14px;">Your Rating</label>
                <div class="star-rating-input">
                    <input type="radio" name="rating" value="5" id="star5_<?= $formId ?>" required>
                    <label for="star5_<?= $formId ?>" title="5 stars"><i class="fas fa-star"></i></label>
                    
                    <input type="radio" name="rating" value="4" id="star4_<?= $formId ?>">
                    <label for="star4_<?= $formId ?>" title="4 stars"><i class="fas fa-star"></i></label>
                    
                    <input type="radio" name="rating" value="3" id="star3_<?= $formId ?>">
                    <label for="star3_<?= $formId ?>" title="3 stars"><i class="fas fa-star"></i></label>
                    
                    <input type="radio" name="rating" value="2" id="star2_<?= $formId ?>">
                    <label for="star2_<?= $formId ?>" title="2 stars"><i class="fas fa-star"></i></label>
                    
                    <input type="radio" name="rating" value="1" id="star1_<?= $formId ?>">
                    <label for="star1_<?= $formId ?>" title="1 star"><i class="fas fa-star"></i></label>
                </div>
                <small class="text-muted d-block" style="font-size: 12px;">Click to rate</small>
            </div>
            <?php endif; ?>
            
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" name="name" placeholder="Your Name" required>
                </div>
                <div class="col-md-6">
                    <input type="email" class="form-control form-control-sm" name="email" placeholder="Your Email" required>
                </div>
            </div>
            
            <div class="mb-2">
                <textarea class="form-control form-control-sm" name="comment" rows="3" placeholder="Your Comment" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>Submit Comment
            </button>
            <?php if ($parentId): ?>
                <button type="button" class="btn btn-secondary" onclick="hideReplyForm(<?= $parentId ?>)">
                    Cancel
                </button>
            <?php endif; ?>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render comments list HTML
 */
function renderCommentsList($postId) {
    $comments = getComments($postId);
    
    if (empty($comments)) {
        return '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No comments yet. Be the first to comment!</div>';
    }
    
    ob_start();
    foreach ($comments as $comment) {
        renderSingleComment($comment);
    }
    return ob_get_clean();
}

/**
 * Render single comment HTML
 */
function renderSingleComment($comment) {
    $replies = getCommentReplies($comment['id']);
    ?>
    <div class="comment-item mb-3 p-2 border rounded" id="comment-<?= $comment['id'] ?>">
        <div class="d-flex align-items-start">
            <div class="comment-avatar me-2">
                <div class="avatar-circle">
                    <?= strtoupper(substr($comment['name'] ?? 'A', 0, 1)) ?>
                </div>
            </div>
            <div class="comment-content flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <strong><?= htmlspecialchars($comment['name'] ?? 'Anonymous') ?></strong>
                        <?php if ($comment['rating']): ?>
                            <span class="ms-2">
                                <?= renderStarRating($comment['rating'], 5, false) ?>
                            </span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted" style="font-size: 12px;">
                            <i class="far fa-clock me-1"></i>
                            <?= date('M j, Y \a\t g:i A', strtotime($comment['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <p class="mb-2" style="font-size: 14px;"><?= nl2br(htmlspecialchars($comment['comment'] ?? '')) ?></p>
                <button class="btn btn-sm btn-outline-primary btn-sm" style="font-size: 12px; padding: 4px 12px;" onclick="showReplyForm(<?= $comment['id'] ?>)">
                    <i class="fas fa-reply me-1"></i>Reply
                </button>
            </div>
        </div>
        
        <!-- Reply Form (hidden by default) -->
        <div id="reply-form-<?= $comment['id'] ?>" class="reply-form mt-2 ms-5" style="display: none;">
            <?= renderCommentForm($comment['post_id'], $comment['id']) ?>
        </div>
        
        <!-- Replies -->
        <?php if (!empty($replies)): ?>
            <div class="replies ms-4 mt-2">
                <?php foreach ($replies as $reply): ?>
                    <div class="comment-item mb-2 p-2 border rounded bg-light" style="font-size: 13px;">
                        <div class="d-flex align-items-start">
                            <div class="comment-avatar me-2">
                                <div class="avatar-circle" style="width: 35px; height: 35px; font-size: 14px;">
                                    <?= strtoupper(substr($reply['name'] ?? 'A', 0, 1)) ?>
                                </div>
                            </div>
                            <div class="comment-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <strong style="font-size: 14px;"><?= htmlspecialchars($reply['name'] ?? 'Anonymous') ?></strong>
                                        <br>
                                        <small class="text-muted" style="font-size: 11px;">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('M j, Y \a\t g:i A', strtotime($reply['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <p class="mb-0" style="font-size: 13px;"><?= nl2br(htmlspecialchars($reply['comment'] ?? '')) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle comment submission via AJAX or POST
 */
function handleCommentSubmission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
        $postId = (int)$_POST['post_id'];
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        $result = submitComment($postId, $name, $email, $comment, $rating, $parentId);
        
        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        // Otherwise, redirect with message
        if ($result['success']) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '#comments');
        } else {
            $_SESSION['comment_error'] = $result['message'];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '#commentForm');
        }
        exit;
    }
}
?>
