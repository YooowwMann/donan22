<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
require_once 'system/role_manager.php';
requireLogin();

$roleManager = getRoleManager();
requirePermission('create_admin');

$pageTitle = 'User Management';
$currentPage = 'users';

$message = '';
$messageType = 'info';

// Handle user actions
if ($_POST) {
    // Validate CSRF token  
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generateCSRFToken()) {
        $message = "Token keamanan tidak valid";
        $messageType = 'danger';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Format email tidak valid";
                    $messageType = 'danger';
                    break;
                }
                
                // Validate password strength (min 8 chars, 1 uppercase, 1 number, 1 special)
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
                    $message = "Password harus minimal 8 karakter dengan 1 huruf besar, 1 angka, dan 1 karakter spesial (@$!%*?&#)";
                    $messageType = 'danger';
                    break;
                }
                
                if ($username && $email && $password && $roleManager->canManageRole($role)) {
                    try {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("INSERT INTO administrators (username, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                        $stmt->execute([$username, $email, $hashedPassword, $role]);
                        
                        $message = "User berhasil dibuat!";
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = "Error creating user: " . $e->getMessage();
                        $messageType = 'danger';
                    }
                } else {
                    $message = "Input tidak valid atau izin tidak mencukupi";
                    $messageType = 'danger';
                }
                break;
                
            case 'update_role':
                $userId = (int)$_POST['user_id'];
                $newRole = $_POST['role'];
                
                try {
                    // Get username before update for logging
                    $stmt = $pdo->prepare("SELECT username FROM administrators WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $roleManager->updateUserRole($userId, $newRole);
                    
                    $message = "Role user berhasil diupdate!";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error updating role: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'edit_user':
                $userId = (int)$_POST['user_id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Format email tidak valid";
                    $messageType = 'danger';
                    break;
                }
                
                // Check if user exists and is not self
                if ($userId === $_SESSION['admin_id']) {
                    $message = "Tidak dapat mengubah data user aktif sendiri di sini. Gunakan Profile page.";
                    $messageType = 'warning';
                    break;
                }
                
                // Validate role permission
                if (!$roleManager->canManageRole($role)) {
                    $message = "Anda tidak memiliki izin untuk mengatur role ini";
                    $messageType = 'danger';
                    break;
                }
                
                try {
                    // Get old data for logging
                    $stmt = $pdo->prepare("SELECT username, email, role FROM administrators WHERE id = ?");
                    $stmt->execute([$userId]);
                    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if email already exists (excluding current user)
                    $stmt = $pdo->prepare("SELECT id FROM administrators WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $userId]);
                    if ($stmt->fetch()) {
                        $message = "Email sudah digunakan oleh user lain";
                        $messageType = 'danger';
                        break;
                    }
                    
                    // Update user data
                    $stmt = $pdo->prepare("UPDATE administrators SET username = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $userId]);
                    
                    // Build change description
                    $changes = [];
                    if ($oldData['username'] !== $username) $changes[] = "username: {$oldData['username']} → $username";
                    if ($oldData['email'] !== $email) $changes[] = "email: {$oldData['email']} → $email";
                    if ($oldData['role'] !== $role) $changes[] = "role: {$oldData['role']} → $role";
                    
                    $message = "User berhasil diupdate!";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error updating user: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'toggle_status':
                $userId = (int)$_POST['user_id'];
                $newStatus = $_POST['status']; // 'active' or 'inactive'
                
                // Validate status value
                if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
                    $message = "Status tidak valid";
                    $messageType = 'danger';
                    break;
                }
                
                // Check if user is not self
                if ($userId === $_SESSION['admin_id']) {
                    $message = "Tidak dapat mengubah status user aktif sendiri";
                    $messageType = 'warning';
                    break;
                }
                
                // Check permission
                if (!hasPermission('manage_users')) {
                    $message = "Anda tidak memiliki izin untuk mengubah status user";
                    $messageType = 'danger';
                    break;
                }
                
                try {
                    // Get user data for logging
                    $stmt = $pdo->prepare("SELECT username, status FROM administrators WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        $message = "User tidak ditemukan";
                        $messageType = 'danger';
                        break;
                    }
                    
                    // Update status
                    $stmt = $pdo->prepare("UPDATE administrators SET status = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $userId]);
                    
                    $message = "Status user berhasil diubah menjadi " . ucfirst($newStatus);
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error updating status: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'reset_password':
                $userId = (int)$_POST['user_id'];
                
                // Check if user is not self
                if ($userId === $_SESSION['admin_id']) {
                    $message = "Tidak dapat reset password user aktif sendiri. Gunakan Profile page.";
                    $messageType = 'warning';
                    break;
                }
                
                // Check permission
                if (!hasPermission('manage_users')) {
                    $message = "Anda tidak memiliki izin untuk reset password user";
                    $messageType = 'danger';
                    break;
                }
                
                try {
                    // Get user data
                    $stmt = $pdo->prepare("SELECT username, email FROM administrators WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        $message = "User tidak ditemukan";
                        $messageType = 'danger';
                        break;
                    }
                    
                    // Generate random secure password (12 characters)
                    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$!%*?&';
                    $newPassword = '';
                    for ($i = 0; $i < 12; $i++) {
                        $newPassword .= $chars[random_int(0, strlen($chars) - 1)];
                    }
                    
                    // Ensure password meets requirements
                    if (!preg_match('/[A-Z]/', $newPassword)) $newPassword .= 'A';
                    if (!preg_match('/[a-z]/', $newPassword)) $newPassword .= 'a';
                    if (!preg_match('/[0-9]/', $newPassword)) $newPassword .= '1';
                    if (!preg_match('/[@#$!%*?&]/', $newPassword)) $newPassword .= '@';
                    
                    // Hash and update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE administrators SET password_hash = ?, failed_login_attempts = 0, login_attempts = 0 WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    // Store temporary password in session for display
                    $_SESSION['temp_password'] = $newPassword;
                    $_SESSION['temp_password_user'] = $user['username'];
                    
                    // TODO: Send email notification (optional)
                    // sendPasswordResetEmail($user['email'], $user['username'], $newPassword);
                    
                    $message = "Password user berhasil direset! Password baru telah digenerate.";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error resetting password: " . $e->getMessage();
                    $messageType = 'danger';
                }
                break;
                
            case 'delete_user':
                $userId = (int)$_POST['user_id'];
                
                if ($userId !== $_SESSION['admin_id'] && hasPermission('delete_users')) {
                    try {
                        // Get username before deletion for logging
                        $stmt = $pdo->prepare("SELECT username, role FROM administrators WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $stmt = $pdo->prepare("DELETE FROM administrators WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $message = "User berhasil dihapus!";
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = "Error deleting user: " . $e->getMessage();
                        $messageType = 'danger';
                    }
                } else {
                    $message = "Tidak dapat menghapus user aktif atau izin tidak mencukupi";
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get all users
$users = $roleManager->getUsersByRole();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('users'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>User Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="fas fa-plus me-1"></i>Create New User
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['temp_password'])): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <h5 class="alert-heading"><i class="fas fa-key me-2"></i>Password Baru untuk <?= htmlspecialchars($_SESSION['temp_password_user']) ?></h5>
                    <hr>
                    <p class="mb-2"><strong>Password Baru:</strong></p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control bg-light" id="tempPassword" value="<?= htmlspecialchars($_SESSION['temp_password']) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()">
                            <i class="fas fa-copy me-1"></i>Copy
                        </button>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Simpan password ini dengan aman. Password tidak akan ditampilkan lagi setelah Anda menutup alert ini.
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="<?php unset($_SESSION['temp_password'], $_SESSION['temp_password_user']); ?>"></button>
                </div>
                <?php unset($_SESSION['temp_password'], $_SESSION['temp_password_user']); endif; ?>

                <!-- Users Table -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>System Users
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" id="searchUsers" class="form-control form-control-sm" placeholder="🔍 Search by username or email...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterRole" class="form-select form-select-sm">
                                            <option value="">All Roles</option>
                                            <?php foreach ($roleManager->getAllRoles() as $roleKey => $roleName): ?>
                                            <option value="<?= $roleKey ?>"><?= $roleName ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="filterStatus" class="form-select form-select-sm">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr class="<?= $user['status'] !== 'active' ? 'table-secondary' : '' ?>">
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                            <span class="badge bg-info ms-1">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'superadmin' ? 'danger' : ($user['role'] === 'admin' ? 'primary' : ($user['role'] === 'moderator' ? 'warning' : 'info')) ?>">
                                                <?= $roleManager->getRoleDisplayName($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'suspended' => 'danger'
                                            ];
                                            $statusColor = $statusColors[$user['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusColor ?>">
                                                <i class="fas fa-circle me-1" style="font-size: 6px;"></i><?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($user['id'] !== $_SESSION['admin_id']): ?>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($roleManager->canManageRole($user['role'])): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        onclick="editUser(<?= $user['id'] ?>, '<?= $user['role'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- Status Toggle Button -->
                                                <button type="button" 
                                                        class="btn btn-outline-<?= $user['status'] === 'active' ? 'warning' : 'success' ?> btn-sm" 
                                                        onclick="toggleStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>', '<?= htmlspecialchars($user['username']) ?>')"
                                                        title="<?= $user['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?> User">
                                                    <i class="fas fa-<?= $user['status'] === 'active' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                </button>
                                                
                                                <!-- Reset Password Button -->
                                                <button type="button" 
                                                        class="btn btn-outline-info btn-sm" 
                                                        onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                                        title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if (hasPermission('delete_users') && $user['role'] !== 'superadmin' && canManageRole($user['role'])): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Role Statistics -->
                <div class="row mt-4">
                    <?php
                    $roleStats = [];
                    foreach ($users as $user) {
                        $role = $user['role'];
                        $roleStats[$role] = ($roleStats[$role] ?? 0) + 1;
                    }
                    ?>
                    
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Super Admins</h6>
                                        <h3><?= $roleStats['superadmin'] ?? 0 ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-shield fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Administrators</h6>
                                        <h3><?= $roleStats['admin'] ?? 0 ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-cog fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Moderators</h6>
                                        <h3><?= $roleStats['moderator'] ?? 0 ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Editors</h6>
                                        <h3><?= $roleStats['editor'] ?? 0 ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-edit fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" id="createUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Buat User Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required minlength="3">
                            <small class="text-muted">Minimal 3 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="userEmail" class="form-control" required>
                            <div id="emailFeedback" class="invalid-feedback">Format email tidak valid</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="userPassword" class="form-control" required minlength="8">
                            <div class="mt-2">
                                <small class="text-muted d-block">Password harus memenuhi:</small>
                                <small id="pwdLength" class="text-muted"><i class="fas fa-circle" style="font-size: 8px;"></i> Minimal 8 karakter</small><br>
                                <small id="pwdUpper" class="text-muted"><i class="fas fa-circle" style="font-size: 8px;"></i> 1 huruf besar (A-Z)</small><br>
                                <small id="pwdNumber" class="text-muted"><i class="fas fa-circle" style="font-size: 8px;"></i> 1 angka (0-9)</small><br>
                                <small id="pwdSpecial" class="text-muted"><i class="fas fa-circle" style="font-size: 8px;"></i> 1 karakter spesial (@$!%*?&#)</small>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <?php foreach ($roleManager->getAllRoles() as $roleKey => $roleName): ?>
                                    <?php if ($roleManager->canManageRole($roleKey)): ?>
                                    <option value="<?= $roleKey ?>"><?= $roleName ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitCreateUser">Buat User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" id="editUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="editUsername" class="form-control" required minlength="3">
                            <small class="text-muted">Minimal 3 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="editUserEmail" class="form-control" required>
                            <div id="editEmailFeedback" class="invalid-feedback">Format email tidak valid</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="editUserRole" class="form-select" required>
                                <?php foreach ($roleManager->getAllRoles() as $roleKey => $roleName): ?>
                                    <?php if ($roleManager->canManageRole($roleKey)): ?>
                                    <option value="<?= $roleKey ?>"><?= $roleName ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>Password tidak diubah. Gunakan fungsi Reset Password untuk mengubah password user.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal - UNUSED, kept for backwards compatibility -->
    <!-- This modal is not currently used. The editUserModal handles role changes. -->
    <!--
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Role User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" id="roleUserId">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Role Baru</label>
                            <select name="role" id="roleUserRole" class="form-select" required>
                                <?php foreach ($roleManager->getAllRoles() as $roleKey => $roleName): ?>
                                    <?php if ($roleManager->canManageRole($roleKey)): ?>
                                    <option value="<?= $roleKey ?>"><?= $roleName ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    -->

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Edit user profile - fetch data and show modal
    function editUser(userId, currentRole) {
        // Fetch user data via hidden data attributes or AJAX
        // For now, we'll get data from table row
        const row = event.target.closest('tr');
        const username = row.cells[1].querySelector('strong').textContent;
        const email = row.cells[2].textContent;
        
        // Populate edit modal
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUsername').value = username;
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserRole').value = currentRole;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
    
    // Delete user with CSRF token
    function deleteUser(userId, username) {
        if (confirm(`Apakah Anda yakin ingin menghapus user "${username}"? Tindakan ini tidak dapat dibatalkan.`)) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Toggle user status (active/inactive)
    function toggleStatus(userId, currentStatus, username) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'mengaktifkan' : 'menonaktifkan';
        
        if (confirm(`Apakah Anda yakin ingin ${action} user "${username}"?`)) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="status" value="${newStatus}">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Reset user password
    function resetPassword(userId, username) {
        if (confirm(`Apakah Anda yakin ingin reset password untuk user "${username}"?\n\nPassword baru akan digenerate secara otomatis.`)) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Copy password to clipboard
    function copyPassword() {
        const passwordInput = document.getElementById('tempPassword');
        passwordInput.select();
        document.execCommand('copy');
        
        // Show feedback
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    }
    
    // Search and Filter Users
    const searchInput = document.getElementById('searchUsers');
    const filterRole = document.getElementById('filterRole');
    const filterStatus = document.getElementById('filterStatus');
    const usersTable = document.getElementById('usersTable');
    
    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const roleFilter = filterRole.value.toLowerCase();
        const statusFilter = filterStatus.value.toLowerCase();
        const rows = usersTable.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const username = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.cells[3].textContent.toLowerCase();
            const status = row.cells[4].textContent.toLowerCase();
            
            const matchSearch = username.includes(searchTerm) || email.includes(searchTerm);
            const matchRole = !roleFilter || role.includes(roleFilter);
            const matchStatus = !statusFilter || status.includes(statusFilter);
            
            if (matchSearch && matchRole && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show "no results" message if needed
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.remove();
        }
        
        if (visibleCount === 0) {
            const tbody = usersTable.querySelector('tbody');
            const tr = document.createElement('tr');
            tr.id = 'noResultsRow';
            tr.innerHTML = '<td colspan="8" class="text-center text-muted py-4"><i class="fas fa-search me-2"></i>Tidak ada user yang sesuai dengan filter</td>';
            tbody.appendChild(tr);
        }
    }
    
    if (searchInput) {
        searchInput.addEventListener('keyup', filterUsers);
        filterRole.addEventListener('change', filterUsers);
        filterStatus.addEventListener('change', filterUsers);
    }
    
    // Real-time password strength validation
    const passwordInput = document.getElementById('userPassword');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            const lengthEl = document.getElementById('pwdLength');
            if (password.length >= 8) {
                lengthEl.innerHTML = '<i class="fas fa-check-circle text-success"></i> Minimal 8 karakter';
                strength += 25;
            } else {
                lengthEl.innerHTML = '<i class="fas fa-circle text-muted" style="font-size: 8px;"></i> Minimal 8 karakter';
            }
            
            // Check uppercase
            const upperEl = document.getElementById('pwdUpper');
            if (/[A-Z]/.test(password)) {
                upperEl.innerHTML = '<i class="fas fa-check-circle text-success"></i> 1 huruf besar (A-Z)';
                strength += 25;
            } else {
                upperEl.innerHTML = '<i class="fas fa-circle text-muted" style="font-size: 8px;"></i> 1 huruf besar (A-Z)';
            }
            
            // Check number
            const numberEl = document.getElementById('pwdNumber');
            if (/\d/.test(password)) {
                numberEl.innerHTML = '<i class="fas fa-check-circle text-success"></i> 1 angka (0-9)';
                strength += 25;
            } else {
                numberEl.innerHTML = '<i class="fas fa-circle text-muted" style="font-size: 8px;"></i> 1 angka (0-9)';
            }
            
            // Check special character
            const specialEl = document.getElementById('pwdSpecial');
            if (/[@$!%*?&#]/.test(password)) {
                specialEl.innerHTML = '<i class="fas fa-check-circle text-success"></i> 1 karakter spesial (@$!%*?&#)';
                strength += 25;
            } else {
                specialEl.innerHTML = '<i class="fas fa-circle text-muted" style="font-size: 8px;"></i> 1 karakter spesial (@$!%*?&#)';
            }
            
            // Update progress bar
            const progressBar = document.getElementById('passwordStrength');
            progressBar.style.width = strength + '%';
            progressBar.className = 'progress-bar';
            
            if (strength === 0) {
                progressBar.classList.add('bg-secondary');
            } else if (strength <= 25) {
                progressBar.classList.add('bg-danger');
            } else if (strength <= 50) {
                progressBar.classList.add('bg-warning');
            } else if (strength <= 75) {
                progressBar.classList.add('bg-info');
            } else {
                progressBar.classList.add('bg-success');
            }
            
            // Enable/disable submit button
            const submitBtn = document.getElementById('submitCreateUser');
            if (strength === 100) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        });
    }
    
    // Email validation
    const emailInput = document.getElementById('userEmail');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(email)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
    </script>
</body>
</html>
