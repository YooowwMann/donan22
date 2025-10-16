<?php
/**
 * ALL-IN-ONE ENHANCEMENT PACKAGE
 * DONAN22 - 5 High Priority Features Combined
 * 
 * Features Included:
 * 1. Comments & Rating System
 * 2. Breadcrumbs with Schema.org
 * 3. Share Buttons (FB, Twitter, WA, Telegram)
 * 4. Download Statistics Display
 * 5. SEO Meta Tags Enhancement
 */

// ============================================
// FEATURE 1: COMMENTS & RATING SYSTEM
// ============================================

require_once __DIR__ . '/comments_handler.php';

// ============================================
// FEATURE 2: BREADCRUMBS GENERATOR
// ============================================

function renderBreadcrumbs($items = []) {
    if (empty($items)) return '';
    
    $html = '<nav aria-label="breadcrumb" class="mb-4">';
    $html .= '<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    $position = 1;
    $lastIndex = count($items) - 1;
    
    foreach ($items as $index => $item) {
        $isLast = ($index === $lastIndex);
        $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if ($isLast) {
            $html .= '<span itemprop="name">' . htmlspecialchars($item['title']) . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '" itemprop="item">';
            $html .= '<span itemprop="name">' . htmlspecialchars($item['title']) . '</span>';
            $html .= '</a>';
        }
        
        $html .= '<meta itemprop="position" content="' . $position . '" />';
        $html .= '</li>';
        $position++;
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

// ============================================
// FEATURE 3: SOCIAL SHARE BUTTONS
// ============================================

function renderShareButtons($url, $title, $description = '') {
    $encodedUrl = urlencode($url);
    $encodedTitle = urlencode($title);
    $encodedDesc = urlencode($description);
    
    $facebook = "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}";
    $twitter = "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedTitle}";
    $whatsapp = "https://wa.me/?text={$encodedTitle}%20{$encodedUrl}";
    $telegram = "https://t.me/share/url?url={$encodedUrl}&text={$encodedTitle}";
    
    ob_start();
    ?>
    <div class="share-buttons">
        <h5 class="mb-3"><i class="fas fa-share-alt me-2"></i>Share This Post</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= $facebook ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm share-btn">
                <i class="fab fa-facebook-f me-1"></i> Facebook
            </a>
            <a href="<?= $twitter ?>" target="_blank" rel="noopener" class="btn btn-info btn-sm share-btn text-white">
                <i class="fab fa-twitter me-1"></i> Twitter
            </a>
            <a href="<?= $whatsapp ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm share-btn">
                <i class="fab fa-whatsapp me-1"></i> WhatsApp
            </a>
            <a href="<?= $telegram ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm share-btn">
                <i class="fab fa-telegram-plane me-1"></i> Telegram
            </a>
            <button onclick="copyToClipboard('<?= htmlspecialchars($url, ENT_QUOTES) ?>')" class="btn btn-secondary btn-sm share-btn">
                <i class="fas fa-link me-1"></i> Copy Link
            </button>
        </div>
    </div>
    
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Link copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    
    // Track share clicks
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let platform = this.textContent.trim();
            console.log('Shared on: ' + platform);
            // You can add analytics tracking here
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// ============================================
// FEATURE 4: DOWNLOAD STATISTICS DISPLAY
// ============================================

function renderDownloadStats($viewCount, $downloadCount, $ratingData = null) {
    ob_start();
    ?>
    <div class="download-stats-widget card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <div class="stat-item">
                        <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                        <h4 class="mb-0"><?= number_format($viewCount) ?></h4>
                        <small class="text-muted">Views</small>
                    </div>
                </div>
                <div class="col-md-4 <?= $ratingData ? 'border-end' : '' ?>">
                    <div class="stat-item">
                        <i class="fas fa-download fa-2x text-success mb-2"></i>
                        <h4 class="mb-0"><?= number_format($downloadCount) ?></h4>
                        <small class="text-muted">Downloads</small>
                    </div>
                </div>
                <?php if ($ratingData): ?>
                <div class="col-md-4">
                    <div class="stat-item">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0"><?= $ratingData['average'] ?></h4>
                        <small class="text-muted"><?= $ratingData['count'] ?> Ratings</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ============================================
// FEATURE 5: SEO META TAGS GENERATOR
// ============================================

function renderSEOMetaTags($data) {
    $defaults = [
        'title' => 'DONAN22 - Download Game Cracks dan Repacks - FitGirl DODI 2025',
        'description' => 'Download game cracks dan repacks gratis dari FitGirl, DODI. Software, game PC, repack terbaru 2025. Download gratis dan aman di DONAN22',
        'keywords' => 'download game cracks, dodi repacks, fitgirl repack, game repack, crack game, free download, software gratis, donan22',
        'image' => 'http://localhost/donan22/assets/images/default-og.jpg',
        'url' => 'http://localhost/donan22',
        'type' => 'website',
        'author' => 'DONAN22',
        'published_time' => date('c'),
        'modified_time' => date('c'),
        'section' => 'Software',
        'tags' => [],
    ];
    
    $meta = array_merge($defaults, $data);
    
    // Optimize title for SEO (max 60 chars)
    $seoTitle = strlen($meta['title']) > 60 ? substr($meta['title'], 0, 57) . '...' : $meta['title'];
    
    // Optimize description for SEO (max 155 chars)
    $seoDescription = strlen($meta['description']) > 155 ? substr($meta['description'], 0, 152) . '...' : $meta['description'];
    
    ob_start();
    ?>
    <!-- ========================================== -->
    <!-- ADVANCED SEO META TAGS - RANKING OPTIMIZED -->
    <!-- ========================================== -->
    
    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta['keywords']) ?>">
    <meta name="author" content="<?= htmlspecialchars($meta['author']) ?>">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow">
    <meta name="language" content="Indonesian">
    <meta name="geo.region" content="ID">
    <meta name="geo.placename" content="Indonesia">
    <meta name="revisit-after" content="1 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">
    
    <!-- Canonical URL (Critical for SEO) -->
    <link rel="canonical" href="<?= htmlspecialchars($meta['url']) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= htmlspecialchars($meta['type']) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($meta['url']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($meta['image']) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:site_name" content="DONAN22">
    <meta property="og:locale" content="id_ID">
    <?php if ($meta['type'] === 'article'): ?>
    <meta property="article:publisher" content="https://www.facebook.com/donan22">
    <meta property="article:section" content="<?= htmlspecialchars($meta['section']) ?>">
    <?php if (!empty($meta['tags'])): ?>
    <?php foreach ($meta['tags'] as $tag): ?>
    <meta property="article:tag" content="<?= htmlspecialchars($tag) ?>">
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($meta['published_time'])): ?>
    <meta property="article:published_time" content="<?= htmlspecialchars($meta['published_time']) ?>">
    <?php endif; ?>
    <?php if (isset($meta['modified_time'])): ?>
    <meta property="article:modified_time" content="<?= htmlspecialchars($meta['modified_time']) ?>">
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@donan22">
    <meta name="twitter:creator" content="@donan22">
    <meta name="twitter:url" content="<?= htmlspecialchars($meta['url']) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($meta['image']) ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($seoTitle) ?>">
    
    <!-- Advanced Schema.org JSON-LD for Better Search Results -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "<?= $meta['type'] === 'article' ? 'Article' : 'SoftwareApplication' ?>",
        "headline": <?= json_encode($seoTitle) ?>,
        "description": <?= json_encode($seoDescription) ?>,
        "image": {
            "@type": "ImageObject",
            "url": <?= json_encode($meta['image']) ?>,
            "width": 1200,
            "height": 630
        },
        "url": <?= json_encode($meta['url']) ?>,
        "datePublished": <?= json_encode($meta['published_time']) ?>,
        "dateModified": <?= json_encode($meta['modified_time']) ?>,
        "author": {
            "@type": "Organization",
            "name": "DONAN22",
            "url": "http://localhost/donan22",
            "logo": {
                "@type": "ImageObject",
                "url": "http://localhost/donan22/assets/images/logo.png"
            }
        },
        "publisher": {
            "@type": "Organization",
            "name": "DONAN22",
            "url": "http://localhost/donan22",
            "logo": {
                "@type": "ImageObject",
                "url": "http://localhost/donan22/assets/images/logo.png",
                "width": 600,
                "height": 60
            }
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": <?= json_encode($meta['url']) ?>
        }
        <?php if ($meta['type'] === 'article' && !empty($meta['tags'])): ?>
        ,
        "keywords": <?= json_encode(implode(', ', $meta['tags'])) ?>
        <?php endif; ?>
    }
    </script>
    
    <!-- Breadcrumb Schema for Better Navigation in Search Results -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "http://localhost/donan22"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?= htmlspecialchars($meta['section']) ?>",
                "item": "http://localhost/donan22/category/<?= strtolower(str_replace(' ', '-', $meta['section'])) ?>"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?= htmlspecialchars($seoTitle) ?>",
                "item": "<?= htmlspecialchars($meta['url']) ?>"
            }
        ]
    }
    </script>
    
    <!-- Website/Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "DONAN22",
        "url": "http://localhost/donan22",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "http://localhost/donan22/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <?php
    return ob_get_clean();
}

// ============================================
// CSS STYLES FOR ALL FEATURES
// ============================================

function renderEnhancementStyles() {
    ?>
    <style>
    /* Comments & Rating Styles */
    .star-rating {
        display: inline-block;
        color: #ffc107;
    }
    .star-rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 3px;
    }
    .star-rating-input input[type="radio"] {
        display: none;
    }
    .star-rating-input label {
        cursor: pointer;
        font-size: 20px;
        color: #ddd;
        transition: color 0.2s;
    }
    .star-rating-input label:hover,
    .star-rating-input label:hover ~ label,
    .star-rating-input input[type="radio"]:checked ~ label {
        color: #ffc107;
    }
    
    /* Compact Form Styles */
    .compact-form .form-control-sm {
        font-size: 14px;
        padding: 6px 10px;
    }
    .compact-form textarea.form-control-sm {
        font-size: 14px;
        padding: 8px 10px;
    }
    .compact-form .btn-sm {
        padding: 6px 16px;
        font-size: 14px;
    }
    
    .comment-item {
        background: #f8f9fa;
        padding: 1rem !important;
    }
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 18px;
    }
    .comment-content {
        font-size: 14px;
    }
    .comment-content strong {
        font-size: 15px;
    }
    .replies {
        border-left: 3px solid #dee2e6;
        padding-left: 15px;
        margin-top: 10px;
    }
    
    .comment-item {
        background: #f8f9fa;
    }
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 18px;
    }
    .replies {
        border-left: 3px solid #dee2e6;
        padding-left: 20px;
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 1rem;
    }
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 18px;
    }
    
    /* Share Buttons */
    .share-buttons {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }
    .share-btn {
        min-width: 120px;
    }
    .share-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transition: all 0.3s;
    }
    
    /* Download Stats Widget */
    .download-stats-widget {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .download-stats-widget .card-body {
        background: white;
        border-radius: 8px;
    }
    .stat-item i {
        opacity: 0.8;
    }
    .stat-item h4 {
        font-weight: bold;
        color: #333;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .comment-item {
        animation: fadeInUp 0.5s ease-out;
    }
    </style>
    <?php
}

// ============================================
// JAVASCRIPT FOR ALL FEATURES
// ============================================

function renderEnhancementScripts() {
    ?>
    <script>
    // Comment Form Functions
    function showReplyForm(commentId) {
        document.getElementById('reply-form-' + commentId).style.display = 'block';
    }
    
    function hideReplyForm(commentId) {
        document.getElementById('reply-form-' + commentId).style.display = 'none';
    }
    
    // AJAX Comment Submission
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Reload to show new comment
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    });
    
    // Smooth scroll to comments
    document.querySelectorAll('a[href="#comments"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('comments').scrollIntoView({ 
                behavior: 'smooth' 
            });
        });
    });
    </script>
    <?php
}

// ============================================
// NEW FEATURE: HELPER FUNCTIONS FOR 8 FEATURES
// ============================================

/**
 * Render post tags as badges
 */
function renderPostTags($postId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT t.* FROM tags t 
        INNER JOIN post_tags pt ON t.id = pt.tag_id 
        WHERE pt.post_id = ? 
        ORDER BY t.name
    ");
    $stmt->execute([$postId]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tags)) return '';
    
    $html = '<div class="post-tags mt-3 mb-2">';
    $html .= '<i class="fas fa-tags me-2" style="color: #6b7280;"></i>';
    
    foreach ($tags as $tag) {
        $html .= '<a href="' . rtrim(SITE_URL, '/') . '/tag/' . urlencode($tag['slug']) . '" ';
        $html .= 'class="badge bg-light text-dark me-1" ';
        $html .= 'style="text-decoration: none; font-weight: 500; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb;">';
        $html .= htmlspecialchars($tag['name']);
        $html .= '</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate Table of Contents from content
 */
function generateTOC($content) {
    preg_match_all('/<h([2-4])[^>]*>(.*?)<\/h[2-4]>/i', $content, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) return ['html' => '', 'content' => $content];
    
    $toc = '<div class="table-of-contents mb-4 p-3 border rounded bg-light">';
    $toc .= '<h5><i class="fas fa-list-ul me-2"></i>Table of Contents</h5>';
    $toc .= '<ul class="list-unstyled mb-0">';
    
    $newContent = $content;
    $tocIndex = 0;
    
    foreach ($matches as $heading) {
        $level = (int)$heading[1];
        $text = strip_tags($heading[2]);
        $id = 'heading-' . $tocIndex;
        $indent = ($level - 2) * 15;
        
        // Add TOC item
        $toc .= '<li style="margin-left: ' . $indent . 'px; margin-bottom: 0.5rem;">';
        $toc .= '<a href="#' . $id . '" class="text-decoration-none">';
        $toc .= '<i class="fas fa-angle-right me-1"></i>';
        $toc .= htmlspecialchars($text);
        $toc .= '</a>';
        $toc .= '</li>';
        
        // Add ID to heading in content
        $oldHeading = $heading[0];
        $newHeading = str_replace('<h' . $level, '<h' . $level . ' id="' . $id . '"', $oldHeading);
        $newContent = str_replace($oldHeading, $newHeading, $newContent);
        
        $tocIndex++;
    }
    
    $toc .= '</ul>';
    $toc .= '</div>';
    
    return ['html' => $toc, 'content' => $newContent];
}

/**
 * Calculate SEO score for a post
 */
function calculateSEOScore($post) {
    $score = 0;
    $issues = [];
    
    $title = $post['title'] ?? '';
    $content = $post['content'] ?? '';
    $metaDesc = $post['meta_description'] ?? '';
    $focusKeyword = strtolower($post['focus_keyword'] ?? '');
    
    // Title checks (20 points)
    $titleLen = strlen($title);
    if ($titleLen >= 30 && $titleLen <= 60) {
        $score += 10;
    } else {
        $issues[] = 'Title should be 30-60 characters (currently ' . $titleLen . ')';
    }
    
    if (!empty($focusKeyword) && stripos($title, $focusKeyword) !== false) {
        $score += 10;
    } else if (!empty($focusKeyword)) {
        $issues[] = 'Focus keyword not found in title';
    }
    
    // Meta description (20 points)
    $metaLen = strlen($metaDesc);
    if ($metaLen >= 120 && $metaLen <= 160) {
        $score += 10;
    } else {
        $issues[] = 'Meta description should be 120-160 characters (currently ' . $metaLen . ')';
    }
    
    if (!empty($focusKeyword) && stripos($metaDesc, $focusKeyword) !== false) {
        $score += 10;
    } else if (!empty($focusKeyword)) {
        $issues[] = 'Focus keyword not found in meta description';
    }
    
    // Content checks (60 points)
    $wordCount = str_word_count(strip_tags($content));
    if ($wordCount >= 300) {
        $score += 20;
    } else {
        $issues[] = 'Content should be at least 300 words (currently ' . $wordCount . ')';
    }
    
    if (!empty($focusKeyword)) {
        $keywordCount = substr_count(strtolower($content), $focusKeyword);
        $density = $wordCount > 0 ? ($keywordCount / $wordCount) * 100 : 0;
        
        if ($density >= 0.5 && $density <= 2.5) {
            $score += 20;
        } else {
            $issues[] = 'Keyword density should be 0.5-2.5% (currently ' . number_format($density, 2) . '%)';
        }
        
        if (preg_match('/<h[12][^>]*>.*?' . preg_quote($focusKeyword, '/') . '.*?<\/h[12]>/i', $content)) {
            $score += 20;
        } else {
            $issues[] = 'Focus keyword should appear in H1 or H2 heading';
        }
    }
    
    return [
        'score' => $score,
        'issues' => $issues,
        'grade' => $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Fair' : 'Poor'))
    ];
}
?>
