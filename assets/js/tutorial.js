/**
 * Table of Contents Generator for Tutorial Articles
 * Auto-generates TOC from h2, h3, h4 headings
 */

document.addEventListener('DOMContentLoaded', function() {
    const tutorialContent = document.querySelector('.tutorial-content');
    const tocContainer = document.querySelector('.table-of-contents');
    
    if (!tutorialContent || !tocContainer) return;
    
    // Get all headings
    const headings = tutorialContent.querySelectorAll('h2, h3, h4');
    
    if (headings.length === 0) return;
    
    // Create main TOC list
    const mainList = document.createElement('ul');
    let currentH2Item = null;
    let currentH3Item = null;
    
    headings.forEach((heading, index) => {
        // Add ID to heading if it doesn't have one
        if (!heading.id) {
            heading.id = 'heading-' + index;
        }
        
        // Create TOC item
        const tocItem = document.createElement('li');
        const tocLink = document.createElement('a');
        
        tocLink.href = '#' + heading.id;
        tocLink.textContent = heading.textContent.replace(/^[📌🎯💡✨]/, '').trim();
        
        tocItem.appendChild(tocLink);
        
        // Build nested structure
        if (heading.tagName === 'H2') {
            // Top level item
            mainList.appendChild(tocItem);
            currentH2Item = tocItem;
            currentH3Item = null;
        } else if (heading.tagName === 'H3') {
            // Create nested ul if needed
            if (currentH2Item) {
                let nestedList = currentH2Item.querySelector('ul');
                if (!nestedList) {
                    nestedList = document.createElement('ul');
                    currentH2Item.appendChild(nestedList);
                }
                nestedList.appendChild(tocItem);
                currentH3Item = tocItem;
            } else {
                mainList.appendChild(tocItem);
                currentH3Item = tocItem;
            }
        } else if (heading.tagName === 'H4') {
            // Deeper nested level
            if (currentH3Item) {
                let nestedList = currentH3Item.querySelector('ul');
                if (!nestedList) {
                    nestedList = document.createElement('ul');
                    currentH3Item.appendChild(nestedList);
                }
                nestedList.appendChild(tocItem);
            } else if (currentH2Item) {
                let nestedList = currentH2Item.querySelector('ul');
                if (!nestedList) {
                    nestedList = document.createElement('ul');
                    currentH2Item.appendChild(nestedList);
                }
                nestedList.appendChild(tocItem);
            } else {
                mainList.appendChild(tocItem);
            }
        }
    });
    
    // Insert TOC after title
    const tocTitle = tocContainer.querySelector('.toc-title');
    if (tocTitle) {
        tocTitle.after(mainList);
    } else {
        tocContainer.appendChild(mainList);
    }
    
    // Smooth scroll
    const tocLinks = mainList.querySelectorAll('a');
    tocLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const offset = 100; // Fixed header offset
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
    let isScrolling = false;
    window.addEventListener('scroll', function() {
        if (!isScrolling) {
            window.requestAnimationFrame(function() {
                updateActiveTOC();
                isScrolling = false;
            });
            isScrolling = true;
        }
    });
    
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
    
    // Initial active state
    updateActiveTOC();
});

/**
 * Copy Code Button Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const codeBlocks = document.querySelectorAll('.tutorial-content pre code');
    
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
                        console.error('Failed to copy code:', err);
                        alert('Failed to copy code. Please copy manually.');
                    });
                });
            }
        }
    });
});

/**
 * Image Modal/Lightbox
 */
document.addEventListener('DOMContentLoaded', function() {
    const tutorialImages = document.querySelectorAll('.tutorial-content img');
    
    tutorialImages.forEach(img => {
        img.addEventListener('click', function() {
            // Create modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                cursor: pointer;
            `;
            
            const modalImg = document.createElement('img');
            modalImg.src = this.src;
            modalImg.alt = this.alt;
            modalImg.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                border-radius: 8px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            `;
            
            modal.appendChild(modalImg);
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            // Close on click
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
                document.body.style.overflow = '';
            });
            
            // Close on ESC key
            const closeOnEsc = function(e) {
                if (e.key === 'Escape') {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                        document.body.style.overflow = '';
                    }
                    document.removeEventListener('keydown', closeOnEsc);
                }
            };
            document.addEventListener('keydown', closeOnEsc);
        });
    });
});

/**
 * Reading Progress Bar
 */
document.addEventListener('DOMContentLoaded', function() {
    const tutorialContent = document.querySelector('.tutorial-content');
    
    if (!tutorialContent) return;
    
    // Create progress bar
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        z-index: 9999;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);
    
    // Update progress on scroll
    window.addEventListener('scroll', function() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
        progressBar.style.width = scrollPercent + '%';
    });
});

/**
 * Auto-number Tutorial Steps
 */
document.addEventListener('DOMContentLoaded', function() {
    const tutorialSteps = document.querySelectorAll('.tutorial-step');
    
    tutorialSteps.forEach((step, index) => {
        if (!step.hasAttribute('data-step')) {
            step.setAttribute('data-step', index + 1);
        }
    });
});
