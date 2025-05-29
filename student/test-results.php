<?php
require_once '../config/database.php';
requireRole('student');

$user = getCurrentUser();

// Get all test results for the student
$stmt = $pdo->prepare("
    SELECT er.*, r.purpose, r.billing_number
    FROM elpt_results er
    JOIN elpt_registrations r ON er.registration_id = r.id
    WHERE er.user_id = ?
    ORDER BY er.test_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll();

// Get current registrations
$stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Tes - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <<link href="../assets/css/custom.css" rel="stylesheet">
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
                        <a class="nav-link" href="elpt-registration.php">
                            <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT
                        </a>
                        <a class="nav-link active" href="test-results.php">
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
                    <h2 class="fw-bold">Hasil Tes ELPT</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <?php if (empty($results)): ?>
                    <!-- No Results -->
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Belum Ada Hasil Tes</h4>
                            <p class="text-muted">Anda belum memiliki hasil tes ELPT atau hasil belum diinput oleh admin.</p>
                            
                            <?php if (!empty($registrations)): ?>
                                <div class="mt-4">
                                    <h6>Status Pendaftaran Anda:</h6>
                                    <?php foreach ($registrations as $reg): ?>
                                        <div class="alert alert-info">
                                            <strong>Tanggal Tes:</strong> <?= formatDate($reg['test_date']) ?><br>
                                            <strong>Status:</strong> 
                                            <span class="badge <?= 
                                                $reg['payment_status'] === 'confirmed' ? 'bg-success' : 
                                                ($reg['payment_status'] === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') 
                                            ?>">
                                                <?= strtoupper($reg['payment_status']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <a href="elpt-registration.php" class="btn btn-primary">
                                    <i class="bi bi-calendar-plus me-2"></i>Daftar ELPT Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Latest Result -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Hasil Tes Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="chart-container">
                                        <canvas id="latestChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="text-center mb-4">
                                        <div class="score-display <?= $results[0]['total_score'] >= 450 ? 'text-success' : 'text-warning' ?>">
                                            <?= $results[0]['total_score'] ?>
                                        </div>
                                        <h6 class="text-muted">Total Score</h6>
                                        <span class="badge <?= $results[0]['total_score'] >= 450 ? 'bg-success' : 'bg-warning text-dark' ?> fs-6 px-3 py-2">
                                            <?= $results[0]['total_score'] >= 450 ? 'LULUS' : 'BELUM LULUS' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="row text-center mb-4">
                                        <div class="col-4">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body p-3">
                                                    <h4><?= $results[0]['listening_score'] ?></h4>
                                                    <small>Listening</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-info text-white">
                                                <div class="card-body p-3">
                                                    <h4><?= $results[0]['structure_score'] ?></h4>
                                                    <small>Structure</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="card bg-success text-white">
                                                <div class="card-body p-3">
                                                    <h4><?= $results[0]['reading_score'] ?></h4>
                                                    <small>Reading</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-muted mb-3">
                                            <i class="bi bi-calendar me-2"></i>
                                            <?= formatDate($results[0]['test_date']) ?>
                                        </p>
                                        
                                        <?php if ($results[0]['total_score'] >= 450): ?>
                                            <a href="../download-certificate.php?id=<?= $results[0]['id'] ?>" class="btn btn-success btn-lg" target="_blank">
                                                <i class="bi bi-download me-2"></i>Download Sertifikat
                                            </a>
                                        <?php else: ?>
                                            <div class="d-grid gap-2">
                                                <a href="elpt-registration.php" class="btn btn-primary">
                                                    <i class="bi bi-arrow-repeat me-2"></i>Daftar ELPT Ulang
                                                </a>
                                                <a href="course.php" class="btn btn-outline-primary">
                                                    <i class="bi bi-book me-2"></i>Daftar Kursus Persiapan
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results History -->
                    <?php if (count($results) > 1): ?>
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Hasil Tes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <?php foreach (array_slice($results, 1) as $result): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card result-card">
                                                <div class="card-header bg-light">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <strong><?= formatDate($result['test_date']) ?></strong>
                                                        <span class="badge <?= $result['total_score'] >= 450 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                            <?= $result['total_score'] >= 450 ? 'LULUS' : 'BELUM LULUS' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="fw-bold text-primary"><?= $result['listening_score'] ?></div>
                                                            <small class="text-muted">Listening</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-info"><?= $result['structure_score'] ?></div>
                                                            <small class="text-muted">Structure</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-success"><?= $result['reading_score'] ?></div>
                                                            <small class="text-muted">Reading</small>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="fs-4 fw-bold <?= $result['total_score'] >= 450 ? 'text-success' : 'text-warning' ?>">
                                                        <?= $result['total_score'] ?>
                                                    </div>
                                                    <small class="text-muted">Total Score</small>
                                                    
                                                    <?php if ($result['total_score'] >= 450): ?>
                                                        <div class="mt-3">
                                                            <a href="../download-certificate.php?id=<?= $result['id'] ?>" class="btn btn-success btn-sm" target="_blank">
                                                                <i class="bi bi-download me-1"></i>Sertifikat
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Score Analysis -->
                    <div class="row g-4 mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Analisis Skor</h6>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $latest = $results[0];
                                    $listening_pct = ($latest['listening_score'] / 250) * 100;
                                    $structure_pct = ($latest['structure_score'] / 250) * 100;
                                    $reading_pct = ($latest['reading_score'] / 250) * 100;
                                    ?>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Listening</span>
                                            <span><?= $latest['listening_score'] ?>/250</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" style="width: <?= $listening_pct ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Structure</span>
                                            <span><?= $latest['structure_score'] ?>/250</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-info" style="width: <?= $structure_pct ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Reading</span>
                                            <span><?= $latest['reading_score'] ?>/250</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: <?= $reading_pct ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <strong>Persentase Keseluruhan: <?= round(($latest['total_score'] / 750) * 100, 1) ?>%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Rekomendasi</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($latest['total_score'] >= 450): ?>
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <strong>Selamat!</strong> Anda telah lulus tes ELPT.
                                        </div>
                                        <ul class="mb-0">
                                            <li>Download sertifikat untuk keperluan akademik</li>
                                            <li>Pertahankan kemampuan bahasa Inggris Anda</li>
                                            <li>Gunakan sertifikat untuk keperluan skripsi/yudisium</li>
                                        </ul>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Belum Lulus.</strong> Skor minimum adalah 450.
                                        </div>
                                        
                                        <?php
                                        $weakest = min($latest['listening_score'], $latest['structure_score'], $latest['reading_score']);
                                        $weakest_section = '';
                                        if ($weakest == $latest['listening_score']) $weakest_section = 'Listening';
                                        elseif ($weakest == $latest['structure_score']) $weakest_section = 'Structure';
                                        else $weakest_section = 'Reading';
                                        ?>
                                        
                                        <p><strong>Area yang perlu ditingkatkan:</strong></p>
                                        <ul>
                                            <li><strong><?= $weakest_section ?></strong> - Skor terendah (<?= $weakest ?>)</li>
                                            <?php if ($latest['listening_score'] < 150): ?>
                                                <li>Perbanyak latihan listening comprehension</li>
                                            <?php endif; ?>
                                            <?php if ($latest['structure_score'] < 150): ?>
                                                <li>Pelajari grammar dan structure lebih dalam</li>
                                            <?php endif; ?>
                                            <?php if ($latest['reading_score'] < 150): ?>
                                                <li>Tingkatkan kemampuan reading comprehension</li>
                                            <?php endif; ?>
                                            <li>Ikuti kursus persiapan ELPT 24 sesi</li>
                                            <li>Daftar ulang tes ELPT setelah persiapan</li>
                                        </ul>
                                        
                                        <div class="d-grid gap-2 mt-3">
                                            <a href="course.php" class="btn btn-primary btn-sm">
                                                <i class="bi bi-book me-2"></i>Daftar Kursus Persiapan
                                            </a>
                                            <a href="elpt-registration.php" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-arrow-repeat me-2"></i>Daftar ELPT Ulang
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($results)): ?>
    <script>
        // Create chart for latest result
        const ctx = document.getElementById('latestChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Listening', 'Structure', 'Reading'],
                datasets: [{
                    data: [<?= $results[0]['listening_score'] ?>, <?= $results[0]['structure_score'] ?>, <?= $results[0]['reading_score'] ?>],
                    backgroundColor: [
                        '#0d6efd',
                        '#17a2b8', 
                        '#28a745'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '/250';
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>