<?php
require_once '../config_modern.php';
requireLogin();

header('Content-Type: application/json');

/**
 * Generate SEO-friendly alt text from filename
 */
function generateAltText($filename) {
    // Remove extension
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Replace separators with spaces
    $name = str_replace(['-', '_', '.'], ' ', $name);
    
    // Remove numbers and special chars
    $name = preg_replace('/[0-9]+/', '', $name);
    $name = preg_replace('/[^a-zA-Z\s]/', '', $name);
    
    // Capitalize words
    $name = ucwords(trim($name));
    
    return $name ?: 'Image';
}

try {
    if (!isset($_FILES['upload'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['upload'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    // SECURITY: Validate file type with multiple checks
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Check 1: Validate MIME type from upload
    if (!in_array($file['type'], $allowedMimeTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
    }
    
    // Check 2: Validate with finfo (real MIME type detection)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($realMimeType, $allowedMimeTypes)) {
        throw new Exception('Invalid file type detected. Only images are allowed.');
    }
    
    // Check 3: Validate image using getimagesize (prevents non-image files)
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('File is not a valid image.');
    }
    
    // Check 4: Validate file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Invalid file extension.');
    }
    
    // Generate secure unique filename (remove original filename completely)
    $filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $extension;
    
    // Create upload directory
    $uploadDir = '../uploads/images/' . date('Y/m/');
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $filename;
    $fileUrl = SITE_URL . '/uploads/images/' . date('Y/m/') . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // SECURITY: Create .htaccess in upload directory to prevent PHP execution
    $htaccessPath = $uploadDir . '.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "php_flag engine off\n<FilesMatch \"\\.php$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";
        @file_put_contents($htaccessPath, $htaccessContent);
    }
    
    // Get image dimensions (already validated above)
    $width = $imageInfo[0] ?? null;
    $height = $imageInfo[1] ?? null;
    
    // SEO: Auto-compress image if > 200KB
    $optimizedSize = $file['size'];
    if ($file['size'] > 200 * 1024) { // > 200KB
        try {
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $img = imagecreatefromjpeg($filePath);
                imagejpeg($img, $filePath, 85); // 85% quality
                imagedestroy($img);
                $optimizedSize = filesize($filePath);
            } elseif ($extension === 'png') {
                $img = imagecreatefrompng($filePath);
                imagepng($img, $filePath, 8); // Compression level 8
                imagedestroy($img);
                $optimizedSize = filesize($filePath);
            }
        } catch (Exception $e) {
            // Continue if compression fails
            error_log('Image compression failed: ' . $e->getMessage());
        }
    }
    
    // SEO: Generate descriptive alt text from filename
    $altText = generateAltText($file['name']);
    
    // Save to media library
    try {
        $stmt = $pdo->prepare("
            INSERT INTO media (filename, original_name, mime_type, file_size, file_path, url, 
                             width, height, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $filename,
            $file['name'],
            $file['type'],
            $file['size'],
            $filePath,
            $fileUrl,
            $width,
            $height,
            $_SESSION['admin_id']
        ]);
    } catch (PDOException $e) {
        // Continue even if database save fails
        error_log('Failed to save media to database: ' . $e->getMessage());
    }
    
    // Return success response for CKEditor with SEO attributes
    echo json_encode([
        'url' => $fileUrl,
        'uploaded' => 1,
        'fileName' => $filename,
        'width' => $width,
        'height' => $height,
        'alt' => $altText,
        'title' => $altText,
        'loading' => 'lazy',
        'originalSize' => $file['size'],
        'optimizedSize' => $optimizedSize,
        'compressionRatio' => round((1 - $optimizedSize / $file['size']) * 100, 1) . '%'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'uploaded' => 0,
        'error' => [
            'message' => $e->getMessage()
        ]
    ]);
}
?>