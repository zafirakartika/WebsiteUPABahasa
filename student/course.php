<?php
require_once '../config/database.php';
requireRole('student');

$user = getCurrentUser();
$errors = [];
$success = '';

// Get current course status
$stmt = $pdo->prepare("SELECT * FROM courses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$current_course = $stmt->fetch();

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $course_id = $_POST['course_id'];
        
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
            $file_name = 'course_payment_' . $course_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $relative_path = 'uploads/payment_proofs/' . date('Y') . '/' . date('m') . '/' . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    // Update course with payment proof
                    $stmt = $pdo->prepare("
                        UPDATE courses 
                        SET payment_status = 'payment_uploaded', 
                            payment_proof_file = ?,
                            payment_proof_uploaded_at = NOW(),
                            updated_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$relative_path, $course_id, $_SESSION['user_id']]);
                    
                    // Log activity
                    logActivity('course_payment_upload', "Uploaded payment proof for course ID: $course_id");
                    
                    showAlert('Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.', 'success');
                    header('Location: course.php');
                    exit;
                } catch (PDOException $e) {
                    // Delete uploaded file if database update fails
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    error_log("Course payment upload error: " . $e->getMessage());
                    $errors[] = 'Gagal menyimpan ke database. Silakan coba lagi.';
                }
            } else {
                $errors[] = 'Gagal mengupload file';
            }
        }
    } else {
        $errors[] = 'Silakan pilih file bukti pembayaran';
    }
    
    // If there are errors, show them
    if (!empty($errors)) {
        showAlert(implode('<br>', $errors), 'error');
    }
}

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && !$current_course) {
    $final_test_date = $_POST['final_test_date'] ?? '';
    
    // Validation
    if (empty($final_test_date)) {
        $errors[] = 'Tanggal final test harus dipilih';
    } else {
        // Check if final test date is valid (Tuesday, Thursday, Saturday)
        $selected_date = new DateTime($final_test_date);
        $day_of_week = $selected_date->format('N'); // 1=Monday, 7=Sunday
        if (!in_array($day_of_week, [2, 4, 6])) { // 2=Tuesday, 4=Thursday, 6=Saturday
            $errors[] = 'Tanggal final test hanya tersedia pada hari Selasa, Kamis, dan Sabtu';
        }
        
        // Check if date is at least 30 days from now (for 22 learning sessions + buffer)
        $min_date = new DateTime('+30 days');
        if ($selected_date <= $min_date) {
            $errors[] = 'Tanggal final test minimal 30 hari dari sekarang';
        }
    }
    
    // Register for course if no errors
    if (empty($errors)) {
        try {
            // Generate billing number for course
            $billing_number = 'COURSE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO courses 
                (user_id, final_test_date, status, payment_status, billing_number) 
                VALUES (?, ?, 'pending', 'pending', ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $final_test_date, $billing_number]);
            
            // Log activity
            logActivity('course_registration', "Registered for course with final test date: $final_test_date");
            
            showAlert('Pendaftaran kursus berhasil! Silakan upload bukti pembayaran untuk melanjutkan.', 'success');
            header('Location: course.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Course registration error: " . $e->getMessage());
            $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
    
    if (!empty($errors)) {
        showAlert(implode('<br>', $errors), 'error');
    }
}

// Get available dates for final test (next 60 days, only Tue/Thu/Sat)
$available_dates = [];
$start_date = new DateTime('+30 days');
$end_date = new DateTime('+60 days');

while ($start_date <= $end_date) {
    $day_of_week = $start_date->format('N');
    if (in_array($day_of_week, [2, 4, 6])) {
        $available_dates[] = [
            'date' => $start_date->format('Y-m-d'),
            'formatted' => $start_date->format('l, d F Y'),
            'day' => $start_date->format('l')
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
    <title>Kursus Persiapan ELPT - UPA Bahasa UPNVJ</title>
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
                    <h2 class="fw-bold">Kursus Persiapan ELPT</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <?php if ($current_course): ?>
                    <!-- Current Course Status -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-book me-2"></i>Status Kursus Anda</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6>Progress Pembelajaran</h6>
                                        <span class="badge <?= 
                                            $current_course['status'] === 'completed' ? 'bg-success' : 
                                            ($current_course['status'] === 'active' ? 'bg-primary' : 'bg-warning text-dark') 
                                        ?> fs-6">
                                            <?= strtoupper($current_course['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="progress session-progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= ($current_course['current_session']/$current_course['total_sessions']) * 100 ?>%">
                                            <?= $current_course['current_session'] ?>/<?= $current_course['total_sessions'] ?> Sesi
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h4 class="text-primary"><?= $current_course['current_session'] ?></h4>
                                                    <small>Sesi Saat Ini</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h4 class="text-info"><?= $current_course['total_sessions'] - $current_course['current_session'] ?></h4>
                                                    <small>Sesi Tersisa</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h4 class="text-success"><?= round(($current_course['current_session']/$current_course['total_sessions']) * 100) ?>%</h4>
                                                    <small>Selesai</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <div class="text-center">
                                        <i class="bi bi-calendar-event text-primary" style="font-size: 3rem;"></i>
                                        <h6 class="mt-3">Final Test</h6>
                                        <p class="fw-bold"><?= formatDate($current_course['final_test_date']) ?></p>
                                        
                                        <!-- Payment Status Display -->
                                        <div class="mt-3">
                                            <h6>Status Pembayaran</h6>
                                            <span class="badge fs-6 <?= 
                                                ($current_course['payment_status'] ?? 'pending') === 'pending' ? 'bg-warning text-dark' : 
                                                (($current_course['payment_status'] ?? 'pending') === 'payment_uploaded' ? 'bg-primary' : 
                                                (($current_course['payment_status'] ?? 'pending') === 'payment_verified' ? 'bg-success' : 'bg-secondary'))
                                            ?>">
                                                <?php
                                                $payment_status = $current_course['payment_status'] ?? 'pending';
                                                switch($payment_status) {
                                                    case 'pending': echo 'MENUNGGU PEMBAYARAN'; break;
                                                    case 'payment_uploaded': echo 'MENUNGGU KONFIRMASI'; break;
                                                    case 'payment_verified': echo 'PEMBAYARAN DIKONFIRMASI'; break;
                                                    default: echo strtoupper($payment_status);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($current_course['status'] === 'active'): ?>
                                            <div class="alert alert-info mt-3">
                                                <small>
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Anda sedang mengikuti kursus sesi ke-<?= $current_course['current_session'] ?>
                                                </small>
                                            </div>
                                        <?php elseif ($current_course['status'] === 'completed'): ?>
                                            <div class="alert alert-success mt-3">
                                                <small>
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Kursus telah selesai!
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Section -->
                            <?php 
                            $payment_status = $current_course['payment_status'] ?? 'pending';
                            if ($payment_status === 'pending'): 
                            ?>
                                <!-- Payment Upload Form -->
                                <div class="mt-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Silakan Upload Bukti Pembayaran Kursus</strong><br>
                                        Biaya kursus: <strong><?= formatCurrency(COURSE_FEE) ?></strong>
                                        <?php if (!empty($current_course['billing_number'])): ?>
                                            <br>Billing Number: <code><?= htmlspecialchars($current_course['billing_number']) ?></code>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Payment Information -->
                                    <div class="alert alert-light border mb-4">
                                        <h6 class="fw-bold mb-3"><i class="bi bi-bank me-2"></i>Informasi Pembayaran</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Rekening Tujuan:</strong><br>
                                                <div class="bg-white p-2 rounded border">
                                                    <strong>Bank BNI</strong><br>
                                                    <code>1234567890</code><br>
                                                    <small>A.n. UPA Bahasa UPNVJ</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Detail Pembayaran:</strong><br>
                                                <div class="bg-white p-2 rounded border">
                                                    <strong>Nominal:</strong> <?= formatCurrency(COURSE_FEE) ?><br>
                                                    <strong>Keterangan:</strong> Kursus Persiapan ELPT<br>
                                                    <small class="text-muted">24 Sesi Pembelajaran</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Upload Form -->
                                    <form method="POST" enctype="multipart/form-data" id="paymentUploadForm" class="p-4 bg-light rounded">
                                        <input type="hidden" name="action" value="upload_payment">
                                        <input type="hidden" name="course_id" value="<?= $current_course['id'] ?>">
                                        
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
                                                <span class="btn-text">
                                                    <i class="bi bi-cloud-upload me-2"></i>Upload Bukti Pembayaran
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm me-2"></span>Mengupload...
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                            <?php elseif ($payment_status === 'payment_uploaded'): ?>
                                <div class="alert alert-primary mt-4">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Bukti Pembayaran Telah Diupload</strong><br>
                                    Bukti pembayaran Anda sedang diverifikasi oleh admin. Anda akan mendapat notifikasi setelah pembayaran dikonfirmasi.
                                    <?php if (!empty($current_course['payment_proof_uploaded_at'])): ?>
                                        <br><small>Diupload pada: <?= formatDateTime($current_course['payment_proof_uploaded_at']) ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($current_course['payment_proof_file'])): ?>
                                    <div class="text-center mt-3">
                                        <a href="../<?= htmlspecialchars($current_course['payment_proof_file']) ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="bi bi-eye me-2"></i>Lihat Bukti Pembayaran yang Diupload
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                            <?php elseif ($payment_status === 'payment_verified'): ?>
                                <div class="alert alert-success mt-4">
                                    <i class="bi bi-shield-check me-2"></i>
                                    <strong>Pembayaran Dikonfirmasi!</strong><br>
                                    Selamat! Pembayaran kursus Anda telah dikonfirmasi. Kursus akan segera dimulai sesuai jadwal.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Course Schedule -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Jadwal Kursus</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="schedule-item p-3">
                                        <h6><i class="bi bi-calendar-day me-2"></i>Selasa</h6>
                                        <p class="mb-1"><strong>09:30 - 12:00</strong> (Sesi Pagi)</p>
                                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Siang)</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="schedule-item p-3">
                                        <h6><i class="bi bi-calendar-day me-2"></i>Kamis</h6>
                                        <p class="mb-1"><strong>09:30 - 12:00</strong> (Sesi Pagi)</p>
                                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Siang)</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="schedule-item p-3">
                                        <h6><i class="bi bi-calendar-day me-2"></i>Sabtu</h6>
                                        <p class="mb-1"><strong>07:00 - 09:30</strong> (Sesi Pagi)</p>
                                        <p class="mb-1"><strong>09:30 - 12:00</strong> (Sesi Siang)</p>
                                        <p class="mb-0"><strong>13:00 - 15:30</strong> (Sesi Sore)</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Perhatian:</strong> Jadwal sesi akan dikonfirmasi oleh admin setelah pembayaran dikonfirmasi.
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Course Registration -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Daftar Kursus Persiapan ELPT</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Course Info -->
                                    <div class="alert alert-info">
                                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Kursus</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="mb-0">
                                                    <li>24 Sesi Total (22 Pembelajaran + 2 Tes)</li>
                                                    <li>Durasi: 2.5 jam per sesi</li>
                                                    <li>Instruktur berpengalaman</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="mb-0">
                                                    <li>Materi: Listening, Structure, Reading</li>
                                                    <li>Include 2x Mock Test ELPT</li>
                                                    <li>Sertifikat kehadiran</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST" id="courseRegistrationForm">
                                        <!-- Student Info -->
                                        <div class="mb-4">
                                            <h6>Data Mahasiswa</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($user['name']) ?></p>
                                                    <p class="mb-1"><strong>NIM:</strong> <?= htmlspecialchars($user['nim']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Program Studi:</strong> <?= htmlspecialchars($user['program']) ?></p>
                                                    <p class="mb-0"><strong>Fakultas:</strong> <?= htmlspecialchars($user['faculty']) ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Final Test Date Selection -->
                                        <div class="mb-4">
                                            <h6>Pilih Tanggal Final Test</h6>
                                            <p class="text-muted small">Final test akan dilaksanakan setelah menyelesaikan 22 sesi pembelajaran</p>
                                            
                                            <div class="row g-3">
                                                <?php foreach (array_slice($available_dates, 0, 12) as $date): ?>
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="date-option p-3" onclick="selectDate('<?= $date['date'] ?>')">
                                                            <input type="radio" name="final_test_date" value="<?= $date['date'] ?>" class="d-none" required>
                                                            <div class="fw-bold"><?= $date['formatted'] ?></div>
                                                            <small class="text-muted"><?= $date['day'] ?></small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary btn-lg" id="registerBtn">
                                                <span class="btn-text">
                                                    <i class="bi bi-book me-2"></i>Daftar Kursus
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm me-2"></span>Mendaftar...
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Course Details -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Kurikulum Kursus</h6>
                                </div>
                                <div class="card-body">
                                    <h6>Materi Pembelajaran:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check me-2 text-success"></i>Listening Comprehension (8 sesi)</li>
                                        <li><i class="bi bi-check me-2 text-success"></i>Structure & Written Expression (7 sesi)</li>
                                        <li><i class="bi bi-check me-2 text-success"></i>Reading Comprehension (7 sesi)</li>
                                        <li><i class="bi bi-star me-2 text-warning"></i>Mock Test 1 (1 sesi)</li>
                                        <li><i class="bi bi-star me-2 text-warning"></i>Mock Test 2 (1 sesi)</li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Fasilitas:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check me-2 text-success"></i>Modul pembelajaran</li>
                                        <li><i class="bi bi-check me-2 text-success"></i>Audio listening</li>
                                        <li><i class="bi bi-check me-2 text-success"></i>Bank soal latihan</li>
                                        <li><i class="bi bi-check me-2 text-success"></i>Progress tracking</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Syarat & Ketentuan</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="small mb-0">
                                        <li>Wajib hadir minimal 80% dari total sesi</li>
                                        <li>Final test dilaksanakan sesuai jadwal yang dipilih</li>
                                        <li>Biaya kursus: <?= formatCurrency(COURSE_FEE) ?> (24 sesi)</li>
                                        <li>Sertifikat diberikan jika lulus final test</li>
                                    </ul>
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
        function selectDate(date) {
            // Remove selected class from all options
            document.querySelectorAll('.date-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
        }
        
        // Course registration form validation
        document.getElementById('courseRegistrationForm')?.addEventListener('submit', function(e) {
            const finalTestDate = document.querySelector('input[name="final_test_date"]:checked');
            
            if (!finalTestDate) {
                alert('Silakan pilih tanggal final test');
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const registerBtn = document.getElementById('registerBtn');
            const btnText = registerBtn.querySelector('.btn-text');
            const btnLoading = registerBtn.querySelector('.btn-loading');
            
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
            registerBtn.disabled = true;
        });
        
        // Payment upload form validation and submission
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
            const btnText = uploadBtn.querySelector('.btn-text');
            const btnLoading = uploadBtn.querySelector('.btn-loading');
            
            if (btnText && btnLoading) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                uploadBtn.disabled = true;
            }
            
            // Form will submit normally, no need to prevent default
        });

        // File input change event for validation feedback
        document.querySelector('input[name="payment_proof"]')?.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            
            // Show file info
            const fileInfo = document.createElement('div');
            fileInfo.className = 'mt-2 text-muted small';
            
            if (file.size > maxSize) {
                fileInfo.innerHTML = '<i class="bi bi-exclamation-triangle text-danger me-1"></i>File terlalu besar (maksimal 5MB)';
                fileInfo.className = 'mt-2 text-danger small';
            } else if (!allowedTypes.includes(file.type)) {
                fileInfo.innerHTML = '<i class="bi bi-exclamation-triangle text-danger me-1"></i>Tipe file tidak didukung';
                fileInfo.className = 'mt-2 text-danger small';
            } else {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                fileInfo.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>File valid (' + fileSize + ' MB)';
                fileInfo.className = 'mt-2 text-success small';
            }
            
            // Remove existing file info
            const existingInfo = this.parentNode.querySelector('.file-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            
            fileInfo.classList.add('file-info');
            this.parentNode.appendChild(fileInfo);
        });
    </script>
</body>
</html>