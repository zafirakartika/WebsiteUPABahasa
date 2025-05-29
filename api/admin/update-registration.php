<?php
// api/admin/update-registration.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$registration_id = $_POST['registration_id'] ?? '';
$test_date = $_POST['test_date'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$payment_status = $_POST['payment_status'] ?? '';

// Validation
$errors = [];

if (empty($registration_id) || !is_numeric($registration_id)) {
    $errors[] = 'Invalid registration ID';
}

if (empty($test_date)) {
    $errors[] = 'Test date is required';
}

if (empty($purpose)) {
    $errors[] = 'Purpose is required';
}

if (!in_array($payment_status, ['pending', 'confirmed', 'rejected'])) {
    $errors[] = 'Invalid payment status';
}

// Validate test date
if (!empty($test_date)) {
    $selected_date = new DateTime($test_date);
    $day_of_week = $selected_date->format('N');
    
    if (!in_array($day_of_week, [2, 4, 6])) {
        $errors[] = 'Test date must be Tuesday, Thursday, or Saturday';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if registration exists
    $stmt = $pdo->prepare("SELECT * FROM elpt_registrations WHERE id = ?");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch();
    
    if (!$registration) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Registration not found']);
        exit;
    }
    
    // Check quota for new test date (if date is being changed)
    if ($test_date !== $registration['test_date']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM elpt_registrations 
            WHERE test_date = ? 
            AND payment_status IN ('pending', 'confirmed')
            AND id != ?
        ");
        $stmt->execute([$test_date, $registration_id]);
        $count = $stmt->fetch()['count'];
        
        $max_participants = getSystemSetting('max_participants_per_session', 30);
        
        if ($count >= $max_participants) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Selected date is fully booked']);
            exit;
        }
    }
    
    // Update registration
    $stmt = $pdo->prepare("
        UPDATE elpt_registrations 
        SET test_date = ?, purpose = ?, payment_status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$test_date, $purpose, $payment_status, $registration_id]);
    
    // Log activity
    logActivity('registration_update', "Updated registration ID: $registration_id");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Registration updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Update registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>