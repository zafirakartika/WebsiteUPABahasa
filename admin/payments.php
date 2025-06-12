<?php
require_once '../config/database.php';
requireRole('admin');

// Handle payment confirmation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $registration_id = $_POST['registration_id'] ?? null;
    $payment_type = $_POST['payment_type'] ?? 'elpt';
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'confirm_payment' && $registration_id) {
        try {
            if ($payment_type === 'elpt') {
                // Update ELPT registration to payment_verified
                $stmt = $pdo->prepare("
                    UPDATE elpt_registrations 
                    SET payment_status = 'payment_verified', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$registration_id]);
            } else if ($payment_type === 'course') {
                // Update course payment and activate course
                $stmt = $pdo->prepare("
                    UPDATE courses 
                    SET payment_status = 'payment_verified', status = 'active', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$registration_id]);
            }
            
            showAlert('Pembayaran berhasil dikonfirmasi!', 'success');
            
        } catch (PDOException $e) {
            showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
        }
    }
    
    if ($action === 'reject_payment' && $registration_id) {
        try {
            if ($payment_type === 'elpt') {
                // Update ELPT registration back to confirmed so student can re-upload
                $stmt = $pdo->prepare("
                    UPDATE elpt_registrations 
                    SET payment_status = 'confirmed', 
                        payment_proof_file = NULL,
                        payment_proof_uploaded_at = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$registration_id]);
            } else if ($payment_type === 'course') {
                // Update course back to pending payment
                $stmt = $pdo->prepare("
                    UPDATE courses 
                    SET payment_status = 'pending', 
                        payment_proof_file = NULL,
                        payment_proof_uploaded_at = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$registration_id]);
            }
            
            showAlert('Pembayaran ditolak. Mahasiswa dapat mengupload ulang bukti pembayaran.', 'warning');
            
        } catch (PDOException $e) {
            showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
        }
    }
    
    header('Location: payments.php');
    exit;
}

// Get all registrations with payment information (both ELPT and Course)
$filter_status = $_GET['status'] ?? 'all';
$filter_service = $_GET['service'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$registrations = [];

// Get ELPT registrations (only if service filter allows)
if ($filter_service === 'all' || $filter_service === 'elpt') {
    $sql_elpt = "
        SELECT r.id, r.user_id, r.test_date, r.time_slot, r.purpose, r.payment_status,
               r.billing_number, r.created_at, r.updated_at, r.payment_proof_file, r.payment_proof_uploaded_at,
               u.name, u.nim, u.no_telpon, u.program, u.faculty, u.level,
               'elpt' as payment_type, 'ELPT Test' as service_name
        FROM elpt_registrations r 
        JOIN users u ON r.user_id = u.id 
        WHERE 1=1
    ";

    $params_elpt = [];

    if ($filter_status !== 'all') {
        $sql_elpt .= " AND r.payment_status = ?";
        $params_elpt[] = $filter_status;
    }

    if (!empty($filter_date)) {
        $sql_elpt .= " AND r.test_date = ?";
        $params_elpt[] = $filter_date;
    }

    if (!empty($search)) {
        $sql_elpt .= " AND (u.name LIKE ? OR u.nim LIKE ? OR r.billing_number LIKE ?)";
        $search_param = '%' . $search . '%';
        $params_elpt[] = $search_param;
        $params_elpt[] = $search_param;
        $params_elpt[] = $search_param;
    }

    $stmt_elpt = $pdo->prepare($sql_elpt);
    $stmt_elpt->execute($params_elpt);
    $elpt_registrations = $stmt_elpt->fetchAll();
    $registrations = array_merge($registrations, $elpt_registrations);
}

// Get course registrations (only if service filter allows)
if ($filter_service === 'all' || $filter_service === 'course') {
    $sql_course = "
        SELECT c.id, c.user_id, c.final_test_date as test_date, NULL as time_slot,
               'Kursus Persiapan ELPT' as purpose, 
               COALESCE(c.payment_status, 'pending') as payment_status,
               CONCAT('COURSE-', YEAR(c.created_at), MONTH(c.created_at), '-', LPAD(c.id, 4, '0')) as billing_number,
               c.created_at, c.updated_at, c.payment_proof_file, c.payment_proof_uploaded_at,
               u.name, u.nim, u.no_telpon, u.program, u.faculty, u.level,
               'course' as payment_type, 'Kursus Persiapan' as service_name
        FROM courses c
        JOIN users u ON c.user_id = u.id 
        WHERE c.payment_status IS NOT NULL
    ";

    $params_course = [];

    if ($filter_status !== 'all') {
        $sql_course .= " AND c.payment_status = ?";
        $params_course[] = $filter_status;
    }

    if (!empty($search)) {
        $search_param = $search_param ?? '%' . $search . '%';
        $sql_course .= " AND (u.name LIKE ? OR u.nim LIKE ?)";
        $params_course[] = $search_param;
        $params_course[] = $search_param;
    }

    $stmt_course = $pdo->prepare($sql_course);
    $stmt_course->execute($params_course);
    $course_registrations = $stmt_course->fetchAll();
    $registrations = array_merge($registrations, $course_registrations);
}

// Sort by created_at
usort($registrations, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get statistics for both ELPT and courses
$stats = [];

// ELPT stats
$stmt = $pdo->query("
    SELECT payment_status, COUNT(*) as count 
    FROM elpt_registrations 
    GROUP BY payment_status
");
while ($row = $stmt->fetch()) {
    $stats[$row['payment_status']] = ($stats[$row['payment_status']] ?? 0) + $row['count'];
}

// Course stats
$stmt = $pdo->query("
    SELECT payment_status, COUNT(*) as count 
    FROM courses 
    WHERE payment_status IS NOT NULL
    GROUP BY payment_status
");
while ($row = $stmt->fetch()) {
    $stats[$row['payment_status']] = ($stats[$row['payment_status']] ?? 0) + $row['count'];
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
    <title>Kelola Pembayaran - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                    <h2 class="fw-bold">Kelola Pembayaran</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-check-circle" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['confirmed'] ?? 0 ?></div>
                                <small>Menunggu Upload</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-upload" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['payment_uploaded'] ?? 0 ?></div>
                                <small>Bukti Terupload</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-shield-check" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['payment_verified'] ?? 0 ?></div>
                                <small>Dikonfirmasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-secondary text-white">
                            <div class="card-body">
                                <i class="bi bi-list-ul" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= array_sum($stats) ?></div>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter Layanan</label>
                                <select name="service" class="form-select">
                                    <option value="all" <?= ($_GET['service'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Layanan</option>
                                    <option value="elpt" <?= ($_GET['service'] ?? '') === 'elpt' ? 'selected' : '' ?>>ELPT Only</option>
                                    <option value="course" <?= ($_GET['service'] ?? '') === 'course' ? 'selected' : '' ?>>Kursus Only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Menunggu Upload</option>
                                    <option value="payment_uploaded" <?= $filter_status === 'payment_uploaded' ? 'selected' : '' ?>>Bukti Terupload</option>
                                    <option value="payment_verified" <?= $filter_status === 'payment_verified' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                </select>
                            </div>
                            <div class="col-md-4">
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
                                            <th>Layanan</th>
                                            <th>Billing</th>
                                            <th>Bukti Bayar</th>
                                            <th>Status</th>
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
                                                    <div>
                                                        <span class="badge <?= $reg['payment_type'] === 'elpt' ? 'bg-primary' : 'bg-success' ?>">
                                                            <?= strtoupper($reg['payment_type']) ?>
                                                        </span><br>
                                                        <?php if ($reg['payment_type'] === 'elpt'): ?>
                                                            <strong><?= formatDate($reg['test_date']) ?></strong><br>
                                                            <small class="text-muted"><?= formatTimeSlot($reg['time_slot'], $reg['test_date']) ?></small><br>
                                                            <span class="badge bg-info"><?= htmlspecialchars($reg['purpose']) ?></span>
                                                        <?php else: ?>
                                                            <strong>Kursus Persiapan ELPT</strong><br>
                                                            <?php if ($reg['test_date']): ?>
                                                                <small class="text-muted">Final Test: <?= formatDate($reg['test_date']) ?></small>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="bg-light p-1 rounded"><?= htmlspecialchars($reg['billing_number']) ?></code><br>
                                                    <small class="text-success fw-bold">
                                                        <?= formatCurrency($reg['payment_type'] === 'elpt' ? ELPT_FEE : COURSE_FEE) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($reg['payment_proof_file']): ?>
                                                        <div class="text-center">
                                                            <a href="../<?= htmlspecialchars($reg['payment_proof_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i> Lihat
                                                            </a><br>
                                                            <small class="text-muted">
                                                                <?= formatDateTime($reg['payment_proof_uploaded_at']) ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">Belum upload</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= getPaymentStatusBadge($reg['payment_status']) ?>">
                                                        <?= getPaymentStatusText($reg['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if ($reg['payment_status'] === 'payment_uploaded'): ?>
                                                            <button class="btn btn-success confirm-payment-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#confirmModal"
                                                                    data-registration-id="<?= $reg['id'] ?>"
                                                                    data-payment-type="<?= $reg['payment_type'] ?>"
                                                                    data-student-name="<?= htmlspecialchars($reg['name']) ?>"
                                                                    data-proof-file="../<?= htmlspecialchars($reg['payment_proof_file']) ?>">
                                                                <i class="bi bi-check-circle"></i> Konfirmasi
                                                            </button>
                                                            <button class="btn btn-warning reject-payment-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#rejectModal"
                                                                    data-registration-id="<?= $reg['id'] ?>"
                                                                    data-payment-type="<?= $reg['payment_type'] ?>"
                                                                    data-student-name="<?= htmlspecialchars($reg['name']) ?>">
                                                                <i class="bi bi-x-circle"></i> Tolak
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted small">
                                                                <?= getPaymentStatusText($reg['payment_status']) ?>
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
                
                <!-- Info Cards -->
                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Alur Pembayaran</h6>
                            </div>
                            <div class="card-body">
                                <ol class="mb-0">
                                    <li>Mahasiswa daftar ELPT/Kursus</li>
                                    <li>Mahasiswa upload bukti pembayaran</li>
                                    <li>Admin konfirmasi pembayaran</li>
                                    <li>Layanan diaktifkan</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Petunjuk</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Periksa bukti pembayaran dengan teliti</li>
                                    <li>ELPT: <?= formatCurrency(ELPT_FEE) ?></li>
                                    <li>Kursus: <?= formatCurrency(COURSE_FEE) ?></li>
                                    <li>Konfirmasi jika pembayaran valid</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>Konfirmasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="confirm_payment">
                        <input type="hidden" name="registration_id" id="confirm_registration_id">
                        <input type="hidden" name="payment_type" id="confirm_payment_type">
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-person me-2"></i>Mahasiswa: <span id="confirm_student_name"></span></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bukti Pembayaran</label>
                            <div class="text-center p-3 border rounded">
                                <img id="confirm_proof_image" src="" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 400px; display: none;">
                                <iframe id="confirm_proof_pdf" src="" style="width: 100%; height: 400px; display: none;"></iframe>
                                <a id="confirm_proof_link" href="" target="_blank" class="btn btn-primary" style="display: none !important;">
                                    <i class="bi bi-download me-2"></i>Download Bukti Pembayaran
                                </a>
                            </div>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Setelah dikonfirmasi:</strong> Status pembayaran akan berubah menjadi "DIKONFIRMASI" dan layanan akan diaktifkan.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Konfirmasi Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Rejection Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle me-2"></i>Tolak Pembayaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject_payment">
                        <input type="hidden" name="registration_id" id="reject_registration_id">
                        <input type="hidden" name="payment_type" id="reject_payment_type">
                        
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-person me-2"></i>Mahasiswa: <span id="reject_student_name"></span></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="4" 
                                      placeholder="Jelaskan alasan penolakan (misalnya: bukti pembayaran tidak jelas, nominal tidak sesuai, dll.)"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Setelah ditolak:</strong> Status akan kembali dan mahasiswa dapat mengupload ulang bukti pembayaran.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-x-circle me-2"></i>Tolak Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm payment modal handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.confirm-payment-btn')) {
                const btn = e.target.closest('.confirm-payment-btn');
                const registrationId = btn.getAttribute('data-registration-id');
                const paymentType = btn.getAttribute('data-payment-type');
                const studentName = btn.getAttribute('data-student-name');
                const proofFile = btn.getAttribute('data-proof-file');
                
                document.getElementById('confirm_registration_id').value = registrationId;
                document.getElementById('confirm_payment_type').value = paymentType;
                document.getElementById('confirm_student_name').textContent = studentName;
                
                // Handle different file types
                const fileExt = proofFile.split('.').pop().toLowerCase();
                const proofImage = document.getElementById('confirm_proof_image');
                const proofPdf = document.getElementById('confirm_proof_pdf');
                const proofLink = document.getElementById('confirm_proof_link');
                
                proofLink.href = proofFile;
                
                if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                    proofImage.src = proofFile;
                    proofImage.style.display = 'block';
                    proofPdf.style.display = 'none';
                } else if (fileExt === 'pdf') {
                    proofPdf.src = proofFile;
                    proofPdf.style.display = 'block';
                    proofImage.style.display = 'none';
                }
            }
        });

        // Reject payment modal handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.reject-payment-btn')) {
                const btn = e.target.closest('.reject-payment-btn');
                const registrationId = btn.getAttribute('data-registration-id');
                const paymentType = btn.getAttribute('data-payment-type');
                const studentName = btn.getAttribute('data-student-name');
                
                document.getElementById('reject_registration_id').value = registrationId;
                document.getElementById('reject_payment_type').value = paymentType;
                document.getElementById('reject_student_name').textContent = studentName;
            }
        });

        // Form validation and loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
                    submitBtn.disabled = true;
                }
            });
        });
    </script>
</body>
</html>

<?php
// Helper functions for payment status
function getPaymentStatusBadge($status) {
    switch($status) {
        case 'pending': return 'bg-warning text-dark';
        case 'confirmed': return 'bg-info text-white';
        case 'payment_uploaded': return 'bg-primary text-white';
        case 'payment_verified': return 'bg-success text-white';
        case 'active': return 'bg-success text-white'; 
        case 'completed': return 'bg-secondary text-white'; 
        case 'rejected': return 'bg-danger text-white';
        default: return 'bg-secondary text-white';
    }
}

function getPaymentStatusText($status) {
    switch($status) {
        case 'pending': return 'MENUNGGU';
        case 'confirmed': return 'MENUNGGU UPLOAD';
        case 'payment_uploaded': return 'BUKTI TERUPLOAD';
        case 'payment_verified': return 'DIKONFIRMASI';
        case 'active': return 'AKTIF'; 
        case 'completed': return 'SELESAI'; 
        case 'rejected': return 'DITOLAK';
        default: return strtoupper($status);
    }
}
?>