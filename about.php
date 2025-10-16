<?php
require_once 'config_modern.php';

$pageTitle = "About Us - DONAN22";
$pageDescription = "Tentang DONAN22 - Platform Download Software & IT Learning Terpercaya";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --dark-color: #1a202c;
            --light-bg: #f8f9fa;
            --muted-color: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #1a202c;
            line-height: 1.6;
        }
        
        /* Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 8px;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-section p {
            font-size: 1.25rem;
            opacity: 0.95;
        }
        
        /* About Section */
        .about-section {
            padding: 80px 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        .about-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .about-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }
        
        .feature-card h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-card p {
            color: var(--muted-color);
            line-height: 1.6;
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 0;
            color: white;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: #1a202c;
            color: white;
            padding: 40px 0 20px;
            margin-top: 80px;
        }
        
        .footer h5 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 30px;
            padding-top: 20px;
            text-align: center;
            color: rgba(255,255,255,0.6);
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.75rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-download"></i>
                DONAN22
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="category.php?slug=software">
                            <i class="fas fa-laptop me-1"></i> Software
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="category.php?slug=blog">
                            <i class="fas fa-graduation-cap me-1"></i> Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">
                            <i class="fas fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1>Tentang DONAN22</h1>
                <p class="lead">Website yang Menyediakan Software, Aplikasi & Blog IT untuk Membantu Kawan-Kawan</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-title">Siapa Kami?</h2>
                    <p class="lead mt-4">
                        Website DONAN22 menyediakan software, aplikasi, dan blog untuk membantu kawan-kawan dalam 
                        meningkatkan produktivitas, mengembangkan skill digital, dan mempelajari teknologi terkini 
                        secara mudah dan terpercaya.
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="about-card">
                        <h3><i class="fas fa-bullseye me-2"></i> Misi Kami</h3>
                        <p>
                            Menyediakan platform yang membantu kawan-kawan dalam mengakses software, aplikasi, dan 
                            konten edukasi berkualitas untuk meningkatkan produktivitas kerja, mengembangkan skill 
                            digital, dan mempelajari teknologi terbaru dengan mudah dan aman.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="about-card">
                        <h3><i class="fas fa-eye me-2"></i> Visi Kami</h3>
                        <p>
                            Menjadi platform terdepan di Indonesia yang membantu kawan-kawan dalam mengakses 
                            software, aplikasi, dan konten pembelajaran IT berkualitas tinggi, serta menjadi 
                            rujukan utama untuk pengembangan skill digital dan teknologi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Mengapa Memilih DONAN22?</h2>
                <p class="lead mt-4">Keunggulan yang kami tawarkan untuk Anda</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Aman & Terpercaya</h4>
                        <p>Platform yang aman dengan konten berkualitas untuk membantu kawan-kawan dalam belajar dan bekerja dengan teknologi terkini.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h4>Download Cepat</h4>
                        <p>Server berkecepatan tinggi memastikan proses download Anda berjalan lancar dan cepat.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4>Konten Terkini</h4>
                        <p>Koleksi software, aplikasi, dan tutorial kami selalu diperbarui untuk membantu kawan-kawan mengikuti perkembangan teknologi terbaru.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h4>Tutorial Lengkap</h4>
                        <p>Panduan step-by-step untuk membantu Anda menguasai berbagai tools dan teknologi IT.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Komunitas Aktif</h4>
                        <p>Bergabung dengan ribuan pengguna lain untuk berbagi pengalaman dan saling membantu.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4>Support 24/7</h4>
                        <p>Tim support kami siap membantu Anda kapan saja jika mengalami kendala.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number"><i class="fas fa-users"></i> 100K+</span>
                        <span class="stat-label">Pengguna Aktif</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number"><i class="fas fa-download"></i> 500K+</span>
                        <span class="stat-label">Total Download</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number"><i class="fas fa-laptop"></i> 1000+</span>
                        <span class="stat-label">Software Tersedia</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <span class="stat-number"><i class="fas fa-book"></i> 200+</span>
                        <span class="stat-label">Tutorial Premium</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-rocket me-2"></i> DONAN22</h5>
                    <p class="mb-3">Website yang menyediakan software, aplikasi, dan blog untuk membantu kawan-kawan dalam mengembangkan skill digital dan teknologi.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Menu</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="category.php?slug=software">Software</a></li>
                        <li><a href="category.php?slug=blog">Blog</a></li>
                        <li><a href="categories.php">Kategori</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Informasi</h5>
                    <ul class="footer-links">
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="contact.php">Hubungi Kami</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Kontak</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope me-2"></i> info@donan22.com</li>
                        <li><i class="fas fa-phone me-2"></i> +62 812-3456-7890</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Jakarta, Indonesia</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> DONAN22. All rights reserved. Made with <i class="fas fa-heart text-danger"></i> in Indonesia</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

