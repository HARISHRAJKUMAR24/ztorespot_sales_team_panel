<?php
// auth/forgot_password.php
require_once "../config/config.php";
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - <?= APP_NAME ?></title>
    <link href="<?= ASSETS_URL ?>dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .otp-timer {
            font-size: 14px;
            color: #28a745;
            font-weight: bold;
            margin-top: 10px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 20px;
            position: relative;
        }
        .step.active {
            background: #0d6efd;
            color: white;
        }
        .step.completed {
            background: #198754;
            color: white;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            width: 40px;
            height: 2px;
            background: #e9ecef;
            left: 40px;
            top: 50%;
        }
        .step.active:after, .step.completed:after {
            background: #0d6efd;
        }
        .debug-otp {
            background: #f8f9fa;
            border: 1px dashed #6c757d;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            color: #dc3545;
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <div class="toast-container"></div>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="text-center mb-4">Forgot Password</h4>
                        
                        <!-- Step Indicators -->
                        <div class="step-indicator">
                            <div class="step active" id="step1">1</div>
                            <div class="step" id="step2">2</div>
                            <div class="step" id="step3">3</div>
                        </div>

                        <!-- Debug OTP Display (for testing) -->
                        <div id="debugOtp" class="debug-otp text-center">
                            <strong>🔑 Test OTP: <span id="otpValue"></span></strong>
                        </div>

                        <!-- Step 1: Email Form -->
                        <div id="step1Form">
                            <form id="emailForm">
                                <div class="mb-3">
                                    <label>Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your registered email" required>
                                    <small class="text-muted">For testing, use OTP: 987654</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="sendOtpBtn">
                                    Send OTP
                                </button>
                            </form>
                        </div>

                        <!-- Step 2: OTP Verification Form (hidden initially) -->
                        <div id="step2Form" style="display: none;">
                            <form id="otpForm">
                                <div class="mb-3">
                                    <label>Enter OTP</label>
                                    <div class="input-group">
                                        <input type="text" name="otp" id="otp" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" required>
                                        <button type="button" class="btn btn-success" id="verifyOtpBtn">Verify</button>
                                    </div>
                                    <small class="text-muted">Use OTP: 987654 (for testing)</small>
                                    <div id="timer" class="otp-timer"></div>
                                    <button type="button" class="btn btn-link btn-sm mt-2" id="resendOtpBtn">Resend OTP</button>
                                    <button type="button" class="btn btn-link btn-sm mt-2" id="changeEmailBtn">Change Email</button>
                                </div>
                            </form>
                        </div>

                        <!-- Step 3: Reset Password Form (hidden initially) -->
                        <div id="step3Form" style="display: none;">
                            <form id="resetPasswordForm">
                                <input type="hidden" name="email" id="resetEmail">
                                <input type="hidden" name="otp_verified" id="otpVerified" value="no">
                                
                                <div class="mb-3">
                                    <label>New Password</label>
                                    <input type="password" name="password" id="newPassword" class="form-control" placeholder="Enter new password" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Confirm new password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100" id="resetPasswordBtn">
                                    Reset Password
                                </button>
                            </form>
                        </div>

                        <!-- Success Message (hidden initially) -->
                        <div id="successMessage" style="display: none;" class="text-center">
                            <div class="mb-3">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M8 12L11 15L16 9"/>
                                </svg>
                            </div>
                            <h5 class="text-success">Password Reset Successfully!</h5>
                            <p class="text-muted mb-3">Your password has been changed.</p>
                            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary">Go to Login</a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="<?= BASE_URL ?>auth/login.php">Back to Login</a>
                        </div>
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
    <script src="<?= BASE_URL ?>js/forgot-password.js"></script>
</body>

</html>