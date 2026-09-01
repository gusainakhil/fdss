<?php
session_start();
require_once __DIR__ . '/includes/login_auth.php';

$login_error = fdss_process_login($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDSS Login - Indian Railways</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <!-- Left Side - Branding -->
            <div class="login-left">
                <div class="brand-section">
                    <div class="logo-icon">
                        <i class="bi bi-train-front-fill"></i>
                    </div>
                    <h1>FDSS / FSDS</h1>
                    <p class="brand-subtitle">Indian Railways</p>
                    <p class="brand-description">Station Level Monitoring System</p>
                </div>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Real-time Train Tracking</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Station Performance Analytics</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Secure Access Control</span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-right">
                <div class="login-form-container">
                    <h2>Welcome Back</h2>
                    <p class="login-subtitle">Sign in to your FDSS / FSDS account</p>

                    <?php if ($login_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <strong>Login Failed!</strong> <?php echo htmlspecialchars($login_error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>Logged Out!</strong> You have been successfully logged out.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['access']) && $_GET['access'] === 'denied'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <strong>Access Denied!</strong> 
                            Your role (<?php echo htmlspecialchars($_GET['role'] ?? 'Unknown'); ?>) does not have permission to access this dashboard. 
                            Only ORG_ADMIN users can access this dashboard. Please contact your administrator.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="login-form">
                        <div class="form-group">
                            <label for="username" class="form-label">
                                <i class="bi bi-person-fill"></i> Username
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username"
                                placeholder="Enter your username"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill"></i> Password
                            </label>
                            <div class="password-input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password"
                                    placeholder="Enter your password"
                                    required
                                >
                                <button type="button" class="btn-toggle-password" id="togglePassword">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn-login">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>

                    <!-- <div class="login-footer">
                        <a href="#" class="forgot-password">Forgot Password?</a>
                        <span class="separator">•</span>
                        <a href="#" class="contact-support">Contact Support</a>
                    </div> -->

                    <!-- <div class="alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Demo: beatle/123456 or admin/123456 or kings/123456</span>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye-fill');
            icon.classList.toggle('bi-eye-slash-fill');
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
