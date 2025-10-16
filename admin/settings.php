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

// Check permission - Only SuperAdmin and Admin can access settings
$roleManager = getRoleManager();
$roleManager->requirePermission('manage_settings', 'dashboard.php');

// Handle settings update
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_settings') {
        try {
            $settings = [
                'site_title' => trim($_POST['site_title']),
                'site_description' => trim($_POST['site_description']),
                'site_keywords' => trim($_POST['site_keywords']),
                'admin_email' => trim($_POST['admin_email']),
                'posts_per_page' => (int)$_POST['posts_per_page'],
                'enable_comments' => isset($_POST['enable_comments']) ? 1 : 0,
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                'maintenance_message' => trim($_POST['maintenance_message'] ?? ''),
                'maintenance_end_time' => trim($_POST['maintenance_end_time'] ?? ''),
                'google_analytics' => trim($_POST['google_analytics']),
                'facebook_url' => trim($_POST['facebook_url']),
                'twitter_url' => trim($_POST['twitter_url']),
                'youtube_url' => trim($_POST['youtube_url']),
                'telegram_url' => trim($_POST['telegram_url'] ?? '')
            ];
            
            foreach ($settings as $key => $value) {
                // Check if setting exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                
                if ($stmt->fetchColumn() > 0) {
                    // Update existing setting
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    // Insert new setting
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $value]);
                }
            }
            
            // Success message
            if (isset($_POST['maintenance_mode'])) {
                $success_msg = "✅ Settings saved! <strong>⚠️ Maintenance mode is ENABLED.</strong> Visitors will be redirected to maintenance page.";
            } else {
                $success_msg = "✅ Settings saved successfully!";
            }
        } catch (Exception $e) {
            $error_msg = "❌ Failed to save settings: " . $e->getMessage();
        }
    }
    
    // Handle logo upload
    if (isset($_POST['action']) && $_POST['action'] == 'upload_logo') {
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $filename = $_FILES['site_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['site_logo']['size'] <= 2097152) { // 2MB
                    $uploadDir = dirname(__DIR__) . '/uploads/';
                    $newFilename = 'logo_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploadPath)) {
                        // Delete old logo
                        $oldLogo = $settings_data['site_logo'] ?? '';
                        if ($oldLogo && file_exists($uploadDir . basename($oldLogo))) {
                            unlink($uploadDir . basename($oldLogo));
                        }
                        
                        // Save to database
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                        $stmt->execute(['site_logo', $newFilename, $newFilename]);
                        
                        $success_msg = "Logo uploaded successfully!";
                    } else {
                        $error_msg = "Failed to upload logo file.";
                    }
                } else {
                    $error_msg = "Logo file too large (max 2MB).";
                }
            } else {
                $error_msg = "Invalid logo file type. Allowed: JPG, PNG, GIF, SVG.";
            }
        }
    }
    
    // Handle favicon upload
    if (isset($_POST['action']) && $_POST['action'] == 'upload_favicon') {
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['ico', 'png'];
            $filename = $_FILES['site_favicon']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['site_favicon']['size'] <= 102400) { // 100KB
                    $uploadDir = dirname(__DIR__) . '/uploads/';
                    $newFilename = 'favicon_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $uploadPath)) {
                        // Delete old favicon
                        $oldFavicon = $settings_data['site_favicon'] ?? '';
                        if ($oldFavicon && file_exists($uploadDir . basename($oldFavicon))) {
                            unlink($uploadDir . basename($oldFavicon));
                        }
                        
                        // Save to database
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                        $stmt->execute(['site_favicon', $newFilename, $newFilename]);
                        
                        $success_msg = "Favicon uploaded successfully!";
                    } else {
                        $error_msg = "Failed to upload favicon file.";
                    }
                } else {
                    $error_msg = "Favicon file too large (max 100KB).";
                }
            } else {
                $error_msg = "Invalid favicon file type. Allowed: ICO, PNG.";
            }
        }
    }
    
    // Handle delete logo
    if (isset($_POST['action']) && $_POST['action'] == 'delete_logo') {
        $oldLogo = $settings_data['site_logo'] ?? '';
        if ($oldLogo) {
            $uploadDir = dirname(__DIR__) . '/uploads/';
            if (file_exists($uploadDir . basename($oldLogo))) {
                unlink($uploadDir . basename($oldLogo));
            }
            $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = 'site_logo'");
            $stmt->execute();
            $success_msg = "Logo deleted successfully!";
        }
    }
    
    // Handle delete favicon
    if (isset($_POST['action']) && $_POST['action'] == 'delete_favicon') {
        $oldFavicon = $settings_data['site_favicon'] ?? '';
        if ($oldFavicon) {
            $uploadDir = dirname(__DIR__) . '/uploads/';
            if (file_exists($uploadDir . basename($oldFavicon))) {
                unlink($uploadDir . basename($oldFavicon));
            }
            $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = 'site_favicon'");
            $stmt->execute();
            $success_msg = "Favicon deleted successfully!";
        }
    }
    
    // Reload settings after any change
    if (isset($success_msg) || isset($error_msg)) {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

// Get current settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
$stmt->execute();
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Default values (database values override defaults)
$default_settings = [
    'site_title' => 'DONAN22',
    'site_description' => 'Download Software Gratis Full Version',
    'site_keywords' => 'download, software, gratis, blog',
    'admin_email' => 'admin@donan22.com',
    'posts_per_page' => 12,
    'enable_comments' => 1,
    'maintenance_mode' => 0,
    'maintenance_message' => 'Kami sedang melakukan pemeliharaan sistem. Website akan kembali normal dalam waktu singkat.',
    'maintenance_end_time' => '',
    'google_analytics' => '',
    'facebook_url' => '',
    'twitter_url' => '',
    'youtube_url' => '',
    'telegram_url' => ''
];

// Merge with database values (database values take priority)
$current_settings = array_merge($default_settings, $settings_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <style>
        .maintenance-warning {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
        }
        .maintenance-active {
            animation: pulse-warning 2s ease-in-out infinite;
        }
        @keyframes pulse-warning {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('settings'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-cog me-2"></i>Website Settings
                    </h1>
                </div>

                <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($current_settings['maintenance_mode']): ?>
                <div class="alert alert-warning maintenance-warning maintenance-active">
                    <h5 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Maintenance Mode Active!
                    </h5>
                    <p class="mb-2">
                        <strong>Public visitors cannot access the website.</strong> They will see a maintenance page instead.
                    </p>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="../maintenance.php" target="_blank" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-external-link-alt me-1"></i>Preview Maintenance Page
                        </a>
                        <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-home me-1"></i>Test Public Access
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Logo & Favicon Upload Section (Separate from main settings form) -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-image me-2"></i>Logo & Favicon Upload
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Site Logo -->
                                    <div class="col-md-6">
                                        <h6 class="mb-3"><i class="fas fa-image text-primary me-2"></i>Site Logo</h6>
                                        <?php
                                        $currentLogo = $settings_data['site_logo'] ?? '';
                                        $logoPath = $currentLogo ? '../uploads/' . $currentLogo : '';
                                        ?>
                                        
                                        <?php if ($currentLogo && file_exists(dirname(__DIR__) . '/uploads/' . $currentLogo)): ?>
                                        <div class="mb-3">
                                            <div class="border rounded p-3 text-center bg-light">
                                                <img src="<?= $logoPath ?>" alt="Current Logo" style="max-height: 100px; max-width: 100%;">
                                            </div>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="delete_logo">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete current logo?')">
                                                    <i class="fas fa-trash me-1"></i>Delete Logo
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="upload_logo">
                                            <div class="mb-3">
                                                <input type="file" name="site_logo" class="form-control" accept="image/*" required>
                                                <small class="text-muted">Allowed: JPG, PNG, GIF, SVG | Max: 2MB</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload me-1"></i>Upload Logo
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Site Favicon -->
                                    <div class="col-md-6">
                                        <h6 class="mb-3"><i class="fas fa-star text-warning me-2"></i>Site Favicon</h6>
                                        <?php
                                        $currentFavicon = $settings_data['site_favicon'] ?? '';
                                        $faviconPath = $currentFavicon ? '../uploads/' . $currentFavicon : '';
                                        ?>
                                        
                                        <?php if ($currentFavicon && file_exists(dirname(__DIR__) . '/uploads/' . $currentFavicon)): ?>
                                        <div class="mb-3">
                                            <div class="border rounded p-3 text-center bg-light">
                                                <img src="<?= $faviconPath ?>" alt="Current Favicon" style="max-height: 64px; max-width: 100%;">
                                            </div>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="delete_favicon">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete current favicon?')">
                                                    <i class="fas fa-trash me-1"></i>Delete Favicon
                                                </button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="upload_favicon">
                                            <div class="mb-3">
                                                <input type="file" name="site_favicon" class="form-control" accept=".ico,.png" required>
                                                <small class="text-muted">Allowed: ICO, PNG | Max: 100KB | Recommended: 32x32px</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload me-1"></i>Upload Favicon
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Settings Form -->
                <form method="POST" id="settingsForm">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="row">
                        <!-- General Settings -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-globe me-2"></i>General Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Site Title</label>
                                        <input type="text" name="site_title" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['site_title']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Site Description</label>
                                        <textarea name="site_description" class="form-control" rows="3" required><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Keywords (SEO)</label>
                                        <input type="text" name="site_keywords" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['site_keywords']); ?>"
                                               placeholder="keyword1, keyword2, keyword3">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Admin Email</label>
                                        <input type="email" name="admin_email" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Posts Per Page</label>
                                        <input type="number" name="posts_per_page" class="form-control" min="1" max="50"
                                               value="<?php echo htmlspecialchars($current_settings['posts_per_page']); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features & Social -->
                        <div class="col-md-6">
                            <div class="card mb-4 <?php echo $current_settings['maintenance_mode'] ? 'border-warning' : ''; ?>">
                                <div class="card-header <?php echo $current_settings['maintenance_mode'] ? 'bg-warning text-dark' : ''; ?>">
                                    <h5 class="mb-0">
                                        <i class="fas fa-toggle-on me-2"></i>Features & Maintenance
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="enable_comments" 
                                               <?php echo $current_settings['enable_comments'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Enable Comments</label>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="card border-warning mb-0">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode"
                                                       data-initial-state="<?php echo $current_settings['maintenance_mode'] ? '1' : '0'; ?>"
                                                       <?php echo $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenanceMode">
                                                    <strong>Maintenance Mode</strong>
                                                    <?php if ($current_settings['maintenance_mode']): ?>
                                                    <span class="badge bg-warning text-dark ms-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>ACTIVE
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="badge bg-success text-white ms-2">
                                                        <i class="fas fa-check me-1"></i>DISABLED
                                                    </span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            
                                            <div class="alert alert-info mb-3">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>What happens when enabled:</strong><br>
                                                    • All public pages redirect to maintenance page<br>
                                                    • Admin users can still access the site<br>
                                                    • Good for updates or troubleshooting
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Custom Message</strong></label>
                                                <textarea name="maintenance_message" class="form-control" rows="3" 
                                                          placeholder="Pesan yang akan ditampilkan di halaman maintenance..."><?php echo htmlspecialchars($current_settings['maintenance_message'] ?? 'Kami sedang melakukan pemeliharaan sistem. Website akan kembali normal dalam waktu singkat.'); ?></textarea>
                                                <small class="text-muted">Pesan ini akan ditampilkan kepada pengunjung di halaman maintenance.</small>
                                            </div>
                                            
                                            <div class="mb-0">
                                                <label class="form-label"><strong>Estimasi Selesai (Optional)</strong></label>
                                                <input type="datetime-local" name="maintenance_end_time" class="form-control" 
                                                       value="<?php echo htmlspecialchars($current_settings['maintenance_end_time'] ?? ''); ?>">
                                                <small class="text-muted">Jika diisi, akan menampilkan countdown timer di halaman maintenance.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-share-alt me-2"></i>Social Media
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-facebook text-primary me-2"></i>Facebook URL
                                        </label>
                                        <input type="url" name="facebook_url" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['facebook_url']); ?>"
                                               placeholder="https://facebook.com/donan22">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-twitter text-info me-2"></i>Twitter URL
                                        </label>
                                        <input type="url" name="twitter_url" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['twitter_url']); ?>"
                                               placeholder="https://twitter.com/donan22">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-youtube text-danger me-2"></i>YouTube URL
                                        </label>
                                        <input type="url" name="youtube_url" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['youtube_url']); ?>"
                                               placeholder="https://youtube.com/@donan22">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-telegram text-primary me-2"></i>Telegram URL
                                        </label>
                                        <input type="url" name="telegram_url" class="form-control" 
                                               value="<?php echo htmlspecialchars($current_settings['telegram_url']); ?>"
                                               placeholder="https://t.me/donan22">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>Analytics & Tracking
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Google Analytics Code</label>
                                <textarea name="google_analytics" class="form-control" rows="6" 
                                          placeholder="Paste your Google Analytics tracking code here..."><?php echo htmlspecialchars($current_settings['google_analytics']); ?></textarea>
                                <small class="form-text text-muted">This code will be inserted in the &lt;head&gt; section of all pages</small>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Save All Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg px-4 ms-2" onclick="location.reload()">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Important Info -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-info-circle me-2"></i>Email Notification Policy</h5>
                    <ul class="mb-0">
                        <li><strong>No email notifications will be sent.</strong> All comments and user activities will only appear in the admin panel.</li>
                        <li><strong>SMTP is not configured.</strong> The system does not send any emails.</li>
                        <li><strong>Admin Email</strong> is used for display purposes only, not for sending emails.</li>
                        <li>To view new comments, check the <a href="comments.php" class="alert-link">Comments section</a> in admin panel.</li>
                    </ul>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-2x mb-3"></i>
                                <h6>Database Status</h6>
                                <span class="badge bg-light text-dark">Connected</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <i class="fas fa-server fa-2x mb-3"></i>
                                <h6>Server Status</h6>
                                <span class="badge bg-light text-dark">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <i class="fas fa-php fa-2x mb-3"></i>
                                <h6>PHP Version</h6>
                                <span class="badge bg-light text-dark"><?php echo PHP_VERSION; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-<?php echo is_writable('../uploads') ? 'success' : 'danger'; ?>">
                            <div class="card-body text-center">
                                <i class="fas fa-folder fa-2x mb-3"></i>
                                <h6>Uploads Dir</h6>
                                <span class="badge bg-light text-dark">
                                    <?php echo is_writable('../uploads') ? 'Writable' : 'Not Writable'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('settingsForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            console.log('Form found:', form);
            console.log('Submit button found:', submitBtn);
            console.log('Form method:', form.method);
            console.log('Form action:', form.action || 'same page');
            
            // Check for any event listeners blocking submission
            form.addEventListener('submit', function(e) {
                console.log('🚀 FORM SUBMIT EVENT TRIGGERED!');
                console.log('Event:', e);
                console.log('Default prevented?', e.defaultPrevented);
                
                // Show visual feedback
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                
                // Don't prevent default - let form submit normally
            }, false);
            
            // Monitor button click
            submitBtn.addEventListener('click', function(e) {
                console.log('✅ SUBMIT BUTTON CLICKED!');
                console.log('Button type:', this.type);
                console.log('Form valid?', form.checkValidity());
            });
        });
        
        // Monitor page unload (form submission causes page reload)
        window.addEventListener('beforeunload', function(e) {
            console.log('⚠️ PAGE UNLOADING (form may be submitting)');
        });
    </script>
</body>
</html>
