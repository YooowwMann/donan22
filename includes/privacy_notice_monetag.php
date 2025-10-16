<!-- ========================================
     MONETAG PRIVACY NOTICE
     Add this to footer or create privacy-notice.php
     ======================================== -->

<!-- Option 1: Simple Footer Notice (Recommended) -->
<div class="monetag-privacy-notice" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-1 text-center mb-2 mb-md-0">
                <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
            </div>
            <div class="col-md-11">
                <p class="mb-0" style="font-size: 14px; line-height: 1.6;">
                    <strong><i class="fas fa-info-circle me-1"></i> Privacy Notice:</strong> 
                    Website ini menggunakan cookies dan partner iklan (Monetag) untuk monetisasi konten. 
                    Dengan melanjutkan menggunakan situs ini, Anda menyetujui penggunaan cookies untuk tujuan periklanan. 
                    Kami tidak mengumpulkan informasi pribadi tanpa persetujuan Anda.
                    <a href="/privacy-policy.php" style="color: #fff; text-decoration: underline; font-weight: bold;">Baca Kebijakan Privasi Lengkap</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Option 2: Compact Banner Version -->
<div class="monetag-privacy-banner" style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 12px 20px; margin: 15px 0;">
    <p style="margin: 0; font-size: 13px; color: #333;">
        <i class="fas fa-cookie-bite" style="color: #667eea;"></i> 
        <strong>Cookie Notice:</strong> 
        Situs ini menggunakan cookies dan iklan pihak ketiga (Monetag) untuk mendukung konten gratis. 
        <a href="/privacy-policy.php" style="color: #667eea; font-weight: bold;">Pelajari lebih lanjut</a>
    </p>
</div>

<!-- Option 3: Popup Modal (First Visit) -->
<script>
    // Check if user already accepted
    if (!localStorage.getItem('monetag_privacy_accepted')) {
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.createElement('div');
            modal.id = 'privacyModal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; align-items: center; justify-content: center;';
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 30px; max-width: 600px; margin: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-cookie-bite" style="font-size: 3rem; color: #667eea;"></i>
                    </div>
                    <h3 style="color: #333; margin-bottom: 15px; text-align: center;">
                        <i class="fas fa-shield-alt" style="color: #667eea;"></i> Privacy & Cookie Notice
                    </h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
                        Kami menggunakan <strong>cookies</strong> dan <strong>partner iklan (Monetag)</strong> untuk:
                    </p>
                    <ul style="color: #666; font-size: 14px; line-height: 1.8; margin-bottom: 20px; padding-left: 30px;">
                        <li>Menyediakan konten gratis berkualitas</li>
                        <li>Meningkatkan pengalaman browsing Anda</li>
                        <li>Menampilkan iklan yang relevan</li>
                        <li>Mendukung operasional website</li>
                    </ul>
                    <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
                        <strong>Kami TIDAK mengumpulkan:</strong> Informasi pribadi sensitif seperti password, data kartu kredit, atau informasi pribadi lainnya.
                    </p>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 13px; color: #555;">
                            <i class="fas fa-info-circle" style="color: #667eea;"></i> 
                            Dengan melanjutkan, Anda menyetujui penggunaan cookies sesuai 
                            <a href="/privacy-policy.php" style="color: #667eea; font-weight: bold;">Kebijakan Privasi</a> kami.
                        </p>
                    </div>
                    <div style="text-align: center;">
                        <button id="acceptPrivacy" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 40px; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;">
                            <i class="fas fa-check-circle"></i> Saya Mengerti & Setuju
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('acceptPrivacy').addEventListener('click', function() {
                localStorage.setItem('monetag_privacy_accepted', 'true');
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => modal.remove(), 300);
            });
        });
    }
</script>

<style>
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    #acceptPrivacy:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }
</style>

<!-- Option 4: Bottom Sticky Bar (Cookie Banner Style) -->
<div id="cookieBanner" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; z-index: 99998; box-shadow: 0 -4px 20px rgba(0,0,0,0.2);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-9 mb-3 mb-md-0">
                <p style="margin: 0; font-size: 14px;">
                    <i class="fas fa-cookie-bite me-2" style="font-size: 1.5rem;"></i>
                    <strong>Cookie Notice:</strong> 
                    Website ini menggunakan cookies dan iklan (Monetag) untuk mendukung konten gratis. 
                    <a href="/privacy-policy.php" style="color: white; text-decoration: underline;">Pelajari lebih lanjut</a>
                </p>
            </div>
            <div class="col-md-3 text-end">
                <button id="acceptCookies" style="background: white; color: #667eea; border: none; padding: 10px 30px; border-radius: 50px; font-weight: bold; cursor: pointer;">
                    <i class="fas fa-check"></i> Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Show cookie banner if not accepted
    if (!localStorage.getItem('monetag_cookies_accepted')) {
        document.getElementById('cookieBanner').style.display = 'block';
    }
    
    document.getElementById('acceptCookies').addEventListener('click', function() {
        localStorage.setItem('monetag_cookies_accepted', 'true');
        document.getElementById('cookieBanner').style.display = 'none';
    });
</script>

<!-- ========================================
     IMPLEMENTATION INSTRUCTIONS
     ======================================== 
     
     Choose ONE option above and add to your site:
     
     1. FOOTER NOTICE (Recommended - Simple)
        - Add to footer section of all pages
        - Always visible, non-intrusive
        - Best for compliance without annoying users
     
     2. COMPACT BANNER
        - Add above or below content
        - Minimal space, still compliant
        - Good for mobile devices
     
     3. POPUP MODAL (Most Visible)
        - Shows on first visit only
        - Stores acceptance in localStorage
        - Best for ensuring users see it
        - Can be annoying if overused
     
     4. STICKY BOTTOM BAR (Cookie Banner Style)
        - Fixed at bottom of screen
        - Dismissible with button
        - Industry standard approach
        - Requires localStorage support
     
     RECOMMENDATION:
     Use Option 1 (Footer Notice) for permanent visibility
     + Option 4 (Sticky Bar) for first-time visitors
     
     This combination provides:
     - Immediate notification (sticky bar)
     - Ongoing compliance (footer notice)
     - User choice (dismissible)
     - Legal protection (always visible)
     
     ======================================== -->
