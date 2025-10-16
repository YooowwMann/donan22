/**
 * Blog & Tutorial JavaScript Features
 * Reading progress, TOC, smooth scroll, copy code
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==================== TABLE OF CONTENTS ====================
    const blogContent = document.querySelector('.blog-content');
    const tocContainer = document.querySelector('.blog-toc-list');
    
    if (blogContent && tocContainer) {
        // Get all headings
        const headings = blogContent.querySelectorAll('h2, h3');
        
        if (headings.length > 0) {
            headings.forEach((heading, index) => {
                // Add ID if not exists
                if (!heading.id) {
                    heading.id = 'section-' + index;
                }
                
                // Create TOC item
                const li = document.createElement('li');
                const a = document.createElement('a');
                
                a.href = '#' + heading.id;
                a.textContent = heading.textContent.replace(/^[📌🎯💡✨]/g, '').trim();
                a.className = heading.tagName === 'H3' ? 'toc-h3' : '';
                
                li.appendChild(a);
                tocContainer.appendChild(li);
            });
            
            // Smooth scroll
            const tocLinks = tocContainer.querySelectorAll('a');
            tocLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        const offset = 100;
                        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - offset;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                        
                        // Update active state
                        tocLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });
            
            // Highlight active section on scroll
            function updateActiveTOC() {
                const scrollPosition = window.pageYOffset + 150;
                
                let currentActiveHeading = null;
                headings.forEach(heading => {
                    const headingTop = heading.getBoundingClientRect().top + window.pageYOffset;
                    if (headingTop <= scrollPosition) {
                        currentActiveHeading = heading;
                    }
                });
                
                tocLinks.forEach(link => {
                    link.classList.remove('active');
                    if (currentActiveHeading && link.getAttribute('href') === '#' + currentActiveHeading.id) {
                        link.classList.add('active');
                    }
                });
            }
            
            // Throttle scroll event
            let ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        updateActiveTOC();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
            
            // Initial active state
            updateActiveTOC();
        }
    }
    
    // ==================== READING PROGRESS BAR ====================
    if (blogContent) {
        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        document.body.prepend(progressBar);
        
        // Update progress on scroll
        window.addEventListener('scroll', function() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
            progressBar.style.width = Math.min(scrollPercent, 100) + '%';
        });
    }
    
    // ==================== COPY CODE BUTTON ====================
    const codeBlocks = document.querySelectorAll('.blog-content pre code');
    
    codeBlocks.forEach((codeBlock) => {
        const pre = codeBlock.parentElement;
        const codeHeader = pre.previousElementSibling;
        
        if (codeHeader && codeHeader.classList.contains('code-header')) {
            const copyBtn = codeHeader.querySelector('.copy-code-btn');
            
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const code = codeBlock.textContent;
                    
                    navigator.clipboard.writeText(code).then(() => {
                        const originalText = copyBtn.textContent;
                        copyBtn.textContent = '✓ Copied!';
                        copyBtn.style.background = '#10b981';
                        
                        setTimeout(() => {
                            copyBtn.textContent = originalText;
                            copyBtn.style.background = '';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy:', err);
                        copyBtn.textContent = 'Failed!';
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                        }, 2000);
                    });
                });
            }
        }
    });
    
    // ==================== SMOOTH SCROLL FOR ALL ANCHORS ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offset = 100;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // ==================== LAZY LOAD IMAGES ====================
    const images = document.querySelectorAll('.blog-content img');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // ==================== ESTIMATED READING TIME ====================
    if (blogContent) {
        const text = blogContent.textContent;
        const wordCount = text.trim().split(/\s+/).length;
        const readingTime = Math.ceil(wordCount / 200); // Average reading speed: 200 words/min
        
        const readingTimeEl = document.querySelector('.reading-time');
        if (readingTimeEl) {
            readingTimeEl.textContent = readingTime + ' min read';
        }
    }
    
    // ==================== BACK TO TOP BUTTON ====================
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'back-to-top';
    backToTop.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        transition: all 0.3s;
        z-index: 1000;
        font-size: 1.2rem;
    `;
    
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    backToTop.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.boxShadow = '0 8px 20px rgba(102, 126, 234, 0.4)';
    });
    
    backToTop.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.3)';
    });
});
