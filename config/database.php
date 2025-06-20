<?php

date_default_timezone_set('Asia/Jakarta');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'upa_bahasa');
define('DB_USER', 'root');
define('DB_PASS', '');

// System constants
define('ELPT_FEE', 75000);
define('COURSE_FEE', 850000);
define('MIN_PASSING_SCORE', 450);

// Error reporting for debugging 
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
 * Get slot capacity for a specific date and time slot
 */
function getSlotCapacity($test_date, $time_slot) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as current, 30 as max 
            FROM elpt_registrations 
            WHERE test_date = ? AND time_slot = ? AND status = 'confirmed'
        ");
        $stmt->execute([$test_date, $time_slot]);
        $result = $stmt->fetch();
        
        return [
            'current' => $result['current'],
            'max' => $result['max'],
            'available' => $result['current'] < $result['max'],
            'remaining' => $result['max'] - $result['current']
        ];
    } catch (PDOException $e) {
        error_log("Error getting slot capacity: " . $e->getMessage());
        return ['current' => 0, 'max' => 30, 'available' => true, 'remaining' => 30];
    }
}

/**
 * Format time slot display based on day and slot
 */
function formatTimeSlot($time_slot, $test_date) {
    if (!$time_slot) return 'N/A';
    
    $selected_date = new DateTime($test_date);
    $day_of_week = $selected_date->format('N');
    
    switch($time_slot) {
        case 'pagi':
            return ($day_of_week == 6) ? 'Pagi (07:00-09:30)' : 'Pagi (09:30-12:00)';
        case 'siang':
            return ($day_of_week == 6) ? 'Siang (09:30-12:00)' : 'Siang (13:00-15:30)';
        case 'sore':
            return 'Sore (13:00-15:30)';
        default:
            return ucfirst($time_slot);
    }
}

/**
 * Get available time slots for a specific date
 */
function getAvailableTimeSlots($test_date) {
    $selected_date = new DateTime($test_date);
    $day_of_week = $selected_date->format('N');
    
    if ($day_of_week == 6) { // Saturday
        return [
            'pagi' => 'Pagi (07:00-09:30)',
            'siang' => 'Siang (09:30-12:00)',
            'sore' => 'Sore (13:00-15:30)'
        ];
    } else { // Tuesday, Thursday
        return [
            'pagi' => 'Pagi (09:30-12:00)',
            'siang' => 'Siang (13:00-15:30)'
        ];
    }
}

/**
 * Validate time slot for specific date
 */
function isValidTimeSlot($time_slot, $test_date) {
    $available_slots = getAvailableTimeSlots($test_date);
    return array_key_exists($time_slot, $available_slots);
}

/**
 * Function to validate NIM with SIAKAD (simulation)
 */
function validateNimWithSiakad($nim) {
    // Simulate API call delay
    usleep(100000); // 0.1 second delay
    
    // Validate based on patterns
    $valid_patterns = [
        '24' => true, // 2024 batch
        '23' => true, // 2023 batch
        '22' => true, // 2022 batch
        '21' => true, // 2021 batch
    ];
    
    $year_prefix = substr($nim, 0, 2);
    
    if (!isset($valid_patterns[$year_prefix])) {
        return [
            'valid' => false,
            'message' => 'NIM tidak terdaftar di SIAKAD UPNVJ'
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'NIM tervalidasi dengan SIAKAD UPNVJ'
    ];
}

/**
 * Function to get SIAKAD data (simulation)
 */
function getSiakadData($nim) {
    // Simulate different programs based on NIM pattern
    $programs = [
        '221050' => [
            'program' => 'Sistem Informasi', 
            'level' => 'D3', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
        '221051' => [
            'program' => 'Hubungan Internasional', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Sosial dan Ilmu Politik'
        ],
        '221052' => [
            'program' => 'Manajemen', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ekonomi dan Bisnis'
        ],
        '221053' => [
            'program' => 'Teknik Informatika', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Teknik'
        ],
        '211050' => [
            'program' => 'Sistem Informasi', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
        '231050' => [
            'program' => 'Sistem Informasi', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Ilmu Komputer'
        ],
        '231051' => [
            'program' => 'Teknik Informatika', 
            'level' => 'S1', 
            'faculty' => 'Fakultas Teknik'
        ],
    ];
    
    $nim_prefix = substr($nim, 0, 6);
    
    // Default to first program if pattern not found
    return $programs[$nim_prefix] ?? $programs['221050'];
}

?>