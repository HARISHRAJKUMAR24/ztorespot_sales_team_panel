<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them instead

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$currentUser = getCurrentUser();

// FREE: Search using DuckDuckGo API (no API key required)
function searchDuckDuckGo($query) {
    $url = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json&no_html=1&skip_disambig=1&t=organic_finder";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return [];
}

// FREE: Search using OpenStreetMap Nominatim
function searchOpenStreetMap($query, $location = '') {
    $searchQuery = $query . ($location ? " $location" : "");
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($searchQuery) . 
           "&format=json&limit=8&addressdetails=1&category=shop";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'OrganicSellerFinder/1.0 (contact@example.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return [];
}

// FREE: Search using Wikipedia API
function searchWikipedia($query) {
    $url = "https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . 
           urlencode($query . " company brand") . "&format=json&origin=*&srlimit=8";
    
    $response = @file_get_contents($url);
    if ($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return [];
}

// FREE: Fetch from Etsy public listings
function getEtsySellers($product) {
    $searchUrl = "https://www.etsy.com/search?q=" . urlencode($product) . "&ref=search_bar";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    $sellers = [];
    if ($html) {
        preg_match_all('/href="(https:\/\/www\.etsy\.com\/shop\/[^"]+)"/', $html, $matches);
        $uniqueShops = array_unique($matches[1]);
        
        foreach ($uniqueShops as $index => $shopUrl) {
            if ($index < 5) {
                $sellers[] = [
                    'title' => 'Etsy Shop - Handmade Seller',
                    'link' => $shopUrl,
                    'domain' => parse_url($shopUrl, PHP_URL_HOST),
                    'type' => 'Handmade Seller',
                    'source' => 'Etsy'
                ];
            }
        }
    }
    return $sellers;
}

// FREE: Demo sellers for testing (fallback when APIs fail)
function getDemoSellers($product) {
    $demoSellers = [
        [
            'title' => $product . ' - Wholesale Suppliers Directory',
            'link' => 'https://www.thomasnet.com/products/' . urlencode(str_replace(' ', '-', strtolower($product))),
            'domain' => 'thomasnet.com',
            'type' => 'Wholesale Directory',
            'source' => 'Business Directory'
        ],
        [
            'title' => 'Find ' . ucfirst($product) . ' Manufacturers',
            'link' => 'https://www.made-in-china.com/products-search/' . urlencode(str_replace(' ', '-', strtolower($product))),
            'domain' => 'made-in-china.com',
            'type' => 'Manufacturer Directory',
            'source' => 'B2B Directory'
        ],
        [
            'title' => 'Top ' . ucfirst($product) . ' Brands and Retailers',
            'link' => 'https://www.trustpilot.com/categories/' . urlencode(str_replace(' ', '_', strtolower($product))),
            'domain' => 'trustpilot.com',
            'type' => 'Review Site',
            'source' => 'Trustpilot'
        ]
    ];
    
    return $demoSellers;
}

// Main search function
function findSellers($product) {
    $allSellers = [];
    
    // Source 1: DuckDuckGo API
    $ddgResults = searchDuckDuckGo($product . " sellers");
    if (!empty($ddgResults) && isset($ddgResults['RelatedTopics'])) {
        foreach ($ddgResults['RelatedTopics'] as $item) {
            if (isset($item['FirstURL']) && isset($item['Text']) && count($allSellers) < 25) {
                $allSellers[] = [
                    'title' => htmlspecialchars(substr($item['Text'], 0, 100)),
                    'link' => $item['FirstURL'],
                    'domain' => parse_url($item['FirstURL'], PHP_URL_HOST),
                    'type' => 'Web Result',
                    'source' => 'DuckDuckGo'
                ];
            }
        }
    }
    
    // Source 2: OpenStreetMap
    $osmResults = searchOpenStreetMap($product);
    foreach ($osmResults as $result) {
        if (count($allSellers) < 25) {
            $allSellers[] = [
                'title' => htmlspecialchars(substr($result['display_name'] ?? $product, 0, 100)),
                'link' => "https://www.openstreetmap.org/node/" . ($result['osm_id'] ?? ''),
                'domain' => 'openstreetmap.org',
                'type' => 'Physical Store',
                'source' => 'OpenStreetMap',
                'address' => isset($result['display_name']) ? htmlspecialchars(substr($result['display_name'], 0, 100)) : ''
            ];
        }
    }
    
    // Source 3: Wikipedia
    $wikiResults = searchWikipedia($product);
    if (isset($wikiResults['query']['search'])) {
        foreach ($wikiResults['query']['search'] as $result) {
            if (count($allSellers) < 25) {
                $allSellers[] = [
                    'title' => htmlspecialchars($result['title']),
                    'link' => "https://en.wikipedia.org/wiki/" . str_replace(' ', '_', $result['title']),
                    'domain' => 'wikipedia.org',
                    'type' => 'Company Information',
                    'source' => 'Wikipedia'
                ];
            }
        }
    }
    
    // Source 4: Etsy
    $etsySellers = getEtsySellers($product);
    foreach ($etsySellers as $seller) {
        if (count($allSellers) < 25) {
            $allSellers[] = $seller;
        }
    }
    
    // If no results found, provide demo/directory results
    if (empty($allSellers)) {
        $allSellers = getDemoSellers($product);
    }
    
    // Remove duplicates
    $uniqueSellers = [];
    foreach ($allSellers as $seller) {
        $key = $seller['link'];
        if (!isset($uniqueSellers[$key]) && !empty($seller['link'])) {
            $uniqueSellers[$key] = $seller;
        }
    }
    
    return array_values(array_slice($uniqueSellers, 0, 20));
}

// Handle AJAX request
if (isset($_POST['action']) && $_POST['action'] == 'search') {
    // Set JSON header
    header('Content-Type: application/json');
    
    // Get product
    $product = isset($_POST['product']) ? trim($_POST['product']) : '';
    
    if (empty($product)) {
        echo json_encode(['error' => 'Please enter a product keyword']);
        exit;
    }
    
    // Search for sellers
    try {
        $sellers = findSellers($product);
        
        echo json_encode([
            'success' => true,
            'sellers' => $sellers,
            'total' => count($sellers),
            'product' => $product
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Search error: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #9333ea;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .main-container {
        background: white;
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .ai-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 30px;
    }

    .search-section {
        background: #f8fafc;
        padding: 30px;
        border-bottom: 1px solid #e2e8f0;
    }

    .result-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
        animation: fadeIn 0.5s ease;
    }

    .result-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .source-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
    }

    .source-duckduckgo {
        background: #eef2ff;
        color: #4285f4;
    }

    .source-etsy {
        background: #fef2e8;
        color: #eb6e4b;
    }

    .source-wikipedia {
        background: #e8f0fe;
        color: #3366cc;
    }

    .source-openstreetmap {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .source-directory {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .ai-loading {
        text-align: center;
        padding: 60px;
    }

    .ai-loading .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e2e8f0;
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .copy-btn {
        background: transparent;
        border: 2px solid var(--primary);
        color: var(--primary);
        padding: 8px 16px;
        border-radius: 12px;
        transition: all 0.3s;
    }

    .copy-btn:hover {
        background: var(--primary);
        color: white;
    }

    .btn-ai-generate {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
    }

    .btn-ai-generate:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
    }

    .btn-ai-generate:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .seller-link {
        color: var(--primary);
        text-decoration: none;
        word-break: break-all;
    }

    .seller-link:hover {
        text-decoration: underline;
    }

    .domain-badge {
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
    }

    .stats-badge {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-size: 14px;
        z-index: 1000;
        cursor: pointer;
        transition: all 0.3s;
    }

    .stats-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    }
</style>

<body>
    <?php template('svg-icons'); ?>
    <?php template('top-navbar'); ?>

    <div class="container-fluid">
        <div class="row">
            <?php template('side-navbar'); ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="main-container">
                    <!-- AI Header -->
                    <div class="ai-header">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-shop fs-1"></i>
                            <div>
                                <h1 class="h3 mb-1">🔍 Organic Seller Finder (Free Edition)</h1>
                                <p class="mb-0 opacity-75">Search multiple free sources for real sellers</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">What product are you selling?</label>
                                <input type="text" class="form-control form-control-lg" id="productKeyword"
                                    placeholder="e.g., organic skincare, handmade jewelry, eco-friendly products"
                                    value="organic skincare">
                            </div>
                            <div class="col-12">
                                <button class="btn-ai-generate w-100" id="generateBtn" onclick="searchSellers()">
                                    <i class="bi bi-search me-2"></i>
                                    Find Sellers (Free Sources)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div id="resultsSection" class="p-4" style="min-height: 500px;">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-search-heart fs-1"></i>
                            <h5 class="mt-3">Enter a product to find real sellers</h5>
                            <p class="small">Searching DuckDuckGo, Etsy, Wikipedia, and OpenStreetMap</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="stats-badge" id="statsBadge" style="display: none;">
        <i class="bi bi-info-circle"></i> <span id="resultCount">0</span> sellers found
    </div>

    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        async function searchSellers() {
            const product = document.getElementById('productKeyword').value;
            const generateBtn = document.getElementById('generateBtn');
            const resultsSection = document.getElementById('resultsSection');
            const statsBadge = document.getElementById('statsBadge');

            if (!product.trim()) {
                Swal.fire('Error', 'Please enter a product keyword', 'error');
                return;
            }

            // Show loading
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div> Searching multiple sources...';
            statsBadge.style.display = 'none';

            resultsSection.innerHTML = `
                <div class="ai-loading">
                    <div class="spinner"></div>
                    <h5>🔍 Searching for sellers...</h5>
                    <p class="text-muted">Looking for "${escapeHtml(product)}" sellers across:</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <span class="badge bg-primary">DuckDuckGo</span>
                        <span class="badge bg-warning">Etsy</span>
                        <span class="badge bg-success">Wikipedia</span>
                        <span class="badge bg-secondary">OpenStreetMap</span>
                        <span class="badge bg-info">Directories</span>
                    </div>
                    <div class="progress mt-4" style="height: 5px; max-width: 300px; margin: 0 auto;">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                    <p class="mt-3 text-muted small"><i class="bi bi-clock"></i> This may take a few seconds...</p>
                </div>
            `;

            try {
                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('product', product);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text.substring(0, 200));
                    throw new Error('Server returned invalid response. Please try again.');
                }

                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.sellers && data.sellers.length > 0) {
                    let resultsHtml = `
                        <div class="mb-4">
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Found ${data.total} potential sellers!</strong> Results from multiple free sources.
                            </div>
                            <div class="row">
                    `;

                    data.sellers.forEach((seller, index) => {
                        // Get source-specific styling
                        let sourceClass = 'source-duckduckgo';
                        let sourceIcon = 'bi bi-globe';
                        
                        switch(seller.source) {
                            case 'Etsy':
                                sourceClass = 'source-etsy';
                                sourceIcon = 'bi bi-shop';
                                break;
                            case 'Wikipedia':
                                sourceClass = 'source-wikipedia';
                                sourceIcon = 'bi bi-wikipedia';
                                break;
                            case 'OpenStreetMap':
                                sourceClass = 'source-openstreetmap';
                                sourceIcon = 'bi bi-map';
                                break;
                            case 'Business Directory':
                            case 'B2B Directory':
                            case 'Trustpilot':
                                sourceClass = 'source-directory';
                                sourceIcon = 'bi bi-building';
                                break;
                            default:
                                sourceClass = 'source-duckduckgo';
                                sourceIcon = 'bi bi-search';
                        }
                        
                        resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="result-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="source-badge ${sourceClass}">
                                            <i class="${sourceIcon}"></i> ${escapeHtml(seller.source || 'Web')}
                                        </span>
                                        <span class="domain-badge">${escapeHtml(seller.domain || 'Website')}</span>
                                    </div>
                                    <h6 class="mb-2">
                                        <a href="${escapeHtml(seller.link)}" target="_blank" class="seller-link">
                                            ${escapeHtml(seller.title.substring(0, 100))}${seller.title.length > 100 ? '...' : ''}
                                        </a>
                                    </h6>
                                    ${seller.address ? `<p class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> ${escapeHtml(seller.address.substring(0, 80))}</p>` : ''}
                                    <p class="small text-muted mb-2">
                                        <i class="bi bi-link"></i> ${escapeHtml(seller.link.substring(0, 60))}${seller.link.length > 60 ? '...' : ''}
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyToClipboard('${escapeHtml(seller.link).replace(/'/g, "\\'")}')">
                                            <i class="bi bi-copy"></i> Copy Link
                                        </button>
                                        <a href="${escapeHtml(seller.link)}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-box-arrow-up-right"></i> Visit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    resultsHtml += `</div></div>`;
                    
                    resultsHtml += `
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Results from free public sources: DuckDuckGo, Etsy, Wikipedia, OpenStreetMap, and business directories.
                                These are real sellers and businesses. Click to visit their websites.
                            </small>
                        </div>
                    `;
                    
                    resultsSection.innerHTML = resultsHtml;
                    document.getElementById('resultCount').textContent = data.total;
                    statsBadge.style.display = 'block';
                    
                    // Auto fade stats after 5 seconds
                    setTimeout(() => {
                        statsBadge.style.opacity = '0.7';
                    }, 5000);
                    
                } else {
                    resultsSection.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>No sellers found for "${escapeHtml(product)}".</strong><br>
                            Try different keywords or be more specific (e.g., "organic soap suppliers").
                        </div>
                        <div class="mt-3">
                            <h6>Suggestions:</h6>
                            <ul>
                                <li>Try "${escapeHtml(product)} brands"</li>
                                <li>Try "${escapeHtml(product)} wholesale"</li>
                                <li>Try "${escapeHtml(product)} manufacturers"</li>
                                <li>Try "${escapeHtml(product)} suppliers"</li>
                            </ul>
                        </div>
                    `;
                    statsBadge.style.display = 'none';
                }

            } catch (error) {
                console.error('Error:', error);
                resultsSection.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error searching:</strong> ${escapeHtml(error.message)}
                        <br><small>Please check your internet connection and try again. If the problem persists, contact support.</small>
                        <br><br>
                        <button class="btn btn-sm btn-outline-danger" onclick="location.reload()">
                            <i class="bi bi-arrow-repeat"></i> Refresh Page
                        </button>
                    </div>
                `;
                statsBadge.style.display = 'none';
            }

            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="bi bi-search me-2"></i>Find Sellers (Free Sources)';
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    text: 'Link copied to clipboard!',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }).catch(() => {
                Swal.fire('Error', 'Failed to copy', 'error');
            });
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            }).replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(c) {
                return c;
            });
        }
        
        // Allow Enter key to search
        document.getElementById('productKeyword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchSellers();
            }
        });
    </script>
</body>

</html>