<?php
require_once dirname(__DIR__) . "/config/config.php";
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account - <?= APP_NAME ?></title>
    <link href="<?= ASSETS_URL ?>dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>

<body class="bg-light">
    <div class="toast-container"></div>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="text-center mb-4">Create Account</h4>
                        
                        <form id="registerForm">
                            <div class="mb-3">
                                <label>Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                            </div>

                            <div class="mb-3">
                                <label>Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" placeholder="Enter 10-15 digit phone number" required>
                              
                            </div>

                            <div class="mb-3">
                                <label>Email (Optional)</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter your email">
                            </div>

                            <div class="mb-3">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="Create a password" required minlength="6">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <button type="submit" class="btn btn-success w-100" id="submitBtn">
                                Create Account
                            </button>

                            <div class="text-center mt-3">
                                <a href="<?= BASE_URL ?>auth/login.php">Already have an account? Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= ASSETS_URL ?>dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
    <script src="<?= BASE_URL ?>js/auth/register.js"></script>
</body>

</html>