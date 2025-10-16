<?php
/**
 * DONAN22 - Monetization Settings
 * Admin panel untuk konfigurasi layanan shortlink monetizer
 */

define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
requireLogin();

$pageTitle = 'Monetization Settings';
$currentPage = 'monetization';

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_monetizer') {
            $id = (int)$_POST['service_id'];
            $api_key = trim($_POST['api_key']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $priority = (int)$_POST['priority'];
            $cpm_rate = (float)$_POST['cpm_rate'];
            
            $stmt = $pdo->prepare("
                UPDATE monetizer_config 
                SET api_key = ?, is_active = ?, priority = ?, cpm_rate = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$api_key, $is_active, $priority, $cpm_rate, $id]);
            
            $message = "Monetizer settings updated successfully!";
            $messageType = "success";
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get monetizer services
$stmt = $pdo->query("SELECT * FROM monetizer_config ORDER BY priority DESC");
$monetizers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-dollar-sign"></i> Monetization Settings
                    </h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#shortlink">
                                <i class="fas fa-link"></i> Shortlink Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#help">
                                <i class="fas fa-question-circle"></i> Help
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- SHORTLINK SERVICES TAB -->
                        <div class="tab-pane fade show active" id="shortlink">
                            <h5 class="mb-3">Shortlink Monetizers Configuration</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 150px;">Service</th>
                                            <th>API Key</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 100px;">Priority</th>
                                            <th style="width: 120px;">CPM Rate ($)</th>
                                            <th style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monetizers as $service): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($service['service_name']) ?></strong>
                                                <?php if ($service['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" id="form_<?= $service['id'] ?>">
                                                    <input type="hidden" name="action" value="update_monetizer">
                                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                                    
                                                    <input type="text" 
                                                           name="api_key" 
                                                           class="form-control form-control-sm" 
                                                           value="<?= htmlspecialchars($service['api_key']) ?>"
                                                           placeholder="Enter API Key"
                                                           style="font-family: monospace; font-size: 12px;">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="is_active"
                                                           id="active_<?= $service['id'] ?>"
                                                           <?= $service['is_active'] ? 'checked' : '' ?>
                                                           onchange="document.getElementById('form_<?= $service['id'] ?>').submit()">
                                                    <label class="form-check-label" for="active_<?= $service['id'] ?>">
                                                        <?= $service['is_active'] ? 'Enabled' : 'Disabled' ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="priority" 
                                                       class="form-control form-control-sm" 
                                                       value="<?= $service['priority'] ?>"
                                                       min="0" max="100">
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="cpm_rate" 
                                                       class="form-control form-control-sm" 
                                                       value="<?= $service['cpm_rate'] ?>"
                                                       step="0.01"
                                                       min="0">
                                            </td>
                                            <td>
                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i>
                                <strong>How to get API Keys:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>ShrinkMe.io:</strong> Login → <a href="https://shrinkme.io/member/tools/api" target="_blank">Tools > API</a> → Copy API Key</li>
                                    <li><strong>ouo.io:</strong> Login → <a href="https://ouo.io/api" target="_blank">Dashboard > API</a> → Generate API Key</li>
                                    <li><strong>exe.io:</strong> Login → <a href="https://exe.io/member/tools/api" target="_blank">Tools > API</a> → Get API Key</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- HELP TAB -->
                        <div class="tab-pane fade" id="help">
                            <h5 class="mb-3">Setup Guide & FAQ</h5>
                            
                            <div class="accordion" id="helpAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#help1">
                                            How to setup ShrinkMe.io?
                                        </button>
                                    </h2>
                                    <div id="help1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Sign up at <a href="https://shrinkme.io" target="_blank">ShrinkMe.io</a></li>
                                                <li>Go to Dashboard → Tools → API</li>
                                                <li>Copy your API key</li>
                                                <li>Paste it in the "ShrinkMe.io" row above</li>
                                                <li>Enable the service and click Save</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help2">
                                            How to create monetized download link?
                                        </button>
                                    </h2>
                                    <div id="help2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            <p>Go to <strong>Manage Links</strong> menu and:</p>
                                            <ol>
                                                <li>Click "Create New Link"</li>
                                                <li>Enter original download URL</li>
                                                <li>Select post (optional)</li>
                                                <li>Enter file details (name, size, password)</li>
                                                <li>Click "Generate Monetized Link"</li>
                                                <li>Use the generated link in your posts</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help3">
                                            What is Priority?
                                        </button>
                                    </h2>
                                    <div id="help3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            Priority determines which service is used when multiple services are enabled.
                                            Higher number = higher priority.
                                            <br><br>
                                            Example: If ShrinkMe (priority 10) and ouo.io (priority 5) are both enabled,
                                            ShrinkMe will be used.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help4">
                                            How much can I earn?
                                        </button>
                                    </h2>
                                    <div id="help4" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Estimated earnings with 10,000 clicks/day:</strong></p>
                                            <ul>
                                                <li>ShrinkMe.io (CPM $7): $70/day = $2,100/month</li>
                                            </ul>
                                            <p>
                                                Tambahkan jaringan monetisasi lain untuk meningkatkan potensi pendapatan.
                                            </p>
                                            <p class="mb-0">
                                                <em>Actual earnings depend on traffic quality, geography, and user engagement.</em>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

