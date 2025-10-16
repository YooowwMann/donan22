<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
requireLogin();

// Check permissions - only admin+ can manage categories
requirePermission('manage_categories');

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Handle category operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $slug = trim($_POST['slug']);
                $description = trim($_POST['description']);
                
                if ($name && $slug) {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $slug, $description]);
                    $success_msg = "Category added successfully!";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['category_id'];
                $name = trim($_POST['name']);
                $slug = trim($_POST['slug']);
                $description = trim($_POST['description']);
                
                if ($name && $slug) {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $description, $id]);
                    $success_msg = "Category updated successfully!";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['category_id'];
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success_msg = "Category deleted successfully!";
                break;
        }
    }
}

// Get all categories with post counts (including secondary categories)
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT CASE WHEN p.status = 'published' THEN p.id END) as published_count
    FROM categories c 
    LEFT JOIN posts p ON (c.id = p.category_id OR c.id = p.secondary_category_id) 
        AND p.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY post_count DESC, c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('categories'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tags me-2"></i>Manage Categories
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-info fs-6 px-3 py-2">
                                <?php echo count($categories); ?> Total Categories
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Add Category Form -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus me-2"></i>Add New Category
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="name" class="form-control" required placeholder="e.g. Web Development">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug" class="form-control" required placeholder="e.g. web-development">
                                        <small class="form-text text-muted">URL-friendly version (lowercase, use dashes)</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Category description..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-2"></i>Add Category
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>All Categories
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($categories)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No categories found</h5>
                                    <p class="text-muted">Start by creating your first category!</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Slug</th>
                                                <th>Posts</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge bg-primary mb-1">
                                                            <?php echo $category['published_count']; ?> Published
                                                        </span>
                                                        <?php if ($category['post_count'] > $category['published_count']): ?>
                                                        <span class="badge bg-secondary">
                                                            <?php echo $category['post_count'] - $category['published_count']; ?> Draft
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 40)); ?>
                                                    <?php if (strlen($category['description'] ?? '') > 40) echo '...'; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="edit_slug" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_slug').value = category.slug;
            document.getElementById('edit_description').value = category.description || '';
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        // Auto-generate slug from category name
        function generateSlug(text) {
            return text
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')         // Replace spaces with dashes
                .replace(/-+/g, '-')          // Replace multiple dashes with single dash
                .replace(/^-|-$/g, '');       // Remove leading/trailing dashes
        }

        // Add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generate slug for add form
            const nameInput = document.querySelector('form input[name="name"]');
            const slugInput = document.querySelector('form input[name="slug"]');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function() {
                    if (!slugInput.value || slugInput.dataset.userModified !== 'true') {
                        slugInput.value = generateSlug(this.value);
                    }
                });

                // Mark slug as user-modified when manually changed
                slugInput.addEventListener('input', function() {
                    this.dataset.userModified = 'true';
                });
            }
        });
    </script>
</body>
</html>
