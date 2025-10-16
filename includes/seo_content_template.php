<?php
/**
 * SEO Content Template Generator - DONAN22
 * Generate structured content with proper H2/H3 headings
 */

/**
 * Generate complete SEO-optimized content template for software
 * @param array $data Post data including title, version, features, etc.
 * @return string Complete HTML content with proper heading structure
 */
function generateSoftwareContentTemplate($data) {
    $softwareName = $data['software_name'] ?? $data['title'];
    $version = $data['version'] ?? '';
    $developer = $data['developer'] ?? '';
    $fileSize = $data['file_size'] ?? '';
    $requirements = $data['requirements'] ?? [];
    $features = $data['features'] ?? [];
    $downloadLinks = $data['download_links'] ?? [];
    
    $content = '';
    
    // H1: Main Heading (SEO Critical!)
    require_once __DIR__ . '/seo_heading_helper.php';
    $h1Title = generateSEOH1($softwareName, 'software', $version);
    $content .= '<h1>' . htmlspecialchars($h1Title) . '</h1>' . "\n\n";
    
    // H2: Tentang Software
    $content .= '<h2 id="tentang">Tentang ' . htmlspecialchars($softwareName) . '</h2>' . "\n";
    $content .= '<p>' . ($data['description'] ?? 'Deskripsi software...') . '</p>' . "\n\n";
    
    // H2: Fitur Utama
    if (!empty($features)) {
        $content .= '<h2 id="fitur">Fitur Utama ' . htmlspecialchars($softwareName) . '</h2>' . "\n";
        $content .= '<ul>' . "\n";
        foreach ($features as $feature) {
            $content .= '  <li>' . htmlspecialchars($feature) . '</li>' . "\n";
        }
        $content .= '</ul>' . "\n\n";
    }
    
    // H2: Screenshot / Preview
    if (!empty($data['screenshots'])) {
        $content .= '<h2 id="screenshot">Screenshot / Preview</h2>' . "\n";
        $content .= '<div class="screenshots-grid">' . "\n";
        foreach ($data['screenshots'] as $screenshot) {
            $content .= '  <img src="' . htmlspecialchars($screenshot['url']) . '" ';
            $content .= 'alt="Screenshot ' . htmlspecialchars($softwareName) . ' - ' . htmlspecialchars($screenshot['caption']) . '" ';
            $content .= 'class="img-fluid rounded shadow mb-3">' . "\n";
        }
        $content .= '</div>' . "\n\n";
    }
    
    // H2: Spesifikasi & System Requirements
    $content .= '<h2 id="spesifikasi">Spesifikasi & System Requirements</h2>' . "\n";
    $content .= '<div class="specifications-box p-4 bg-light rounded">' . "\n";
    $content .= '<table class="table table-borderless mb-0">' . "\n";
    $content .= '  <tbody>' . "\n";
    
    if ($softwareName) {
        $content .= '    <tr><td><strong>Software:</strong></td><td>' . htmlspecialchars($softwareName) . '</td></tr>' . "\n";
    }
    if ($version) {
        $content .= '    <tr><td><strong>Version:</strong></td><td>' . htmlspecialchars($version) . '</td></tr>' . "\n";
    }
    if ($developer) {
        $content .= '    <tr><td><strong>Developer:</strong></td><td>' . htmlspecialchars($developer) . '</td></tr>' . "\n";
    }
    if ($fileSize) {
        $content .= '    <tr><td><strong>File Size:</strong></td><td>' . htmlspecialchars($fileSize) . '</td></tr>' . "\n";
    }
    
    // System Requirements
    if (!empty($requirements)) {
        $content .= '    <tr><td colspan="2"><strong class="text-primary">System Requirements:</strong></td></tr>' . "\n";
        foreach ($requirements as $key => $value) {
            $content .= '    <tr><td><strong>' . htmlspecialchars(ucfirst($key)) . ':</strong></td><td>' . htmlspecialchars($value) . '</td></tr>' . "\n";
        }
    } else {
        // Default requirements
        $content .= '    <tr><td colspan="2"><strong class="text-primary">System Requirements:</strong></td></tr>' . "\n";
        $content .= '    <tr><td><strong>OS:</strong></td><td>Windows 10/11 (64-bit)</td></tr>' . "\n";
        $content .= '    <tr><td><strong>Processor:</strong></td><td>Intel Core i3 or AMD equivalent</td></tr>' . "\n";
        $content .= '    <tr><td><strong>RAM:</strong></td><td>4GB minimum, 8GB recommended</td></tr>' . "\n";
        $content .= '    <tr><td><strong>Storage:</strong></td><td>500MB available space</td></tr>' . "\n";
        $content .= '    <tr><td><strong>Graphics:</strong></td><td>DirectX 11 compatible</td></tr>' . "\n";
    }
    
    $content .= '  </tbody>' . "\n";
    $content .= '</table>' . "\n";
    $content .= '</div>' . "\n\n";
    
    // H2: Kelebihan dan Kekurangan (NEW - untuk mencapai 6+ H2)
    $content .= '<h2 id="kelebihan-kekurangan">Kelebihan dan Kekurangan</h2>' . "\n";
    $content .= '<div class="row">' . "\n";
    $content .= '<div class="col-md-6">' . "\n";
    $content .= '<h3 id="kelebihan">✅ Kelebihan</h3>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li>Gratis dan mudah digunakan</li>' . "\n";
    $content .= '  <li>Interface modern dan user-friendly</li>' . "\n";
    $content .= '  <li>Performa cepat dan stabil</li>' . "\n";
    $content .= '  <li>Fitur lengkap untuk kebutuhan profesional</li>' . "\n";
    $content .= '  <li>Update rutin dan dukungan komunitas aktif</li>' . "\n";
    $content .= '</ul>' . "\n";
    $content .= '</div>' . "\n";
    $content .= '<div class="col-md-6">' . "\n";
    $content .= '<h3 id="kekurangan">❌ Kekurangan</h3>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li>Membutuhkan spesifikasi hardware yang cukup tinggi</li>' . "\n";
    $content .= '  <li>Kurva pembelajaran yang cukup curam untuk pemula</li>' . "\n";
    $content .= '  <li>Beberapa fitur premium memerlukan lisensi</li>' . "\n";
    $content .= '</ul>' . "\n";
    $content .= '</div>' . "\n";
    $content .= '</div>' . "\n\n";
    
    // H2: Tips dan Trik Penggunaan (NEW - untuk mencapai 6+ H2)
    $content .= '<h2 id="tips-trik">Tips dan Trik Penggunaan</h2>' . "\n";
    $content .= '<p>Berikut beberapa tips untuk memaksimalkan penggunaan ' . htmlspecialchars($softwareName) . ':</p>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li><strong>Pelajari Shortcut Keyboard:</strong> Gunakan keyboard shortcuts untuk mempercepat workflow Anda.</li>' . "\n";
    $content .= '  <li><strong>Gunakan Template:</strong> Manfaatkan template bawaan untuk memulai project dengan cepat.</li>' . "\n";
    $content .= '  <li><strong>Backup Berkala:</strong> Selalu backup file project Anda secara rutin.</li>' . "\n";
    $content .= '  <li><strong>Update Rutin:</strong> Pastikan selalu menggunakan versi terbaru untuk fitur dan security patch.</li>' . "\n";
    $content .= '  <li><strong>Join Community:</strong> Bergabung dengan forum atau grup untuk tips dan troubleshooting.</li>' . "\n";
    $content .= '</ul>' . "\n\n";
    
    // H2: Cara Download dan Install
    $content .= '<h2 id="cara-install">Cara Download dan Install ' . htmlspecialchars($softwareName) . '</h2>' . "\n";
    
    // H3: Langkah-langkah
    $content .= '<h3 id="langkah-1">Langkah 1: Download File</h3>' . "\n";
    $content .= '<p>Klik tombol download di bawah untuk mengunduh file installer ' . htmlspecialchars($softwareName) . '. File akan otomatis terdownload ke folder Downloads Anda.</p>' . "\n\n";
    
    $content .= '<h3 id="langkah-2">Langkah 2: Extract File</h3>' . "\n";
    $content .= '<p>Setelah download selesai, extract file .zip atau .rar menggunakan WinRAR atau 7-Zip. Pastikan Anda extract ke folder yang mudah diakses.</p>' . "\n\n";
    
    $content .= '<h3 id="langkah-3">Langkah 3: Install Software</h3>' . "\n";
    $content .= '<p>Buka folder hasil extract, lalu jalankan file <code>setup.exe</code> atau <code>installer.exe</code>. Ikuti wizard instalasi hingga selesai.</p>' . "\n\n";
    
    $content .= '<h3 id="langkah-4">Langkah 4: Aktivasi (Optional)</h3>' . "\n";
    $content .= '<p>Jika diperlukan aktivasi, gunakan crack/patch yang tersedia di folder. <strong>Matikan antivirus</strong> sementara, lalu copy file crack ke folder instalasi. Jalankan sebagai Administrator.</p>' . "\n\n";
    
    // H2: Link Download (akan diisi oleh sistem download links)
    $content .= '<h2 id="download">Link Download ' . htmlspecialchars($softwareName) . '</h2>' . "\n";
    $content .= '<p>Pilih salah satu link download di bawah ini:</p>' . "\n";
    $content .= '<div id="download-links-placeholder"></div>' . "\n\n";
    
    // H2: FAQ
    $content .= '<h2 id="faq">FAQ (Frequently Asked Questions)</h2>' . "\n\n";
    
    $content .= '<h3 id="faq-gratis">Apakah ' . htmlspecialchars($softwareName) . ' ini gratis?</h3>' . "\n";
    $content .= '<p>Ya, versi yang kami bagikan adalah <strong>full version gratis</strong>. Anda tidak perlu membayar atau berlangganan. Semua fitur premium sudah teraktivasi.</p>' . "\n\n";
    
    $content .= '<h3 id="faq-aman">Apakah aman digunakan?</h3>' . "\n";
    $content .= '<p>Software ini sudah kami test dan <strong>bebas virus</strong>. Namun, beberapa antivirus mungkin mendeteksi crack/patch sebagai false positive. Pastikan download dari link resmi kami.</p>' . "\n\n";
    
    $content .= '<h3 id="faq-update">Bagaimana cara update ke versi terbaru?</h3>' . "\n";
    $content .= '<p>Anda dapat mengecek update terbaru di website kami. Kami selalu mengupdate software dengan versi terbaru secara berkala. Subscribe newsletter untuk notifikasi update.</p>' . "\n\n";
    
    $content .= '<h3 id="faq-error">Software tidak bisa dibuka / error?</h3>' . "\n";
    $content .= '<p>Pastikan sistem Anda memenuhi minimum requirements. Install <strong>Visual C++ Redistributable</strong> dan <strong>.NET Framework</strong> terbaru. Jalankan sebagai Administrator dan matikan antivirus sementara.</p>' . "\n\n";
    
    return $content;
}

/**
 * Generate game content template
 */
function generateGameContentTemplate($data) {
    $gameName = $data['game_name'] ?? $data['title'];
    $version = $data['version'] ?? '';
    $genre = $data['genre'] ?? 'Action';
    $fileSize = $data['file_size'] ?? '';
    
    $content = '';
    
    // H1: Main Heading (SEO Critical!)
    require_once __DIR__ . '/seo_heading_helper.php';
    $h1Title = generateSEOH1($gameName, 'game', $version);
    $content .= '<h1>' . htmlspecialchars($h1Title) . '</h1>' . "\n\n";
    
    // H2: Tentang Game
    $content .= '<h2 id="tentang">Tentang ' . htmlspecialchars($gameName) . '</h2>' . "\n";
    $content .= '<p>' . ($data['description'] ?? 'Deskripsi game...') . '</p>' . "\n\n";
    
    // H2: Gameplay & Features
    $content .= '<h2 id="gameplay">Gameplay & Features</h2>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li>Genre: ' . htmlspecialchars($genre) . '</li>' . "\n";
    $content .= '  <li>Mode: Single Player / Multiplayer</li>' . "\n";
    $content .= '  <li>Platform: PC Windows</li>' . "\n";
    $content .= '</ul>' . "\n\n";
    
    // H2: Screenshot
    $content .= '<h2 id="screenshot">Screenshot / Gameplay</h2>' . "\n";
    $content .= '<p><em>Screenshot akan ditampilkan di sini...</em></p>' . "\n\n";
    
    // H2: System Requirements
    $content .= '<h2 id="spesifikasi">System Requirements</h2>' . "\n";
    $content .= '<div class="row"><div class="col-md-6">' . "\n";
    $content .= '<h4>Minimum:</h4>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li>OS: Windows 10 64-bit</li>' . "\n";
    $content .= '  <li>Processor: Intel Core i5</li>' . "\n";
    $content .= '  <li>RAM: 8GB</li>' . "\n";
    $content .= '  <li>Graphics: NVIDIA GTX 1050</li>' . "\n";
    $content .= '  <li>Storage: 50GB</li>' . "\n";
    $content .= '</ul>' . "\n";
    $content .= '</div><div class="col-md-6">' . "\n";
    $content .= '<h4>Recommended:</h4>' . "\n";
    $content .= '<ul>' . "\n";
    $content .= '  <li>OS: Windows 11 64-bit</li>' . "\n";
    $content .= '  <li>Processor: Intel Core i7</li>' . "\n";
    $content .= '  <li>RAM: 16GB</li>' . "\n";
    $content .= '  <li>Graphics: NVIDIA RTX 3060</li>' . "\n";
    $content .= '  <li>Storage: 100GB SSD</li>' . "\n";
    $content .= '</ul>' . "\n";
    $content .= '</div></div>' . "\n\n";
    
    // H2: Cara Download dan Install
    $content .= '<h2 id="cara-install">Cara Download dan Install</h2>' . "\n";
    $content .= '<h3 id="langkah-1">Langkah 1: Download File</h3>' . "\n";
    $content .= '<p>Download semua part file game...</p>' . "\n\n";
    $content .= '<h3 id="langkah-2">Langkah 2: Extract</h3>' . "\n";
    $content .= '<p>Extract menggunakan WinRAR...</p>' . "\n\n";
    $content .= '<h3 id="langkah-3">Langkah 3: Install</h3>' . "\n";
    $content .= '<p>Jalankan setup.exe...</p>' . "\n\n";
    
    // H2: Download
    $content .= '<h2 id="download">Link Download ' . htmlspecialchars($gameName) . '</h2>' . "\n";
    $content .= '<div id="download-links-placeholder"></div>' . "\n\n";
    
    // H2: FAQ
    $content .= '<h2 id="faq">FAQ</h2>' . "\n";
    $content .= '<h3>Apakah game ini repack atau full version?</h3>' . "\n";
    $content .= '<p>Full version dengan semua DLC included...</p>' . "\n\n";
    
    return $content;
}

/**
 * Parse existing content and add missing headings
 */
function enhanceContentWithHeadings($content, $postType = 'software', $softwareName = '') {
    // Check if content already has H2 headings
    if (preg_match('/<h2/i', $content)) {
        return $content; // Already has structure
    }
    
    // If no H2, wrap existing content and add template structure
    $enhanced = '';
    
    // Add default H2: Tentang
    $enhanced .= '<h2 id="tentang">Tentang ' . htmlspecialchars($softwareName) . '</h2>' . "\n";
    $enhanced .= $content . "\n\n";
    
    // Add FAQ section
    $enhanced .= '<h2 id="faq">FAQ (Frequently Asked Questions)</h2>' . "\n";
    $enhanced .= '<h3>Apakah software ini gratis?</h3>' . "\n";
    $enhanced .= '<p>Ya, software ini gratis untuk digunakan.</p>' . "\n";
    
    return $enhanced;
}

/**
 * Generate Table of Contents from headings
 */
function generateTableOfContents($content) {
    preg_match_all('/<h([2-3])[^>]*id=["\']([^"\']+)["\'][^>]*>(.*?)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        return '';
    }
    
    $toc = '<div class="table-of-contents-auto">' . "\n";
    $toc .= '<h4><i class="fas fa-list-ul"></i> Daftar Isi</h4>' . "\n";
    $toc .= '<ul class="toc-list">' . "\n";
    
    foreach ($matches as $match) {
        $level = $match[1];
        $id = $match[2];
        $text = strip_tags($match[3]);
        
        $class = $level == 2 ? 'toc-h2' : 'toc-h3';
        $toc .= '<li class="' . $class . '"><a href="#' . $id . '">' . htmlspecialchars($text) . '</a></li>' . "\n";
    }
    
    $toc .= '</ul>' . "\n";
    $toc .= '</div>' . "\n";
    
    return $toc;
}
