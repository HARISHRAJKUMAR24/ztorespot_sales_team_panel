<?php
require_once './lib/functions.php';
require_once './config/config.php';
// Check if user is logged in
if (!isLoggedIn()) {
    // If not logged in, redirect to login page
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php template('head-tag'); ?>

<body>

<?php template('svg-icons'); ?>

  <?php template('top-navbar'); ?>
  <div class="container-fluid">
    <div class="row">

      <?php template('side-navbar'); ?>
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div
          class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Dashboard</h1>

        </div>

      </main>
    </div>
  </div>
  <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>

  <script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.3.2/dist/chart.umd.js"
    integrity="sha384-eI7PSr3L1XLISH8JdDII5YN/njoSsxfbrkCTnJrzXt+ENP5MOVBxD+l6sEG4zoLp"
    crossorigin="anonymous"></script>

  <script src="<?= BASE_URL ?>dashboard.js"></script>
  <script src="<?= BASE_URL ?>js/auth/logout.js"></script>
</body>

</html>