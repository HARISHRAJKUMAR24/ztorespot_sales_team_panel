<header class="navbar sticky-top bg-dark flex-md-nowrap p-0 shadow" data-bs-theme="dark">
    <!-- Left side - Logo and Company Name -->
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 text-white d-flex align-items-center gap-2" href="#">
        <img src="<?= ASSETS_URL ?>images/logo.png" alt="Logo" height="30" class="d-inline-block align-text-top">
        <span>Sales Team</span>
    </a>
    
    <!-- Right side - Username with Profile -->
    <div class="d-flex ms-auto me-3 align-items-center">
        <?php
        $user_uid = $_SESSION['user_uid'] ?? '';
        $user = getUserByUid($user_uid);
        $profileImage = $user['profile_image'] ?? '';
        $userName = $user['name'] ?? 'User';
        ?>
        
        <!-- Username visible on larger screens -->
        <span class="text-white me-3 d-none d-lg-inline">
            <strong><?= htmlspecialchars(explode(' ', $userName)[0]) ?></strong>
        </span>
        
        <!-- Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-link text-white p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($profileImage) && file_exists(__DIR__ . '/../' . $profileImage)): ?>
                    <img src="<?= BASE_URL . $profileImage ?>" alt="Profile" width="35" height="35" class="rounded-circle border border-2 border-light">
                <?php else: ?>
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 35px; height: 35px; font-size: 16px;">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                <?php endif; ?>
               
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 260px;">
                <!-- User Info Header -->
                <li class="dropdown-header">
                    <div class="d-flex align-items-center gap-3 px-2 py-2">
                        <?php if (!empty($profileImage) && file_exists(__DIR__ . '/../' . $profileImage)): ?>
                            <img src="<?= BASE_URL . $profileImage ?>" alt="Profile" width="48" height="48" class="rounded-circle">
                        <?php else: ?>
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 48px; height: 48px; font-size: 20px;">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-bold fs-6"><?= htmlspecialchars($userName) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                            <div><small class="text-muted">ID: <?= htmlspecialchars($user_uid) ?></small></div>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>settings/settings.php">
                        <i class="bi bi-person-circle me-2 fs-5"></i> My Profile
                    </a>
                </li>
                
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="#" id="logoutLink">
                        <i class="bi bi-box-arrow-right me-2 fs-5"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Mobile menu button -->
    <ul class="navbar-nav flex-row d-md-none">
        <li class="nav-item text-nowrap">
            <button class="nav-link px-3 text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <svg class="bi" width="24" height="24" fill="currentColor">
                    <use xlink:href="#list"></use>
                </svg>
            </button>
        </li>
    </ul>
    
    <!-- Search bar (collapsible) -->
    <div id="navbarSearch" class="navbar-search w-100 collapse">
        <input class="form-control w-100 rounded-0 border-0" type="text" placeholder="Search" aria-label="Search" />
    </div>
</header>
