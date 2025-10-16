    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Common Admin Scripts -->
    <script>
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');
            const adminHeader = document.getElementById('adminHeader');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('show');
                    mainContent.classList.toggle('sidebar-open');
                    adminHeader.classList.toggle('sidebar-open');
                }
            });
            
            // Close sidebar on overlay click (mobile)
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Auto-adjust sidebar on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.add('show');
                    sidebarOverlay.classList.remove('show');
                    mainContent.classList.add('sidebar-open');
                    adminHeader.classList.add('sidebar-open');
                } else {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('sidebar-open');
                    adminHeader.classList.remove('sidebar-open');
                }
            });
            
            // Initialize sidebar state
            if (window.innerWidth >= 768) {
                sidebar.classList.add('show');
                mainContent.classList.add('sidebar-open');
                adminHeader.classList.add('sidebar-open');
            }
            
            // Loading overlay functions
            window.showLoading = function() {
                loadingOverlay.classList.add('show');
            };
            
            window.hideLoading = function() {
                loadingOverlay.classList.remove('show');
            };
            
            // Auto-hide loading on page load
            window.addEventListener('load', function() {
                setTimeout(hideLoading, 500);
            });
            
            // Form validation helper
            window.validateForm = function(formElement) {
                const inputs = formElement.querySelectorAll('[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        valid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                return valid;
            };
            
            // Auto-save functionality
            window.enableAutoSave = function(formElement, saveUrl) {
                const inputs = formElement.querySelectorAll('input, textarea, select');
                let autoSaveTimeout;
                
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        clearTimeout(autoSaveTimeout);
                        autoSaveTimeout = setTimeout(() => {
                            const formData = new FormData(formElement);
                            formData.append('auto_save', '1');
                            
                            fetch(saveUrl, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showToast('Auto-saved', 'success');
                                }
                            })
                            .catch(error => console.log('Auto-save error:', error));
                        }, 2000);
                    });
                });
            };
            
            // Toast notification
            window.showToast = function(message, type = 'info', duration = 3000) {
                const toastContainer = document.getElementById('toastContainer') || createToastContainer();
                
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0`;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                toastContainer.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast, { delay: duration });
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', () => {
                    toast.remove();
                });
            };
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(container);
                return container;
            }
            
            // Confirm dialog
            window.confirmAction = function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            };
            
            // AJAX helper
            window.ajaxRequest = function(url, data, method = 'POST') {
                return fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: method === 'GET' ? null : JSON.stringify(data)
                })
                .then(response => response.json());
            };
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Handle form submissions with loading
            document.addEventListener('submit', function(e) {
                if (e.target.classList.contains('ajax-form')) {
                    e.preventDefault();
                    showLoading();
                    
                    const formData = new FormData(e.target);
                    fetch(e.target.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showToast(data.message || 'Operation successful', 'success');
                            if (data.redirect) {
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 1000);
                            }
                        } else {
                            showToast(data.message || 'Operation failed', 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showToast('An error occurred', 'error');
                        console.error('Error:', error);
                    });
                }
            });
        });
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            <?php if (DEBUG_MODE): ?>
            showToast('JavaScript error occurred. Check console for details.', 'error');
            <?php endif; ?>
        });
        
        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            <?php if (DEBUG_MODE): ?>
            showToast('Promise rejection occurred. Check console for details.', 'error');
            <?php endif; ?>
        });
    </script>
    
    <!-- Page-specific scripts will be inserted here -->
    <?php if (isset($pageScripts)): ?>
        <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>