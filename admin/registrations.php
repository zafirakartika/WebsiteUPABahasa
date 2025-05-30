<?php
// admin/registrations.php
require_once '../config/database.php';
requireRole('admin');

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $registration_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM elpt_registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        showAlert('Pendaftaran berhasil dihapus!', 'success');
        header('Location: registrations.php');
        exit;
    } catch (PDOException $e) {
        showAlert('Tidak dapat menghapus pendaftaran: ' . $e->getMessage(), 'error');
    }
}

// Handle registration update (without payment status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $registration_id = $_POST['registration_id'] ?? '';
    $test_date = $_POST['test_date'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    
    $errors = [];
    
    if (empty($registration_id) || !is_numeric($registration_id)) {
        $errors[] = 'Invalid registration ID';
    }
    
    if (empty($test_date)) {
        $errors[] = 'Test date is required';
    }
    
    if (empty($purpose)) {
        $errors[] = 'Purpose is required';
    }
    
    // Validate test date
    if (!empty($test_date)) {
        $selected_date = new DateTime($test_date);
        $day_of_week = $selected_date->format('N');
        
        if (!in_array($day_of_week, [2, 4, 6])) {
            $errors[] = 'Test date must be Tuesday, Thursday, or Saturday';
        }
    }
    
    if (empty($errors)) {
        try {
            // Check if registration exists
            $stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            $registration = $stmt->fetch();
            
            if (!$registration) {
                $errors[] = 'Registration not found';
            } else {
                // Check quota for new test date (if date is being changed)
                if ($test_date !== $registration['test_date']) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM elpt_registrations 
                        WHERE test_date = ? 
                        AND payment_status IN ('pending', 'confirmed')
                        AND id != ?
                    ");
                    $stmt->execute([$test_date, $registration_id]);
                    $count = $stmt->fetch()['count'];
                    
                    $max_participants = getSystemSetting('max_participants_per_session', 30);
                    
                    if ($count >= $max_participants) {
                        $errors[] = 'Selected date is fully booked';
                    }
                }
                
                if (empty($errors)) {
                    // Update registration (without payment status)
                    $stmt = $pdo->prepare("
                        UPDATE elpt_registrations 
                        SET test_date = ?, purpose = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([$test_date, $purpose, $registration_id]);
                    
                    showAlert('Pendaftaran berhasil diperbarui!', 'success');
                    header('Location: registrations.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    }
    
    if (!empty($errors)) {
        showAlert(implode('<br>', $errors), 'error');
    }
}

// Get all registrations with filters
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT r.*, u.name, u.nim, u.no_telpon, u.program, u.faculty, u.level 
    FROM elpt_registrations r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1
";

$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND r.payment_status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_date)) {
    $sql .= " AND r.test_date = ?";
    $params[] = $filter_date;
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

// Get available test dates
$stmt = $pdo->query("SELECT DISTINCT test_date FROM elpt_registrations ORDER BY test_date DESC");
$available_dates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftaran - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                        <a class="nav-link active" href="registrations.php">
                            <i class="bi bi-calendar-event me-2"></i>Pendaftaran
                        </a>
                        <a class="nav-link" href="payments.php">
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
                    <h2 class="fw-bold">Kelola Pendaftaran ELPT</h2>
                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>

                <?php displayAlert(); ?>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-calendar-event" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= array_sum($stats) ?></div>
                                <h6>Total Pendaftaran</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-warning text-dark">
                            <div class="card-body">
                                <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['pending'] ?? 0 ?></div>
                                <h6>Pending</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['confirmed'] ?? 0 ?></div>
                                <h6>Dikonfirmasi</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body">
                                <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['rejected'] ?? 0 ?></div>
                                <h6>Ditolak</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Untuk mengelola status pembayaran (konfirmasi/tolak), gunakan halaman 
                    <a href="payments.php" class="alert-link">Kelola Pembayaran</a>. 
                    Di halaman ini Anda hanya dapat mengubah tanggal tes dan keperluan.
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                    <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Tes</label>
                                <select name="date" class="form-select">
                                    <option value="">Semua Tanggal</option>
                                    <?php foreach ($available_dates as $date): ?>
                                        <option value="<?= $date['test_date'] ?>" <?= $filter_date === $date['test_date'] ? 'selected' : '' ?>>
                                            <?= formatDate($date['test_date']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pencarian</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, NIM, atau Billing Number" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="registrations.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Registrations Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Data Pendaftaran</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($registrations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Tidak ada data pendaftaran</h5>
                                <p class="text-muted">Belum ada pendaftaran atau tidak ada yang sesuai dengan filter</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 data-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal Daftar</th>
                                            <th>Mahasiswa</th>
                                            <th>Kontak</th>
                                            <th>Tanggal Tes</th>
                                            <th>Keperluan</th>
                                            <th>Billing</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= formatDate($reg['created_at']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($reg['name']) ?></strong><br>
                                                        <small class="text-muted">
                                                            NIM: <?= htmlspecialchars($reg['nim']) ?><br>
                                                            <?= htmlspecialchars($reg['program']) ?> - <?= htmlspecialchars($reg['level']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($reg['no_telpon'] ?? 'N/A') ?><br>
                                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($reg['faculty']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?= formatDate($reg['test_date']) ?></strong><br>
                                                    <small class="text-muted"><?= date('l', strtotime($reg['test_date'])) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= htmlspecialchars($reg['purpose']) ?></span>
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
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#detailModal"
                                                                data-registration='<?= json_encode($reg) ?>'
                                                                title="Lihat Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal"
                                                                data-registration='<?= json_encode($reg) ?>'
                                                                title="Edit Tanggal & Keperluan">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="?delete=<?= $reg['id'] ?>" 
                                                           class="btn btn-outline-danger confirm-action"
                                                           data-message="Hapus pendaftaran ini?"
                                                           title="Hapus Pendaftaran">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
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

                <!-- Quick Actions Info -->
                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Panduan Aksi</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li><i class="bi bi-eye text-primary me-2"></i><strong>Detail:</strong> Lihat informasi lengkap pendaftaran</li>
                                    <li><i class="bi bi-pencil text-warning me-2"></i><strong>Edit:</strong> Ubah tanggal tes dan keperluan</li>
                                    <li><i class="bi bi-trash text-danger me-2"></i><strong>Hapus:</strong> Hapus pendaftaran (tidak dapat dikembalikan)</li>
                                </ul>
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
                                    <li>Status pembayaran hanya bisa diubah di halaman <strong>Kelola Pembayaran</strong></li>
                                    <li>Perubahan tanggal tes akan memeriksa kuota otomatis</li>
                                    <li>Penghapusan pendaftaran tidak dapat dibatalkan</li>
                                    <li>Mahasiswa akan mendapat notifikasi untuk perubahan penting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>Detail Pendaftaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal (Only Date and Purpose) -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Edit Pendaftaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="registration_id" id="edit_registration_id">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Anda hanya dapat mengubah tanggal tes dan keperluan. 
                            Untuk mengubah status pembayaran, gunakan halaman 
                            <a href="payments.php" class="alert-link">Kelola Pembayaran</a>.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Tes</label>
                            <input type="date" class="form-control" name="test_date" id="edit_test_date" required>
                            <small class="text-muted">Hanya tersedia hari Selasa, Kamis, dan Sabtu</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keperluan</label>
                            <select class="form-select" name="purpose" id="edit_purpose" required>
                                <option value="Skripsi/Tesis/Tugas Akhir">Skripsi/Tesis/Tugas Akhir</option>
                                <option value="Yudisium">Yudisium</option>
                                <option value="Kelulusan">Kelulusan</option>
                                <option value="Lamar Kerja">Lamar Kerja</option>
                                <option value="Lamar Beasiswa">Lamar Beasiswa</option>
                                <option value="Ijazah">Ijazah</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                columnDefs: [
                    { orderable: false, targets: [-1] } // Disable sorting on action column
                ]
            });

            // Detail Modal Handler
            $('#detailModal').on('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const registration = JSON.parse(button.getAttribute('data-registration'));
                
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Data Mahasiswa</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Nama:</strong></td><td>${registration.name}</td></tr>
                                <tr><td><strong>NIM:</strong></td><td>${registration.nim}</td></tr>
                                <tr><td><strong>Program Studi:</strong></td><td>${registration.program}</td></tr>
                                <tr><td><strong>Jenjang:</strong></td><td>${registration.level}</td></tr>
                                <tr><td><strong>Fakultas:</strong></td><td>${registration.faculty}</td></tr>
                                <tr><td><strong>No. Telepon:</strong></td><td>${registration.no_telpon || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Data Pendaftaran</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Tanggal Daftar:</strong></td><td>${new Date(registration.created_at).toLocaleDateString('id-ID')}</td></tr>
                                <tr><td><strong>Tanggal Tes:</strong></td><td>${new Date(registration.test_date).toLocaleDateString('id-ID')}</td></tr>
                                <tr><td><strong>Keperluan:</strong></td><td>${registration.purpose}</td></tr>
                                <tr><td><strong>Billing Number:</strong></td><td><code>${registration.billing_number}</code></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-${getStatusColor(registration.payment_status)}">${registration.payment_status.toUpperCase()}</span></td></tr>
                                <tr><td><strong>Update Terakhir:</strong></td><td>${new Date(registration.updated_at).toLocaleDateString('id-ID')}</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    ${registration.payment_status === 'pending' ? 
                        '<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle me-2"></i><strong>Status:</strong> Pembayaran masih menunggu konfirmasi.</div>' : 
                        (registration.payment_status === 'confirmed' ? 
                            '<div class="alert alert-success mt-3"><i class="bi bi-check-circle me-2"></i><strong>Status:</strong> Pembayaran telah dikonfirmasi.</div>' : 
                            '<div class="alert alert-danger mt-3"><i class="bi bi-x-circle me-2"></i><strong>Status:</strong> Pembayaran ditolak.</div>')
                    }
                `;
                
                document.getElementById('detailContent').innerHTML = content;
            });

            // Edit Modal Handler
            $('#editModal').on('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const registration = JSON.parse(button.getAttribute('data-registration'));
                
                document.getElementById('edit_registration_id').value = registration.id;
                document.getElementById('edit_test_date').value = registration.test_date;
                document.getElementById('edit_purpose').value = registration.purpose;
            });

            // Form submission handler
            $('#editForm').on('submit', function(e) {
                const testDate = document.getElementById('edit_test_date').value;
                
                // Validate test date (Tuesday, Thursday, Saturday)
                if (testDate) {
                    const selectedDate = new Date(testDate);
                    const dayOfWeek = selectedDate.getDay(); // 0=Sunday, 1=Monday, ..., 6=Saturday
                    
                    if (![2, 4, 6].includes(dayOfWeek)) { // Tuesday=2, Thursday=4, Saturday=6
                        e.preventDefault();
                        alert('Tanggal tes hanya tersedia pada hari Selasa, Kamis, dan Sabtu');
                        return false;
                    }
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                submitBtn.disabled = true;
                
                // Note: Form will submit normally since we're not using AJAX here
                // The loading state is just for user feedback
            });

            // Confirmation for delete actions
            $('.confirm-action').on('click', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-message') || 'Apakah Anda yakin?';
                if (confirm(message)) {
                    window.location.href = this.href;
                }
            });

            // Add tooltips for better UX
            $('[title]').tooltip();
        });

        function getStatusColor(status) {
            switch(status) {
                case 'confirmed': return 'success';
                case 'rejected': return 'danger';
                default: return 'warning';
            }
        }

        // Set minimum date for test date input (tomorrow)
        document.addEventListener('DOMContentLoaded', function() {
            const testDateInput = document.getElementById('edit_test_date');
            if (testDateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                testDateInput.min = tomorrow.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>