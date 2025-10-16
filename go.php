<?php
/**
 * DONAN22 - Shortlink Redirect Handler
 * URL: /go/{code}
 */

require_once 'config_modern.php';
require_once 'includes/MonetizationManager.php';

// Get short code from URL
$short_code = $_GET['code'] ?? '';

// Try to get from path: /go/abc123
if (empty($short_code)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/go\/([a-zA-Z0-9]+)/', $request_uri, $matches)) {
        $short_code = $matches[1];
    }
}

if (empty($short_code)) {
    header('Location: /');
    exit;
}

// Initialize monetization
$monetization = new MonetizationManager($pdo);
$link = $monetization->getLinkByCode($short_code);

if (!$link) {
    header('Location: /404.php');
    exit;
}

// Track view event
$monetization->trackEvent($link['id'], 'view', $link['monetizer_service']);

// Check if social unlock
$skip_monetizer = false;
if (isset($_COOKIE['social_unlock_' . $link['id']])) {
    $skip_monetizer = true;
}

// Determine redirect URL
if ($skip_monetizer) {
    // Direct download (social unlocked)
    $redirect_url = $link['original_url'];
    $show_countdown = false;
} else {
    // Go through monetizer
    $redirect_url = $link['monetized_url'] ?? $link['original_url'];
    $show_countdown = !empty($link['monetized_url']);
    
    // Track click
    $monetization->trackEvent($link['id'], 'click', $link['monetizer_service']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Monetag Ads - Loaded AFTER <head> tag for proper initialization -->
    <?php include_once __DIR__ . '/includes/propeller_ads.php'; ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preparing Download - <?= htmlspecialchars($link['download_title'] ?? 'DONAN22') ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .file-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .file-info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .file-info-item:last-child {
            border-bottom: none;
        }
        
        .file-info-label {
            color: #666;
            font-weight: 600;
        }
        
        .file-info-value {
            color: #333;
            font-weight: 500;
        }
        
        .countdown {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            margin: 30px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            color: #666;
            margin: 15px 0;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 50px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            margin-top: 20px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .social-unlock {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px dashed #e0e0e0;
        }
        
        .social-unlock h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .social-unlock p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .social-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .social-btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .social-btn:hover {
            transform: scale(1.05);
        }
        
        .social-btn.facebook { background: #1877f2; }
        .social-btn.twitter { background: #1da1f2; }
        .social-btn.whatsapp { background: #25d366; }
        .social-btn.telegram { background: #0088cc; }
        
        .password-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 20px;
            margin: 25px 0;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .password-box strong {
            color: white;
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .password-box p {
            color: rgba(255,255,255,0.95);
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .password-copy {
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            gap: 10px;
        }
        
        .password-copy span {
            color: #667eea;
            flex: 1;
            word-break: break-all;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            white-space: nowrap;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
        }
        
        .copy-btn:active {
            transform: translateY(0);
        }
        
        .copy-btn:hover {
            background: #1976D2;
        }
        
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📥</div>
        <h1><?= htmlspecialchars($link['download_title'] ?? 'File Download') ?></h1>
        <p class="subtitle">Preparing your download...</p>
        
        <?php if ($link['download_title'] || $link['file_size']): ?>
        <div class="file-info">
            <?php if ($link['download_title']): ?>
            <div class="file-info-item">
                <span class="file-info-label">📄 File Name:</span>
                <span class="file-info-value"><?= htmlspecialchars($link['download_title']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($link['file_size']): ?>
            <div class="file-info-item">
                <span class="file-info-label">💾 File Size:</span>
                <span class="file-info-value"><?= htmlspecialchars($link['file_size']) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="file-info-item">
                <span class="file-info-label">⬇️ Total Downloads:</span>
                <span class="file-info-value"><?= number_format($link['total_downloads']) ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($link['file_password']): ?>
        <div class="password-box">
            <strong>🔐 ZIP Password Required</strong>
            <p>File ini dilindungi dengan password ZIP. Copy password di bawah untuk extract file:</p>
            <div class="password-copy">
                <span id="password"><?= htmlspecialchars($link['file_password']) ?></span>
                <button class="copy-btn" onclick="copyPassword()">📋 Copy ZIP Password</button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_countdown): ?>
        <!-- Countdown Mode -->
        <div class="countdown" id="countdown">10</div>
        <p class="message">
            ⏱️ Please wait <strong><span id="seconds">10</span> seconds</strong>...<br>
            You will be redirected automatically
        </p>
        
        <div class="spinner"></div>
        
        <button class="btn" id="downloadBtn" disabled>
            ⏳ Preparing... (<span id="btnCounter">10</span>s)
        </button>
        
        <?php else: ?>
        <!-- Direct Download Mode -->
        <p class="message">
            ✅ <strong>Verification Complete!</strong><br>
            Click the button below to start download
        </p>
        
        <a href="<?= htmlspecialchars($redirect_url) ?>" class="btn" id="downloadBtn">
            ⬇️ Download Now
        </a>
        <?php endif; ?>
        
        <!-- Social Unlock -->
        <?php if (!$skip_monetizer): ?>
        <div class="social-unlock">
            <h3>⚡ Skip Wait Time!</h3>
            <p>Share this to social media for <strong>instant download</strong> without ads</p>
            <div class="social-buttons">
                <a href="#" class="social-btn facebook" onclick="socialUnlock('facebook'); return false;">
                    📘 Facebook
                </a>
                <a href="#" class="social-btn twitter" onclick="socialUnlock('twitter'); return false;">
                    🐦 Twitter
                </a>
                <a href="#" class="social-btn whatsapp" onclick="socialUnlock('whatsapp'); return false;">
                    💬 WhatsApp
                </a>
                <a href="#" class="social-btn telegram" onclick="socialUnlock('telegram'); return false;">
                    ✈️ Telegram
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="warning">
            ⚠️ <strong>Important:</strong> Disable ad-blocker for better experience. Support us by allowing ads!
        </div>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($link['total_clicks']) ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($link['total_downloads']) ?></div>
                <div class="stat-label">Downloads</div>
            </div>
        </div>
    </div>
    
    <script>
        <?php if ($show_countdown): ?>
        // Countdown logic
        let countdown = 10;
        const countdownEl = document.getElementById('countdown');
        const secondsEl = document.getElementById('seconds');
        const btnCounterEl = document.getElementById('btnCounter');
        const downloadBtn = document.getElementById('downloadBtn');
        
        const interval = setInterval(() => {
            countdown--;
            
            if (countdownEl) countdownEl.textContent = countdown;
            if (secondsEl) secondsEl.textContent = countdown;
            if (btnCounterEl) btnCounterEl.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                
                if (downloadBtn) {
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = '⬇️ Continue to Download';
                    downloadBtn.onclick = function() {
                        <?php if ($link['monetized_url']): ?>
                        // Track download
                        fetch('/api/track-download.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({link_id: <?= $link['id'] ?>})
                        });
                        <?php endif; ?>
                        
                        window.open('<?= htmlspecialchars($redirect_url) ?>', '_blank');
                    };
                }
            }
        }, 1000);
        <?php endif; ?>
        
        // Copy password
        function copyPassword() {
            const password = document.getElementById('password').textContent;
            navigator.clipboard.writeText(password).then(() => {
                alert('✅ ZIP Password berhasil dicopy! Gunakan untuk extract file ZIP.');
            });
        }
        
        // Social unlock
        function socialUnlock(platform) {
            const pageUrl = window.location.origin + window.location.pathname;
            const title = '<?= htmlspecialchars($link['download_title'] ?? 'Download') ?>';
            const shareText = encodeURIComponent('Download: ' + title);
            const shareUrl = encodeURIComponent(pageUrl);
            
            let popupUrl = '';
            
            switch(platform) {
                case 'facebook':
                    popupUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
                    break;
                case 'twitter':
                    popupUrl = `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareText}`;
                    break;
                case 'whatsapp':
                    popupUrl = `https://wa.me/?text=${shareText}%20${shareUrl}`;
                    break;
                case 'telegram':
                    popupUrl = `https://t.me/share/url?url=${shareUrl}&text=${shareText}`;
                    break;
            }
            
            // Open share window
            window.open(popupUrl, 'share', 'width=600,height=400');
            
            // Set cookie and redirect after 3 seconds
            setTimeout(() => {
                document.cookie = 'social_unlock_<?= $link['id'] ?>=1; path=/; max-age=86400';
                
                // Track social unlock
                fetch('/api/track-social-unlock.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        link_id: <?= $link['id'] ?>,
                        platform: platform
                    })
                }).then(() => {
                    // Redirect to direct download
                    window.open('<?= htmlspecialchars($link['original_url']) ?>', '_blank');
                });
            }, 3000);
        }
    </script>
</body>
</html>
