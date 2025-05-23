<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_upabahasa');
define('DB_USER', 'root');
define('DB_PASS', '');

// System constants
define('ELPT_FEE', 100000);
define('COURSE_FEE', 750000);
define('MIN_PASSING_SCORE', 450);

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Log error instead of displaying it
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        header('Location: ../index.php');
        exit;
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('d F Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    return date('d F Y H:i', strtotime($datetime));
}

function generateBillingNumber() {
    return 'ELPT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $alertClass = '';
        
        switch($alert['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            default:
                $alertClass = 'alert-info';
        }
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($alert['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        unset($_SESSION['alert']);
    }
}

/**
 * Sanitize user input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Log activity
 */
function logActivity($activity_type, $description = '', $user_id = null) {
    global $pdo;
    
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $activity_type,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Get system setting
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
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Indonesian phone number
 */
function isValidPhone($phone) {
    $phone = preg_replace('/\s+/', '', $phone);
    return preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', $phone);
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if date is valid test date (Tuesday, Thursday, Saturday)
 */
function isValidTestDate($date) {
    $day_of_week = date('N', strtotime($date));
    return in_array($day_of_week, [2, 4, 6]); // Tuesday, Thursday, Saturday
}

/**
 * Calculate total score from individual scores
 */
function calculateTotalScore($listening, $structure, $reading) {
    return intval($listening) + intval($structure) + intval($reading);
}

/**
 * Check if ELPT score is passing
 */
function isPassingScore($total_score) {
    return $total_score >= MIN_PASSING_SCORE;
}

/**
 * Format currency to Indonesian Rupiah
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Get available test dates for next 30 days
 */
function getAvailableTestDates($days = 30) {
    $dates = [];
    $start_date = new DateTime('+1 day');
    $end_date = new DateTime("+{$days} days");
    
    while ($start_date <= $end_date) {
        if (isValidTestDate($start_date->format('Y-m-d'))) {
            $dates[] = [
                'date' => $start_date->format('Y-m-d'),
                'formatted' => $start_date->format('l, d F Y'),
                'day' => $start_date->format('l')
            ];
        }
        $start_date->modify('+1 day');
    }
    
    return $dates;
}

/**
 * Check registration quota for a specific date
 */
function checkRegistrationQuota($test_date) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM elpt_registrations 
            WHERE test_date = ? 
            AND payment_status IN ('pending', 'confirmed')
        ");
        $stmt->execute([$test_date]);
        $result = $stmt->fetch();
        
        $max_participants = getSystemSetting('max_participants_per_session', 30);
        return [
            'current' => $result['count'],
            'max' => $max_participants,
            'available' => $result['count'] < $max_participants,
            'remaining' => $max_participants - $result['count']
        ];
    } catch (PDOException $e) {
        error_log("Error checking registration quota: " . $e->getMessage());
        return ['current' => 0, 'max' => 30, 'available' => true, 'remaining' => 30];
    }
}
?>