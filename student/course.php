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

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$current_course) {
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
            $stmt = $pdo->prepare("INSERT INTO courses (user_id, final_test_date, status) VALUES (?, ?, 'active')");
            $stmt->execute([$_SESSION['user_id'], $final_test_date]);
            
            showAlert('Pendaftaran kursus berhasil! Anda akan dihubungi untuk jadwal sesi pertama.', 'success');
            header('Location: course.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
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
                                        
                                        <?php if ($current_course['status'] === 'active'): ?>
                                            <div class="alert alert-info">
                                                <small>
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Anda sedang mengikuti kursus sesi ke-<?= $current_course['current_session'] ?>
                                                </small>
                                            </div>
                                        <?php elseif ($current_course['status'] === 'completed'): ?>
                                            <div class="alert alert-success">
                                                <small>
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Kursus telah selesai!
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
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
                                <strong>Perhatian:</strong> Jadwal sesi akan dikonfirmasi oleh admin setelah pendaftaran.
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

                                    <form method="POST">
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
                                                            <input type="radio" name="final_test_date" value="<?= $date['date'] ?>" class="d-none">
                                                            <div class="fw-bold"><?= $date['formatted'] ?></div>
                                                            <small class="text-muted"><?= $date['day'] ?></small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-book me-2"></i>Daftar Kursus
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
                                        <li>Biaya kursus: Rp 850.000 (24 sesi)</li>
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
        
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const finalTestDate = document.querySelector('input[name="final_test_date"]:checked');
            
            if (!finalTestDate) {
                alert('Silakan pilih tanggal final test');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>