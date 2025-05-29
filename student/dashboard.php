<?php
require_once '../config/database.php';
requireRole('student');

$user = getCurrentUser();

// Get latest registration
$stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$latest_registration = $stmt->fetch();

// Get latest result
$stmt = $pdo->prepare("SELECT * FROM elpt_results WHERE user_id = ? ORDER BY test_date DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$latest_result = $stmt->fetch();

// Get course status
$stmt = $pdo->prepare("SELECT * FROM courses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$course_status = $stmt->fetch();

// Check if user needs to register for ELPT
$needs_registration = !$latest_registration || 
                     ($latest_registration['payment_status'] === 'rejected') ||
                     ($latest_result && $latest_result['total_score'] < 450);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/student.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="text-white p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-2">UPA Bahasa</h5>
                        <small>UPNVJ</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="elpt-registration.php">
                            <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT
                        </a>
                        <a class="nav-link" href="test-results.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Hasil Tes
                        </a>
                        <a class="nav-link" href="course.php">
                            <i class="bi bi-book me-2"></i>Kursus
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Dashboard Mahasiswa</h2>
                        <p class="text-muted mb-0">Selamat datang, <?= htmlspecialchars($user['name']) ?>!</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">NIM: <?= htmlspecialchars($user['nim']) ?></small><br>
                        <small class="text-muted"><?= htmlspecialchars($user['program']) ?> - <?= htmlspecialchars($user['level']) ?></small>
                    </div>
                </div>

                <!-- Alert Section -->
                <?php displayAlert(); ?>

                <!-- Status Cards -->
                <div class="row g-4 mb-4">
                    <!-- ELPT Status -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-award me-2"></i>Status ELPT</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($latest_result): ?>
                                    <!-- Has Test Result -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="scoreChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center mb-3">
                                                <div class="score-display <?= $latest_result['total_score'] >= 450 ? 'text-success' : 'text-warning' ?>">
                                                    <?= $latest_result['total_score'] ?>
                                                </div>
                                                <p class="text-muted">Total Score</p>
                                                <span class="status-badge <?= $latest_result['total_score'] >= 450 ? 'bg-success' : 'bg-warning' ?> text-white">
                                                    <?= $latest_result['total_score'] >= 450 ? 'LULUS' : 'BELUM LULUS' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">Tanggal Tes:</small><br>
                                                <strong><?= formatDate($latest_result['test_date']) ?></strong>
                                            </div>
                                            
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <div class="fw-bold text-primary"><?= $latest_result['listening_score'] ?></div>
                                                        <small class="text-muted">Listening</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <div class="fw-bold text-info"><?= $latest_result['structure_score'] ?></div>
                                                        <small class="text-muted">Structure</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border rounded p-2">
                                                        <div class="fw-bold text-success"><?= $latest_result['reading_score'] ?></div>
                                                        <small class="text-muted">Reading</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <?php if ($latest_result['total_score'] >= 450): ?>
                                                    <a href="../download-certificate.php?id=<?= $latest_result['id'] ?>" class="btn btn-success btn-lg" target="_blank">
                                                        <i class="bi bi-download me-2"></i>Download Sertifikat
                                                    </a>
                                                <?php else: ?>
                                                    <a href="elpt-registration.php" class="btn btn-primary w-100 mb-2">
                                                        <i class="bi bi-arrow-repeat me-2"></i>Daftar ELPT Ulang
                                                    </a>
                                                    <a href="course.php" class="btn btn-outline-primary w-100">
                                                        <i class="bi bi-book me-2"></i>Daftar Kursus Persiapan
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($latest_registration): ?>
                                    <!-- Has Registration but No Result -->
                                    <div class="text-center">
                                        <i class="bi bi-clock-history text-primary" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3">Pendaftaran ELPT Anda</h5>
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 mb-3">
                                                    <strong>Tanggal Tes</strong><br>
                                                    <span class="text-primary"><?= formatDate($latest_registration['test_date']) ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 mb-3">
                                                    <strong>Keperluan</strong><br>
                                                    <span><?= htmlspecialchars($latest_registration['purpose']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 mb-3">
                                                    <strong>Billing Number</strong><br>
                                                    <code><?= htmlspecialchars($latest_registration['billing_number']) ?></code>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 mb-3">
                                                    <strong>Status Pembayaran</strong><br>
                                                    <span class="status-badge <?= 
                                                        $latest_registration['payment_status'] === 'confirmed' ? 'bg-success' : 
                                                        ($latest_registration['payment_status'] === 'rejected' ? 'bg-danger' : 'bg-warning') 
                                                    ?> text-white">
                                                        <?= strtoupper($latest_registration['payment_status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($latest_registration['payment_status'] === 'pending'): ?>
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Silakan lakukan pembayaran dan tunggu konfirmasi dari admin.
                                            </div>
                                        <?php elseif ($latest_registration['payment_status'] === 'confirmed'): ?>
                                            <div class="alert alert-success">
                                                <i class="bi bi-check-circle me-2"></i>
                                                Pembayaran telah dikonfirmasi. Harap hadir pada tanggal yang ditentukan.
                                            </div>
                                        <?php elseif ($latest_registration['payment_status'] === 'rejected'): ?>
                                            <div class="alert alert-danger">
                                                <i class="bi bi-x-circle me-2"></i>
                                                Pembayaran ditolak. Silakan daftar ulang atau hubungi admin.
                                            </div>
                                            <a href="elpt-registration.php" class="btn btn-primary">
                                                <i class="bi bi-arrow-repeat me-2"></i>Daftar Ulang
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                <?php else: ?>
                                    <!-- No Registration -->
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                                        <h5 class="mt-3 text-muted">Belum Ada Pendaftaran ELPT</h5>
                                        <p class="text-muted">Daftar sekarang untuk mengikuti tes ELPT</p>
                                        <a href="elpt-registration.php" class="btn btn-primary btn-lg">
                                            <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT Sekarang
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Profile & Course Status -->
                    <div class="col-lg-4">
                        <!-- Profile Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Profil Mahasiswa</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px; font-size: 1.5rem; color: white;">
                                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <h6 class="fw-bold"><?= htmlspecialchars($user['name']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                </div>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">NIM</small><br>
                                        <strong><?= htmlspecialchars($user['nim']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Jenjang</small><br>
                                        <strong><?= htmlspecialchars($user['level']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Course Status Card -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-book me-2"></i>Status Kursus</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($course_status): ?>
                                    <div class="text-center">
                                        <div class="progress mb-3" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= ($course_status['current_session']/$course_status['total_sessions']) * 100 ?>%">
                                                <?= $course_status['current_session'] ?>/<?= $course_status['total_sessions'] ?>
                                            </div>
                                        </div>
                                        <h6>Sesi ke-<?= $course_status['current_session'] ?></h6>
                                        <p class="text-muted mb-3">dari <?= $course_status['total_sessions'] ?> sesi total</p>
                                        
                                        <?php if ($course_status['final_test_date']): ?>
                                            <div class="alert alert-info">
                                                <small>
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    Final Test: <?= formatDate($course_status['final_test_date']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span class="status-badge <?= 
                                            $course_status['status'] === 'completed' ? 'bg-success' : 
                                            ($course_status['status'] === 'active' ? 'bg-primary' : 'bg-warning') 
                                        ?> text-white">
                                            <?= strtoupper($course_status['status']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <i class="bi bi-book text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">Belum mengikuti kursus</p>
                                        <a href="course.php" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-plus-circle me-1"></i>Daftar Kursus
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-calendar-plus text-primary" style="font-size: 2rem;"></i>
                                <h6 class="mt-3">Daftar ELPT</h6>
                                <p class="text-muted small">Daftar untuk tes ELPT terbaru</p>
                                <a href="elpt-registration.php" class="btn btn-primary btn-sm">Daftar Sekarang</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-file-earmark-text text-info" style="font-size: 2rem;"></i>
                                <h6 class="mt-3">Hasil Tes</h6>
                                <p class="text-muted small">Lihat riwayat hasil tes ELPT</p>
                                <a href="test-results.php" class="btn btn-info btn-sm">Lihat Hasil</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-book text-success" style="font-size: 2rem;"></i>
                                <h6 class="mt-3">Kursus Persiapan</h6>
                                <p class="text-muted small">Ikuti kursus persiapan ELPT</p>
                                <a href="course.php" class="btn btn-success btn-sm">Info Kursus</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($latest_result): ?>
    <script>
        // Create score chart
        const ctx = document.getElementById('scoreChart').getContext('2d');
        const scoreChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Listening', 'Structure', 'Reading'],
                datasets: [{
                    data: [<?= $latest_result['listening_score'] ?>, <?= $latest_result['structure_score'] ?>, <?= $latest_result['reading_score'] ?>],
                    backgroundColor: [
                        '#667eea',
                        '#17a2b8',
                        '#28a745'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>