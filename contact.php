<?php
/**
 * Halaman Contact - Hubungi kami
 */

require_once 'config_modern.php';

// Check maintenance mode
checkMaintenanceMode();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami - Donan22</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <meta name="description" content="Hubungi tim Donan22 untuk pertanyaan, saran, atau bantuan terkait software dan tutorial yang tersedia di website kami.">
    <meta name="keywords" content="kontak, hubungi kami, bantuan, support, feedback, donan22">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Live Search CSS -->
    <link href="<?= SITE_URL ?>/assets/css/live-search.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background: #f8fafc;
            line-height: 1.6;
        }
        
        .main-header {
            background: linear-gradient(135deg, #4f6bebff 0%, #7a4aaaff 100%);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        
        .navbar-brand:hover {
            color: #f0f9ff !important;
            transform: scale(1.02);
            transition: all 0.3s ease;
        }
        
        .nav-link {
            font-weight: 500;
            color: #ffffff !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        
        .navbar-nav .nav-link {
            color: #64748b !important;
            font-weight: 500;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 50px;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M421.9,6.5c22.6-2.5,51.5,0.4,75.3,5.3c23.6,4.9,70.9,23.5,100.5,35.7c75.8,32.2,133.7,44.5,192.6,49.7c23.6,2.1,48.7,3.5,103.4-2.5c54.7-6,106.2-25.6,106.2-25.6V0H0v30.3c0,0,72,32.6,158.4,30.5c39.2-0.7,92.8-6.7,115-11.1C297.6,46.9,347.6,35.1,421.9,6.5z" fill="rgba(255,255,255,0.1)"/></svg>') repeat-x bottom;
            background-size: 100% 50px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .page-header .lead {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .contact-section {
            background: white;
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            margin-bottom: 50px;
        }
        
        .contact-info {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .contact-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: white;
            font-size: 1.5rem;
        }
        
        .contact-details h5 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .contact-details p {
            color: #6b7280;
            margin: 0;
            font-weight: 500;
        }
        
        .form-section h3 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }
        
        .info-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            color: white;
        }
        
        .info-card h5 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .info-card p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .faq-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            margin-top: 40px;
        }
        
        .faq-item {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .faq-question {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .faq-answer {
            color: #6b7280;
            line-height: 1.6;
        }
        
        .response-time {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        
        .response-time h5 {
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .response-time p {
            margin: 0;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 60px 0;
            }
            
            .page-header h1 {
                font-size: 2.2rem;
            }
            
            .contact-section {
                padding: 30px 20px;
            }
            
            .contact-info {
                padding: 30px 20px;
            }
            
            .contact-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .social-links {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .social-link {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <i class="fas fa-rocket me-2"></i>
                    <span>DONAN22</span>
                </a>
            
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto me-4">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/software"><i class="fas fa-download me-1"></i> Software</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/blog"><i class="fas fa-graduation-cap me-1"></i> Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/mobile-apps"><i class="fas fa-mobile-alt me-1"></i> Mobile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/windows-software"><i class="fab fa-windows me-1"></i> Windows</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/category/mac-software"><i class="fab fa-apple me-1"></i> Mac</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php"><i class="fas fa-th-large me-1"></i> Kategori</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Search Bar Below Nav -->
        <div class="container py-2">
            <form action="<?= SITE_URL ?>/search.php" method="GET" id="searchForm" class="live-search-container position-relative" style="max-width: 400px; margin: 0 auto;">
                <input 
                    type="search" 
                    name="q"
                    class="form-control form-control-sm" 
                    id="live-search-input"
                    placeholder="Cari software..." 
                    autocomplete="off"
                    style="padding-right: 35px; border-radius: 20px;"
                >
                <button class="btn btn-sm position-absolute" type="submit" id="searchButton" style="right: 5px; top: 50%; transform: translateY(-50%); border: none; background: transparent;">
                    <i class="fas fa-search text-primary"></i>
                </button>
                
                <!-- Live Search Results Dropdown -->
                <div class="live-search-results" id="live-search-results"></div>
            </div>
        </div>
    </header>
    
    <!-- Page Header -->
    <section class="page-header">
        <div class="container text-center">
            <h1><i class="fas fa-envelope-open me-3"></i>Hubungi Kami</h1>
            <p class="lead">Punya pertanyaan, saran, atau butuh bantuan? Kami siap membantu Anda!</p>
        </div>
    </section>
    
    <div class="container">
        <div class="row">
            <!-- How to Contact Us -->
            <div class="col-lg-12">
                <div class="contact-section">
                    <div class="form-section">
                        <h3><i class="fas fa-comments me-3"></i>Cara Menghubungi Kami</h3>
                        
                        <div class="alert alert-info d-flex align-items-start" role="alert">
                            <i class="fas fa-info-circle me-3 mt-1 fs-4"></i>
                            <div>
                                <h5 class="alert-heading mb-2">Hubungi Kami Melalui Comments</h5>
                                <p class="mb-2">Untuk menghubungi kami, silakan tinggalkan komentar di halaman post yang relevan dengan pertanyaan atau masalah Anda.</p>
                                <hr>
                                <p class="mb-0"><strong>Cara menggunakan:</strong></p>
                                <ol class="mb-0 mt-2">
                                    <li>Kunjungi halaman post yang ingin Anda komentari</li>
                                    <li>Scroll ke bawah hingga menemukan section "Comments"</li>
                                    <li>Tulis pertanyaan, saran, atau masalah Anda</li>
                                    <li>Klik tombol "Post Comment"</li>
                                    <li>Tim kami akan merespons dalam 1-2 hari kerja</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="info-card">
                                    <div class="info-icon bg-primary">
                                        <i class="fas fa-question-circle"></i>
                                    </div>
                                    <h5>Pertanyaan Umum?</h5>
                                    <p>Tinggalkan komentar di post yang relevan atau lihat FAQ di bawah</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-card">
                                    <div class="info-icon bg-warning">
                                        <i class="fas fa-bug"></i>
                                    </div>
                                    <h5>Laporkan Bug?</h5>
                                    <p>Tulis detail bug di comments section dengan tag [BUG]</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-card">
                                    <div class="info-icon bg-success">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <h5>Request Software?</h5>
                                    <p>Gunakan comments dengan tag [REQUEST] untuk request software</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="info-card">
                                    <div class="info-icon bg-danger">
                                        <i class="fas fa-hands-helping"></i>
                                    </div>
                                    <h5>Butuh Bantuan?</h5>
                                    <p>Jelaskan masalah Anda di comments, kami siap membantu</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-lg btn-primary">
                                <i class="fas fa-home me-2"></i>Kembali ke Halaman Utama
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="faq-section">
            <h3 class="text-center mb-4">
                <i class="fas fa-question-circle me-3"></i>Frequently Asked Questions
            </h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-download me-2"></i>Apakah semua software gratis?
                        </div>
                        <div class="faq-answer">
                            Ya, semua software yang kami bagikan adalah gratis untuk digunakan. Kami menyediakan link download dari sumber resmi dan terpercaya.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-shield-alt me-2"></i>Apakah software aman dari virus?
                        </div>
                        <div class="faq-answer">
                            Kami selalu memastikan setiap software telah diuji dan aman dari virus. Namun kami tetap menyarankan untuk menggunakan antivirus terpercaya.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-sync me-2"></i>Seberapa sering konten diupdate?
                        </div>
                        <div class="faq-answer">
                            Kami mengupdate konten secara rutin setiap hari dengan software terbaru dan tutorial menarik.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-key me-2"></i>Bagaimana jika ada password file?
                        </div>
                        <div class="faq-answer">
                            Jika file memiliki password, biasanya password yang digunakan adalah: <strong>donan22.com</strong> atau tertera di halaman download.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-comment me-2"></i>Bisakah request software tertentu?
                        </div>
                        <div class="faq-answer">
                            Tentu! Anda bisa request software melalui form kontak atau kolom komentar. Kami akan berusaha menyediakannya.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-mobile-alt me-2"></i>Ada aplikasi mobile?
                        </div>
                        <div class="faq-answer">
                            Saat ini kami belum memiliki aplikasi mobile, tapi website kami sudah responsive dan bisa diakses dengan mudah di smartphone.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe contact items and FAQ items
            document.querySelectorAll('.contact-item, .faq-item, .info-card').forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(item);
            });
        });
    </script>
    
    <!-- Live Search JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/live-search.js"></script>
</body>
</html>
