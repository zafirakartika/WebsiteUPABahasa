<?php
// admin/students.php
require_once '../config/database.php';
requireRole('admin');

// Handle student actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $student_id = $_GET['id'] ?? null;
    
    switch ($action) {
        case 'deactivate':
            if ($student_id) {
                try {
                    // First check current status
                    $stmt = $pdo->prepare("SELECT is_active, name FROM users WHERE id = ? AND role = 'student'");
                    $stmt->execute([$student_id]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        $new_status = $student['is_active'] ? 0 : 1;
                        $action_text = $new_status ? 'diaktifkan' : 'dinonaktifkan';
                        
                        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'student'");
                        $stmt->execute([$new_status, $student_id]);
                        
                        // Log activity
                        logActivity('student_' . ($new_status ? 'activated' : 'deactivated'), 
                                  "Student {$student['name']} has been " . ($new_status ? 'activated' : 'deactivated'));
                        
                        showAlert("Mahasiswa {$student['name']} berhasil {$action_text}", 'success');
                    } else {
                        showAlert('Mahasiswa tidak ditemukan', 'error');
                    }
                } catch (PDOException $e) {
                    showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
                }
            }
            break;
    }
    
    header('Location: students.php');
    exit;
}

// Handle student update (including course status) 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
    $student_id = $_POST['student_id'] ?? null;
    $name = sanitizeInput($_POST['name'] ?? '');
    $program = sanitizeInput($_POST['program'] ?? '');
    $faculty = sanitizeInput($_POST['faculty'] ?? '');
    $level = $_POST['level'] ?? '';
    $no_telpon = sanitizeInput($_POST['no_telpon'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Course data
    $course_status = $_POST['course_status'] ?? '';
    $current_session = intval($_POST['current_session'] ?? 0);
    $total_sessions = intval($_POST['total_sessions'] ?? 24);
    $final_test_date = $_POST['final_test_date'] ?? null;
    
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = 'Nama tidak boleh kosong';
    if ($current_session < 0 || $current_session > $total_sessions) $errors[] = 'Sesi saat ini tidak valid';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update user data 
            $stmt = $pdo->prepare("
                UPDATE users SET 
                name = ?, program = ?, faculty = ?, level = ?, no_telpon = ?, is_active = ?
                WHERE id = ? AND role = 'student'
            ");
            $stmt->execute([$name, $program, $faculty, $level, $no_telpon, $is_active, $student_id]);
            
            // Handle course data
            if (!empty($course_status)) {
                // Check if course record exists
                $stmt = $pdo->prepare("SELECT id FROM courses WHERE user_id = ?");
                $stmt->execute([$student_id]);
                $course_exists = $stmt->fetch();
                
                if ($course_exists) {
                    // Update existing course
                    $stmt = $pdo->prepare("
                        UPDATE courses SET 
                        current_session = ?, total_sessions = ?, final_test_date = ?, status = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$current_session, $total_sessions, $final_test_date, $course_status, $student_id]);
                } else {
                    // Insert new course record
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (user_id, current_session, total_sessions, final_test_date, status)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$student_id, $current_session, $total_sessions, $final_test_date, $course_status]);
                }
            } else {
                // If course_status is empty, delete course record
                $stmt = $pdo->prepare("DELETE FROM courses WHERE user_id = ?");
                $stmt->execute([$student_id]);
            }
            
            $pdo->commit();
            showAlert('Data mahasiswa berhasil diperbarui', 'success');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            showAlert('Terjadi kesalahan: ' . $e->getMessage(), 'error');
        }
    } else {
        showAlert(implode('<br>', $errors), 'error');
    }
    
    header('Location: students.php');
    exit;
}

// Get students with filters
$filter_fakultas = $_GET['faculty'] ?? '';
$filter_jenjang = $_GET['level'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT u.*, 
           COUNT(DISTINCT er.id) as total_registrations,
           COUNT(DISTINCT res.id) as total_results,
           MAX(res.total_score) as best_score,
           COUNT(DISTINCT CASE WHEN res.is_passed = 1 THEN res.id END) as passed_tests,
           c.id as course_id, c.status as course_status, c.current_session, c.total_sessions, c.final_test_date
    FROM users u 
    LEFT JOIN elpt_registrations er ON u.id = er.user_id
    LEFT JOIN elpt_results res ON u.id = res.user_id
    LEFT JOIN courses c ON u.id = c.user_id
    WHERE u.role = 'student'
";

$params = [];

if (!empty($filter_fakultas)) {
    $sql .= " AND u.faculty = ?";
    $params[] = $filter_fakultas;
}

if (!empty($filter_jenjang)) {
    $sql .= " AND u.level = ?";
    $params[] = $filter_jenjang;
}

if ($filter_status !== 'all') {
    $sql .= " AND u.is_active = ?";
    $params[] = $filter_status === 'active' ? 1 : 0;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.nim LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get filter options
$stmt = $pdo->query("SELECT DISTINCT faculty FROM users WHERE role = 'student' AND faculty IS NOT NULL ORDER BY faculty");
$fakultas_options = $stmt->fetchAll(PDO::FETCH_COLUMN);

$jenjang_options = ['D3', 'S1', 'S2', 'S3'];

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND is_active = 1");
$stats['active'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND is_active = 0");
$stats['inactive'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND DATE(created_at) = CURDATE()");
$stats['new_today'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - UPA Bahasa UPNVJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    
    <style>
        .readonly-field {
            background-color: #f8f9fa !important;
            border-color: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed;
        }
        
        .readonly-field:focus {
            background-color: #f8f9fa !important;
            border-color: #e9ecef !important;
            box-shadow: none !important;
        }
        
        .readonly-label {
            color: #6c757d;
            font-weight: normal;
        }
        
        .readonly-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        .status-toggle-btn {
            min-width: 90px;
        }

        .data-protection-notice {
            background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
            border: 1px solid #81c784;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
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
                    <h2 class="fw-bold">Data Mahasiswa</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download me-2"></i>Export Data
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>

                <?php displayAlert(); ?>

                <!-- Data Protection Notice -->
                <div class="data-protection-notice">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check text-success me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1 text-success"><strong>Perlindungan Data Mahasiswa</strong></h6>
                            <small class="text-muted">
                                Data mahasiswa dilindungi dan tidak dapat dihapus permanen. Gunakan fitur <strong>Nonaktifkan</strong> untuk menonaktifkan akun mahasiswa.
                                Data yang dinonaktifkan masih tersimpan dan dapat diaktifkan kembali kapan saja.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['total'] ?></div>
                                <h6>Total Mahasiswa</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="bi bi-person-check" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['active'] ?></div>
                                <h6>Aktif</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-warning text-dark">
                            <div class="card-body">
                                <i class="bi bi-person-x" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['inactive'] ?></div>
                                <h6>Nonaktif</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                                <div class="display-6 fw-bold mt-2"><?= $stats['new_today'] ?></div>
                                <h6>Baru Hari Ini</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua</option>
                                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jenjang</label>
                                <select name="level" class="form-select">
                                    <option value="">Semua Jenjang</option>
                                    <?php foreach ($jenjang_options as $jenjang): ?>
                                        <option value="<?= $jenjang ?>" <?= $filter_jenjang === $jenjang ? 'selected' : '' ?>>
                                            <?= $jenjang ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fakultas</label>
                                <select name="faculty" class="form-select">
                                    <option value="">Semua Fakultas</option>
                                    <?php foreach ($fakultas_options as $fakultas): ?>
                                        <option value="<?= $fakultas ?>" <?= $filter_fakultas === $fakultas ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fakultas) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pencarian</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, NIM, atau Email" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="students.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Data Mahasiswa</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($students)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Tidak ada data mahasiswa</h5>
                                <p class="text-muted">Belum ada mahasiswa terdaftar atau tidak ada yang sesuai dengan filter</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 data-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mahasiswa</th>
                                            <th>Kontak</th>
                                            <th>Program Studi</th>
                                            <th>Statistik ELPT</th>
                                            <th>Status Kursus</th>
                                            <th>Bergabung</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr class="<?= $student['is_active'] ? '' : 'table-secondary' ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="student-avatar bg-primary me-3">
                                                            <?= strtoupper(substr($student['name'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($student['name']) ?></strong>
                                                            <?php if (!$student['is_active']): ?>
                                                                <span class="badge bg-secondary ms-2">Nonaktif</span>
                                                            <?php endif; ?>
                                                            <br>
                                                            <small class="text-muted">NIM: <?= htmlspecialchars($student['nim']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($student['email']) ?><br>
                                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($student['no_telpon'] ?? 'N/A') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($student['program']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($student['level']) ?> - <?= htmlspecialchars($student['faculty']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <div class="d-flex justify-content-between">
                                                            <span>Pendaftaran:</span>
                                                            <strong><?= $student['total_registrations'] ?></strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Hasil Tes:</span>
                                                            <strong><?= $student['total_results'] ?></strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Lulus:</span>
                                                            <strong class="text-success"><?= $student['passed_tests'] ?></strong>
                                                        </div>
                                                        <?php if ($student['best_score']): ?>
                                                            <div class="d-flex justify-content-between">
                                                                <span>Best Score:</span>
                                                                <strong class="<?= $student['best_score'] >= 450 ? 'text-success' : 'text-warning' ?>">
                                                                    <?= $student['best_score'] ?>
                                                                </strong>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($student['course_status']): ?>
                                                        <div>
                                                            <span class="badge <?= 
                                                                $student['course_status'] === 'completed' ? 'bg-success' : 
                                                                ($student['course_status'] === 'active' ? 'bg-primary' : 'bg-warning text-dark') 
                                                            ?>">
                                                                <?= strtoupper($student['course_status']) ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                Sesi: <?= $student['current_session'] ?>/<?= $student['total_sessions'] ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum Ikut</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= formatDate($student['created_at']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $student['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $student['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#studentModal"
                                                                data-student='<?= json_encode($student) ?>'
                                                                title="Lihat Detail">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editStudentModal"
                                                                data-student='<?= json_encode($student) ?>'
                                                                title="Edit Data">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="?action=deactivate&id=<?= $student['id'] ?>" 
                                                           class="btn <?= $student['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> confirm-action status-toggle-btn"
                                                           data-message="<?= $student['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> mahasiswa <?= htmlspecialchars($student['name']) ?>?"
                                                           title="<?= $student['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> Mahasiswa">
                                                            <i class="bi bi-<?= $student['is_active'] ? 'person-x' : 'person-check' ?>"></i>
                                                            <span class="d-none d-lg-inline ms-1">
                                                                <?= $student['is_active'] ? 'Nonaktif' : 'Aktifkan' ?>
                                                            </span>
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
            </div>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person me-2"></i>Detail Mahasiswa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentDetailContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal with readonly NIM and Email -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Edit Data Mahasiswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editStudentForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_student">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        
                        <!-- Enhanced Tab Navigation -->
                        <ul class="nav nav-tabs" id="editTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="student-info-tab" data-bs-toggle="tab" data-bs-target="#student-info" type="button" role="tab">
                                    <i class="bi bi-person-circle"></i>Data Mahasiswa
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="course-info-tab" data-bs-toggle="tab" data-bs-target="#course-info" type="button" role="tab">
                                    <i class="bi bi-book-half"></i>Status Kursus
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3" id="editTabsContent">
                            <!-- Student Info Tab -->
                            <div class="tab-pane fade show active" id="student-info" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" name="name" id="edit_name" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label readonly-label">
                                            Email 
                                            <i class="bi bi-lock-fill text-muted ms-1" title="Tidak dapat diubah"></i>
                                        </label>
                                        <input type="email" class="form-control readonly-field" id="edit_email" readonly tabindex="-1">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label readonly-label">
                                            NIM 
                                            <i class="bi bi-lock-fill text-muted ms-1" title="Tidak dapat diubah"></i>
                                        </label>
                                        <input type="text" class="form-control readonly-field" id="edit_nim" readonly tabindex="-1">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">No. Telepon</label>
                                        <input type="text" class="form-control" name="no_telpon" id="edit_no_telpon">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Jenjang</label>
                                        <select class="form-select" name="level" id="edit_level" required>
                                            <option value="">Pilih Jenjang</option>
                                            <option value="D3">D3</option>
                                            <option value="S1">S1</option>
                                            <option value="S2">S2</option>
                                            <option value="S3">S3</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Program Studi</label>
                                        <input type="text" class="form-control" name="program" id="edit_program" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Fakultas</label>
                                        <input type="text" class="form-control" name="faculty" id="edit_faculty" required>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                            <label class="form-check-label" for="edit_is_active">
                                                Mahasiswa Aktif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Course Info Tab -->
                            <div class="tab-pane fade" id="course-info" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Status Kursus</label>
                                        <select class="form-select" name="course_status" id="edit_course_status">
                                            <option value="">Tidak Mengikuti Kursus</option>
                                            <option value="pending">Pending</option>
                                            <option value="active">Active</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Total Sesi</label>
                                        <input type="number" class="form-control" name="total_sessions" id="edit_total_sessions" min="1" max="50" value="24">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sesi Saat Ini</label>
                                        <input type="number" class="form-control" name="current_session" id="edit_current_session" min="0" max="50" value="1">
                                        <small class="text-muted">Sesi yang telah diselesaikan</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Final Test</label>
                                        <input type="date" class="form-control" name="final_test_date" id="edit_final_test_date">
                                    </div>
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <h6><i class="bi bi-info-circle me-2"></i>Panduan Status Kursus:</h6>
                                            <ul class="mb-0">
                                                <li><strong>Pending:</strong> Mahasiswa telah mendaftar tapi belum mulai kursus</li>
                                                <li><strong>Active:</strong> Mahasiswa sedang mengikuti kursus</li>
                                                <li><strong>Completed:</strong> Mahasiswa telah menyelesaikan semua sesi kursus</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="col-md-12" id="course_progress_container" style="display: none;">
                                        <label class="form-label">Progress Kursus</label>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-success" role="progressbar" id="course_progress_bar" style="width: 0%">
                                                <span id="course_progress_text">0/24</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-download me-2"></i>Export Data Mahasiswa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm" method="GET" action="export-students.php" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Format Export</label>
                            <select class="form-select" name="format" required>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                            <small class="text-muted">Pilih format file yang diinginkan</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Filter Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all">Semua Status</option>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Filter Jenjang</label>
                                    <select class="form-select" name="level">
                                        <option value="">Semua Jenjang</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Filter Fakultas</label>
                            <select class="form-select" name="faculty">
                                <option value="">Semua Fakultas</option>
                                <?php foreach ($fakultas_options as $fakultas): ?>
                                    <option value="<?= htmlspecialchars($fakultas) ?>"><?= htmlspecialchars($fakultas) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="start_date">
                                    <small class="text-muted">Tanggal bergabung mulai</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control" name="end_date">
                                    <small class="text-muted">Tanggal bergabung sampai</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data Tambahan</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="include_stats" id="includeStats" checked>
                                <label class="form-check-label" for="includeStats">
                                    <strong>Statistik ELPT</strong>
                                    <small class="d-block text-muted">Total pendaftaran, tes, skor terbaik</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="include_course" id="includeCourse" checked>
                                <label class="form-check-label" for="includeCourse">
                                    <strong>Status Kursus</strong>
                                    <small class="d-block text-muted">Progress kursus dan final test</small>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="exportForm" class="btn btn-success">
                        <i class="bi bi-download me-2"></i>Download
                    </button>
                </div>
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
                order: [[5, 'desc']], // Sort by join date
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                columnDefs: [
                    { orderable: false, targets: [-1] } // Disable sorting on action column
                ]
            });

            // Student Detail Modal Handler
            $('#studentModal').on('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const student = JSON.parse(button.getAttribute('data-student'));
                
                const content = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Pribadi</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><td><strong>Nama:</strong></td><td>${student.name}</td></tr>
                                        <tr><td><strong>NIM:</strong></td><td>${student.nim}</td></tr>
                                        <tr><td><strong>Email:</strong></td><td>${student.email}</td></tr>
                                        <tr><td><strong>No. Telepon:</strong></td><td>${student.no_telpon || 'N/A'}</td></tr>
                                        <tr><td><strong>Status:</strong></td><td>
                                            <span class="badge ${student.is_active ? 'bg-success' : 'bg-secondary'}">
                                                ${student.is_active ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </td></tr>
                                        <tr><td><strong>Bergabung:</strong></td><td>${new Date(student.created_at).toLocaleDateString('id-ID')}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Akademik</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><td><strong>Program Studi:</strong></td><td>${student.program}</td></tr>
                                        <tr><td><strong>Jenjang:</strong></td><td>${student.level}</td></tr>
                                        <tr><td><strong>Fakultas:</strong></td><td>${student.faculty}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Statistik ELPT</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><td><strong>Total Pendaftaran:</strong></td><td>${student.total_registrations}</td></tr>
                                        <tr><td><strong>Total Tes:</strong></td><td>${student.total_results}</td></tr>
                                        <tr><td><strong>Lulus:</strong></td><td><span class="text-success">${student.passed_tests}</span></td></tr>
                                        <tr><td><strong>Best Score:</strong></td><td>
                                            ${student.best_score ? 
                                                `<span class="${student.best_score >= 450 ? 'text-success' : 'text-warning'}">${student.best_score}</span>` : 
                                                'N/A'
                                            }
                                        </td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${student.course_status ? `
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Status Kursus</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Status:</strong><br>
                                            <span class="badge ${getStatusBadgeClass(student.course_status)}">${student.course_status.toUpperCase()}</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Progress:</strong><br>
                                            ${student.current_session}/${student.total_sessions} sesi
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Persentase:</strong><br>
                                            ${Math.round((student.current_session / student.total_sessions) * 100)}%
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Final Test:</strong><br>
                                            ${student.final_test_date ? new Date(student.final_test_date).toLocaleDateString('id-ID') : 'Belum dijadwalkan'}
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: ${(student.current_session / student.total_sessions) * 100}%">
                                                ${Math.round((student.current_session / student.total_sessions) * 100)}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                `;
                
                document.getElementById('studentDetailContent').innerHTML = content;
            });

            // Edit Student Modal Handler
            $('#editStudentModal').on('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const student = JSON.parse(button.getAttribute('data-student'));
                
                // Populate student data
                document.getElementById('edit_student_id').value = student.id;
                document.getElementById('edit_name').value = student.name;
                
                // Populate readonly fields (email and nim) but don't include in form submission
                document.getElementById('edit_email').value = student.email;
                document.getElementById('edit_nim').value = student.nim;
                
                document.getElementById('edit_no_telpon').value = student.no_telpon || '';
                document.getElementById('edit_level').value = student.level || '';
                document.getElementById('edit_program').value = student.program || '';
                document.getElementById('edit_faculty').value = student.faculty || '';
                document.getElementById('edit_is_active').checked = student.is_active == 1;
                
                // Populate course data
                document.getElementById('edit_course_status').value = student.course_status || '';
                document.getElementById('edit_current_session').value = student.current_session || 1;
                document.getElementById('edit_total_sessions').value = student.total_sessions || 24;
                document.getElementById('edit_final_test_date').value = student.final_test_date || '';
                
                // Update progress display
                updateCourseProgress();
            });

            // Course status change handler
            $('#edit_course_status, #edit_current_session, #edit_total_sessions').on('input change', function() {
                updateCourseProgress();
            });

            // Function to update course progress display
            function updateCourseProgress() {
                const status = document.getElementById('edit_course_status').value;
                const current = parseInt(document.getElementById('edit_current_session').value) || 0;
                const total = parseInt(document.getElementById('edit_total_sessions').value) || 24;
                const progressContainer = document.getElementById('course_progress_container');
                const progressBar = document.getElementById('course_progress_bar');
                const progressText = document.getElementById('course_progress_text');
                
                if (status && status !== '') {
                    progressContainer.style.display = 'block';
                    const percentage = Math.round((current / total) * 100);
                    progressBar.style.width = percentage + '%';
                    progressText.textContent = current + '/' + total;
                    
                    // Update progress bar color based on status
                    progressBar.className = 'progress-bar ';
                    if (status === 'completed') {
                        progressBar.className += 'bg-success';
                    } else if (status === 'active') {
                        progressBar.className += 'bg-primary';
                    } else {
                        progressBar.className += 'bg-warning';
                    }
                } else {
                    progressContainer.style.display = 'none';
                }
            }

            // Validation for current session
            $('#edit_current_session').on('input', function() {
                const current = parseInt(this.value) || 0;
                const total = parseInt(document.getElementById('edit_total_sessions').value) || 24;
                
                if (current > total) {
                    this.value = total;
                    alert('Sesi saat ini tidak boleh melebihi total sesi!');
                }
                if (current < 0) {
                    this.value = 0;
                }
            });

            //Form submission handler - prevent readonly fields from being submitted
            $('#editStudentForm').on('submit', function(e) {
                const current = parseInt(document.getElementById('edit_current_session').value) || 0;
                const total = parseInt(document.getElementById('edit_total_sessions').value) || 24;
                
                if (current > total) {
                    e.preventDefault();
                    alert('Sesi saat ini tidak boleh melebihi total sesi!');
                    return false;
                }
                
                // Disable readonly fields before submission to prevent them from being sent
                const emailField = document.getElementById('edit_email');
                const nimField = document.getElementById('edit_nim');
                
                emailField.disabled = true;
                nimField.disabled = true;
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                submitBtn.disabled = true;
                
                // Re-enable readonly fields after a short delay (for user experience)
                setTimeout(function() {
                    emailField.disabled = false;
                    nimField.disabled = false;
                }, 100);
            });

            // Prevent copy/paste and typing in readonly fields
            $('#edit_email, #edit_nim').on('keydown paste cut', function(e) {
                e.preventDefault();
                return false;
            });

            // Show tooltip when user tries to click readonly fields
            $('#edit_email, #edit_nim').on('focus click', function() {
                $(this).tooltip('dispose').tooltip({
                    title: 'Field ini tidak dapat diubah karena merupakan kredensial login',
                    placement: 'top'
                }).tooltip('show');
                
                setTimeout(() => {
                    $(this).tooltip('dispose');
                }, 2000);
            });

            // Enhanced confirmation for status toggle actions
            $('.confirm-action').on('click', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-message') || 'Apakah Anda yakin?';
                const isDeactivate = this.href.includes('action=deactivate');
                
                let confirmMessage = message;
                if (isDeactivate) {
                    const isCurrentlyActive = this.classList.contains('btn-outline-danger');
                    if (isCurrentlyActive) {
                        confirmMessage += '\n\nMahasiswa yang dinonaktifkan tidak dapat login ke sistem, namun data akan tetap tersimpan dan dapat diaktifkan kembali.';
                    } else {
                        confirmMessage += '\n\nMahasiswa akan dapat login kembali ke sistem.';
                    }
                }
                
                if (confirm(confirmMessage)) {
                    // Show loading state
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    this.style.pointerEvents = 'none';
                    
                    window.location.href = this.href;
                }
            });

            // Export form handler
            $('#exportForm').on('submit', function(e) {
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Show loading state
                submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');
                submitBtn.prop('disabled', true);
                
                // Re-enable button after 3 seconds
                setTimeout(function() {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                    $('#exportModal').modal('hide');
                }, 3000);
            });

            // Pre-fill export form with current filters
            $('#exportModal').on('show.bs.modal', function() {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Set current filters to export form
                if (urlParams.get('status')) {
                    $('select[name="status"]').val(urlParams.get('status'));
                }
                if (urlParams.get('level')) {
                    $('select[name="level"]').val(urlParams.get('level'));
                }
                if (urlParams.get('faculty')) {
                    $('select[name="faculty"]').val(urlParams.get('faculty'));
                }
            });

            // Enhanced visual feedback for nonactive students
            $('.table tbody tr.table-secondary').each(function() {
                $(this).find('.student-avatar').removeClass('bg-primary').addClass('bg-secondary');
            });
        });

        // Helper function for status badge classes
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'completed': return 'bg-success';
                case 'active': return 'bg-primary';
                case 'pending': return 'bg-warning text-dark';
                default: return 'bg-secondary';
            }
        }
    </script>
</body>
</html>