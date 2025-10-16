<?php
define('ADMIN_ACCESS', true);
require_once '../config_modern.php';
require_once 'includes/navigation.php';
requireLogin();

$pageTitle = 'Reports';
$currentPage = 'reports';

// Initialize variables
$contentReport = [];
$monthlyReport = [];
$performanceReport = [];
$analyticsReport = [];

// Get Content Report
try {
    $stmt = $pdo->query("SELECT 
        c.name as category_name,
        COUNT(p.id) as total_posts,
        COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published_posts,
        COUNT(CASE WHEN p.status = 'draft' THEN 1 END) as draft_posts,
        AVG(LENGTH(p.content)) as avg_content_length
        FROM categories c 
        LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
        GROUP BY c.id, c.name
        ORDER BY total_posts DESC");
    $contentReport = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Content Report Error: " . $e->getMessage());
}

// Get Monthly Report
try {
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as posts_created,
        COUNT(CASE WHEN status = 'published' THEN 1 END) as published,
        COUNT(CASE WHEN status = 'draft' THEN 1 END) as drafts
        FROM posts 
        WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC");
    $monthlyReport = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Monthly Report Error: " . $e->getMessage());
}

// Get Performance Report
try {
    $stmt = $pdo->query("SELECT 
        title,
        status,
        LENGTH(content) as content_length,
        created_at
        FROM posts 
        WHERE deleted_at IS NULL
        ORDER BY LENGTH(content) DESC 
        LIMIT 20");
    $performanceReport = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Performance Report Error: " . $e->getMessage());
}

// Get Analytics Report
try {
    $stmt = $pdo->query("SELECT 
        p.title,
        c.name as category_name,
        COALESCE(p.view_count, 0) as views,
        COALESCE(p.download_count, 0) as downloads,
        p.status,
        p.created_at
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL AND p.status = 'published'
        ORDER BY p.view_count DESC
        LIMIT 50");
    $analyticsReport = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Analytics Report Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - DONAN22 Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">`r`n`r`n    <!--  Responsive Scaling System -->`r`n    <link href="../assets/css/responsive-scale.css" rel="stylesheet">
    <style>
        .filter-input {
            max-width: 300px;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php renderAdminNavigation('reports'); ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-bar me-2"></i>Reports Dashboard
                    </h1>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Total Categories</h6>
                                <h2 class="mb-0"><?= count($contentReport) ?></h2>
                                <small><?= array_sum(array_column($contentReport, 'total_posts')) ?> Total Posts</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Active Months</h6>
                                <h2 class="mb-0"><?= count($monthlyReport) ?></h2>
                                <small>Last 12 months</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Performance Items</h6>
                                <h2 class="mb-0"><?= count($performanceReport) ?></h2>
                                <small>Top content</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Published Posts</h6>
                                <h2 class="mb-0"><?= count($analyticsReport) ?></h2>
                                <small>With analytics</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button">
                            <i class="fas fa-tags me-1"></i> Content Analysis
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button">
                            <i class="fas fa-calendar me-1"></i> Monthly Summary
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button">
                            <i class="fas fa-chart-line me-1"></i> Performance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button">
                            <i class="fas fa-chart-pie me-1"></i> Analytics
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="reportTabsContent">
                    
                    <!-- Content Analysis Tab -->
                    <div class="tab-pane fade show active" id="content" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Content Analysis by Category</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm filter-input" id="filterContent" placeholder="Filter by category...">
                                    <button class="btn btn-primary btn-sm" onclick="exportCSV('content')">
                                        <i class="fas fa-download me-1"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($contentReport)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No category data available.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm" id="contentTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Category Name</th>
                                                    <th class="text-center">Total Posts</th>
                                                    <th class="text-center">Published</th>
                                                    <th class="text-center">Drafts</th>
                                                    <th class="text-center">Avg Length</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($contentReport as $row): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($row['category_name']) ?></strong></td>
                                                        <td class="text-center"><?= $row['total_posts'] ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success"><?= $row['published_posts'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-warning"><?= $row['draft_posts'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <?= $row['avg_content_length'] ? number_format($row['avg_content_length'], 0) : '0' ?> chars
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

                    <!-- Monthly Summary Tab -->
                    <div class="tab-pane fade" id="monthly" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Monthly Summary</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm filter-input" id="filterMonthly" placeholder="Filter by month...">
                                    <button class="btn btn-primary btn-sm" onclick="exportCSV('monthly')">
                                        <i class="fas fa-download me-1"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($monthlyReport)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No monthly data available.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm" id="monthlyTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Month</th>
                                                    <th class="text-center">Posts Created</th>
                                                    <th class="text-center">Published</th>
                                                    <th class="text-center">Drafts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthlyReport as $row): ?>
                                                    <tr>
                                                        <td><strong><?= date('F Y', strtotime($row['month'] . '-01')) ?></strong></td>
                                                        <td class="text-center"><?= $row['posts_created'] ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success"><?= $row['published'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-warning"><?= $row['drafts'] ?></span>
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

                    <!-- Performance Tab -->
                    <div class="tab-pane fade" id="performance" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Content Performance</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm filter-input" id="filterPerformance" placeholder="Filter by title...">
                                    <button class="btn btn-primary btn-sm" onclick="exportCSV('performance')">
                                        <i class="fas fa-download me-1"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($performanceReport)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No performance data available.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm" id="performanceTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Title</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center">Content Length</th>
                                                    <th class="text-center">Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($performanceReport as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars(substr($row['title'], 0, 60)) ?><?= strlen($row['title']) > 60 ? '...' : '' ?></td>
                                                        <td class="text-center">
                                                            <?php if ($row['status'] === 'published'): ?>
                                                                <span class="badge bg-success">Published</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Draft</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center"><?= number_format($row['content_length']) ?></td>
                                                        <td class="text-center"><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-pane fade" id="analytics" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Analytics Report</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm filter-input" id="filterAnalytics" placeholder="Filter by title or category...">
                                    <button class="btn btn-primary btn-sm" onclick="exportCSV('analytics')">
                                        <i class="fas fa-download me-1"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($analyticsReport)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No analytics data available.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm" id="analyticsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th class="text-center">Views</th>
                                                    <th class="text-center">Downloads</th>
                                                    <th class="text-center">Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($analyticsReport as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars(substr($row['title'], 0, 50)) ?><?= strlen($row['title']) > 50 ? '...' : '' ?></td>
                                                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary"><?= number_format($row['views']) ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success"><?= number_format($row['downloads']) ?></span>
                                                        </td>
                                                        <td class="text-center"><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
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

    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functions for each table
        document.getElementById('filterContent')?.addEventListener('keyup', function() {
            filterTable('contentTable', this.value, 0);
        });

        document.getElementById('filterMonthly')?.addEventListener('keyup', function() {
            filterTable('monthlyTable', this.value, 0);
        });

        document.getElementById('filterPerformance')?.addEventListener('keyup', function() {
            filterTable('performanceTable', this.value, 0);
        });

        document.getElementById('filterAnalytics')?.addEventListener('keyup', function() {
            filterTable('analyticsTable', this.value, [0, 1]); // Search in title and category
        });

        function filterTable(tableId, searchTerm, columnIndices) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');
            const term = searchTerm.toLowerCase();

            // If columnIndices is a number, convert to array
            if (typeof columnIndices === 'number') {
                columnIndices = [columnIndices];
            }

            for (let i = 0; i < rows.length; i++) {
                let found = false;
                const cells = rows[i].getElementsByTagName('td');

                for (let colIndex of columnIndices) {
                    if (cells[colIndex] && cells[colIndex].textContent.toLowerCase().includes(term)) {
                        found = true;
                        break;
                    }
                }

                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Export to CSV function
        function exportCSV(type) {
            let tableId, filename;
            
            switch(type) {
                case 'content':
                    tableId = 'contentTable';
                    filename = 'content_analysis.csv';
                    break;
                case 'monthly':
                    tableId = 'monthlyTable';
                    filename = 'monthly_summary.csv';
                    break;
                case 'performance':
                    tableId = 'performanceTable';
                    filename = 'performance_report.csv';
                    break;
                case 'analytics':
                    tableId = 'analyticsTable';
                    filename = 'analytics_report.csv';
                    break;
                default:
                    return;
            }

            const table = document.getElementById(tableId);
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let row of rows) {
                const cols = row.querySelectorAll('td, th');
                const csvRow = [];
                for (let col of cols) {
                    // Remove HTML tags and clean text
                    let text = col.textContent.replace(/,/g, '');
                    csvRow.push(text.trim());
                }
                csv.push(csvRow.join(','));
            }

            // Create download link
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>

