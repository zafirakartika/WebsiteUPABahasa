<?php
require_once '../config/database.php';
requireRole('admin');

// Handle test result submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = $_POST['registration_id'] ?? '';
    $listening_score = $_POST['listening_score'] ?? '';
    $structure_score = $_POST['structure_score'] ?? '';
    $reading_score = $_POST['reading_score'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($registration_id) || !is_numeric($registration_id)) {
        $errors[] = 'ID registrasi tidak valid';
    }
    
    if (!is_numeric($listening_score) || $listening_score < 0 || $listening_score > 250) {
        $errors[] = 'Skor Listening harus antara 0-250';
    }
    
    if (!is_numeric($structure_score) || $structure_score < 0 || $structure_score > 250) {
        $errors[] = 'Skor Structure harus antara 0-250';
    }
    
    if (!is_numeric($reading_score) || $reading_score < 0 || $reading_score > 250) {
        $errors[] = 'Skor Reading harus antara 0-250';
    }
    
    if (empty($errors)) {
        try {
            // Get registration details
            $stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE id = ? AND payment_status = 'confirmed'");
            $stmt->execute([$registration_id]);
            $registration = $stmt->fetch();
            
            if (!$registration) {
                $errors[] = 'Registrasi tidak ditemukan atau belum dikonfirmasi';
            } else {
                // Check if result already exists
                $stmt = $pdo->prepare("SELECT id FROM elpt_results WHERE registration_id = ?");
                $stmt->execute([$registration_id]);
                $existing_result = $stmt->fetch();
                
                if ($existing_result) {
                    // Update existing result
                    $stmt = $pdo->prepare("
                        UPDATE elpt_results 
                        SET listening_score = ?, structure_score = ?, reading_score = ?
                        WHERE registration_id = ?
                    ");
                    $stmt->execute([$listening_score, $structure_score, $reading_score, $registration_id]);
                    showAlert('Hasil tes berhasil diperbarui!', 'success');
                    $result_id = $existing_result['id'];
                } else {
                    // Insert new result
                    $stmt = $pdo->prepare("
                        INSERT INTO elpt_results (user_id, registration_id, listening_score, structure_score, reading_score, test_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $registration['user_id'], 
                        $registration_id, 
                        $listening_score, 
                        $structure_score, 
                        $reading_score, 
                        $registration['test_date']
                    ]);
                    $result_id = $pdo->lastInsertId();
                    showAlert('Hasil tes berhasil disimpan!', 'success');
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        showAlert(implode('<br>', $errors), 'error');
    }
}

// Get confirmed registrations that need test results
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT r.*, u.name, u.nim, u.program, u.faculty,
           er.id as result_id, er.listening_score, er.structure_score, er.reading_score, er.total_score
    FROM elpt_registrations r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN elpt_results er ON r.id = er.registration_id
    WHERE r.payment_status = 'confirmed'
";

$params = [];

if (!empty($filter_date)) {
    $sql .= " AND r.test_date = ?";
    $params[] = $filter_date;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.nim LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY r.test_date DESC, u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Get available test dates
$stmt = $pdo->query("SELECT DISTINCT test_date FROM elpt_registrations WHERE payment_status = 'confirmed' ORDER BY test_date DESC");
$available_dates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Hasil Tes - UPA Bahasa UPNVJ</title>
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
                    <h2 class="fw-bold">Input Hasil Tes ELPT</h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
                </div>

                <?php displayAlert(); ?>

                <!-- Filter and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Filter Tanggal Tes</label>
                                <select name="date" class="form-select">
                                    <option value="">Semua Tanggal</option>
                                    <?php foreach ($available_dates as $date): ?>
                                        <option value="<?= $date['test_date'] ?>" <?= $filter_date === $date['test_date'] ? 'selected' : '' ?>>
                                            <?= formatDate($date['test_date']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pencarian</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama atau NIM" value="<?= htmlspecialchars($search) ?>">
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

                <!-- Test Results Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Data Peserta Tes</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($registrations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Tidak ada data peserta</h5>
                                <p class="text-muted">Belum ada peserta yang terkonfirmasi atau tidak ada yang sesuai dengan filter</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Peserta</th>
                                            <th>Tanggal Tes</th>
                                            <th>Listening</th>
                                            <th>Structure</th>
                                            <th>Reading</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $reg): ?>
                                            <tr class="result-row <?= $reg['result_id'] ? 'has-result' : '' ?>">
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($reg['name']) ?></strong><br>
                                                        <small class="text-muted">
                                                            NIM: <?= htmlspecialchars($reg['nim']) ?><br>
                                                            <?= htmlspecialchars($reg['program']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= formatDate($reg['test_date']) ?></strong><br>
                                                    <span class="badge bg-info"><?= htmlspecialchars($reg['purpose']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($reg['result_id']): ?>
                                                        <span class="badge bg-primary"><?= $reg['listening_score'] ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($reg['result_id']): ?>
                                                        <span class="badge bg-primary"><?= $reg['structure_score'] ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($reg['result_id']): ?>
                                                        <span class="badge bg-primary"><?= $reg['reading_score'] ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#inputModal"
                                                            data-registration-id="<?= $reg['id'] ?>"
                                                            data-student-name="<?= htmlspecialchars($reg['name']) ?>"
                                                            data-student-nim="<?= htmlspecialchars($reg['nim']) ?>"
                                                            data-test-date="<?= formatDate($reg['test_date']) ?>"
                                                            data-listening="<?= $reg['listening_score'] ?? '' ?>"
                                                            data-structure="<?= $reg['structure_score'] ?? '' ?>"
                                                            data-reading="<?= $reg['reading_score'] ?? '' ?>">
                                                        <i class="bi bi-<?= $reg['result_id'] ? 'pencil' : 'plus' ?>"></i>
                                                        <?= $reg['result_id'] ? 'Edit' : 'Input' ?>
                                                    </button>
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
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Petunjuk Input Hasil</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li><strong>Listening:</strong> Skor 0-250</li>
                                    <li><strong>Structure:</strong> Skor 0-250</li>
                                    <li><strong>Reading:</strong> Skor 0-250</li>
                                    <li><strong>Total Maksimal:</strong> 750</li>
                                    <li><strong>Batas Lulus:</strong> 450</li>
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
                                    <li>Pastikan skor yang diinput sudah benar</li>
                                    <li>Hasil yang sudah disimpan dapat diedit</li>
                                    <li>Mahasiswa dapat melihat hasil secara real-time</li>
                                    <li>Sertifikat otomatis tersedia jika lulus</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input Modal -->
    <div class="modal fade" id="inputModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-plus me-2"></i>Input Hasil Tes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="registration_id" name="registration_id">
                        
                        <!-- Student Info -->
                        <div class="alert alert-info">
                            <strong id="student_name"></strong><br>
                            <small>NIM: <span id="student_nim"></span> | Tanggal Tes: <span id="test_date"></span></small>
                        </div>
                        
                        <!-- Score Inputs -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Listening Score</label>
                                <input type="number" class="form-control score-input" 
                                       name="listening_score" id="listening_score" 
                                       min="0" max="250" required>
                                <small class="text-muted">0-250</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Structure Score</label>
                                <input type="number" class="form-control score-input" 
                                       name="structure_score" id="structure_score" 
                                       min="0" max="250" required>
                                <small class="text-muted">0-250</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reading Score</label>
                                <input type="number" class="form-control score-input" 
                                       name="reading_score" id="reading_score" 
                                       min="0" max="250" required>
                                <small class="text-muted">0-250</small>
                            </div>
                        </div>
                        
                        <!-- Total Preview -->
                        <div class="mt-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6>Total Score Preview</h6>
                                    <div class="display-6 fw-bold" id="total_preview">0</div>
                                    <div id="status_preview" class="mt-2">
                                        <span class="badge bg-secondary">Belum Diisi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Simpan Hasil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle modal data population
        document.getElementById('inputModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            // Populate modal with data
            document.getElementById('registration_id').value = button.getAttribute('data-registration-id');
            document.getElementById('student_name').textContent = button.getAttribute('data-student-name');
            document.getElementById('student_nim').textContent = button.getAttribute('data-student-nim');
            document.getElementById('test_date').textContent = button.getAttribute('data-test-date');
            
            // Populate existing scores if available
            document.getElementById('listening_score').value = button.getAttribute('data-listening') || '';
            document.getElementById('structure_score').value = button.getAttribute('data-structure') || '';
            document.getElementById('reading_score').value = button.getAttribute('data-reading') || '';
            
            // Update preview
            updateTotalPreview();
        });
        
        // Update total score preview
        function updateTotalPreview() {
            const listening = parseInt(document.getElementById('listening_score').value) || 0;
            const structure = parseInt(document.getElementById('structure_score').value) || 0;
            const reading = parseInt(document.getElementById('reading_score').value) || 0;
            const total = listening + structure + reading;
            
            document.getElementById('total_preview').textContent = total;
            
            const statusElement = document.getElementById('status_preview');
            if (total === 0) {
                statusElement.innerHTML = '<span class="badge bg-secondary">Belum Diisi</span>';
            } else if (total >= 450) {
                statusElement.innerHTML = '<span class="badge bg-success">LULUS</span>';
            } else {
                statusElement.innerHTML = '<span class="badge bg-warning text-dark">BELUM LULUS</span>';
            }
        }
        
        // Add event listeners to score inputs
        document.getElementById('listening_score').addEventListener('input', updateTotalPreview);
        document.getElementById('structure_score').addEventListener('input', updateTotalPreview);
        document.getElementById('reading_score').addEventListener('input', updateTotalPreview);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const listening = parseInt(document.getElementById('listening_score').value);
            const structure = parseInt(document.getElementById('structure_score').value);
            const reading = parseInt(document.getElementById('reading_score').value);
            
            if (listening < 0 || listening > 250 || 
                structure < 0 || structure > 250 || 
                reading < 0 || reading > 250) {
                alert('Semua skor harus antara 0-250');
                e.preventDefault();
                return false;
            }
            
            if (listening + structure + reading === 0) {
                if (!confirm('Total skor adalah 0. Yakin ingin menyimpan?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>