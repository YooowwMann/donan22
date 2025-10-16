<?php
/**
 * 404 Error Page - Halaman tidak ditemukan
 */

require_once 'config_modern.php';

// Check maintenance mode
checkMaintenanceMode();

// Set 404 header
http_response_code(404);

$pageTitle = 'Halaman Tidak Ditemukan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - DONAN21</title>
    <meta name="description" content="Halaman yang Anda cari tidak ditemukan. Kembali ke beranda atau cari konten lainnya di DONAN21.">
    <meta name="robots" content="noindex,nofollow">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--light-bg) 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            width: 90%;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon {
            font-size: 6rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .error-title {
            font-size: 3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .error-subtitle {
            font-size: 1.2rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .search-section {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            border-radius: 50px;
            padding: 0.75rem 1.5rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .search-box .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .quick-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .quick-links a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
        }

        .quick-links a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .error-container {
                padding: 2rem;
                margin: 1rem;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        
        <h1 class="error-title">404</h1>
        <p class="error-subtitle">
            Oops! Halaman yang Anda cari tidak ditemukan.<br>
            Mungkin halaman telah dipindahkan atau URL salah.
        </p>

        <div class="search-section">
            <h5 class="mb-3">
                <i class="fas fa-lightbulb me-2 text-warning"></i>
                Coba cari konten yang Anda butuhkan
            </h5>
            <form action="<?= SITE_URL ?>/search" method="GET" class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" class="form-control" 
                       placeholder="Cari software atau blog..." 
                       required>
                <button type="submit" class="btn btn-primary mt-2">
                    <i class="fas fa-search me-2"></i>Cari Sekarang
                </button>
            </form>
        </div>

        <div class="quick-links">
            <a href="index.php">
                <i class="fas fa-home"></i>
                Beranda
            </a>
            <a href="categories.php">
                <i class="fas fa-th-large"></i>
                Kategori
            </a>
            <a href="<?= SITE_URL ?>/search/software">
                <i class="fas fa-download"></i>
                Software
            </a>
            <a href="category.php?slug=blog">
                <i class="fas fa-book"></i>
                Blog
            </a>
        </div>

        <div class="mt-4">
            <p class="text-muted mb-0">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Jika masalah terus terjadi, silakan hubungi admin
                </small>
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto focus on search input -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>
