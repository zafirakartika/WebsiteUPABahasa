<?php
require_once '../config/database.php';
requireRole('admin');

// Handle payment verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $registration_id = $_POST['registration_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'verify_payment' && $registration_id) {
        try {
            $pdo->beginTransaction();
            
            // Update registration status
            $stmt = $pdo->prepare("
                UPDATE elpt_registrations 
                SET payment_status = 'payment_verified' 
                WHERE id = ?
            ");
            $stmt->execute([$registration_id]);
            
            // Update payment proof status
            $stmt = $pdo->prepare("
                UPDATE payment_proofs 
                SET status = 'verified', verified_at = NOW(), verified_by = ?, notes = ?
                WHERE registration_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $registration_id]);
            
            $pdo->commit();
            showAlert('Pembayaran berhasil diverifikasi!', 'success');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
        }
    }
    
    if ($action === 'reject_payment' && $registration_id) {
        try {
            $pdo->beginTransaction();
            
            // Update registration status back to confirmed
            $stmt = $pdo->prepare("
                UPDATE elpt_registrations 
                SET payment_status = 'confirmed' 
                WHERE id = ?
            ");
            $stmt->execute([$registration_id]);
            
            // Update payment proof status
            $stmt = $pdo->prepare("
                UPDATE payment_proofs 
                SET status = 'rejected', verified_at = NOW(), verified_by = ?, notes = ?
                WHERE registration_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $registration_id]);
            
            $pdo->commit();
            showAlert('Pembayaran ditolak. Mahasiswa perlu mengupload ulang bukti pembayaran.', 'warning');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
        }
    }
    
    header('Location: payments.php');
    exit;
}

// Handle registration confirmation (triggers payment deadline)
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $registration_id = $_GET['confirm'];
    
    try {
        // Set payment deadline (1 hour from now)
        $deadline = new DateTime();
        $deadline->modify('+' . getSystemSetting('payment_deadline_hours', 1) . ' hours');
        
        $stmt = $pdo->prepare("
            UPDATE elpt_registrations 
            SET payment_status = 'confirmed', 
                registration_confirmed_at = NOW(),
                payment_proof_deadline = ?,
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$deadline->format('Y-m-d H:i:s'), $registration_id]);
        
        showAlert('Pendaftaran berhasil dikonfirmasi! Mahasiswa dapat mengupload bukti pembayaran dalam 1 jam.', 'success');
        header('Location: payments.php');
        exit;
    } catch (PDOException $e) {
        showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
    }
}

// Get all registrations with payment proof information
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT r.*, u.name, u.nim, u.no_telpon, u.program, u.faculty, u.level,
           pp.file_name as proof_file, pp.file_path as proof_path, pp.uploaded_at as proof_uploaded_at,
           pp.status as proof_status, pp.notes as proof_notes,
           v.name as verified_by_name
    FROM elpt_registrations r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN payment_proofs pp ON r.id = pp.registration_id
    LEFT JOIN users v ON pp.verified_by = v.id
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
$stmt = $pdo->query("
    SELECT payment_status, COUNT(*) as count 
    FROM elpt_registrations 
    GROUP BY payment_status
");
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
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (same as before) -->
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

                <!-- Enhanced Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-2">
                        <div class="card text-center bg-warning text-dark">
                            <div class="card-body">
                                <i class="bi bi-hourglass-split" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['pending'] ?? 0 ?></div>
                                <small>Menunggu</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-check-circle" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['confirmed'] ?? 0 ?></div>
                                <small>Dikonfirmasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-upload" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['payment_uploaded'] ?? 0 ?></div>
                                <small>Bukti Upload</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-shield-check" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['payment_verified'] ?? 0 ?></div>
                                <small>Terverifikasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body">
                                <i class="bi bi-x-circle" style="font-size: 1.5rem;"></i>
                                <div class="display-6 fw-bold mt-1"><?= $stats['rejected'] ?? 0 ?></div>
                                <small>Ditolak</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
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
                            <div class="col-md-4">
                                <label class="form-label">Filter Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                    <option value="payment_uploaded" <?= $filter_status === 'payment_uploaded' ? 'selected' : '' ?>>Bukti Terupload</option>
                                    <option value="payment_verified" <?= $filter_status === 'payment_verified' ? 'selected' : '' ?>>Terverifikasi</option>
                                    <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
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

                <!-- Enhanced Payments Table -->
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
                                                        <strong><?= formatDate($reg['test_date']) ?></strong><br>
                                                        <small class="text-muted"><?= formatTimeSlot($reg['time_slot'], $reg['test_date']) ?></small><br>
                                                        <span class="badge bg-info"><?= htmlspecialchars($reg['purpose']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code class="bg-light p-1 rounded"><?= htmlspecialchars($reg['billing_number']) ?></code><br>
                                                    <small class="text-success fw-bold"><?= formatCurrency(ELPT_FEE) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($reg['proof_file']): ?>
                                                        <div class="text-center">
                                                            <a href="../<?= htmlspecialchars($reg['proof_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i> Lihat
                                                            </a><br>
                                                            <small class="text-muted">
                                                                <?= formatDateTime($reg['proof_uploaded_at']) ?>
                                                            </small>
                                                            <?php if ($reg['proof_status'] === 'verified'): ?>
                                                                <br><small class="text-success">
                                                                    <i class="bi bi-check-circle"></i> Verified by <?= htmlspecialchars($reg['verified_by_name']) ?>
                                                                </small>
                                                            <?php elseif ($reg['proof_status'] === 'rejected'): ?>
                                                                <br><small class="text-danger">
                                                                    <i class="bi bi-x-circle"></i> Rejected
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">Belum upload</small>
                                                        <?php if ($reg['payment_proof_deadline']): ?>
                                                            <br><small class="text-warning">
                                                                Deadline: <?= formatDateTime($reg['payment_proof_deadline']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= getPaymentStatusBadge($reg['payment_status']) ?>">
                                                        <?= getPaymentStatusText($reg['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if ($reg['payment_status'] === 'pending'): ?>
                                                            <a href="?confirm=<?= $reg['id'] ?>" 
                                                               class="btn btn-success"
                                                               onclick="return confirm('Konfirmasi pendaftaran ini? Mahasiswa akan mendapat 1 jam untuk upload bukti pembayaran.')">
                                                                <i class="bi bi-check"></i> Konfirmasi
                                                            </a>
                                                            <a href="?reject=<?= $reg['id'] ?>" 
                                                               class="btn btn-danger"
                                                               onclick="return confirm('Tolak pendaftaran ini?')">
                                                                <i class="bi bi-x"></i> Tolak
                                                            </a>
                                                        <?php elseif ($reg['payment_status'] === 'payment_uploaded'): ?>
                                                            <button class="btn btn-success verify-payment-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#verifyModal"
                                                                    data-registration-id="<?= $reg['id'] ?>"
                                                                    data-student-name="<?= htmlspecialchars($reg['name']) ?>"
                                                                    data-proof-file="../<?= htmlspecialchars($reg['proof_path']) ?>">
                                                                <i class="bi bi-shield-check"></i> Verifikasi
                                                            </button>
                                                            <button class="btn btn-warning reject-payment-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#rejectModal"
                                                                    data-registration-id="<?= $reg['id'] ?>"
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
            </div>
        </div>
    </div>

    <!-- Payment Verification Modal -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-check me-2"></i>Verifikasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="verify_payment">
                        <input type="hidden" name="registration_id" id="verify_registration_id">
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-person me-2"></i>Mahasiswa: <span id="verify_student_name"></span></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bukti Pembayaran</label>
                            <div class="text-center p-3 border rounded">
                                <img id="verify_proof_image" src="" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 300px; display: none;">
                                <iframe id="verify_proof_pdf" src="" style="width: 100%; height: 300px; display: none;"></iframe>
                                <a id="verify_proof_link" href="" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>Download Bukti Pembayaran
                                </a>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan Verifikasi (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Setelah diverifikasi:</strong> Mahasiswa akan mendapat notifikasi untuk datang ke 
                            <strong><?= getSystemSetting('test_location', 'Gd. RA Kartini lt. 3 ruang 301/302/303') ?></strong> 
                            pada tanggal dan waktu yang telah dipilih.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-shield-check me-2"></i>Verifikasi Pembayaran
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
                        
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-person me-2"></i>Mahasiswa: <span id="reject_student_name"></span></h6>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="notes" rows="4" required 
                                      placeholder="Jelaskan alasan penolakan pembayaran (misalnya: bukti pembayaran tidak jelas, nominal tidak sesuai, dll.)"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Setelah ditolak:</strong> Status pendaftaran akan kembali ke "Dikonfirmasi" dan mahasiswa perlu mengupload ulang bukti pembayaran.
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
        // Verify payment modal handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.verify-payment-btn')) {
                const btn = e.target.closest('.verify-payment-btn');
                const registrationId = btn.getAttribute('data-registration-id');
                const studentName = btn.getAttribute('data-student-name');
                const proofFile = btn.getAttribute('data-proof-file');
                
                document.getElementById('verify_registration_id').value = registrationId;
                document.getElementById('verify_student_name').textContent = studentName;
                
                // Handle different file types
                const fileExt = proofFile.split('.').pop().toLowerCase();
                const proofImage = document.getElementById('verify_proof_image');
                const proofPdf = document.getElementById('verify_proof_pdf');
                const proofLink = document.getElementById('verify_proof_link');
                
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
                const studentName = btn.getAttribute('data-student-name');
                
                document.getElementById('reject_registration_id').value = registrationId;
                document.getElementById('reject_student_name').textContent = studentName;
            }
        });

        // Form validation
        document.querySelector('#rejectModal form').addEventListener('submit', function(e) {
            const notes = this.querySelector('[name="notes"]').value.trim();
            if (!notes) {
                alert('Alasan penolakan harus diisi');
                e.preventDefault();
                return false;
            }
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
        case 'rejected': return 'bg-danger text-white';
        default: return 'bg-secondary text-white';
    }
}

function getPaymentStatusText($status) {
    switch($status) {
        case 'pending': return 'MENUNGGU';
        case 'confirmed': return 'DIKONFIRMASI';
        case 'payment_uploaded': return 'BUKTI TERUPLOAD';
        case 'payment_verified': return 'TERVERIFIKASI';
        case 'rejected': return 'DITOLAK';
        default: return strtoupper($status);
    }
}
?>