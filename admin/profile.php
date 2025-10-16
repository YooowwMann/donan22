<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
requireLogin();

$admin = getCurrentAdmin();
if (!$admin || !is_array($admin)) {
    header('Location: login.php');
    exit;
}

// Handle profile update
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        // Note: bio field removed as it doesn't exist in administrators table
        
        // Handle password change
        if (!empty($_POST['new_password'])) {
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password'] ?? '');
            
            if ($new_password !== $confirm_password) {
                $error_msg = "New passwords do not match!";
            } elseif (strlen($new_password) < 6) {
                $error_msg = "Password must be at least 6 characters long!";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update with password
                $stmt = $pdo->prepare("UPDATE administrators SET username = ?, email = ?, password_hash = ?, full_name = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$username, $email, $password_hash, $full_name, $_SESSION['admin_id']]);
            }
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE administrators SET username = ?, email = ?, full_name = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$username, $email, $full_name, $_SESSION['admin_id']]);
        }
        
        if (isset($result) && $result) {
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_name'] = $full_name ?: $username;
            $success_msg = "Profile updated successfully!";
        } else if (!isset($error_msg)) {
            $error_msg = "Error updating profile!";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed)) {
                $new_filename = 'avatar_' . $_SESSION['admin_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Update avatar in database
                    $stmt = $pdo->prepare("UPDATE administrators SET avatar = ? WHERE id = ?");
                    if ($stmt->execute([$new_filename, $_SESSION['admin_id']])) {
                        $success_msg = "Avatar updated successfully!";
                    }
                } else {
                    $error_msg = "Error uploading avatar!";
                }
            } else {
                $error_msg = "Only JPG, JPEG, PNG & GIF files are allowed!";
            }
        }
    }
}

// Get current admin user data
$stmt = $pdo->prepare("SELECT * FROM administrators WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_posts FROM posts");
$stmt->execute();
$total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total_posts'];

$stmt = $pdo->prepare("SELECT COUNT(*) as published_posts FROM posts WHERE status = 'published'");
$stmt->execute();
$published_posts = $stmt->fetch(PDO::FETCH_ASSOC)['published_posts'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_categories FROM categories");
$stmt->execute();
$total_categories = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .avatar-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('profile'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user me-2"></i>My Profile
                    </h1>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Profile Overview -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="position-relative d-inline-block mb-3">
                                    <?php if ($admin_user['avatar']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($admin_user['avatar']); ?>" 
                                         class="profile-avatar rounded-circle" alt="Avatar">
                                    <?php else: ?>
                                    <div class="profile-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user fa-4x text-white"></i>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                                        <input type="hidden" name="action" value="upload_avatar">
                                        <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                                        <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <h4><?php echo htmlspecialchars($admin_user['full_name'] ?: $admin_user['username']); ?></h4>
                                <p class="text-muted">@<?php echo htmlspecialchars($admin_user['username']); ?></p>
                                <p class="small"><?php echo ucfirst($admin_user['role']); ?> since <?php echo date('M Y', strtotime($admin_user['created_at'])); ?></p>
                                
                                <div class="row text-center mt-3">
                                    <div class="col">
                                        <h5><?php echo $total_posts; ?></h5>
                                        <small class="text-muted">Total Posts</small>
                                    </div>
                                    <div class="col">
                                        <h5><?php echo $published_posts; ?></h5>
                                        <small class="text-muted">Published</small>
                                    </div>
                                    <div class="col">
                                        <h5><?php echo $total_categories; ?></h5>
                                        <small class="text-muted">Categories</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Info -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Account Info
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Member Since:</strong><br>
                                <?php echo date('F j, Y', strtotime($admin_user['created_at'])); ?></p>
                                
                                <p><strong>Last Updated:</strong><br>
                                <?php echo $admin_user['updated_at'] ? date('F j, Y g:i A', strtotime($admin_user['updated_at'])) : 'Never'; ?></p>
                                
                                <p><strong>Status:</strong><br>
                                <span class="badge bg-success">Active</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Username *</label>
                                                <input type="text" name="username" class="form-control" 
                                                       value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="full_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($admin_user['full_name']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" class="form-control" rows="3" 
                                                  placeholder="Tell us about yourself..." readonly><?php echo htmlspecialchars($admin_user['full_name'] . ' - ' . $admin_user['role']); ?></textarea>
                                        <small class="form-text text-muted">Bio field is auto-generated from your name and role</small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6>Change Password</h6>
                                    <small class="text-muted">Leave blank if you don't want to change password</small>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" name="new_password" class="form-control" 
                                                       placeholder="Enter new password">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Confirm Password</label>
                                                <input type="password" name="confirm_password" class="form-control" 
                                                       placeholder="Confirm new password">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Activity Log -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Activity
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-sign-in-alt text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-1">Logged into admin panel</p>
                                            <small class="text-muted"><?php echo date('F j, Y g:i A'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="list-group-item d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-user-edit text-info"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-1">Profile viewed</p>
                                            <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime('-2 hours')); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="list-group-item d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-file-alt text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="mb-1">Managed posts</p>
                                            <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime('-1 day')); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
