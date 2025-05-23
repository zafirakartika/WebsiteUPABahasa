<?php
require_once '../config/database.php';
requireRole('admin');

// Handle payment confirmation
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $registration_id = $_GET['confirm'];
    
    try {
        $stmt = $pdo->prepare("UPDATE elpt_registrations SET payment_status = 'confirmed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$registration_id]);
        
        showAlert('Pembayaran berhasil dikonfirmasi!', 'success');
        header('Location: payments.php');
        exit;
    } catch (PDOException $e) {
        showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
    }
}

// Handle payment rejection
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $registration_id = $_GET['reject'];
    
    try {
        $stmt = $pdo->prepare("UPDATE elpt_registrations SET payment_status = 'rejected', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$registration_id]);
        
        showAlert('Pembayaran ditolak!', 'warning');
        header('Location: payments.php');
        exit;
    } catch (PDOException $e) {
        showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
    }
}

// Get all registrations with payment status
$filter = $_GET['filter'] ?? 'pending';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT r.*, u.name, u.nim, u.no_telpon, u.program, u.faculty, u.level 
    FROM elpt_registrations r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1
";

$params = [];

if ($filter !== 'all') {
    $sql .= " AND r.payment_status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.nim LIKE ? OR r.billing_number LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM elpt_registrations GROUP BY payment_status");
while ($row = $stmt->fetch()) {
    $stats[$row['payment_status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #dc3545 0%, #6f42c1 100%);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 10px;
            margin: 5px 0;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .stat-badge {
            font-size: 1.2rem;
            padding: 10px 15px;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="text-white p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-gear-fill" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-2">Admin Panel</h5>
                        <small>UPA Bahasa UPNVJ</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="registrations.php">
                            <i class="bi bi-calendar-event me-2"></i>Pendaftaran
                        </a>
                        <a class="nav-link active" href="payments.php">
                            <i class="bi bi-credit-card me-2"></i>Pembayaran
                        </a>
                        <a class="nav-link" href="test-results.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Input Hasil
                        </a>
                        <a class="nav-link" href="students.php">
                            <i class="bi bi-people me-2"></i>Data Mahasiswa
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
                    <h2 class="fw-bold">Kelola Pembayaran</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-warning text-dark">
                            <div class="card-body">
                                <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                                <div class="stat-badge bg-dark text-white mt-2">
                                    <?= $stats['pending'] ?? 0 ?>
                                </div>
                                <h6 class="mt-2">Menunggu</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                <div class="stat-badge bg-dark mt-2">
                                    <?= $stats['confirmed'] ?? 0 ?>
                                </div>
                                <h6 class="mt-2">Dikonfirmasi</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body">
                                <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                                <div class="stat-badge bg-dark mt-2">
                                    <?= $stats['rejected'] ?? 0 ?>
                                </div>
                                <h6 class="mt-2">Ditolak</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-list-ul" style="font-size: 2rem;"></i>
                                <div class="stat-badge bg-dark mt-2">
                                    <?= array_sum($stats) ?>
                                </div>
                                <h6 class="mt-2">Total</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Filter Status</label>
                                <select name="filter" class="form-select">
                                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="confirmed" <?= $filter === 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                    <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pencarian</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, NIM, atau Billing Number" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Data Pembayaran</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($registrations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Tidak ada data pembayaran</h5>
                                <p class="text-muted">Belum ada pendaftaran atau tidak ada yang sesuai dengan filter</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mahasiswa</th>
                                            <th>Kontak</th>
                                            <th>Tes Info</th>
                                            <th>Billing</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($reg['name']) ?></strong><br>
                                                        <small class="text-muted">
                                                            NIM: <?= htmlspecialchars($reg['nim']) ?><br>
                                                            <?= htmlspecialchars($reg['program_studi']) ?> - <?= htmlspecialchars($reg['jenjang']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($reg['no_telpon'] ?? 'N/A') ?><br>
                                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($reg['fakultas']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= formatDate($reg['test_date']) ?></strong><br>
                                                        <span class="badge bg-info"><?= htmlspecialchars($reg['keperluan']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="bg-light p-1 rounded"><?= htmlspecialchars($reg['billing_number']) ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge <?= 
                                                        $reg['payment_status'] === 'confirmed' ? 'bg-success' : 
                                                        ($reg['payment_status'] === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') 
                                                    ?>">
                                                        <?= strtoupper($reg['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        Daftar: <?= formatDate($reg['created_at']) ?><br>
                                                        Update: <?= formatDate($reg['updated_at']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if ($reg['payment_status'] === 'pending'): ?>
                                                            <a href="?confirm=<?= $reg['id'] ?>" 
                                                               class="btn btn-success"
                                                               onclick="return confirm('Konfirmasi pembayaran ini?')">
                                                                <i class="bi bi-check"></i> Konfirmasi
                                                            </a>
                                                            <a href="?reject=<?= $reg['id'] ?>" 
                                                               class="btn btn-danger"
                                                               onclick="return confirm('Tolak pembayaran ini?')">
                                                                <i class="bi bi-x"></i> Tolak
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">
                                                                <?= $reg['payment_status'] === 'confirmed' ? 'Sudah dikonfirmasi' : 'Ditolak' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Petunjuk Konfirmasi</h6>
                            </div>
                            <div class="card-body">
                                <ol class="mb-0">
                                    <li>Verifikasi pembayaran mahasiswa melalui sistem keuangan</li>
                                    <li>Pastikan jumlah pembayaran sesuai (Rp 100.000)</li>
                                    <li>Cocokkan billing number dengan data pembayaran</li>
                                    <li>Klik "Konfirmasi" jika pembayaran valid</li>
                                    <li>Klik "Tolak" jika ada masalah dengan pembayaran</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Perhatian</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Konfirmasi pembayaran tidak dapat dibatalkan</li>
                                    <li>Mahasiswa akan mendapat notifikasi otomatis</li>
                                    <li>Pastikan data sudah benar sebelum konfirmasi</li>
                                    <li>Pembayaran yang ditolak dapat didaftar ulang</li>
                                </ul>
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