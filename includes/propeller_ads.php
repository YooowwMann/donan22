<?php
/**
 * ====================================
 * MONETAG (PROPELLER) ADS CONFIGURATION
 * Optimized Direct HTML Implementation
 * ====================================
 */

// Prevent direct access
if (!defined('ADMIN_ACCESS') && !function_exists('getSettings')) {
    if (!file_exists(__DIR__ . '/../config_modern.php')) {
        die('Unauthorized access');
    }
}

/**
 * Get current page type
 */
if (!function_exists('getPropellerPageType')) {
    function getPropellerPageType() {
        $currentFile = basename($_SERVER['PHP_SELF'], '.php');
        
        $pageTypes = [
            'index' => ['index', 'test_propeller', 'test_monetag_complete', 'test_direct_antiadblock', 'test_antiadblock', 'test_fixed_antiadblock'],
            'post' => ['post', 'download'],
            'go' => ['go'],
            'category' => ['category', 'categories', 'search'],
        ];
        
        foreach ($pageTypes as $type => $files) {
            if (in_array($currentFile, $files)) {
                return $type;
            }
        }
        
        return 'other';
    }
}

/**
 * Main Monetag zone configuration
 * Zone IDs from your Monetag dashboard
 */
if (!function_exists('getMontagZones')) {
    function getMontagZones() {
    return [
        // OnClick Popunder - Categories, Search, About ONLY (NO INDEX!)
        'onclick_index' => [
            'id' => '10021739',
            'name' => 'OnClick Popunder - Category',
            'type' => 'onclick',
            'domain' => '5gvci.com',
            'pages' => ['category', 'search', 'about'] // REMOVED 'index' and 'other'
        ],
        // OnClick Popunder - Posts & Go pages ONLY (NO INDEX!)
        'onclick_post' => [
            'id' => '10021730',
            'name' => 'OnClick Popunder - Post',
            'type' => 'onclick',
            'domain' => '5gvci.com',
            'pages' => ['post', 'go'] // REMOVED 'index' and 'other'
        ],
        // In-Page Push - All pages
        'inpage_push' => [
            'id' => '10021747',
            'name' => 'In-Page Push',
            'type' => 'inpage',
            'domain' => 'ueuee.com',
            'pages' => ['index', 'post', 'go', 'category', 'search', 'about', 'other']
        ],
        // Push Notifications - All pages
        'push_notification' => [
            'id' => '10021743',
            'name' => 'Push Notifications',
            'type' => 'push',
            'domain' => '5gvci.com',
            'pages' => ['index', 'post', 'category', 'go', 'search', 'about', 'other']
        ],
        // Vignette Banner - All pages
        'vignette' => [
            'id' => '10021755',
            'name' => 'Vignette Banner',
            'type' => 'vignette',
            'domain' => 'gizokraijaw.net',
            'pages' => ['index', 'post', 'go', 'category', 'search', 'about', 'other']
        ]
    ];
    }
}

/**
 * Render Monetag script directly
 */
if (!function_exists('renderMontagZone')) {
    function renderMontagZone($zone) {
    $zoneId = $zone['id'];
    $domain = $zone['domain'];
    $type = $zone['type'];
    $name = $zone['name'];
    
    echo "\n<!-- Monetag: {$name} (Zone {$zoneId}) -->\n";
    
    switch ($type) {
        case 'onclick':
            // OnClick Popunder - Use Anti-AdBlock API
            $antiAdblockFile = __DIR__ . '/../assets/propeller/pa_antiadblock_' . $zoneId . '_SIMPLE.php';
            
            if (file_exists($antiAdblockFile)) {
                // Load script tag from Anti-AdBlock API
                $scriptTag = include($antiAdblockFile);
                if (!empty($scriptTag)) {
                    echo $scriptTag . "\n";
                } else {
                    // Fallback to direct script
                    echo "<script type='text/javascript' src='https://{$domain}/pfe/current/tag.min.js?z={$zoneId}' data-cfasync='false' async></script>\n";
                }
            } else {
                // Fallback to direct script if anti-adblock file not found
                echo "<script type='text/javascript' src='https://{$domain}/pfe/current/tag.min.js?z={$zoneId}' data-cfasync='false' async></script>\n";
            }
            break;
            
        case 'inpage':
            // In-Page Push - Use Anti-AdBlock API
            $antiAdblockFile = __DIR__ . '/../assets/propeller/pa_antiadblock_' . $zoneId . '_SIMPLE.php';
            
            if (file_exists($antiAdblockFile)) {
                // Load script tag from Anti-AdBlock API
                $scriptTag = include($antiAdblockFile);
                if (!empty($scriptTag)) {
                    echo $scriptTag . "\n";
                } else {
                    // Fallback to direct script
                    echo "<script>(function(d,z,s){s.src='https://'+d+'/400/'+z;try{(document.body||document.documentElement).appendChild(s)}catch(e){}})";
                    echo "('{$domain}',{$zoneId},document.createElement('script'))</script>\n";
                }
            } else {
                // Fallback to direct script if anti-adblock file not found
                echo "<script>(function(d,z,s){s.src='https://'+d+'/400/'+z;try{(document.body||document.documentElement).appendChild(s)}catch(e){}})";
                echo "('{$domain}',{$zoneId},document.createElement('script'))</script>\n";
            }
            break;
            
        case 'push':
            // Push Notifications - Service Worker based
            echo "<script>(function(s,u,z,p){s.src=u,s.setAttribute('data-zone',z),p.appendChild(s);})";
            echo "(document.createElement('script'),'https://{$domain}/400/{$zoneId}',{$zoneId},document.body||document.documentElement)</script>\n";
            break;
            
        case 'vignette':
            // Vignette Banner - Simple Direct Load (FIXED)
            echo "<script type=\"text/javascript\">\n";
            echo "(function() {\n";
            echo "    var s = document.createElement('script');\n";
            echo "    s.dataset.zone = '{$zoneId}';\n";
            echo "    s.src = 'https://{$domain}/vignette.min.js';\n";
            echo "    s.type = 'text/javascript';\n";
            echo "    (document.body || document.documentElement).appendChild(s);\n";
            echo "})();\n";
            echo "</script>\n";
            break;
            
        case 'native':
            // Native Banner - Async display
            echo "<script async=\"async\" data-cfasync=\"false\" src=\"https://{$domain}/a/display.php?r={$zoneId}\"></script>\n";
            break;
    }
    }
}

/**
 * Load Monetag ads based on page type
 */
if (!function_exists('loadPropellerAds')) {
    function loadPropellerAds($pageType = null) {
    if ($pageType === null) {
        $pageType = getPropellerPageType();
    }
    
    $zones = getMontagZones();
    
    // Load zones for current page
    foreach ($zones as $key => $zone) {
        if (in_array($pageType, $zone['pages'])) {
            renderMontagZone($zone);
        }
    }
    }
}

/**
 * Main execution
 */
if (!function_exists('propeller_ads_loaded')) {
    function propeller_ads_loaded() {
        return true;
    }
}

// Auto-load ads if not in admin area
if (!defined('ADMIN_ACCESS')) {
    loadPropellerAds();
}
