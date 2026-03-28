<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$currentUser = getCurrentUser();

// Function to scrape real Google search results
function scrapeGoogleSearch($query, $numResults = 10)
{
    $query = urlencode($query);
    $url = "https://www.google.com/search?q=" . $query . "&num=" . $numResults;

    // Initialize CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($html)) {
        return ['error' => 'Could not fetch Google results. Try again later.'];
    }

    // Parse results
    $results = [];

    // Extract titles and links
    preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $html, $titleMatches);
    preg_match_all('/<a[^>]*href="\/url\?q=([^"&]+)[^"]*"[^>]*>(.*?)<\/a>/i', $html, $linkMatches);

    for ($i = 0; $i < count($titleMatches[0]); $i++) {
        $title = strip_tags($titleMatches[0][$i]);
        $link = isset($linkMatches[1][$i]) ? urldecode($linkMatches[1][$i]) : '';

        if (!empty($title) && !empty($link) && strpos($link, 'google.com') === false) {
            // Extract domain
            $domain = parse_url($link, PHP_URL_HOST);

            // Check if it's a potential seller (e-commerce, shop, store, etc.)
            $isSeller = preg_match('/shop|store|product|buy|seller|market|amazon|flipkart|instagram|facebook/i', $title . $link);

            $results[] = [
                'title' => htmlspecialchars($title),
                'link' => $link,
                'domain' => $domain,
                'type' => $isSeller ? 'Seller' : 'Related'
            ];
        }
    }

    return array_slice($results, 0, $numResults);
}

// Function to search Instagram profiles
function scrapeInstagramSearch($query)
{
    $searchUrl = "https://www.instagram.com/explore/tags/" . urlencode(str_replace(' ', '', $query));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $html = curl_exec($ch);
    curl_close($ch);

    if (empty($html)) {
        return [];
    }

    // Extract hashtag posts (simplified - real Instagram scraping requires API)
    $results = [];
    preg_match_all('/<a[^>]*href="\/p\/([^"]+)"[^>]*>/i', $html, $matches);

    return $results;
}

// Handle AJAX request
if (isset($_POST['action']) && $_POST['action'] == 'search') {
    header('Content-Type: application/json');

    $product = isset($_POST['product']) ? trim($_POST['product']) : '';
    $platform = isset($_POST['platform']) ? $_POST['platform'] : 'google';

    if (empty($product)) {
        echo json_encode(['error' => 'Please enter a product keyword']);
        exit;
    }

    $results = [];

    if ($platform == 'google' || $platform == 'both') {
        $searchQueries = [
            $product . " sellers",
            $product . " shop",
            $product . " store",
            $product . " wholesale",
            $product . " suppliers",
            $product . " distributors"
        ];

        $allGoogleResults = [];
        foreach ($searchQueries as $query) {
            $googleResults = scrapeGoogleSearch($query, 5);
            if (!isset($googleResults['error'])) {
                $allGoogleResults = array_merge($allGoogleResults, $googleResults);
            }
        }

        // Remove duplicates
        $uniqueResults = [];
        foreach ($allGoogleResults as $result) {
            $key = $result['link'];
            if (!isset($uniqueResults[$key])) {
                $uniqueResults[$key] = $result;
            }
        }

        $results['google'] = array_values($uniqueResults);
    }

    if ($platform == 'instagram' || $platform == 'both') {
        $results['instagram'] = scrapeInstagramSearch($product);
    }

    echo json_encode($results);
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

    .platform-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
    }

    .platform-google {
        background: #eef2ff;
        color: #4285f4;
    }

    .platform-instagram {
        background: #fef2f2;
        color: #e4405f;
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
                            <i class="bi bi-robot fs-1"></i>
                            <div>
                                <h1 class="h3 mb-1">🔍 Real Organic Seller Finder</h1>
                                <p class="mb-0 opacity-75">Search real Google results for e-commerce sellers</p>
                            </div>
                        </div>
                    </div>

                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">What product are you selling?</label>
                                <input type="text" class="form-control form-control-lg" id="productKeyword"
                                    placeholder="e.g., organic skincare, handmade jewelry, eco-friendly products"
                                    value="organic skincare">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Search Platform</label>
                                <select class="form-select form-select-lg" id="platformSelect">
                                    <option value="google">Google Search (Real Results)</option>
                                    <option value="instagram">Instagram (Coming Soon)</option>
                                    <option value="both">Both (Google Only)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn-ai-generate w-100" id="generateBtn" onclick="searchRealLeads()">
                                    <i class="bi bi-search me-2"></i>
                                    Search Real Google Results
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div id="resultsSection" class="p-4" style="min-height: 500px;">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-search-heart fs-1"></i>
                            <h5 class="mt-3">Enter a product to find real sellers</h5>
                            <p class="small">This searches real Google results for potential e-commerce sellers</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        async function searchRealLeads() {
            const product = document.getElementById('productKeyword').value;
            const platform = document.getElementById('platformSelect').value;
            const generateBtn = document.getElementById('generateBtn');
            const resultsSection = document.getElementById('resultsSection');

            if (!product.trim()) {
                Swal.fire('Error', 'Please enter a product keyword', 'error');
                return;
            }

            // Show loading
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div> Searching Google...';

            resultsSection.innerHTML = `
                <div class="ai-loading">
                    <div class="spinner"></div>
                    <h5>🔍 Searching real Google results...</h5>
                    <p class="text-muted">Looking for "${product}" sellers on Google</p>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: 0%" id="aiProgress"></div>
                    </div>
                </div>
            `;

            // Progress animation
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 10;
                const progressBar = document.getElementById('aiProgress');
                if (progressBar) progressBar.style.width = progress + '%';
                if (progress >= 100) clearInterval(progressInterval);
            }, 200);

            try {
                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('product', product);
                formData.append('platform', platform);

                const response = await fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                clearInterval(progressInterval);

                if (data.error) {
                    throw new Error(data.error);
                }

                let resultsHtml = '';

                // Google Results
                if (data.google && data.google.length > 0) {
                    resultsHtml += `
                        <div class="mb-4">
                            <h4 class="mb-3">
                                <i class="bi bi-google text-primary me-2"></i>
                                Real Google Search Results
                                <span class="badge bg-primary ms-2">${data.google.length} Found</span>
                            </h4>
                            <div class="row">
                    `;

                    data.google.forEach((result, index) => {
                        resultsHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="result-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="platform-badge platform-google">
                                            <i class="bi bi-google"></i> Google
                                        </span>
                                        <span class="domain-badge">${result.domain || 'Website'}</span>
                                    </div>
                                    <h6 class="mb-2">
                                        <a href="${result.link}" target="_blank" class="seller-link">
                                            ${result.title}
                                        </a>
                                    </h6>
                                    <p class="small text-muted mb-2">
                                        <i class="bi bi-link"></i> ${result.link.substring(0, 60)}${result.link.length > 60 ? '...' : ''}
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary copy-btn" onclick="copyToClipboard('${result.link.replace(/'/g, "\\'")}')">
                                            <i class="bi bi-copy"></i> Copy Link
                                        </button>
                                        <a href="${result.link}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-box-arrow-up-right"></i> Visit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    resultsHtml += `</div></div>`;
                } else {
                    resultsHtml += `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No results found for "${product}". Try different keywords.
                        </div>
                    `;
                }

                resultsSection.innerHTML = `
                    <div class="mb-3">
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Search Complete!</strong> Found real Google results for "${product}"
                        </div>
                    </div>
                    ${resultsHtml}
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            These are real Google search results. Click the links to visit seller websites.
                        </small>
                    </div>
                `;

            } catch (error) {
                console.error('Error:', error);
                resultsSection.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Error searching: ${error.message}
                        <br><small>Note: Google may block automated requests. Try again in a few minutes.</small>
                    </div>
                `;
            }

            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="bi bi-search me-2"></i>Search Real Google Results';
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
    </script>
</body>

</html>