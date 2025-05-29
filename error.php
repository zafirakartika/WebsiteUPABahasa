<?php
// error.php
$error_code = $_GET['error'] ?? '404';
$error_messages = [
    '400' => [
        'title' => 'Bad Request',
        'message' => 'Permintaan tidak valid atau tidak dapat diproses.',
        'description' => 'Server tidak dapat memahami permintaan karena syntax yang salah.'
    ],
    '401' => [
        'title' => 'Unauthorized',
        'message' => 'Anda tidak memiliki akses ke halaman ini.',
        'description' => 'Silakan login terlebih dahulu untuk mengakses halaman ini.'
    ],
    '403' => [
        'title' => 'Forbidden',
        'message' => 'Akses ditolak.',
        'description' => 'Anda tidak memiliki izin untuk mengakses resource ini.'
    ],
    '404' => [
        'title' => 'Page Not Found',
        'message' => 'Halaman yang Anda cari tidak ditemukan.',
        'description' => 'Halaman mungkin telah dipindahkan, dihapus, atau URL yang dimasukkan salah.'
    ],
    '500' => [
        'title' => 'Internal Server Error',
        'message' => 'Terjadi kesalahan pada server.',
        'description' => 'Server mengalami masalah internal dan tidak dapat menyelesaikan permintaan.'
    ]
];

$error = $error_messages[$error_code] ?? $error_messages['404'];
http_response_code(intval($error_code));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $error['title'] ?> - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="container">
        <div class="error-container">
            <!-- Error Header -->
            <div class="error-header">
                <div class="error-code"><?= $error_code ?></div>
                <div class="error-title"><?= $error['title'] ?></div>
            </div>
            
            <!-- Error Body -->
            <div class="error-body">
                <div class="error-icon">
                    <i class="bi bi-<?= $error_code === '404' ? 'file-earmark-x' : ($error_code === '403' ? 'shield-x' : ($error_code === '401' ? 'lock' : 'exclamation-triangle')) ?>"></i>
                </div>
                
                <h3 class="mb-3"><?= $error['message'] ?></h3>
                <p class="text-muted mb-4"><?= $error['description'] ?></p>
                
                <!-- Search Box -->
                <div class="search-box">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Cari halaman..." id="searchInput">
                        <button class="btn btn-outline-primary" type="button" onclick="performSearch()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3 justify-content-center flex-wrap mb-4">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                    <a href="/" class="btn btn-primary btn-home">
                        <i class="bi bi-house me-2"></i>Beranda
                    </a>
                    <?php if ($error_code === '401'): ?>
                        <a href="/login.php" class="btn btn-success">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Helpful Links -->
                <div class="helpful-links">
                    <h5 class="mb-3">Halaman yang Mungkin Anda Cari:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <a href="/" class="link-item">
                                <i class="bi bi-house-door"></i>
                                <span>Beranda</span>
                            </a>
                            <a href="/student/dashboard.php" class="link-item">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard Mahasiswa</span>
                            </a>
                            <a href="/student/elpt-registration.php" class="link-item">
                                <i class="bi bi-calendar-plus"></i>
                                <span>Daftar ELPT</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/student/test-results.php" class="link-item">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Hasil Tes</span>
                            </a>
                            <a href="/student/course.php" class="link-item">
                                <i class="bi bi-book"></i>
                                <span>Kursus Persiapan</span>
                            </a>
                            <a href="/login.php" class="link-item">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Login</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="mt-4">
                    <p class="text-muted small">
                        Jika masalah berlanjut, silakan hubungi:
                        <a href="mailto:upabahasa@upnvj.ac.id" class="text-decoration-none">upabahasa@upnvj.ac.id</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Back to Top -->
        <div class="text-center mt-4">
            <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="text-white text-decoration-none">
                <i class="bi bi-arrow-up-circle me-2"></i>Kembali ke Atas
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus search input
        document.getElementById('searchInput').focus();
        
        // Search functionality
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                // Simple search redirect - you can enhance this
                window.location.href = '/?search=' + encodeURIComponent(query);
            }
        }
        
        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Add some animation
        window.addEventListener('load', function() {
            document.querySelector('.error-container').style.animation = 'slideInUp 0.5s ease-out';
        });
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInUp {
                from {
                    transform: translateY(50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>