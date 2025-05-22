<?php
require_once 'api/config/database.php';

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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $nim = trim($_POST['nim'] ?? '');
    $no_telpon = trim($_POST['no_telpon'] ?? '');
    $program_studi = trim($_POST['program_studi'] ?? '');
    $jenjang = $_POST['jenjang'] ?? '';
    $fakultas = trim($_POST['fakultas'] ?? '');
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
        if ($admin_code !== 'ADMIN123') {
            $errors[] = 'Kode admin tidak valid';
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar';
        }
    }
    
    // Check if NIM already exists (for students)
    if ($role === 'student' && !empty($nim) && empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE nim = ?");
        $stmt->execute([$nim]);
        if ($stmt->fetch()) {
            $errors[] = 'NIM sudah terdaftar';
        }
    }
    
    // Register user if no errors
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === 'admin') {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, nim, no_telpon, role, program_studi, jenjang, fakultas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $nim, $no_telpon, $role, $program_studi, $jenjang, $fakultas]);
            }
        }
            $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
    }  
}    
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
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

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= htmlspecialchars($success) ?>
                            <div class="mt-2">
                                <a href="login.php" class="btn btn-sm btn-success">Login Sekarang</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Form -->
                    <form method="POST" id="registerForm">
                        <!-- Role Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Pilih Role</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="role-option p-3 text-center" onclick="selectRole('student')">
                                        <input type="radio" name="role" value="student" id="role_student" class="d-none" <?= (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : '' ?>>
                                        <i class="bi bi-person-badge d-block mb-2" style="font-size: 2rem;"></i>
                                        <strong>Mahasiswa</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="role-option p-3 text-center" onclick="selectRole('admin')">
                                        <input type="radio" name="role" value="admin" id="role_admin" class="d-none" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : '' ?>>
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
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Nama Lengkap" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    <label for="name">Nama Lengkap</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    <label for="email">Email</label>
                                </div>
                            </div>
                        </div>

                        <!-- Student Fields -->
                        <div id="student-fields" style="display: <?= (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'block' : 'none' ?>">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nim" name="nim" placeholder="NIM" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
                                        <label for="nim">NIM</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="no_telpon" name="no_telpon" placeholder="Nomor Telepon" value="<?= htmlspecialchars($_POST['no_telpon'] ?? '') ?>">
                                        <label for="no_telpon">Nomor Telepon</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="program_studi" name="program_studi" placeholder="Program Studi" value="<?= htmlspecialchars($_POST['program_studi'] ?? '') ?>">
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
                                        <input type="text" class="form-control" id="fakultas" name="fakultas" placeholder="Fakultas" value="<?= htmlspecialchars($_POST['fakultas'] ?? '') ?>">
                                        <label for="fakultas">Fakultas</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Fields -->
                        <div id="admin-fields" style="display: <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'block' : 'none' ?>">
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="admin_code" name="admin_code" placeholder="Kode Admin">
                                        <label for="admin_code">Kode Admin</label>
                                    </div>
                                    <small class="text-muted">Untuk testing gunakan: ADMIN123</small>
                                </div>
                            </div>
                        </div>

                        <!-- Password Fields -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password" required>
                                    <label for="confirm_password">Konfirmasi Password</label>
                                </div>
                            </div>
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
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            selectRole(selectedRole);
            
            // Update visual state
            document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
            document.querySelector(`input[value="${selectedRole}"]`).closest('.role-option').classList.add('active');
        });
    </script>
</body>
</html>