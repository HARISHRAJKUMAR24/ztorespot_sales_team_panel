<?php
// auth/login.php
require_once "../config/config.php";
require_once "../lib/functions.php";

// Check if user is already logged in via session or remember token
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Check for remember token
if (isset($_COOKIE['remember_token'])) {
    $user = getUserByRememberToken($_COOKIE['remember_token']);
    if ($user) {
        // Log the user in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uid'] = $user['user_uid'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;

        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= APP_NAME ?></title>
    <link href="<?= ASSETS_URL ?>dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>

<body class="bg-light">
    <div class="toast-container"></div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="text-center mb-4">Sales Team Login</h4>

                        <form id="loginForm">
                            <div class="mb-3">
                                <label>Phone or Email</label>
                                <input type="text" name="login" class="form-control" placeholder="Enter phone or email" required>
                            </div>

                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                                Login
                            </button>

                            <div class="text-center mt-3">
                                <a href="<?= BASE_URL ?>auth/register.php">Don't have an account? Register</a>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?= BASE_URL ?>auth/forgot_password.php">Forgot Password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/auth.js"></script>
</body>

</html>