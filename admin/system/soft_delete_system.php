<?php
/**
 * DONAN22 Soft Delete System
 * Sistem penghapusan lunak dan restore untuk posts dan files
 */

// Proteksi akses langsung
if (!defined('ADMIN_ACCESS') && !isset($_SESSION)) {
    http_response_code(403);
    die('Access Denied');
}

class SoftDeleteManager {
    private $pdo;
    private $uploadsDir;
    private $trashDir;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->uploadsDir = __DIR__ . '/uploads/';
        $this->trashDir = __DIR__ . '/trash/';
        
        // Buat direktori trash jika belum ada
        if (!is_dir($this->trashDir)) {
            mkdir($this->trashDir, 0755, true);
            // Proteksi direktori trash
            file_put_contents($this->trashDir . '.htaccess', "Order Deny,Allow\nDeny from all");
        }
    }
    
    /**
     * Soft delete post
     */
    public function deletePost($postId, $deleteReason = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Get post data sebelum dihapus
            $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            if (!$post) {
                throw new Exception('Post tidak ditemukan atau sudah dihapus');
            }
            
            // Update posts table dengan soft delete
            $stmt = $this->pdo->prepare("
                UPDATE posts 
                SET deleted_at = NOW(), deleted_by = ?, delete_reason = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['admin_id'] ?? null,
                $deleteReason,
                $postId
            ]);
            
            // Data post sudah di-backup dengan soft delete di tabel posts
            
            // Move file jika ada featured_image
            if (!empty($post['featured_image'])) {
                $this->moveFileToTrash($post['featured_image'], 'image', $postId);
            }
            
            $this->pdo->commit();
            
            // Log aktivitas
            global $security;
            if (isset($security)) {
                $security->logSecurityEvent('soft_delete', "Post deleted: {$post['title']} (ID: $postId)", 'low');
            }
            
            return [
                'success' => true,
                'message' => 'Post berhasil dihapus dan dapat di-restore',
                'post_id' => $postId
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore post dari soft delete
     */
    public function restorePost($postId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get deleted post data from posts table
            $stmt = $this->pdo->prepare("
                SELECT * FROM posts 
                WHERE id = ? AND deleted_at IS NOT NULL
            ");
            $stmt->execute([$postId]);
            $deletedPost = $stmt->fetch();
            
            if (!$deletedPost) {
                throw new Exception('Data post yang dihapus tidak ditemukan');
            }
            
            // Restore post di tabel posts
            $stmt = $this->pdo->prepare("
                UPDATE posts 
                SET deleted_at = NULL, deleted_by = NULL, delete_reason = NULL,
                    restored_at = NOW(), restored_by = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['admin_id'] ?? null,
                $postId
            ]);
            
            // Restore file jika ada
            if (!empty($deletedPost['featured_image'])) {
                $this->restoreFileFromTrash($deletedPost['featured_image'], 'image', $postId);
            }
            
            $this->pdo->commit();
            
            // Log aktivitas
            global $security;
            if (isset($security)) {
                $security->logSecurityEvent('restore', "Post restored: {$deletedPost['title']} (ID: $postId)", 'low');
            }
            
            return [
                'success' => true,
                'message' => 'Post berhasil di-restore'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Permanent delete post
     */
    public function permanentDeletePost($postId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get post data
            $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            if ($post) {
                // Delete file permanently
                if (!empty($post['featured_image'])) {
                    $this->permanentDeleteFile($post['featured_image'], 'image', $postId);
                }
                
                // Delete dari database
                $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$postId]);
            }
            
            // Post sudah dihapus dari tabel posts
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Post berhasil dihapus permanen'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Move file ke trash
     */
    public function moveFileToTrash($filename, $fileType = 'file', $relatedId = null) {
        try {
            $sourcePath = $this->uploadsDir . $filename;
            
            if (!file_exists($sourcePath)) {
                return false;
            }
            
            // Create unique filename in trash
            $trashFilename = date('Y-m-d_H-i-s') . '_' . $filename;
            $trashPath = $this->trashDir . $trashFilename;
            
            // Move file
            if (rename($sourcePath, $trashPath)) {
                // Log to file_trash table
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_trash 
                    (original_filename, trash_filename, file_type, file_path, 
                     related_id, deleted_by, deleted_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $filename,
                    $trashFilename,
                    $fileType,
                    $trashPath,
                    $relatedId,
                    $_SESSION['admin_id'] ?? null
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error moving file to trash: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restore file dari trash
     */
    public function restoreFileFromTrash($originalFilename, $fileType = 'file', $relatedId = null) {
        try {
            // Get trash file info
            $stmt = $this->pdo->prepare("
                SELECT * FROM file_trash 
                WHERE original_filename = ? AND file_type = ? AND related_id = ? AND restored_at IS NULL
                ORDER BY deleted_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$originalFilename, $fileType, $relatedId]);
            $trashFile = $stmt->fetch();
            
            if (!$trashFile) {
                return false;
            }
            
            $trashPath = $trashFile['file_path'];
            $restorePath = $this->uploadsDir . $originalFilename;
            
            // Jika file tujuan sudah ada, buat nama unik
            if (file_exists($restorePath)) {
                $pathInfo = pathinfo($originalFilename);
                $restorePath = $this->uploadsDir . $pathInfo['filename'] . '_restored_' . time() . '.' . $pathInfo['extension'];
            }
            
            if (file_exists($trashPath) && rename($trashPath, $restorePath)) {
                // Update file_trash record
                $stmt = $this->pdo->prepare("
                    UPDATE file_trash 
                    SET restored_at = NOW(), restored_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_SESSION['admin_id'] ?? null,
                    $trashFile['id']
                ]);
                
                return basename($restorePath);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error restoring file from trash: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Permanent delete file
     */
    public function permanentDeleteFile($filename, $fileType = 'file', $relatedId = null) {
        try {
            // Delete from uploads
            $uploadPath = $this->uploadsDir . $filename;
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            
            // Delete from trash
            $stmt = $this->pdo->prepare("
                SELECT file_path FROM file_trash 
                WHERE original_filename = ? AND file_type = ? AND related_id = ?
            ");
            $stmt->execute([$filename, $fileType, $relatedId]);
            
            while ($row = $stmt->fetch()) {
                if (file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }
            
            // Delete records from file_trash
            $stmt = $this->pdo->prepare("
                DELETE FROM file_trash 
                WHERE original_filename = ? AND file_type = ? AND related_id = ?
            ");
            $stmt->execute([$filename, $fileType, $relatedId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error permanently deleting file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get deleted posts
     */
    public function getDeletedPosts($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT p.*, p.delete_reason, p.deleted_at as soft_deleted_at,
                       a1.username as deleted_by_name, a2.username as restored_by_name,
                       c.name as category_name
                FROM posts p
                LEFT JOIN administrators a1 ON p.deleted_by = a1.id
                LEFT JOIN administrators a2 ON p.restored_by = a2.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting deleted posts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trash files
     */
    public function getTrashFiles($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT ft.*, a1.username as deleted_by_name, a2.username as restored_by_name
                FROM file_trash ft
                LEFT JOIN administrators a1 ON ft.deleted_by = a1.id
                LEFT JOIN administrators a2 ON ft.restored_by = a2.id
                WHERE ft.restored_at IS NULL
                ORDER BY ft.deleted_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting trash files: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old deleted items
     */
    public function cleanupOldDeleted($days = 30) {
        try {
            $this->pdo->beginTransaction();
            
            // Get old deleted posts
            $stmt = $this->pdo->prepare("
                SELECT id, featured_image FROM posts 
                WHERE deleted_at IS NOT NULL 
                AND deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $oldPosts = $stmt->fetchAll();
            
            foreach ($oldPosts as $post) {
                $this->permanentDeletePost($post['id']);
            }
            
            // Clean up old trash files
            $stmt = $this->pdo->prepare("
                SELECT * FROM file_trash 
                WHERE deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND restored_at IS NULL
            ");
            $stmt->execute([$days]);
            $oldFiles = $stmt->fetchAll();
            
            foreach ($oldFiles as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            
            // Delete old file_trash records
            $stmt = $this->pdo->prepare("
                DELETE FROM file_trash 
                WHERE deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND restored_at IS NULL
            ");
            $stmt->execute([$days]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'cleaned_posts' => count($oldPosts),
                'cleaned_files' => count($oldFiles)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}

// Initialize soft delete manager
try {
    $softDelete = new SoftDeleteManager($pdo);
} catch (Exception $e) {
    error_log("Soft delete system initialization error: " . $e->getMessage());
}
?>