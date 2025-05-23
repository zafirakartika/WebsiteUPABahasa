<?php
// includes/header.php - Main header for UPA Bahasa UPNVJ
require_once __DIR__ . '/../config/database.php';

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];

// Check if user is logged in and get user data
$user = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
}

// Get system settings for dynamic content
$app_name = getSystemSetting('app_name', 'UPA Bahasa UPNVJ');
$maintenance_mode = getSystemSetting('maintenance_mode', '0');

// Redirect if maintenance mode is on (except for admin)
if ($maintenance_mode == '1' && (!isLoggedIn() || $_SESSION['user_role'] !== 'admin')) {
    include_once __DIR__ . '/maintenance.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Unit Pelayanan Akademik Bahasa UPNVJ - Layanan ELPT dan Kursus Persiapan Bahasa Inggris">
    <meta name="keywords" content="ELPT, UPNVJ, Bahasa Inggris, Test, Kursus, UPA Bahasa">
    <meta name="author" content="UPA Bahasa UPNVJ">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= getBaseURL() ?>/assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= getBaseURL() ?>/assets/css/custom.css" rel="stylesheet">
    
    <!-- Chart.js for dashboard -->
    <?php if (strpos($current_path, 'dashboard') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?><?= htmlspecialchars($app_name) ?></title>
</head>
<body class="<?= isset($body_class) ? htmlspecialchars($body_class) : 'bg-light' ?>">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="<?= getHomeURL() ?>">
                <i class="bi bi-mortarboard me-2" style="font-size: 1.5rem;"></i>
                <span><?= htmlspecialchars($app_name) ?></span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <!-- Logged In Navigation -->
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <!-- Admin Navigation -->
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'admin/dashboard') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/admin/dashboard.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'registrations') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/admin/registrations.php">
                                    <i class="bi bi-calendar-event me-1"></i>Pendaftaran
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'payments') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/admin/payments.php">
                                    <i class="bi bi-credit-card me-1"></i>Pembayaran
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'test-results') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/admin/test-results.php">
                                    <i class="bi bi-file-earmark-text me-1"></i>Hasil Tes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'students') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/admin/students.php">
                                    <i class="bi bi-people me-1"></i>Mahasiswa
                                </a>
                            </li>
                        </ul>
                    <?php else: ?>
                        <!-- Student Navigation -->
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'student/dashboard') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/student/dashboard.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'elpt-registration') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/student/elpt-registration.php">
                                    <i class="bi bi-calendar-plus me-1"></i>Daftar ELPT
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'test-results') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/student/test-results.php">
                                    <i class="bi bi-file-earmark-text me-1"></i>Hasil Tes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'course') !== false ? 'active' : '' ?>" 
                                   href="<?= getBaseURL() ?>/student/course.php">
                                    <i class="bi bi-book me-1"></i>Kursus
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- User Menu -->
                    <div class="navbar-nav">
                        <!-- Notifications (if any) -->
                        <div class="nav-item dropdown me-2">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-bell" style="font-size: 1.2rem;"></i>
                                <?php 
                                $notification_count = getUnreadNotificationCount($_SESSION['user_id']);
                                if ($notification_count > 0): 
                                ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notification_count > 9 ? '9+' : $notification_count ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                <li><h6 class="dropdown-header">Notifikasi</h6></li>
                                <?php if ($notification_count > 0): ?>
                                    <?php foreach (getRecentNotifications($_SESSION['user_id'], 5) as $notification): ?>
                                    <li>
                                        <a class="dropdown-item small" href="<?= $notification['link'] ?? '#' ?>">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-<?= $notification['icon'] ?? 'info-circle' ?> text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <div class="fw-semibold"><?= htmlspecialchars($notification['title']) ?></div>
                                                    <div class="text-muted"><?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...</div>
                                                    <small class="text-muted"><?= timeAgo($notification['created_at']) ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center small" href="<?= getBaseURL() ?>/notifications.php">Lihat Semua</a></li>
                                <?php else: ?>
                                    <li><span class="dropdown-item text-muted small">Tidak ada notifikasi baru</span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <!-- User Profile Dropdown -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center me-2" 
                                     style="width: 32px; height: 32px; font-size: 0.8rem; color: white;">
                                    <?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>
                                </div>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <div class="dropdown-item-text">
                                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($_SESSION['user_email']) ?><br>
                                            <?= ucfirst($_SESSION['user_role']) ?>
                                            <?php if ($_SESSION['user_role'] === 'student' && isset($user['nim'])): ?>
                                                | NIM: <?= htmlspecialchars($user['nim']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?= getBaseURL() ?>/profile.php">
                                        <i class="bi bi-person me-2"></i>Profil Saya
                                    </a>
                                </li>
                                <?php if ($_SESSION['user_role'] === 'student'): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= getBaseURL() ?>/student/settings.php">
                                        <i class="bi bi-gear me-2"></i>Pengaturan
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?= getBaseURL() ?>/help.php">
                                        <i class="bi bi-question-circle me-2"></i>Bantuan
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= getBaseURL() ?>/logout.php" 
                                       onclick="return confirm('Yakin ingin logout?')">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest Navigation -->
                    <div class="navbar-nav ms-auto">
                        <a href="<?= getBaseURL() ?>/login.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                        <a href="<?= getBaseURL() ?>/register.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>Daftar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb (for logged in users) -->
    <?php if (isLoggedIn() && !in_array($current_page, ['index', 'dashboard'])): ?>
    <nav aria-label="breadcrumb" class="bg-light border-bottom">
        <div class="container">
            <ol class="breadcrumb py-2 mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= getHomeURL() ?>" class="text-decoration-none">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
                <?php 
                $breadcrumbs = generateBreadcrumbs($current_path);
                foreach ($breadcrumbs as $index => $breadcrumb): 
                    if ($index === count($breadcrumbs) - 1): 
                ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= htmlspecialchars($breadcrumb['title']) ?>
                </li>
                <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($breadcrumb['url']) ?>" class="text-decoration-none">
                        <?= htmlspecialchars($breadcrumb['title']) ?>
                    </a>
                </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Alert Messages -->
    <?php displayAlert(); ?>

    <!-- Main Content Container -->
    <main class="<?= isset($main_class) ? htmlspecialchars($main_class) : 'container-fluid' ?>">

<?php
// Helper Functions for Header

/**
 * Get base URL for the application
 */
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = str_replace(basename($script), '', $script);
    return $protocol . $host . rtrim($path, '/');
}

/**
 * Get home URL based on user role
 */
function getHomeURL() {
    if (isLoggedIn()) {
        if ($_SESSION['user_role'] === 'admin') {
            return getBaseURL() . '/admin/dashboard.php';
        } else {
            return getBaseURL() . '/student/dashboard.php';
        }
    }
    return getBaseURL() . '/index.php';
}

/**
 * Get system setting value
 */
function getSystemSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent notifications for user
 */
function getRecentNotifications($user_id, $limit = 5) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Generate breadcrumbs based on current path
 */
function generateBreadcrumbs($path) {
    $breadcrumbs = [];
    $path_parts = explode('/', trim($path, '/'));
    
    // Define page titles
    $page_titles = [
        'student' => 'Mahasiswa',
        'admin' => 'Admin',
        'dashboard' => 'Dashboard',
        'elpt-registration' => 'Daftar ELPT',
        'test-results' => 'Hasil Tes',
        'course' => 'Kursus',
        'registrations' => 'Pendaftaran',
        'payments' => 'Pembayaran',
        'students' => 'Data Mahasiswa',
        'profile' => 'Profil',
        'settings' => 'Pengaturan'
    ];
    
    $current_path = '';
    foreach ($path_parts as $part) {
        if (empty($part)) continue;
        
        $current_path .= '/' . $part;
        $title = $page_titles[$part] ?? ucfirst(str_replace(['-', '_'], ' ', $part));
        
        $breadcrumbs[] = [
            'title' => $title,
            'url' => getBaseURL() . $current_path . '.php'
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Time ago helper function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari lalu';
    
    return date('d M Y', strtotime($datetime));
}
?>