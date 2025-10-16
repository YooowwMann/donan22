<?php
require_once '../config_modern.php';
requireLogin();

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Handle comment actions
if ($_POST) {
    if (isset($_POST['action'])) {
        $comment_id = (int)$_POST['comment_id'];
        
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_msg = "Comment approved successfully!";
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_msg = "Comment rejected successfully!";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $success_msg = "Comment deleted successfully!";
                break;
                
            case 'bulk_action':
                $action = $_POST['bulk_action'];
                $selected_comments = $_POST['selected_comments'] ?? [];
                
                if (!empty($selected_comments) && !empty($action)) {
                    $placeholders = str_repeat('?,', count($selected_comments) - 1) . '?';
                    
                    if ($action === 'delete') {
                        $stmt = $pdo->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                    } else {
                        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id IN ($placeholders)");
                        array_unshift($selected_comments, $action);
                    }
                    
                    $stmt->execute($selected_comments);
                    $count = count($_POST['selected_comments']);
                    $success_msg = "$count comments updated successfully!";
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(c.content LIKE ? OR c.user_name LIKE ? OR c.user_email LIKE ? OR p.title LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM comments c LEFT JOIN posts p ON c.post_id = p.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_comments = $stmt->fetchColumn();
$total_pages = ceil($total_comments / $limit);

// Get comments with pagination
$sql = "SELECT c.*, p.title as post_title, p.slug as post_slug 
        FROM comments c 
        LEFT JOIN posts p ON c.post_id = p.id 
        $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

// Get comment statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM comments";
$stmt = $pdo->prepare($stats_sql);
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <?php require_once 'includes/navigation.php'; ?>
    <style>
        .comment-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-pending { color: #f39c12; }
        .status-approved { color: #27ae60; }
        .status-rejected { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('comments'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-comments me-2"></i>Comments Management
                    </h1>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $stats['total']; ?></h4>
                                        <p class="card-text">Total Comments</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-comments fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $stats['pending']; ?></h4>
                                        <p class="card-text">Pending</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $stats['approved']; ?></h4>
                                        <p class="card-text">Approved</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title"><?php echo $stats['rejected']; ?></h4>
                                        <p class="card-text">Rejected</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status Filter</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search comments, author, post..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Comments Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Comments List</h5>
                        <small class="text-muted"><?php echo $total_comments; ?> total comments</small>
                    </div>
                    
                    <?php if (!empty($comments)): ?>
                    <form method="POST" id="commentsForm">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <select name="bulk_action" class="form-select">
                                            <option value="">Bulk Actions</option>
                                            <option value="approved">Approve Selected</option>
                                            <option value="rejected">Reject Selected</option>
                                            <option value="delete">Delete Selected</option>
                                        </select>
                                        <button type="submit" name="action" value="bulk_action" class="btn btn-outline-secondary">Apply</button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleSelectAll()">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" id="selectAll"></th>
                                        <th>Author</th>
                                        <th>Comment</th>
                                        <th>Post</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_comments[]" value="<?php echo $comment['id']; ?>" class="comment-checkbox">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($comment['user_name'] ?? 'Anonymous'); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($comment['user_email'] ?? 'No email'); ?></small>
                                        </td>
                                        <td>
                                            <div class="comment-content">
                                                <?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . (strlen($comment['content']) > 100 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($comment['post_title']): ?>
                                                <a href="<?= SITE_URL ?>/post/<?php echo $comment['post_slug']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars(substr($comment['post_title'], 0, 30)) . (strlen($comment['post_title']) > 30 ? '...' : ''); ?>
                                                </a>
                                            <?php else: ?>
                                                <em>Post deleted</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $comment['status'] === 'approved' ? 'success' : ($comment['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($comment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($comment['status'] !== 'approved'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($comment['status'] !== 'rejected'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn btn-warning btn-sm" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this comment?')">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5>No comments found</h5>
                        <p class="text-muted">No comments match your current filters.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.comment-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !selectAll.checked;
            });
            
            selectAll.checked = !selectAll.checked;
        }
        
        // Auto-check select all when all individual checkboxes are checked
        document.querySelectorAll('.comment-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.comment-checkbox');
                const selectAll = document.getElementById('selectAll');
                selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
            });
        });
    </script>
</body>
</html>
