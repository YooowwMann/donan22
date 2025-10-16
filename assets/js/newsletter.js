/**
 * Newsletter Widget JavaScript
 * Handle newsletter subscription form
 */

(function() {
    'use strict';
    
    const newsletterForm = document.getElementById('newsletter-form');
    
    if (!newsletterForm) return;
    
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = this.querySelector('input[name="email"]');
        const submitBtn = this.querySelector('button[type="submit"]');
        const messageDiv = document.getElementById('newsletter-message');
        
        const email = emailInput.value.trim();
        
        if (!email || !isValidEmail(email)) {
            showMessage('Please enter a valid email address', 'danger');
            return;
        }
        
        // Disable button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subscribing...';
        
        // Send AJAX request
        fetch('subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                emailInput.value = '';
                
                // Track conversion (optional - Google Analytics)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'newsletter_signup', {
                        'event_category': 'engagement',
                        'event_label': 'Newsletter Subscription'
                    });
                }
            } else {
                showMessage(data.message, 'warning');
            }
        })
        .catch(error => {
            console.error('Newsletter subscription error:', error);
            showMessage('An error occurred. Please try again later.', 'danger');
        })
        .finally(() => {
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Subscribe';
        });
    });
    
    function showMessage(message, type) {
        const messageDiv = document.getElementById('newsletter-message');
        messageDiv.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            const alert = messageDiv.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
})();
