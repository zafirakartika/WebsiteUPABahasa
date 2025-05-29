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
$nim_validated = false;
$nim_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $nim = sanitizeInput($_POST['nim'] ?? '');
    $is_admin = isset($_POST['isAdmin']);
    $registration_code = $_POST['registrationCode'] ?? '';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Nama lengkap diperlukan';
    }
    
    if (empty($email)) {
        $errors[] = 'Email diperlukan';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Masukkan email yang valid';
    }
    
    if (empty($password)) {
        $errors[] = 'Password diperlukan';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password harus setidaknya memiliki 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Password tidak sama';
    }
    
    // Student-specific validation
    if (!$is_admin) {
        if (empty($nim)) {
            $errors[] = 'NIM diperlukan untuk registrasi mahasiswa';
        } elseif (!preg_match('/^\d{10}$/', $nim)) {
            $errors[] = 'NIM harus 10 digit';
        } else {
            // Validate NIM with SIAKAD simulation
            $nim_validation = validateNimWithSiakad($nim);
            if (!$nim_validation['valid']) {
                $errors[] = $nim_validation['message'];
            } else {
                $nim_validated = true;
            }
        }
    }
    
    // Admin-specific validation
    if ($is_admin) {
        $valid_admin_code = getSystemSetting('admin_registration_code', 'ADMIN123');
        if ($registration_code !== $valid_admin_code) {
            $errors[] = 'Kode registrasi admin invalid';
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email telah terdaftar';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi error saat mengecek email';
        }
    }
    
    // Check if NIM already exists (for students)
    if (!$is_admin && !empty($nim) && empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nim = ?");
            $stmt->execute([$nim]);
            if ($stmt->fetch()) {
                $errors[] = 'NIM telah terdaftar';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi error saat mengecek NIM';
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = $is_admin ? 'admin' : 'student';
            
            if ($is_admin) {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $hashed_password, $role]);
            } else {
                // Get additional data from SIAKAD simulation
                $siakad_data = getSiakadData($nim);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (name, email, password, nim, role, program, faculty, level) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $email, $hashed_password, $nim, $role,
                    $siakad_data['program'], $siakad_data['faculty'], $siakad_data['level']
                ]);
            }
            
            $user_id = $pdo->lastInsertId();
            
            // Log activity
            logActivity('user_registration', "New $role registered: $email", $user_id);
            
            $pdo->commit();
            
            // Redirect to login with success message
            header('Location: login.php?registered=true');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'Registrasi gagal. Silakan coba lagi.';
        }
    }
}

// Function to validate NIM with SIAKAD (simulation)
function validateNimWithSiakad($nim) {
    // Simulate API call delay
    usleep(100000); // 0.1 second delay
    
    // For demo purposes, validate based on patterns
    $valid_patterns = [
        '22' => true, // 2022 batch
        '21' => true, // 2021 batch
        '20' => true, // 2020 batch
        '19' => true, // 2019 batch
    ];
    
    $year_prefix = substr($nim, 0, 2);
    
    if (!isset($valid_patterns[$year_prefix])) {
        return [
            'valid' => false,
            'message' => 'NIM tidak terdaftar di SIAKAD UPNVJ'
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'NIM tervalidasi dengan SIAKAD UPNVJ'
    ];
}

// Function to get SIAKAD data (simulation)
function getSiakadData($nim) {
    // Simulate different programs based on NIM pattern
    $programs = [
        '221050' => [
            'program' => 'Sistem Informasi', 
            'level' => 'D3', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
        '221051' => [
            'program' => 'Hubungan Internasional', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Sosial dan Ilmu Politik'
        ],
        '221052' => [
            'program' => 'Manajemen', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ekonomi dan Bisnis'
        ],
        '221053' => [
            'program' => 'Teknik Informatika', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Teknik'
        ],
        '211050' => [
            'program' => 'Sistem Informasi', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
        '201051' => [
            'program' => 'Sistem Informasi', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
    ];
    
    $nim_prefix = substr($nim, 0, 6);
    
    // Default to first program if pattern not found
    return $programs[$nim_prefix] ?? $programs['221050'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <!-- Back to Home -->
    <div class="back-home">
        <a href="index.php">
            <i class="bi bi-arrow-left me-2"></i>
            Kembali ke Beranda
        </a>
    </div>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="text-primary mb-3">
                    <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
                </div>
                <h1>Daftar Akun</h1>
                <p>Buat akun dan bergabung dengan UPA Bahasa UPNVJ</p>
            </div>

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

            <form method="POST" id="registerForm">
                <!-- Admin Toggle -->
                <div class="admin-toggle">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isAdmin" name="isAdmin" onchange="toggleAdminFields()">
                        <label class="form-check-label" for="isAdmin">
                            <strong>Daftar sebagai Admin</strong>
                            <small class="d-block text-muted">Centang jika mendaftar sebagai admin</small>
                        </label>
                    </div>
                </div>

                <!-- Full Name -->
                <div class="form-floating mb-3">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                        required
                        placeholder="Nama lengkap Anda"
                    >
                    <label for="name">
                        <i class="bi bi-person me-2"></i>Nama Lengkap
                    </label>
                </div>

                <!-- Email -->
                <div class="form-floating mb-3">
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                        required
                        placeholder="nama@upnvj.ac.id"
                    >
                    <label for="email">
                        <i class="bi bi-envelope me-2"></i>Email
                    </label>
                </div>

                <!-- NIM (Student Only) -->
                <div class="form-floating mb-3" id="nimField">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="nim" 
                        name="nim" 
                        value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" 
                        required
                        placeholder="2210501032"
                        pattern="\d{10}"
                        onblur="validateNim()"
                    >
                    <label for="nim">
                        <i class="bi bi-card-text me-2"></i>NIM (10 digit)
                    </label>
                    <div id="nimValidation" class="nim-validation"></div>
                    <small class="text-muted mt-1">
                        <i class="bi bi-info-circle me-1"></i>
                        NIM akan divalidasi dengan SIAKAD UPNVJ
                    </small>
                </div>

                <!-- Admin Registration Code (Admin Only) -->
                <div class="form-floating mb-3 d-none" id="registrationCodeField">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="registrationCode" 
                        name="registrationCode" 
                        placeholder="Masukkan kode registrasi admin"
                    >
                    <label for="registrationCode">
                        <i class="bi bi-key me-2"></i>Kode Registrasi Admin
                    </label>
                    <small class="text-muted mt-1">
                        Untuk demo gunakan kode: <strong>ADMIN123</strong>
                    </small>
                </div>

                <!-- Password -->
                <div class="form-floating mb-3">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required
                        minlength="6"
                        placeholder="••••••••"
                    >
                    <label for="password">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                </div>

                <!-- Confirm Password -->
                <div class="form-floating mb-4">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirmPassword" 
                        name="confirmPassword" 
                        required
                        placeholder="••••••••"
                    >
                    <label for="confirmPassword">
                        <i class="bi bi-lock-fill me-2"></i>Konfirmasi Password
                    </label>
                </div>

                <button type="submit" class="btn btn-register text-white" id="registerBtn">
                    <span class="btn-text">
                        Daftar Sekarang
                        <i class="bi bi-arrow-right ms-2"></i>
                    </span>
                    <span class="btn-loading d-none">
                        <span class="loading-spinner me-2"></span>
                        Mendaftar...
                    </span>
                </button>
            </form>

            <div class="login-link">
                <span class="text-muted">Sudah memiliki akun?</span>
                <a href="login.php" class="ms-1">Login di sini</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let nimValidated = <?= $nim_validated ? 'true' : 'false' ?>;
        let nimChecking = false;

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');
            const btnText = registerBtn.querySelector('.btn-text');
            const btnLoading = registerBtn.querySelector('.btn-loading');

            // Auto-focus first field
            document.getElementById('name').focus();

            // Form submission handling
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                registerBtn.disabled = true;
            });

            // Real-time validation
            document.getElementById('email').addEventListener('blur', validateEmail);
            document.getElementById('password').addEventListener('input', validatePassword);
            document.getElementById('confirmPassword').addEventListener('input', validateConfirmPassword);
        });

        function toggleAdminFields() {
            const isAdmin = document.getElementById('isAdmin').checked;
            const nimField = document.getElementById('nimField');
            const registrationCodeField = document.getElementById('registrationCodeField');
            const nimInput = document.getElementById('nim');
            const registrationCodeInput = document.getElementById('registrationCode');

            if (isAdmin) {
                nimField.classList.add('d-none');
                registrationCodeField.classList.remove('d-none');
                nimInput.required = false;
                registrationCodeInput.required = true;
            } else {
                nimField.classList.remove('d-none');
                registrationCodeField.classList.add('d-none');
                nimInput.required = true;
                registrationCodeInput.required = false;
            }
        }

        async function validateNim() {
            const isAdmin = document.getElementById('isAdmin').checked;
            if (isAdmin) return;

            const nimInput = document.getElementById('nim');
            const nimValidation = document.getElementById('nimValidation');
            const nim = nimInput.value.trim();

            if (!nim || nim.length !== 10 || !/^\d{10}$/.test(nim)) {
                nimValidated = false;
                nimValidation.className = 'nim-validation invalid';
                nimValidation.innerHTML = '<i class="bi bi-x-circle me-1"></i>NIM harus terdiri dari 10 digit angka';
                nimInput.classList.add('is-invalid');
                nimInput.classList.remove('is-valid');
                return;
            }

            if (nimChecking) return;

            nimChecking = true;
            nimValidation.className = 'nim-validation validating';
            nimValidation.innerHTML = '<span class="loading-spinner me-2"></span>Memvalidasi dengan SIAKAD...';
            nimInput.classList.remove('is-invalid', 'is-valid');

            try {
                // Simulate API call to SIAKAD
                await new Promise(resolve => setTimeout(resolve, 1000));

                // For demo purposes, validate based on pattern
                const year = nim.substring(0, 2);
                const validYears = ['19', '20', '21', '22'];
                const isValid = validYears.includes(year);

                if (isValid) {
                    nimValidated = true;
                    nimValidation.className = 'nim-validation valid';
                    nimValidation.innerHTML = '<i class="bi bi-check-circle me-1"></i>NIM tervalidasi dengan SIAKAD UPNVJ';
                    nimInput.classList.add('is-valid');
                    nimInput.classList.remove('is-invalid');
                } else {
                    nimValidated = false;
                    nimValidation.className = 'nim-validation invalid';
                    nimValidation.innerHTML = '<i class="bi bi-x-circle me-1"></i>NIM tidak terdaftar di SIAKAD UPNVJ';
                    nimInput.classList.add('is-invalid');
                    nimInput.classList.remove('is-valid');
                }
            } catch (error) {
                nimValidated = false;
                nimValidation.className = 'nim-validation invalid';
                nimValidation.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Gagal memvalidasi NIM. Silakan coba lagi.';
                nimInput.classList.add('is-invalid');
                nimInput.classList.remove('is-valid');
            } finally {
                nimChecking = false;
            }
        }

        function validateEmail() {
            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email && !emailRegex.test(email)) {
                showFieldError(emailInput, 'Silakan masukkan alamat email yang valid');
                return false;
            } else {
                clearFieldError(emailInput);
                return true;
            }
        }

        function validatePassword() {
            const passwordInput = document.getElementById('password');
            const password = passwordInput.value;

            if (password && password.length < 6) {
                showFieldError(passwordInput, 'Password harus minimal 6 karakter');
                return false;
            } else {
                clearFieldError(passwordInput);
                return true;
            }
        }

        function validateConfirmPassword() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword && password !== confirmPassword) {
                showFieldError(confirmPasswordInput, 'Password tidak sama');
                return false;
            } else {
                clearFieldError(confirmPasswordInput);
                return true;
            }
        }

        function validateForm() {
            let isValid = true;

            // Clear all errors first
            clearAllErrors();

            // Validate name
            const name = document.getElementById('name').value.trim();
            if (!name) {
                showFieldError(document.getElementById('name'), 'Nama lengkap diperlukan');
                isValid = false;
            }

            // Validate email
            if (!validateEmail()) {
                isValid = false;
            }

            // Validate NIM (for students)
            const isAdmin = document.getElementById('isAdmin').checked;
            if (!isAdmin) {
                const nim = document.getElementById('nim').value.trim();
                if (!nim) {
                    showFieldError(document.getElementById('nim'), 'NIM diperlukan');
                    isValid = false;
                } else if (!/^\d{10}$/.test(nim)) {
                    showFieldError(document.getElementById('nim'), 'NIM harus tepat 10 digit');
                    isValid = false;
                } else if (!nimValidated) {
                    showFieldError(document.getElementById('nim'), 'Silakan tunggu validasi NIM selesai');
                    isValid = false;
                }
            }

            // Validate admin code (for admins)
            if (isAdmin) {
                const code = document.getElementById('registrationCode').value.trim();
                if (!code) {
                    showFieldError(document.getElementById('registrationCode'), 'Kode registrasi diperlukan untuk akun admin');
                    isValid = false;
                }
            }

            // Validate password
            if (!validatePassword()) {
                isValid = false;
            }

            // Validate confirm password
            if (!validateConfirmPassword()) {
                isValid = false;
            }

            return isValid;
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

        function clearAllErrors() {
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
    </script>
</body>
</html>