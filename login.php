<?php
require_once 'config/database.php';
require_once 'config/recaptcha.php'; // Include reCAPTCHA config

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

$errors = [];
$success = '';
$account_inactive = false; // Flag untuk notifikasi akun nonaktif 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email diperlukan';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Masukkan email yang valid';
    }
    
    if (empty($password)) {
        $errors[] = 'Password diperlukan';
    }
    
    // Verify reCAPTCHA
    if (!verifyRecaptcha($recaptcha_response)) {
        $errors[] = 'Silakan verifikasi bahwa Anda bukan robot';
    }
    
    // Attempt login if no validation errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if account is active (only for students)
                if ($user['role'] === 'student' && $user['is_active'] == 0) {
                    $account_inactive = true;
                    // Log failed login attempt due to inactive account
                    logActivity('login_failed_inactive', 'Login attempt with inactive account: ' . $email);
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Log successful login
                    logActivity('user_login', 'User logged in successfully');
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: student/dashboard.php');
                    }
                    exit;
                }
                
            } else {
                $errors[] = 'Email atau password tidak valid';
                // Log failed login attempt
                logActivity('login_failed', 'Failed login attempt for email: ' . $email);
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success = 'Registrasi berhasil! Silakan login dengan kredensial Anda.';
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $success = 'Anda telah berhasil logout.';
}

// Check for account deactivated message
if (isset($_GET['error']) && $_GET['error'] === 'account_deactivated') {
    $success = ''; // Clear any success message
    $account_inactive = true; // Show modal
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-body">
    <!-- Back to Home -->
    <div class="back-home">
        <a href="index.php">
            <i class="bi bi-arrow-left me-2"></i>
            Kembali ke Beranda
        </a>
    </div>

    <div class="login-container">
        <div class="login-card" id="loginCard">
            <div class="login-header">
                <div class="text-primary mb-3">
                    <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
                </div>
                <h1>Selamat Datang Kembali!</h1>
                <p>Login ke akun UPA Bahasa UPNVJ</p>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-floating mb-3">
                    <input 
                        type="email" 
                        class="form-control <?= (!empty($errors) && empty($_POST['email'])) ? 'is-invalid' : '' ?>" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                        required
                        autocomplete="email"
                        placeholder="name@upnvj.ac.id"
                    >
                    <label for="email">
                        <i class="bi bi-envelope me-2"></i>Email
                    </label>
                </div>

                <div class="form-floating mb-3">
                    <input 
                        type="password" 
                        class="form-control <?= (!empty($errors) && empty($_POST['password'])) ? 'is-invalid' : '' ?>" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                </div>

                <!-- Google reCAPTCHA -->
                <div class="mb-3 text-center">
                    <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                </div>

                <button type="submit" class="btn btn-login text-white" id="loginBtn">
                    <span class="btn-text">
                        Login
                        <i class="bi bi-arrow-right ms-2"></i>
                    </span>
                    <span class="btn-loading d-none">
                        <span class="loading-spinner me-2"></span>
                        Logging in...
                    </span>
                </button>
            </form>

            <div class="register-link">
                <span class="text-muted">Belum memiliki akun?</span>
                <a href="register.php" class="ms-1">Buat Akun</a>
            </div>
        </div>
    </div>

    <!-- Modal untuk Akun Nonaktif -->
    <div class="modal fade modal-inactive" id="inactiveAccountModal" tabindex="-1" aria-labelledby="inactiveAccountModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inactiveAccountModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Akun Tidak Aktif
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="warning-icon">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <h4 class="text-danger mb-3">Tidak Dapat Login</h4>
                    <p class="mb-4">
                        Akun Anda saat ini dalam status <strong>nonaktif</strong>. 
                        Silakan hubungi admin UPA Bahasa untuk mengaktifkan kembali akun Anda.
                    </p>
                    <div class="contact-info p-3 bg-light rounded mb-3">
                        <small class="text-muted">
                            <i class="bi bi-envelope me-2"></i>
                            Email: admin@upabahasa.upnvj.ac.id<br>
                            <i class="bi bi-telephone me-2"></i>
                            Telepon: (021) 7656971
                        </small>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-understand" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-2"></i>
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const btnLoading = loginBtn.querySelector('.btn-loading');
            const loginCard = document.getElementById('loginCard'); 

            // Check if account is inactive and show modal
            <?php if ($account_inactive): ?>
                // Add shake animation to login card
                loginCard.classList.add('shake-animation');
                
                // Show inactive account modal after shake animation
                setTimeout(function() {
                    var inactiveModal = new bootstrap.Modal(document.getElementById('inactiveAccountModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    inactiveModal.show();
                    
                    // Remove shake class after animation
                    setTimeout(() => {
                        loginCard.classList.remove('shake-animation');
                    }, 820);
                }, 100);
            <?php endif; ?>

            // Auto-focus first empty field
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            if (!emailField.value) {
                emailField.focus();
            } else if (!passwordField.value) {
                passwordField.focus();
            }

            // Form submission handling
            form.addEventListener('submit', function(e) {
                const email = emailField.value.trim();
                const password = passwordField.value;
                const recaptchaResponse = grecaptcha.getResponse();

                // Basic client-side validation
                if (!email) {
                    e.preventDefault();
                    showFieldError(emailField, 'Email diperlukan');
                    return;
                }

                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showFieldError(emailField, 'Masukkan email yang valid');
                    return;
                }

                if (!password) {
                    e.preventDefault();
                    showFieldError(passwordField, 'Password diperlukan');
                    return;
                }

                // Validate reCAPTCHA
                if (!recaptchaResponse) {
                    e.preventDefault();
                    alert('Silakan verifikasi bahwa Anda bukan robot');
                    return;
                }

                // Clear any existing errors
                clearFieldErrors();

                // Show loading state
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                loginBtn.disabled = true;
            });

            // Real-time validation
            emailField.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !isValidEmail(email)) {
                    showFieldError(this, 'Masukkan email yang valid');
                } else {
                    clearFieldError(this);
                }
            });

            // Clear errors on input
            [emailField, passwordField].forEach(field => {
                field.addEventListener('input', function() {
                    clearFieldError(this);
                });
            });

            // Utility functions
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function showFieldError(field, message) {
                clearFieldError(field);
                field.classList.add('is-invalid');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${message}`;
                
                field.parentNode.appendChild(errorDiv);
            }

            function clearFieldError(field) {
                field.classList.remove('is-invalid');
                const errorMsg = field.parentNode.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }

            function clearFieldErrors() {
                document.querySelectorAll('.form-control').forEach(field => {
                    clearFieldError(field);
                });
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Modal event handlers
            const inactiveModal = document.getElementById('inactiveAccountModal');
            if (inactiveModal) {
                inactiveModal.addEventListener('hidden.bs.modal', function () {
                    // Clear password field when modal is closed for security
                    document.getElementById('password').value = '';
                    document.getElementById('email').focus();
                    
                    // Reset reCAPTCHA when modal is closed
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                });

                // Handle understand button click
                const understandBtn = inactiveModal.querySelector('.btn-understand');
                if (understandBtn) {
                    understandBtn.addEventListener('click', function() {
                        // Clear form and focus email field
                        form.reset();
                        clearFieldErrors();
                        emailField.focus();
                        
                        // Reset reCAPTCHA
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>