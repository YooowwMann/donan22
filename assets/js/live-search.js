/**
 * Live Search JavaScript
 * Auto-complete search with AJAX
 */

(function() {
    'use strict';
    
    let searchTimeout;
    const searchInput = document.getElementById('live-search-input');
    const searchResults = document.getElementById('live-search-results');
    const searchForm = document.getElementById('searchForm');
    const searchButton = document.getElementById('searchButton');
    
    if (!searchInput || !searchResults) return;
    
    // Handle form submit (Enter key or button click)
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            
            // If query is empty, prevent submit
            if (query.length === 0) {
                e.preventDefault();
                searchInput.focus();
                return false;
            }
            
            // If query is less than 2 characters, go to search page anyway
            if (query.length < 2) {
                return true; // Allow form submit
            }
            
            // Hide dropdown when submitting
            hideResults();
            
            // Allow form to submit normally to search.php
            return true;
        });
    }
    
    // Handle search button click
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            const query = searchInput.value.trim();
            if (query.length > 0) {
                // Let form handle the submit
                searchForm.submit();
            } else {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
    
    // Debounce search for live results
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideResults();
            return;
        }
        
        // Show loading
        searchResults.innerHTML = '<div class="live-search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        searchResults.classList.add('show');
        
        // Delay search to avoid too many requests
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Handle Enter key in input (will trigger form submit)
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query.length > 0) {
                // Hide dropdown and let form submit
                hideResults();
            }
        }
    });
    
    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            hideResults();
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideResults();
            searchInput.blur();
        }
    });
    
    function performSearch(query) {
        // Use absolute path from domain root
        const pathArray = window.location.pathname.split('/');
        const baseFolder = pathArray[1]; // e.g., 'donan22'
        const apiUrl = `/${baseFolder}/api/search-live.php`;
        
        fetch(`${apiUrl}?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    displayNoResults(query);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="live-search-error"><i class="fas fa-exclamation-triangle"></i> Search error occurred</div>';
            });
    }
    
    function displayResults(results) {
        let html = '';
        
        results.forEach(result => {
            const typeIcon = getTypeIcon(result.type_slug);
            const typeColor = getTypeColor(result.type_slug);
            
            html += `
                <a href="${result.url}" class="live-search-item">
                    <div class="live-search-icon" style="background: ${typeColor}">
                        <i class="${typeIcon}"></i>
                    </div>
                    <div class="live-search-content">
                        <h6>${highlightQuery(result.title, searchInput.value)}</h6>
                        <p>${result.excerpt}</p>
                        <div class="live-search-meta">
                            <span class="badge bg-secondary">${result.category}</span>
                            <span class="ms-2 text-muted"><i class="far fa-eye"></i> ${result.views}</span>
                        </div>
                    </div>
                    <div class="live-search-thumb">
                        <img src="${result.image}" alt="${result.title}" onerror="this.src='assets/images/placeholder.svg'">
                    </div>
                </a>
            `;
        });
        
        html += `
            <div class="live-search-footer">
                <a href="search.php?q=${encodeURIComponent(searchInput.value)}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-search me-2"></i>View All Results
                </a>
            </div>
        `;
        
        searchResults.innerHTML = html;
        searchResults.classList.add('show');
    }
    
    function displayNoResults(query) {
        searchResults.innerHTML = `
            <div class="live-search-no-results">
                <i class="fas fa-search mb-2"></i>
                <p>No results found for "<strong>${escapeHtml(query)}</strong>"</p>
                <a href="search.php?q=${encodeURIComponent(query)}" class="btn btn-sm btn-outline-secondary">
                    Try Advanced Search
                </a>
            </div>
        `;
        searchResults.classList.add('show');
    }
    
    function hideResults() {
        searchResults.classList.remove('show');
    }
    
    function highlightQuery(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    function getTypeIcon(typeSlug) {
        const icons = {
            'software': 'fas fa-desktop',
            'game': 'fas fa-gamepad',
            'mobile-app': 'fas fa-mobile-alt',
            'tutorial': 'fas fa-graduation-cap',
            'guide': 'fas fa-book'
        };
        return icons[typeSlug] || 'fas fa-file-alt';
    }
    
    function getTypeColor(typeSlug) {
        const colors = {
            'software': '#3b82f6',
            'game': '#ef4444',
            'mobile-app': '#10b981',
            'tutorial': '#f59e0b',
            'guide': '#8b5cf6'
        };
        return colors[typeSlug] || '#6b7280';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function escapeRegex(text) {
        return text.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    }
})();
