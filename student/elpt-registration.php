<?php
require_once '../config/database.php';
requireRole('student');

$user = getCurrentUser();
$errors = [];
$success = '';

// Check if user already has active registration
$stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE user_id = ? AND payment_status IN ('confirmed', 'payment_uploaded', 'payment_verified') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$active_registration = $stmt->fetch();

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $registration_id = $_POST['registration_id'];
        
        // Validate file
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['size'] > $max_size) {
            $errors[] = 'File terlalu besar. Maksimal 5MB';
        } elseif (!in_array($file_ext, $allowed_types)) {
            $errors[] = 'Tipe file tidak diizinkan. Hanya JPG, PNG, dan PDF';
        } else {
            // Create upload directory
            $upload_dir = '../uploads/payment_proofs/' . date('Y') . '/' . date('m') . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_name = 'payment_' . $registration_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $relative_path = 'uploads/payment_proofs/' . date('Y') . '/' . date('m') . '/' . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    $pdo->beginTransaction();
                    
                    // 1. INSERT ke payment_proofs table
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_proofs 
                        (registration_id, file_name, file_path, file_size, file_type, status, ip_address, user_agent) 
                        VALUES (?, ?, ?, ?, ?, 'uploaded', ?, ?)
                    ");
                    $stmt->execute([
                        $registration_id,
                        $file_name,
                        $relative_path,
                        $file['size'],
                        $file['type'],
                        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                    
                    // 2. UPDATE elpt_registrations (seperti sebelumnya)
                    $stmt = $pdo->prepare("
                        UPDATE elpt_registrations 
                        SET payment_status = 'payment_uploaded', 
                            payment_proof_file = ?,
                            payment_proof_uploaded_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$relative_path, $registration_id]);
                    
                    // 3. Log activity
                    logActivity('payment_proof_upload', "Uploaded payment proof for registration ID: $registration_id");
                    
                    $pdo->commit();
                    
                    showAlert('Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.', 'success');
                    header('Location: elpt-registration.php');
                    exit;
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    unlink($file_path); // Delete uploaded file if database update fails
                    $errors[] = 'Gagal menyimpan ke database: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Gagal mengupload file';
            }
        }
    } else {
        $errors[] = 'Silakan pilih file bukti pembayaran';
    }
}

// Handle new registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && !$active_registration) {
    $test_date = $_POST['test_date'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    
    // Validation
    if (empty($test_date)) {
        $errors[] = 'Tanggal tes harus dipilih';
    } else {
        // Check if test date is at least tomorrow
        $selected_date = new DateTime($test_date);
        $tomorrow = new DateTime('+1 day');
        if ($selected_date <= $tomorrow) {
            $errors[] = 'Tanggal tes minimal H+1 dari sekarang';
        }
        
        // Check if test date is valid (Tuesday, Thursday, Saturday)
        $day_of_week = $selected_date->format('N'); // 1=Monday, 7=Sunday
        if (!in_array($day_of_week, [2, 4, 6])) { // 2=Tuesday, 4=Thursday, 6=Saturday
            $errors[] = 'Tanggal tes hanya tersedia pada hari Selasa, Kamis, dan Sabtu';
        }
        
        // Check availability (max 30 students per date)
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM elpt_registrations WHERE test_date = ? AND payment_status IN ('confirmed', 'payment_uploaded', 'payment_verified')");
            $stmt->execute([$test_date]);
            $count = $stmt->fetch()['count'];
            
            if ($count >= 30) {
                $errors[] = 'Kuota untuk tanggal tersebut sudah penuh (maksimal 30 peserta)';
            }
        }
    }
    
    if (empty($time_slot)) {
        $errors[] = 'Waktu tes harus dipilih';
    }
    
    if (empty($purpose)) {
        $errors[] = 'Keperluan harus dipilih';
    }
    
    // Register if no errors - AUTOMATICALLY CONFIRMED if slot available
    if (empty($errors)) {
        try {
            $billing_number = generateBillingNumber();
            
            // Insert with status 'confirmed' (auto-confirm if slot available)
            $stmt = $pdo->prepare("INSERT INTO elpt_registrations (user_id, test_date, time_slot, purpose, billing_number, payment_status, registration_confirmed_at) VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())");
            $stmt->execute([$_SESSION['user_id'], $test_date, $time_slot, $purpose, $billing_number]);
            
            showAlert('Pendaftaran berhasil dikonfirmasi! Silakan upload bukti pembayaran untuk melanjutkan.', 'success');
            header('Location: elpt-registration.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get available dates (next 30 days, only Tue/Thu/Sat)
$available_dates = [];
$start_date = new DateTime('+1 day');
$end_date = new DateTime('+30 days');

while ($start_date <= $end_date) {
    $day_of_week = $start_date->format('N');
    if (in_array($day_of_week, [2, 4, 6])) {
        $date_str = $start_date->format('Y-m-d');
        
        // Check how many registered for this date
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM elpt_registrations WHERE test_date = ? AND payment_status IN ('confirmed', 'payment_uploaded', 'payment_verified')");
        $stmt->execute([$date_str]);
        $count = $stmt->fetch()['count'];
        
        // Get available time slots based on day
        $time_slots = [];
        if ($day_of_week == 6) { // Saturday
            $time_slots = [
                'pagi' => 'Pagi (07:00-09:30)',
                'siang' => 'Siang (09:30-12:00)',
                'sore' => 'Sore (13:00-15:30)'
            ];
        } else { // Tuesday, Thursday
            $time_slots = [
                'pagi' => 'Pagi (09:30-12:00)',
                'siang' => 'Siang (13:00-15:30)'
            ];
        }
        
        $available_dates[] = [
            'date' => $date_str,
            'formatted' => $start_date->format('l, d F Y'),
            'count' => $count,
            'available' => $count < 30,
            'day_name' => $start_date->format('l'),
            'time_slots' => $time_slots
        ];
    }
    $start_date->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran ELPT - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/student.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Pendaftaran ELPT</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <?php if ($active_registration): ?>
                    <!-- Registration Status with Payment Upload -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Status Pendaftaran Anda
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Registration Details -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 mb-3">
                                                <strong>Tanggal Tes</strong><br>
                                                <span class="text-primary fs-5"><?= formatDate($active_registration['test_date']) ?></span><br>
                                                <small class="text-muted"><?= formatTimeSlot($active_registration['time_slot'], $active_registration['test_date']) ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 mb-3">
                                                <strong>Billing Number</strong><br>
                                                <code class="fs-5"><?= htmlspecialchars($active_registration['billing_number']) ?></code><br>
                                                <small class="text-muted">Biaya: <?= formatCurrency(ELPT_FEE) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3">
                                                <strong>Keperluan</strong><br>
                                                <span><?= htmlspecialchars($active_registration['purpose']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3">
                                                <strong>Status Pembayaran</strong><br>
                                                <span class="badge fs-6 <?= 
                                                    $active_registration['payment_status'] === 'confirmed' ? 'bg-warning text-dark' : 
                                                    ($active_registration['payment_status'] === 'payment_uploaded' ? 'bg-primary' : 'bg-success')
                                                ?>">
                                                    <?php
                                                    switch($active_registration['payment_status']) {
                                                        case 'confirmed': echo 'MENUNGGU UPLOAD BUKTI'; break;
                                                        case 'payment_uploaded': echo 'MENUNGGU KONFIRMASI'; break;
                                                        case 'payment_verified': echo 'PEMBAYARAN DIKONFIRMASI'; break;
                                                        default: echo strtoupper($active_registration['payment_status']);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Status Specific Content -->
                                    <?php if ($active_registration['payment_status'] === 'confirmed'): ?>
                                        <!-- Payment Upload Form -->
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Silakan Upload Bukti Pembayaran</strong><br>
                                            Pendaftaran Anda telah dikonfirmasi. Silakan lakukan pembayaran dan upload bukti pembayaran di bawah ini.
                                        </div>
                                        
                                        <!-- Error Messages for Upload -->
                                        <?php if (!empty($errors)): ?>
                                            <div class="alert alert-danger">
                                                <ul class="mb-0">
                                                    <?php foreach ($errors as $error): ?>
                                                        <li><?= htmlspecialchars($error) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Payment Information -->
                                        <div class="alert alert-light border mb-4">
                                            <h6 class="fw-bold mb-3"><i class="bi bi-bank me-2"></i>Informasi Pembayaran</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Rekening Tujuan:</strong><br>
                                                    <div class="bg-white p-2 rounded border">
                                                        <strong>Biro Umum dan Keuangan UPNVJ</strong><br>
                                                        <code>1234567890</code><br>
                                                        <small>A.n. UPA Bahasa UPNVJ</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Detail Pembayaran:</strong><br>
                                                    <div class="bg-white p-2 rounded border">
                                                        <strong>Nominal:</strong> <?= formatCurrency(ELPT_FEE) ?><br>
                                                        <strong>Kode Billing:</strong> <code><?= htmlspecialchars($active_registration['billing_number']) ?></code><br>
                                                        <small class="text-muted">Gunakan kode billing sebagai berita acara</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Upload Form -->
                                        <form method="POST" enctype="multipart/form-data" id="paymentUploadForm" class="p-4 bg-light rounded">
                                            <input type="hidden" name="action" value="upload_payment">
                                            <input type="hidden" name="registration_id" value="<?= $active_registration['id'] ?>">
                                            
                                            <h6 class="fw-bold mb-3"><i class="bi bi-cloud-upload me-2"></i>Upload Bukti Pembayaran</h6>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Pilih File Bukti Transfer</label>
                                                <input type="file" class="form-control form-control-lg" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                                <div class="form-text">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Format yang diizinkan: JPG, PNG, PDF (maksimal 5MB)<br>
                                                    Pastikan bukti pembayaran jelas dan terbaca dengan baik
                                                </div>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary btn-lg" id="uploadBtn">
                                                    <i class="bi bi-cloud-upload me-2"></i>Upload Bukti Pembayaran
                                                </button>
                                            </div>
                                        </form>
                                    
                                    <?php elseif ($active_registration['payment_status'] === 'payment_uploaded'): ?>
                                        <div class="alert alert-primary">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <strong>Bukti Pembayaran Telah Diupload</strong><br>
                                            Bukti pembayaran Anda sedang diverifikasi oleh admin. Anda akan mendapat notifikasi setelah pembayaran dikonfirmasi.
                                            <?php if ($active_registration['payment_proof_uploaded_at']): ?>
                                                <br><small>Diupload pada: <?= formatDateTime($active_registration['payment_proof_uploaded_at']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($active_registration['payment_proof_file']): ?>
                                            <div class="text-center">
                                                <a href="../<?= htmlspecialchars($active_registration['payment_proof_file']) ?>" target="_blank" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye me-2"></i>Lihat Bukti Pembayaran yang Diupload
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    
                                    <?php elseif ($active_registration['payment_status'] === 'payment_verified'): ?>
                                        <div class="alert alert-success">
                                            <i class="bi bi-shield-check me-2"></i>
                                            <strong>Pembayaran Dikonfirmasi!</strong><br>
                                            Selamat! Pembayaran Anda telah dikonfirmasi oleh admin. Harap hadir pada tanggal dan waktu yang telah ditentukan.
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="bi bi-geo-alt me-2"></i>Detail Kehadiran:</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Lokasi Tes:</strong><br>
                                                    Gedung RA Kartini Lt. 3<br>
                                                    Ruang 301<br>
                                                    Universitas Pembangunan Nasional "Veteran" Jakarta
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Waktu Kehadiran:</strong><br>
                                                    <?= formatTimeSlot($active_registration['time_slot'], $active_registration['test_date']) ?><br>
                                                    <small class="text-muted">Harap hadir 30 menit sebelum waktu tes dimulai</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Yang Harus Dibawa:</h6>
                                            <ul class="mb-0">
                                                <li>Kartu identitas (KTM)</li>
                                                <li>Alat tulis (pensil 2B, penghapus)</li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Sidebar -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Petunjuk Pembayaran</h6>
                                </div>
                                <div class="card-body">
                                    <ol class="small">
                                        <li>Lakukan transfer ke rekening yang tertera</li>
                                        <li>Gunakan billing number sebagai berita acara</li>
                                        <li>Upload bukti transfer (struk/screenshot)</li>
                                        <li>Tunggu konfirmasi dari admin (1x24 jam)</li>
                                        <li>Hadir pada jadwal yang telah ditentukan</li>
                                    </ol>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Perhatian</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="small mb-0">
                                        <li>Pastikan nominal transfer sesuai</li>
                                        <li>Bukti pembayaran harus jelas dan terbaca</li>
                                        <li>Jika ada kendala, hubungi admin</li>
                                        <li>Batas waktu pembayaran: 3x24 jam</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- New Registration Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body p-4">
                                    <!-- Student Info -->
                                    <div class="alert alert-info">
                                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Mahasiswa</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Nama:</strong> <?= htmlspecialchars($user['name']) ?><br>
                                                <strong>NIM:</strong> <?= htmlspecialchars($user['nim']) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Program Studi:</strong> <?= htmlspecialchars($user['program']) ?><br>
                                                <strong>Fakultas:</strong> <?= htmlspecialchars($user['faculty']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Messages -->
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?= htmlspecialchars($error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" id="registrationForm">
                                        <!-- Date Selection -->
                                        <div class="mb-4">
                                            <h5 class="mb-3">Pilih Tanggal Tes</h5>
                                            <div class="row g-3">
                                                <?php foreach ($available_dates as $date): ?>
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="date-option p-3 <?= !$date['available'] ? 'unavailable' : '' ?>" 
                                                            <?php if ($date['available']): ?>
                                                                onclick="selectDate('<?= $date['date'] ?>', <?= htmlspecialchars(json_encode($date['time_slots'])) ?>, this)"
                                                            <?php endif; ?>>
                                                            <input type="radio" name="test_date" value="<?= $date['date'] ?>" 
                                                                class="d-none" <?= !$date['available'] ? 'disabled' : '' ?> required>
                                                            <div class="fw-bold"><?= $date['formatted'] ?></div>
                                                            <small class="<?= $date['available'] ? 'text-success' : 'text-danger' ?>">
                                                                <?= $date['available'] ? 
                                                                    ($date['count'] . '/30 terdaftar') : 
                                                                    'Kuota penuh' ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Time Slot Selection -->
                                        <div class="mb-4" id="timeSlotSection" style="display: none;">
                                            <h5 class="mb-3">Pilih Waktu Tes</h5>
                                            <div class="row g-3" id="timeSlotContainer">
                                                <!-- Time slots will be populated by JavaScript -->
                                            </div>
                                        </div>

                                        <!-- Purpose Selection -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Keperluan</label>
                                            <div class="row g-3">
                                                <?php 
                                                $purposes = [
                                                    'Skripsi/Tesis/Tugas Akhir',
                                                    'Yudisium',
                                                    'Kelulusan',
                                                    'Lamar Kerja',
                                                    'Lamar Beasiswa',
                                                    'Ijazah'
                                                ];
                                                foreach ($purposes as $purpose): 
                                                ?>
                                                    <div class="col-md-6">
                                                        <div class="purpose-option p-3" onclick="selectPurpose('<?= str_replace(['/', ' '], ['_', '_'], $purpose) ?>', this)">
                                                            <input class="form-check-input" type="radio" name="purpose" 
                                                                   value="<?= htmlspecialchars($purpose) ?>" 
                                                                   id="purpose_<?= str_replace(['/', ' '], ['_', '_'], $purpose) ?>" 
                                                                   required>
                                                            <label class="form-check-label fw-medium" for="purpose_<?= str_replace(['/', ' '], ['_', '_'], $purpose) ?>">
                                                                <?= htmlspecialchars($purpose) ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Info Sidebar -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Penting</h6>
                                </div>
                                <div class="card-body">
                                    <h6>Jadwal Tes ELPT</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Selasa</strong> 
                                            <small class="d-block ms-4">Pagi: 09:30-12:00<br>Siang: 13:00-15:30</small>
                                        </li>
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Kamis</strong> 
                                            <small class="d-block ms-4">Pagi: 09:30-12:00<br>Siang: 13:00-15:30</small>
                                        </li>
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Sabtu</strong> 
                                            <small class="d-block ms-4">Pagi: 07:00-09:30<br>Siang: 09:30-12:00<br>Sore: 13:00-15:30</small>
                                        </li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Ketentuan</h6>
                                    <ul class="small">
                                        <li>Pendaftaran otomatis terkonfirmasi jika slot tersedia</li>
                                        <li>Maksimal 30 peserta per sesi</li>
                                        <li>Biaya tes: <?= formatCurrency(ELPT_FEE) ?></li>
                                        <li>Skor minimum kelulusan: <?= MIN_PASSING_SCORE ?></li>
                                        <li>Hasil tes keluar 3-5 hari kerja</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Alur Pendaftaran</h6>
                                </div>
                                <div class="card-body">
                                        <ol class="small">
                                            <li>Pilih tanggal & waktu tes</li>
                                            <li>Upload bukti pembayaran</li>
                                            <li>Admin konfirmasi pembayaran</li>
                                            <li>Hadir pada jadwal yang telah ditentukan</li>
                                        </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedTimeSlots = {};
        
        function selectDate(date, timeSlots, element) {
            // Remove selected class from all date options
            document.querySelectorAll('.date-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
            
            // Store time slots for this date
            selectedTimeSlots = timeSlots;
            
            // Show time slot section and populate options
            showTimeSlots(timeSlots);
        }

        function selectTimeSlot(slotKey, element) {
            // Remove selected class from all time slot options
            document.querySelectorAll('.time-slot-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById('time_slot_' + slotKey).checked = true;
        }

        function selectPurpose(purposeId, element) {
            // Remove selected class from all purpose options
            document.querySelectorAll('.purpose-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById('purpose_' + purposeId).checked = true;
        }

        function showTimeSlots(timeSlots) {
            const timeSlotSection = document.getElementById('timeSlotSection');
            const timeSlotContainer = document.getElementById('timeSlotContainer');
            
            // Clear previous time slots
            timeSlotContainer.innerHTML = '';
            
            // Create time slot options
            Object.keys(timeSlots).forEach(slotKey => {
                const slotLabel = timeSlots[slotKey];
                const slotDiv = document.createElement('div');
                slotDiv.className = 'col-md-6';
                slotDiv.innerHTML = `
                    <div class="time-slot-option p-3" onclick="selectTimeSlot('${slotKey}', this)">
                        <input type="radio" name="time_slot" value="${slotKey}" 
                               id="time_slot_${slotKey}" class="d-none" required>
                        <label class="form-check-label fw-medium" for="time_slot_${slotKey}">
                            <i class="bi bi-clock me-2"></i>${slotLabel}
                        </label>
                    </div>
                `;
                timeSlotContainer.appendChild(slotDiv);
            });
            
            // Show the time slot section
            timeSlotSection.style.display = 'block';
            
            // Smooth scroll to time slot section
            timeSlotSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Enhanced form validation with visual feedback
        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            const testDate = document.querySelector('input[name="test_date"]:checked');
            const timeSlot = document.querySelector('input[name="time_slot"]:checked');
            const purpose = document.querySelector('input[name="purpose"]:checked');
            
            let hasErrors = false;
            let errorMessage = '';
            
            if (!testDate) {
                errorMessage += 'Silakan pilih tanggal tes.\n';
                hasErrors = true;
            }
            
            if (!timeSlot) {
                errorMessage += 'Silakan pilih waktu tes.\n';
                hasErrors = true;
            }
            
            if (!purpose) {
                errorMessage += 'Silakan pilih keperluan tes.\n';
                hasErrors = true;
            }
            
            if (hasErrors) {
                alert(errorMessage.trim());
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
            submitBtn.disabled = true;
            
            // Re-enable button after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });
        
        // Payment upload form validation
        document.getElementById('paymentUploadForm')?.addEventListener('submit', function(e) {
            const fileInput = document.querySelector('input[name="payment_proof"]');
            
            if (!fileInput.files.length) {
                alert('Silakan pilih file bukti pembayaran');
                e.preventDefault();
                return false;
            }
            
            const file = fileInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            
            if (file.size > maxSize) {
                alert('File terlalu besar. Maksimal 5MB');
                e.preventDefault();
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Tipe file tidak diizinkan. Hanya JPG, PNG, dan PDF');
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const uploadBtn = document.getElementById('uploadBtn');
            const originalText = uploadBtn.innerHTML;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengupload...';
            uploadBtn.disabled = true;
        });
    </script>
    
    <style>
        .time-slot-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .time-slot-option:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .time-slot-option.selected {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .time-slot-option.selected .bi {
            color: white;
        }
        
        .purpose-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .purpose-option:hover {
            border-color: #28a745;
            background-color: #f8fff9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .purpose-option.selected {
            border-color: #28a745;
            background-color: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .date-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .date-option:hover:not(.unavailable) {
            border-color: #007bff;
            background-color: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .date-option.selected {
            border-color: #007bff;
            background-color: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .date-option.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }
        
        .timeline {
            position: relative;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }
        
        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 12px;
            top: 30px;
            width: 2px;
            height: 15px;
            background: #dee2e6;
        }
        
        .timeline-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .timeline-text {
            font-size: 13px;
            line-height: 1.3;
        }
    </style>
</body>
</html>