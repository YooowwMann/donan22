< <?php
require_once 'config_modern.php';

// Check maintenance mode
checkMaintenanceMode();

// SEO Meta Tags
$page_title = "Tentang DONAN - Platform Download Software Terpercaya | DONAN22.COM";
$page_description = "Kenali lebih dekat DONAN22 - Platform download software, aplikasi, game gratis terpercaya di Indonesia. Download di DONAN dengan mudah, aman, dan gratis sejak 2020!";
$page_keywords = "tentang donan, about donan22, platform donan, situs donan, download software donan, donan22.com";
$page_url = SITE_URL . '/about-donan.php';
$page_image = SITE_URL . '/assets/images/og-image.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="author" content="DONAN22">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($page_url) ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= htmlspecialchars($page_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($page_image) ?>">
    <meta property="og:site_name" content="DONAN22">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($page_image) ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.8;
            color: #1f2937;
        }
        .hero-about {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
        }
        .hero-about h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .hero-about .lead {
            font-size: 1.3rem;
            opacity: 0.95;
        }
        .content-section {
            margin-bottom: 50px;
        }
        .content-section h2 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .content-section h3 {
            color: #764ba2;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .feature-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .feature-box:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        .feature-box i {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .feature-box h4 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .stats-box {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-box .number {
            font-size: 3rem;
            font-weight: 800;
            color: #667eea;
            display: block;
        }
        .stats-box .label {
            font-size: 1.1rem;
            color: #6b7280;
            font-weight: 500;
        }
        .cta-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px;
            border-radius: 15px;
            text-align: center;
            margin: 50px 0;
        }
        .cta-box h2 {
            color: white;
            margin-bottom: 20px;
        }
        .cta-box .btn {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #667eea;
        }
        .timeline-item {
            margin-bottom: 40px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -46px;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #667eea;
        }
        .timeline-item .year {
            font-size: 1.3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
    </style>
    
    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "AboutPage",
        "name": "Tentang DONAN22",
        "description": "<?= htmlspecialchars($page_description) ?>",
        "url": "<?= htmlspecialchars($page_url) ?>",
        "mainEntity": {
            "@type": "Organization",
            "name": "DONAN22",
            "alternateName": "DONAN",
            "url": "<?= SITE_URL ?>",
            "logo": "<?= SITE_URL ?>/assets/images/logo.png",
            "description": "Platform download software, aplikasi, game gratis terpercaya di Indonesia",
            "foundingDate": "2020"
        }
    }
    </script>
</head>
<body>
    <!-- Header -->
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="hero-about">
        <div class="container">
            <h1><i class="fas fa-info-circle"></i> Tentang DONAN22</h1>
            <p class="lead">
                Platform Download Software, Aplikasi, dan Game Gratis Terpercaya di Indonesia
            </p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Apa itu DONAN -->
                <div class="content-section">
                    <h2><i class="fas fa-question-circle"></i> Apa itu DONAN?</h2>
                    <p>
                        <strong>DONAN</strong> (DONAN22.COM) adalah platform download software, aplikasi, 
                        dan game gratis terpercaya di Indonesia. Didirikan pada tahun 2020, 
                        <strong>DONAN</strong> hadir sebagai solusi bagi Anda yang mencari software 
                        berkualitas dengan panduan lengkap dan mudah dipahami.
                    </p>
                    <p>
                        Download di <strong>DONAN</strong> untuk mendapatkan software Windows, Mac, 
                        Android dengan mudah dan aman. <strong>DONAN</strong> menyediakan tutorial 
                        lengkap untuk setiap software yang kami bagikan, sehingga Anda tidak perlu 
                        bingung dalam proses instalasi dan penggunaan.
                    </p>
                    <p>
                        <strong>DONAN</strong> bukan sekadar platform download biasa. Kami berkomitmen 
                        untuk menyediakan software full version, update terbaru, dan 100% gratis untuk 
                        semua pengguna di Indonesia.
                    </p>
                </div>
                
                <!-- Kenapa Pilih DONAN -->
                <div class="content-section">
                    <h2><i class="fas fa-star"></i> Kenapa Pilih DONAN?</h2>
                    <p>
                        Ada banyak platform download software di internet, tapi kenapa harus 
                        <strong>DONAN</strong>? Berikut adalah keunggulan yang membuat 
                        <strong>DONAN22</strong> menjadi pilihan terbaik:
                    </p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-gift"></i>
                                <h4>100% Gratis</h4>
                                <p>
                                    Semua software di <strong>DONAN</strong> gratis tanpa biaya tersembunyi. 
                                    Tidak ada subscription, tidak ada premium membership, semuanya GRATIS!
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-shield-alt"></i>
                                <h4>Aman & Terpercaya</h4>
                                <p>
                                    <strong>DONAN</strong> hanya menyediakan software yang aman dan terpercaya. 
                                    Semua file sudah di-scan untuk memastikan keamanan Anda.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-sync-alt"></i>
                                <h4>Update Terbaru</h4>
                                <p>
                                    <strong>DONAN</strong> selalu update software versi terbaru. Dapatkan 
                                    software dengan fitur terkini dan bug fixes.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-book"></i>
                                <h4>Panduan Lengkap</h4>
                                <p>
                                    Setiap software di <strong>DONAN</strong> dilengkapi tutorial instalasi 
                                    lengkap dengan screenshot dan video.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-flag-usa"></i>
                                <h4>Bahasa Indonesia</h4>
                                <p>
                                    <strong>DONAN</strong> menyediakan panduan dalam Bahasa Indonesia 
                                    yang mudah dipahami untuk semua kalangan.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-box">
                                <i class="fas fa-headset"></i>
                                <h4>Support 24/7</h4>
                                <p>
                                    Punya pertanyaan? Tim <strong>DONAN</strong> siap membantu Anda 
                                    kapan saja melalui comment atau contact form.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sejarah DONAN -->
                <div class="content-section">
                    <h2><i class="fas fa-history"></i> Sejarah DONAN22</h2>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="year">2020</div>
                            <h4>Awal Mula DONAN</h4>
                            <p>
                                <strong>DONAN22</strong> didirikan dengan misi sederhana: menyediakan 
                                akses mudah ke software berkualitas untuk semua orang di Indonesia.
                            </p>
                        </div>
                        <div class="timeline-item">
                            <div class="year">2021</div>
                            <h4>Pertumbuhan Pesat</h4>
                            <p>
                                <strong>DONAN</strong> berkembang pesat dengan menambahkan ratusan software 
                                populer dan tutorial lengkap. User base tumbuh hingga ribuan pengguna aktif.
                            </p>
                        </div>
                        <div class="timeline-item">
                            <div class="year">2022-2023</div>
                            <h4>Ekspansi Konten</h4>
                            <p>
                                <strong>DONAN</strong> memperluas kategori dengan menambahkan game, 
                                aplikasi mobile, dan tools khusus untuk designer dan developer.
                            </p>
                        </div>
                        <div class="timeline-item">
                            <div class="year">2024-2025</div>
                            <h4>Platform Terpercaya #1</h4>
                            <p>
                                Kini <strong>DONAN22</strong> telah dipercaya oleh puluhan ribu pengguna 
                                di Indonesia sebagai platform download software terpercaya.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Visi & Misi -->
                <div class="content-section">
                    <h2><i class="fas fa-bullseye"></i> Visi dan Misi DONAN</h2>
                    
                    <h3>Visi DONAN:</h3>
                    <p>
                        Menjadi platform download software #1 di Indonesia yang menyediakan akses mudah, 
                        aman, dan gratis untuk semua kalangan.
                    </p>
                    
                    <h3>Misi DONAN:</h3>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-success me-2"></i> 
                            Menyediakan software gratis berkualitas untuk semua pengguna Indonesia
                        </li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> 
                            Memberikan tutorial lengkap dan mudah dipahami dalam Bahasa Indonesia
                        </li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> 
                            Memastikan keamanan setiap software yang kami bagikan
                        </li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> 
                            Selalu update dengan software versi terbaru
                        </li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> 
                            Membangun komunitas pengguna yang saling membantu
                        </li>
                    </ul>
                </div>
                
                <!-- Kategori di DONAN -->
                <div class="content-section">
                    <h2><i class="fas fa-th-large"></i> Kategori Software di DONAN</h2>
                    <p>
                        <strong>DONAN</strong> menyediakan berbagai kategori software untuk memenuhi 
                        kebutuhan Anda:
                    </p>
                    <div class="row">
                        <div class="col-md-4">
                            <ul>
                                <li><a href="<?= SITE_URL ?>/category/adobe">Adobe di DONAN</a></li>
                                <li><a href="<?= SITE_URL ?>/category/microsoft-office">Microsoft Office di DONAN</a></li>
                                <li><a href="<?= SITE_URL ?>/category/video-editors">Video Editor di DONAN</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul>
                                <li><a href="<?= SITE_URL ?>/category/activator">Activator di DONAN</a></li>
                                <li><a href="<?= SITE_URL ?>/category/design-graphics">Design & Graphics</a></li>
                                <li><a href="<?= SITE_URL ?>/category/development-tools">Development Tools</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul>
                                <li><a href="<?= SITE_URL ?>/category/game-pc">Game PC</a></li>
                                <li><a href="<?= SITE_URL ?>/category/utilities">Utilities & Tools</a></li>
                                <li><a href="<?= SITE_URL ?>/categories.php">Semua Kategori DONAN</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Stats -->
                <div class="stats-box">
                    <span class="number">1000+</span>
                    <span class="label">Software & Aplikasi</span>
                </div>
                <div class="stats-box">
                    <span class="number">500+</span>
                    <span class="label">Tutorial Lengkap</span>
                </div>
                <div class="stats-box">
                    <span class="number">50K+</span>
                    <span class="label">User Terpercaya</span>
                </div>
                <div class="stats-box">
                    <span class="number">100%</span>
                    <span class="label">Gratis & Aman</span>
                </div>
                
                <!-- Quick Links -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-link"></i> Link Penting</h5>
                        <ul class="list-unstyled">
                            <li><a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Homepage DONAN</a></li>
                            <li><a href="<?= SITE_URL ?>/categories.php"><i class="fas fa-th"></i> Semua Kategori</a></li>
                            <li><a href="<?= SITE_URL ?>/search.php"><i class="fas fa-search"></i> Cari Software</a></li>
                            <li><a href="<?= SITE_URL ?>/contact.php"><i class="fas fa-envelope"></i> Hubungi DONAN</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="row">
            <div class="col-12">
                <div class="cta-box">
                    <h2>Mulai Download di DONAN Sekarang!</h2>
                    <p class="lead mb-4">
                        Bergabunglah dengan puluhan ribu pengguna yang sudah mempercayai 
                        <strong>DONAN</strong> sebagai sumber software terpercaya mereka.
                    </p>
                    <a href="<?= SITE_URL ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-home"></i> Kunjungi Homepage DONAN
                    </a>
                    <a href="<?= SITE_URL ?>/categories.php" class="btn btn-outline-light btn-lg ms-2">
                        <i class="fas fa-th"></i> Lihat Semua Kategori
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once __DIR__ . '/includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
