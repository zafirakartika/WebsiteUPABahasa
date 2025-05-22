<?php
// register.php - User registration page
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
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        $nim = sanitizeInput($_POST['nim'] ?? '');
        $no_telpon = sanitizeInput($_POST['no_telpon'] ?? '');
        $program_studi = sanitizeInput($_POST['program_studi'] ?? '');
        $jenjang = $_POST['jenjang'] ?? '';
        $fakultas = sanitizeInput($_POST['fakultas'] ?? '');
        $admin_code = $_POST['admin_code'] ?? '';
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Nama lengkap harus diisi';
        }
        
        if (empty($email)) {
            $errors[] = 'Email harus diisi';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }
        
        if (empty($password)) {
            $errors[] = 'Password harus diisi';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok';
        }
        
        // Student-specific validation
        if ($role === 'student') {
            if (empty($nim)) {
                $errors[] = 'NIM harus diisi';
            } elseif (!preg_match('/^\d{10,15}$/', $nim)) {
                $errors[] = 'NIM harus berupa angka 10-15 digit';
            }
            
            if (empty($no_telpon)) {
                $errors[] = 'Nomor telepon harus diisi';
            } elseif (!preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', preg_replace('/\s+/', '', $no_telpon))) {
                $errors[] = 'Format nomor telepon tidak valid';
            }
            
            if (empty($program_studi)) {
                $errors[] = 'Program studi harus diisi';
            }
            
            if (empty($jenjang)) {
                $errors[] = 'Jenjang harus dipilih';
            }
            
            if (empty($fakultas)) {
                $errors[] = 'Fakultas harus diisi';
            }
        }
        
        // Admin-specific validation
        if ($role === 'admin') {
            $valid_admin_code = getSystemSetting('admin_registration_code', 'ADMIN123');
            if ($admin_code !== $valid_admin_code) {
                $errors[] = 'Kode admin tidak valid';
            }
        }
        
        // Check if email already exists
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email sudah terdaftar';
                }
            } catch (PDOException $e) {
                $errors[] = 'Terjadi kesalahan saat mengecek email';
            }
        }
        
        // Check if NIM already exists (for students)
        if ($role === 'student' && !empty($nim) && empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE nim = ?");
                $stmt->execute([$nim]);
                if ($stmt->fetch()) {
                    $errors[] = 'NIM sudah terdaftar';
                }
            } catch (PDOException $e) {
                $errors[] = 'Terjadi kesalahan saat mengecek NIM';
            }
        }
        
        // Register user if no errors
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                if ($role === 'admin') {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $email, $hashed_password, $role]);
                } else {
                    // Format phone number
                    $no_telpon = preg_replace('/\s+/', '', $no_telpon);
                    if (substr($no_telpon, 0, 1) === '0') {
                        $no_telpon = '62' . substr($no_telpon, 1);
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                        (name, email, password, nim, no_telpon, role, program_studi, jenjang, fakultas) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $name, $email, $hashed_password, $nim, $no_telpon, 
                        $role, $program_studi, $jenjang, $fakultas
                    ]);
                }
                
                $user_id = $pdo->lastInsertId();
                
                // Log activity
                logActivity('registration', "New user registration: $name ($email)", $user_id);
                
                $pdo->commit();
                
                // Set success message
                showAlert('Registrasi berhasil! Silakan login dengan akun Anda.', 'success');
                header('Location: login.php');
                exit;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Registration error: " . $e->getMessage());
                $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin: 40px auto;
            max-width: 800px;
        }
        .form-floating > label {
            color: #6c757d;
        }
        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .role-option.active {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="register-card p-5">
            <!-- Header -->
            <div class="text-center mb-4">
                <i class="bi bi-mortarboard text-primary" style="font-size: 3rem;"></i>
                <h2 class="fw-bold mt-3">Daftar Akun</h2>
                <p class="text-muted">UPA Bahasa UPNVJ</p>
            </div>

            <!-- Alerts -->
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

            <!-- Registration Form -->
            <form method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <!-- Role Selection -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Pilih Role</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="role-option p-3 text-center" onclick="selectRole('student')">
                                <input type="radio" name="role" value="student" id="role_student" class="d-none" checked>
                                <i class="bi bi-person-badge d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Mahasiswa</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="role-option p-3 text-center" onclick="selectRole('admin')">
                                <input type="radio" name="role" value="admin" id="role_admin" class="d-none">
                                <i class="bi bi-person-gear d-block mb-2" style="font-size: 2rem;"></i>
                                <strong>Admin</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common Fields -->
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="name" name="name" 
                                   placeholder="Nama Lengkap" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            <label for="name">Nama Lengkap</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <label for="email">Email</label>
                        </div>
                    </div>
                </div>

                <!-- Student Fields -->
                <div id="student-fields">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nim" name="nim" 
                                       placeholder="NIM" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
                                <label for="nim">NIM</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="no_telpon" name="no_telpon" 
                                       placeholder="Nomor Telepon" value="<?= htmlspecialchars($_POST['no_telpon'] ?? '') ?>">
                                <label for="no_telpon">Nomor Telepon (08xx)</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="program_studi" name="program_studi" 
                                       placeholder="Program Studi" value="<?= htmlspecialchars($_POST['program_studi'] ?? '') ?>">
                                <label for="program_studi">Program Studi</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="jenjang" name="jenjang">
                                    <option value="">Pilih</option>
                                    <option value="D3" <?= (isset($_POST['jenjang']) && $_POST['jenjang'] === 'D3') ? 'selected' : '' ?>>D3</option>
                                    <option value="S1" <?= (isset($_POST['jenjang']) && $_POST['jenjang'] === 'S1') ? 'selected' : '' ?>>S1</option>
                                    <option value="S2" <?= (isset($_POST['jenjang']) && $_POST['jenjang'] === 'S2') ? 'selected' : '' ?>>S2</option>
                                    <option value="S3" <?= (isset($_POST['jenjang']) && $_POST['jenjang'] === 'S3') ? 'selected' : '' ?>>S3</option>
                                </select>
                                <label for="jenjang">Jenjang</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="fakultas" name="fakultas" 
                                       placeholder="Fakultas" value="<?= htmlspecialchars($_POST['fakultas'] ?? '') ?>">
                                <label for="fakultas">Fakultas</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Fields -->
                <div id="admin-fields" style="display: none;">
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="admin_code" name="admin_code" 
                                       placeholder="Kode Admin">
                                <label for="admin_code">Kode Admin</label>
                            </div>
                            <small class="text-muted">Hubungi administrator untuk mendapatkan kode admin</small>
                        </div>
                    </div>
                </div>

                <!-- Password Fields -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Konfirmasi Password" required>
                            <label for="confirm_password">Konfirmasi Password</label>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label" for="terms">
                        Saya setuju dengan <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">syarat dan ketentuan</a>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-register btn-lg">
                        <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <span class="text-muted">Sudah punya akun?</span>
                    <a href="login.php" class="text-decoration-none fw-semibold">Login di sini</a>
                </div>
            </form>
        </div>
        
        <!-- Back to Home -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Syarat dan Ketentuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Persyaratan Umum</h6>
                    <p>Dengan mendaftar, Anda menyetujui untuk memberikan informasi yang benar dan akurat.</p>
                    
                    <h6>2. Penggunaan Layanan</h6>
                    <p>Layanan UPA Bahasa UPNVJ hanya dapat digunakan untuk keperluan akademik yang sah.</p>
                    
                    <h6>3. Privasi</h6>
                    <p>Data pribadi Anda akan dijaga kerahasiaannya sesuai dengan kebijakan privasi kami.</p>
                    
                    <h6>4. Pembayaran</h6>
                    <p>Biaya yang telah dibayarkan tidak dapat dikembalikan kecuali dalam kondisi tertentu.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRole(role) {
            // Update radio buttons
            document.getElementById('role_student').checked = (role === 'student');
            document.getElementById('role_admin').checked = (role === 'admin');
            
            // Update visual appearance
            document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            // Show/hide appropriate fields
            document.getElementById('student-fields').style.display = (role === 'student') ? 'block' : 'none';
            document.getElementById('admin-fields').style.display = (role === 'admin') ? 'block' : 'none';
            
            // Update required attributes
            const studentFields = document.querySelectorAll('#student-fields input, #student-fields select');
            const adminFields = document.querySelectorAll('#admin-fields input');
            
            if (role === 'student') {
                studentFields.forEach(field => {
                    if (['nim', 'no_telpon', 'program_studi', 'jenjang', 'fakultas'].includes(field.name)) {
                        field.required = true;
                    }
                });
                adminFields.forEach(field => field.required = false);
            } else {
                studentFields.forEach(field => field.required = false);
                adminFields.forEach(field => {
                    if (field.name === 'admin_code') {
                        field.required = true;
                    }
                });
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            selectRole('student');
            document.querySelector('.role-option').classList.add('active');
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>