<?php
require_once '../config/database.php';
requireRole('student');

$user = getCurrentUser();
$errors = [];
$success = '';

// Check if user already has pending registration
$stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE user_id = ? AND payment_status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_registration = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pending_registration) {
    $test_date = $_POST['test_date'] ?? '';
    // Keep as 'purpose' to match database column and backend processing
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
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM elpt_registrations WHERE test_date = ? AND payment_status IN ('pending', 'confirmed')");
            $stmt->execute([$test_date]);
            $count = $stmt->fetch()['count'];
            
            if ($count >= 30) {
                $errors[] = 'Kuota untuk tanggal tersebut sudah penuh (maksimal 30 peserta)';
            }
        }
    }
    
    if (empty($purpose)) {
        $errors[] = 'Keperluan harus dipilih';
    }
    
    // Register if no errors
    if (empty($errors)) {
        try {
            $billing_number = generateBillingNumber();
            
            // Use 'purpose' to match the database column name
            $stmt = $pdo->prepare("INSERT INTO elpt_registrations (user_id, test_date, purpose, billing_number) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $test_date, $purpose, $billing_number]);
            
            showAlert('Pendaftaran berhasil! Silakan lakukan pembayaran dengan billing number: ' . $billing_number, 'success');
            header('Location: dashboard.php');
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM elpt_registrations WHERE test_date = ? AND payment_status IN ('pending', 'confirmed')");
        $stmt->execute([$date_str]);
        $count = $stmt->fetch()['count'];
        
        $available_dates[] = [
            'date' => $date_str,
            'formatted' => $start_date->format('l, d F Y'),
            'count' => $count,
            'available' => $count < 30
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
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="text-white p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-2">UPA Bahasa</h5>
                        <small>UPNVJ</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="elpt-registration.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Pendaftaran ELPT</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <?php if ($pending_registration): ?>
                    <!-- Pending Registration Alert -->
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Anda masih memiliki pendaftaran yang menunggu konfirmasi pembayaran.
                        <div class="mt-2">
                            <strong>Tanggal Tes:</strong> <?= formatDate($pending_registration['test_date']) ?><br>
                            <strong>Billing Number:</strong> <code><?= htmlspecialchars($pending_registration['billing_number']) ?></code>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
                    </div>
                    
                <?php else: ?>
                    <!-- Registration Form -->
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
                                                             onclick="<?= $date['available'] ? "selectDate('{$date['date']}')" : '' ?>">
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

                                        <!-- Purpose Selection - FIXED -->
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
                                                        <div class="purpose-option p-3" onclick="selectPurpose('<?= str_replace(['/', ' '], ['_', '_'], $purpose) ?>')">
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
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Selasa:</strong> 09:30-12:00 & 13:00-15:30</li>
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Kamis:</strong> 09:30-12:00 & 13:00-15:30</li>
                                        <li><i class="bi bi-calendar me-2 text-primary"></i><strong>Sabtu:</strong> 07:00-09:30, 09:30-12:00 & 13:00-15:30</li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Ketentuan</h6>
                                    <ul class="small text-muted">
                                        <li>Pendaftaran minimal H+1 dari tanggal daftar</li>
                                        <li>Maksimal 30 peserta per sesi</li>
                                        <li>Biaya tes: <?= formatCurrency(ELPT_FEE) ?></li>
                                        <li>Skor minimum kelulusan: <?= MIN_PASSING_SCORE ?></li>
                                        <li>Hasil tes keluar 3-5 hari kerja</li>
                                    </ul>
                                    
                                    <hr>
                                    
                                    <h6>Komponen Tes</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="bg-light rounded p-2">
                                                <div class="fw-bold text-primary">250</div>
                                                <small>Listening</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="bg-light rounded p-2">
                                                <div class="fw-bold text-info">250</div>
                                                <small>Structure</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="bg-light rounded p-2">
                                                <div class="fw-bold text-success">250</div>
                                                <small>Reading</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <strong>Total: 750 poin</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Perhatian</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="small mb-0">
                                        <li>Harap hadir 30 menit sebelum tes dimulai</li>
                                        <li>Bawa kartu identitas (KTM)</li>
                                        <li>Bawa alat tulis (pensil 2B, penghapus)</li>
                                        <li>Dilarang membawa HP/kalkulator</li>
                                        <li>Berpakaian rapi dan sopan</li>
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
        
        // NEW: Function to handle purpose selection
        function selectPurpose(purposeId) {
            // Remove selected class from all purpose options
            document.querySelectorAll('.purpose-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('purpose_' + purposeId).checked = true;
        }
        
        // Enhanced form validation with visual feedback
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const testDate = document.querySelector('input[name="test_date"]:checked');
            const purpose = document.querySelector('input[name="purpose"]:checked');
            
            let hasErrors = false;
            let errorMessage = '';
            
            if (!testDate) {
                errorMessage += 'Silakan pilih tanggal tes.\n';
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
        
        // Debug: Log form data before submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const formData = new FormData(this);
            console.log('Form submission data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });
    </script>
</body>
</html>