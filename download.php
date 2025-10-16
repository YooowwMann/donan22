<?php
/**
 * Download Handler - DONAN22
 * Handles file downloads with tracking
 */

require_once 'config_modern.php';
require_once 'includes/tracking.php';
checkMaintenanceMode();

// Get parameters
$postId = (int)($_GET['post'] ?? 0);
$fileId = (int)($_GET['file'] ?? 0);
$token = $_GET['token'] ?? '';

if (!$postId || !$fileId) {
    header('Location: index.php');
    exit;
}

try {
    // Get post and download link information
    $stmt = $pdo->prepare("
        SELECT p.*, dl.file_path, dl.file_name, dl.file_size, dl.download_token,
               c.name as category_name
        FROM posts p
        LEFT JOIN download_links dl ON p.id = dl.post_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND dl.id = ? AND p.status = 'published' AND dl.status = 'active'
    ");
    $stmt->execute([$postId, $fileId]);
    $download = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$download) {
        header('HTTP/1.0 404 Not Found');
        echo "Download not found or no longer available.";
        exit;
    }
    
    // Verify token if required
    if (!empty($download['download_token']) && $download['download_token'] !== $token) {
        header('HTTP/1.0 403 Forbidden');
        echo "Invalid download token.";
        exit;
    }
    
    // SECURITY: Validate and sanitize file path to prevent directory traversal
    $filePath = $download['file_path'];
    
    // Get the real path and check if it's within allowed directory
    $realPath = realpath($filePath);
    $uploadsDir = realpath(__DIR__ . '/uploads');
    
    // Ensure file path doesn't contain directory traversal attempts
    if ($realPath === false || strpos($realPath, $uploadsDir) !== 0) {
        header('HTTP/1.0 403 Forbidden');
        error_log("Path traversal attempt detected: {$filePath}");
        echo "Access denied.";
        exit;
    }
    
    // Check if file exists
    if (!file_exists($realPath)) {
        header('HTTP/1.0 404 Not Found');
        echo "File not found on server.";
        exit;
    }
    
    // Track the download
    trackDownload($postId, $download['file_name'], $realPath);
    
    // Update download count in download_links table if exists
    $stmt = $pdo->prepare("UPDATE download_links SET downloads_count = downloads_count + 1 WHERE id = ?");
    $stmt->execute([$fileId]);
    
    // Get file info
    $fileName = $download['file_name'] ?: basename($realPath);
    $fileSize = $download['file_size'] ?: filesize($realPath);
    $mimeType = 'application/octet-stream';
    
    // Determine MIME type based on file extension
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'dmg' => 'application/x-apple-diskimage',
        'deb' => 'application/x-debian-package',
        'rpm' => 'application/x-rpm',
        'apk' => 'application/vnd.android.package-archive',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (isset($mimeTypes[$extension])) {
        $mimeType = $mimeTypes[$extension];
    }
    
    // Start download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Read and output file
    $handle = fopen($realPath, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        header('HTTP/1.0 500 Internal Server Error');
        echo "Error reading file.";
    }
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo "Download error occurred.";
}
?>
