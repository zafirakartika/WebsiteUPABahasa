<?php
require_once 'config/database.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    // Attempt login if no validation errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Set remember me cookie if requested
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Store token in database
                    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
                    
                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                }
                
                // Log activity
                logActivity('login', "User logged in: $email");
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit;
                
            } else {
                $errors[] = 'Invalid email or password';
                
                // Log failed attempt
                logActivity('login_failed', "Failed login attempt for: $email");
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = 'A system error occurred. Please try again later.';
        }
    }
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success = 'Registration successful! Please login with your credentials.';
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $success = 'You have been successfully logged out.';
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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            border: none;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #64748b;
            margin: 0;
        }
        .form-floating > label {
            color: #64748b;
            font-weight: 500;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-control.is-invalid {
            border-color: #ef4444;
        }
        .form-control.is-invalid:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-login:disabled {
            opacity: 0.7;
            transform: none;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
        }
        .success-message {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
        }
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .forgot-password:hover {
            color: #5a6acf;
            text-decoration: underline;
        }
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            color: #5a6acf;
            text-decoration: underline;
        }
        .back-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 1000;
        }
        .back-home a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .back-home a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Back to Home -->
    <div class="back-home">
        <a href="index.php">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Home
        </a>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="text-primary mb-3">
                    <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your UPA Bahasa UPNVJ account</p>
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
                        placeholder="name@upnvj.ac.id"
                    >
                    <label for="email">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </label>
                </div>

                <div class="form-floating mb-3">
                    <input 
                        type="password" 
                        class="form-control <?= (!empty($errors) && empty($_POST['password'])) ? 'is-invalid' : '' ?>" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="••••••••"
                    >
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn btn-login text-white" id="loginBtn">
                    <span class="btn-text">
                        Sign In
                        <i class="bi bi-arrow-right ms-2"></i>
                    </span>
                    <span class="btn-loading d-none">
                        <span class="loading-spinner me-2"></span>
                        Signing in...
                    </span>
                </button>
            </form>

            <div class="register-link">
                <span class="text-muted">Don't have an account?</span>
                <a href="register.php" class="ms-1">Create Account</a>
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

                // Basic client-side validation
                if (!email) {
                    e.preventDefault();
                    showFieldError(emailField, 'Email is required');
                    return;
                }

                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showFieldError(emailField, 'Please enter a valid email address');
                    return;
                }

                if (!password) {
                    e.preventDefault();
                    showFieldError(passwordField, 'Password is required');
                    return;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    showFieldError(passwordField, 'Password must be at least 6 characters');
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
                    showFieldError(this, 'Please enter a valid email address');
                } else {
                    clearFieldError(this);
                }
            });

            passwordField.addEventListener('input', function() {
                const password = this.value;
                if (password && password.length < 6) {
                    showFieldError(this, 'Password must be at least 6 characters');
                } else {
                    clearFieldError(this);
                }
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
        });
    </script>
</body>
</html>