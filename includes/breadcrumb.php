<?php
/**
 * Breadcrumb Navigation System
 * SEO-friendly breadcrumbs with schema.org markup
 */

/**
 * Generate breadcrumb HTML
 * @param array $items - Array of breadcrumb items [['name' => 'Home', 'url' => '/'], ...]
 * @return string HTML output
 */
function renderBreadcrumb($items = []) {
    if (empty($items)) {
        return '';
    }
    
    // Add home by default if not present
    if (!isset($items[0]) || $items[0]['name'] !== 'Home') {
        array_unshift($items, ['name' => 'Home', 'url' => SITE_URL . '/index.php']);
    }
    
    $breadcrumbHTML = '';
    $breadcrumbHTML .= '<nav aria-label="breadcrumb" class="breadcrumb-nav">';
    $breadcrumbHTML .= '<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    $totalItems = count($items);
    foreach ($items as $index => $item) {
        $position = $index + 1;
        $isLast = ($index === $totalItems - 1);
        
        $breadcrumbHTML .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if ($isLast) {
            // Last item - no link
            $breadcrumbHTML .= '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span>';
        } else {
            // Link item
            $breadcrumbHTML .= '<a href="' . htmlspecialchars($item['url']) . '" itemprop="item">';
            $breadcrumbHTML .= '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span>';
            $breadcrumbHTML .= '</a>';
        }
        
        $breadcrumbHTML .= '<meta itemprop="position" content="' . $position . '">';
        $breadcrumbHTML .= '</li>';
    }
    
    $breadcrumbHTML .= '</ol>';
    $breadcrumbHTML .= '</nav>';
    
    return $breadcrumbHTML;
}

/**
 * Get breadcrumb for post page
 */
function getPostBreadcrumb($post) {
    $items = [];
    
    // Add category if exists
    if (!empty($post['category_name'])) {
        $items[] = [
            'name' => $post['category_name'],
            'url' => SITE_URL . '/category/' . $post['category_slug']
        ];
    }
    
    // Add post title (current page)
    $items[] = [
        'name' => $post['title'],
        'url' => '' // No URL for current page
    ];
    
    return $items;
}

/**
 * Get breadcrumb for category page
 */
function getCategoryBreadcrumb($category) {
    $items = [];
    
    // Add parent category if exists
    if (!empty($category['parent_id']) && !empty($category['parent_name'])) {
        $items[] = [
            'name' => $category['parent_name'],
            'url' => SITE_URL . '/category/' . $category['parent_slug']
        ];
    }
    
    // Add current category
    $items[] = [
        'name' => $category['name'],
        'url' => '' // No URL for current page
    ];
    
    return $items;
}

/**
 * Get breadcrumb for search page
 */
function getSearchBreadcrumb($query) {
    return [
        ['name' => 'Search Results', 'url' => ''],
        ['name' => '"' . $query . '"', 'url' => '']
    ];
}

/**
 * Breadcrumb CSS Styles
 */
function breadcrumbStyles() {
    return '
    <style>
    .breadcrumb-nav {
        background: transparent;
        padding: 15px 0;
        margin-bottom: 20px;
    }
    
    .breadcrumb {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 10px;
        padding: 12px 20px;
        margin-bottom: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        font-size: 0.9rem;
    }
    
    .breadcrumb-item {
        color: #64748b;
        font-weight: 500;
    }
    
    .breadcrumb-item a {
        color: #3b82f6;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    
    .breadcrumb-item a:hover {
        color: #1e40af;
        text-decoration: underline;
    }
    
    .breadcrumb-item.active {
        color: #1f2937;
        font-weight: 600;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        color: #94a3b8;
        font-size: 1.2rem;
        font-weight: 400;
        padding: 0 10px;
    }
    
    .breadcrumb-item:first-child a::before {
        content: "\f015";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: 5px;
        font-size: 0.85rem;
    }
    
    @media (max-width: 768px) {
        .breadcrumb {
            padding: 10px 15px;
            font-size: 0.85rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            padding: 0 5px;
        }
        
        .breadcrumb-item span {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
    }
    </style>
    ';
}
