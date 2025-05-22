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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit;
            } else {
                $error = 'Email atau password salah';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem';
        }
    }
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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-left {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
        }
        .form-floating > label {
            color: #6c757d;
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .demo-accounts {
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="login-card">
                    <div class="row g-0">
                        <!-- Left Side -->
                        <div class="col-lg-6 login-left">
                            <div class="text-center p-5">
                                <i class="bi bi-mortarboard" style="font-size: 5rem;"></i>
                                <h3 class="fw-bold mt-4">UPA Bahasa UPNVJ</h3>
                                <p class="lead">Unit Pelayanan Akademik Bahasa</p>
                                <p>Tingkatkan kemampuan bahasa Inggris Anda dengan layanan ELPT dan kursus persiapan terbaik</p>
                            </div>
                        </div>
                        
                        <!-- Right Side -->
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center mb-4">
                                    <h2 class="fw-bold">Selamat Datang</h2>
                                    <p class="text-muted">Masuk ke akun Anda</p>
                                </div>

                                <!-- Error Alert -->
                                <?php if ($error): ?>
                                    <div class="alert alert-danger d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Login Form -->
                                <form method="POST">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                        <label for="email">Email</label>
                                    </div>

                                    <div class="form-floating mb-4">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                        <label for="password">Password</label>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-login btn-lg">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                                        </button>
                                    </div>

                                    <div class="text-center mb-4">
                                        <span class="text-muted">Belum punya akun?</span>
                                        <a href="register.php" class="text-decoration-none fw-semibold">Daftar di sini</a>
                                    </div>
                                </form>

                                <!-- Demo Accounts -->
                                <div class="demo-accounts p-3">
                                    <h6 class="fw-bold mb-2">
                                        <i class="bi bi-info-circle me-2"></i>Akun Demo
                                    </h6>
                                    <small class="text-muted">
                                        <strong>Admin:</strong> admin@upabahasa.upnvj.ac.id<br>
                                        <strong>Mahasiswa:</strong> budi@student.upnvj.ac.id<br>
                                        <strong>Password:</strong> password
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Home -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>