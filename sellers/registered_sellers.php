<?php
require_once '../lib/functions.php';
require_once '../config/config.php';

// Optional: check login (remove if public page)
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body class="bg-light">

    <?php template('svg-icons'); ?>
    <?php template('top-navbar'); ?>

    <div class="container-fluid">
        <div class="row">

            <?php template('side-navbar'); ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 d-flex align-items-center justify-content-center" style="height: 90vh;">

                <div class="text-center">

                    <h1 class="display-4 fw-bold mb-3">
                        🚀 Coming Soon
                    </h1>

                    <p class="lead text-muted mb-4">
                        We are working on something awesome. Stay tuned!
                    </p>



                    <!-- Button -->
                    <a href="<?= BASE_URL ?>" class="btn btn-primary rounded-pill px-4">
                        Back to Dashboard
                    </a>

                </div>

            </main>
        </div>
    </div>

    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .card {
            border-radius: 15px;
        }
    </style>

</body>

</html>