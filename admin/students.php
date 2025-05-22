<?php
// admin/students.php
require_once '../api/config/database.php';
requireRole('admin');

// Handle student actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $student_id = $_GET['id'] ?? null;
    
    switch ($action) {
        case 'activate':
            if ($student_id) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND role = 'student'");
                $stmt->execute([$student_id]);
                showAlert('Mahasiswa berhasil diaktifkan', 'success');
            }
            break;
            
        case 'deactivate':
            if ($student_id) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role = 'student'");
                $stmt->execute([$student_id]);
                showAlert('Mahasiswa berhasil dinonaktifkan', 'warning');
            }
            break;
            
        case 'delete':
            if ($student_id) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                    $stmt->execute([$student_id]);
                    showAlert('Data mahasiswa berhasil dihapus', 'success');
                } catch (PDOException $e) {
                    showAlert('Tidak dapat menghapus mahasiswa yang memiliki data terkait', 'error');
                }
            }
            break;
    }
    
    header('Location: students.php');
    exit;
}

// Get students with filters
$filter_fakultas = $_GET['fakultas'] ?? '';
$filter_jenjang = $_GET['jenjang'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT u.*, 
           COUNT(DISTINCT er.id) as total_registrations,
           COUNT(DISTINCT res.id) as total_results,
           MAX(res.total_score) as best_score,
           COUNT(DISTINCT CASE WHEN res.is_passed = 1 THEN res.id END) as passed_tests,
           (SELECT c.status FROM courses c WHERE c.user_id = u.id ORDER BY c.created_at DESC LIMIT 1) as course_status
    FROM users u 
    LEFT JOIN elpt_registrations er ON u.id = er.user_id
    LEFT JOIN elpt_results res ON u.id = res.user_id
    WHERE u.role = 'student'
";

$params = [];

if (!empty($filter_fakultas)) {
    $sql .= " AND u.fakultas = ?";
    $params[] = $filter_fakultas;
}

if (!empty($filter_jenjang)) {
    $sql .= " AND u.jenjang = ?";
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
$stmt = $pdo->query("SELECT DISTINCT fakultas FROM users WHERE role = 'student' AND fakultas IS NOT NULL ORDER BY fakultas");
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
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #dc3545 0%, #6f42c1 100%);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 10px;
            margin: 5px 0;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .progress-small {
            height: 6px;
        }
    </style>
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
                        <a class="nav-link" href="registrations.php">
                            <i class="bi bi-calendar-event me-2"></i>Pendaftaran
                        </a>
                        <a class="nav-link" href="payments.php">
                            <i class="bi bi-credit-card me-2"></i>Pembayaran
                        </a>
                        <a class="nav-link" href="test-results.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Input Hasil
                        </a>
                        <a class="nav-link active" href="students.php">
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
                    <h2 class="fw-bold">Data Mahasiswa</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus me-2"></i>Tambah Mahasiswa
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download me-2"></i>Export Data
                        </button>
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
                                <select name="jenjang" class="form-select">
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
                                <select name="fakultas" class="form-select">
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
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="student-avatar bg-primary me-3">
                                                            <?= strtoupper(substr($student['name'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($student['name']) ?></strong><br>
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
                                                        <strong><?= htmlspecialchars($student['program_studi']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($student['jenjang']) ?> - <?= htmlspecialchars($student['fakultas']) ?>
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
                                                        <span class="badge <?= 
                                                            $student['course_status'] === 'completed' ? 'bg-success' : 
                                                            ($student['course_status'] === 'active' ? 'bg-primary' : 'bg-warning text-dark') 
                                                        ?>">
                                                            <?= strtoupper($student['course_status']) ?>
                                                        </span>
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
                                                                data-student='<?= json_encode($student) ?>'>
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editStudentModal"
                                                                data-student='<?= json_encode($student) ?>'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($student['is_active']): ?>
                                                            <a href="?action=deactivate&id=<?= $student['id'] ?>" 
                                                               class="btn btn-outline-warning confirm-action"
                                                               data-message="Nonaktifkan mahasiswa ini?">
                                                                <i class="bi bi-pause"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=activate&id=<?= $student['id'] ?>" 
                                                               class="btn btn-outline-success confirm-action"
                                                               data-message="Aktifkan mahasiswa ini?">
                                                                <i class="bi bi-play"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?action=delete&id=<?= $student['id'] ?>" 
                                                           class="btn btn-outline-danger confirm-action"
                                                           data-message="Hapus mahasiswa ini? Data tidak dapat dikembalikan!">
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
                                        <tr><td><strong>Program Studi:</strong></td><td>${student.program_studi}</td></tr>
                                        <tr><td><strong>Jenjang:</strong></td><td>${student.jenjang}</td></tr>
                                        <tr><td><strong>Fakultas:</strong></td><td>${student.fakultas}</td></tr>
                                        <tr><td><strong>Status Kursus:</strong></td><td>
                                            ${student.course_status ? 
                                                `<span class="badge bg-primary">${student.course_status.toUpperCase()}</span>` : 
                                                '<span class="text-muted">Belum Ikut</span>'
                                            }
                                        </td></tr>
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
                `;
                
                document.getElementById('studentDetailContent').innerHTML = content;
            });

            // Confirmation for actions
            $('.confirm-action').on('click', function(e) {
                e.preventDefault();
                const message = this.getAttribute('data-message') || 'Apakah Anda yakin?';
                if (confirm(message)) {
                    window.location.href = this.href;
                }
            });
        });
    </script>
</body>
</html>