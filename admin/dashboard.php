<?php
require_once '../config/database.php';
requireRole('admin');

// Get statistics
$stats = [];

// Total registrations
$stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations");
$stats['total_registrations'] = $stmt->fetch()['count'];

// Pending payments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations WHERE payment_status = 'pending'");
$stats['pending_payments'] = $stmt->fetch()['count'];

// Confirmed for today and future
$stmt = $pdo->query("SELECT COUNT(*) as count FROM elpt_registrations WHERE payment_status = 'confirmed' AND test_date >= CURDATE()");
$stats['upcoming_tests'] = $stmt->fetch()['count'];

// Total students
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $stmt->fetch()['count'];

// Recent registrations
$stmt = $pdo->query("
    SELECT r.*, u.name, u.nim, u.program, u.faculty 
    FROM elpt_registrations r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$recent_registrations = $stmt->fetchAll();

// Upcoming tests (next 7 days)
$stmt = $pdo->query("
    SELECT test_date, COUNT(*) as count
    FROM elpt_registrations 
    WHERE test_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND payment_status = 'confirmed'
    GROUP BY test_date 
    ORDER BY test_date
");
$upcoming_tests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Dashboard Admin</h2>
                        <p class="text-muted mb-0">Selamat datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted"><?= date('l, d F Y') ?></small>
                    </div>
                </div>

                <?php displayAlert(); ?>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card card bg-primary text-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="display-4"><?= $stats['total_registrations'] ?></div>
                                    <h6>Total Pendaftaran</h6>
                                </div>
                                <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card card bg-warning text-dark p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="display-4"><?= $stats['pending_payments'] ?></div>
                                    <h6>Menunggu Pembayaran</h6>
                                </div>
                                <i class="bi bi-hourglass-split" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card card bg-info text-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="display-4"><?= $stats['upcoming_tests'] ?></div>
                                    <h6>Tes Mendatang</h6>
                                </div>
                                <i class="bi bi-calendar-event" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card card bg-success text-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="display-4"><?= $stats['total_students'] ?></div>
                                    <h6>Total Mahasiswa</h6>
                                </div>
                                <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row g-4">
                    <!-- Recent Registrations -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pendaftaran Terbaru</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recent_registrations)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Belum ada pendaftaran</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama</th>
                                                    <th>NIM</th>
                                                    <th>Tanggal Tes</th>
                                                    <th>Keperluan</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_registrations as $reg): ?>
                                                    <tr>
                                                        <td>
                                                            <div>
                                                                <strong><?= htmlspecialchars($reg['name']) ?></strong><br>
                                                                <small class="text-muted"><?= htmlspecialchars($reg['program']) ?></small>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($reg['nim']) ?></td>
                                                        <td><?= formatDate($reg['test_date']) ?></td>
                                                        <td><span class="badge bg-info"><?= htmlspecialchars($reg['purpose']) ?></span></td>
                                                        <td>
                                                            <span class="badge <?= 
                                                                $reg['payment_status'] === 'confirmed' ? 'bg-success' : 
                                                                ($reg['payment_status'] === 'rejected' ? 'bg-danger' : 'bg-warning') 
                                                            ?>">
                                                                <?= strtoupper($reg['payment_status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="registrations.php?id=<?= $reg['id'] ?>" class="btn btn-outline-primary">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <?php if ($reg['payment_status'] === 'pending'): ?>
                                                                    <a href="payments.php?confirm=<?= $reg['id'] ?>" class="btn btn-outline-success">
                                                                        <i class="bi bi-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer">
                                        <a href="registrations.php" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-right me-1"></i>Lihat Semua
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Tests & Quick Actions -->
                    <div class="col-lg-4">
                        <!-- Upcoming Tests -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Tes Minggu Ini</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcoming_tests)): ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2 mb-0">Tidak ada tes minggu ini</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($upcoming_tests as $test): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <strong><?= formatDate($test['test_date']) ?></strong><br>
                                                <small class="text-muted"><?= $test['count'] ?> peserta</small>
                                            </div>
                                            <span class="badge bg-primary"><?= $test['count'] ?></span>
                                        </div>
                                        <?php if (!next($upcoming_tests)): ?><hr><?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Aksi Cepat</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="payments.php" class="btn btn-outline-warning">
                                        <i class="bi bi-credit-card me-2"></i>Konfirmasi Pembayaran
                                        <?php if ($stats['pending_payments'] > 0): ?>
                                            <span class="badge bg-warning text-dark ms-2"><?= $stats['pending_payments'] ?></span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <a href="test-results.php" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-plus me-2"></i>Input Hasil Tes
                                    </a>
                                    
                                    <a href="registrations.php" class="btn btn-outline-info">
                                        <i class="bi bi-calendar-event me-2"></i>Kelola Pendaftaran
                                    </a>
                                    
                                    <a href="students.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-people me-2"></i>Data Mahasiswa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-server text-success me-2"></i>
                                            <div>
                                                <strong>Database</strong><br>
                                                <small class="text-success">Online</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-shield-check text-success me-2"></i>
                                            <div>
                                                <strong>System</strong><br>
                                                <small class="text-success">Secure</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-clock text-info me-2"></i>
                                            <div>
                                                <strong>Last Update</strong><br>
                                                <small class="text-muted"><?= date('H:i') ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <i class="bi bi-person-badge text-primary me-2"></i>
                                            <div>
                                                <strong>Admin</strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($_SESSION['user_name']) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>