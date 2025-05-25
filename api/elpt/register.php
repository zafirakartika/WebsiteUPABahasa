<?php
// api/elpt/register.php - API endpoint for ELPT registration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit;
}

// Only students can register for ELPT
if ($_SESSION['user_role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only students can register for ELPT.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$test_date = $input['test_date'] ?? '';
$purpose = $input['purpose'] ?? '';
$time_slot = $input['time_slot'] ?? '';

// Validation
$errors = [];

if (empty($test_date)) {
    $errors[] = 'Test date is required';
}

if (empty($purpose)) {
    $errors[] = 'Purpose is required';
}

// Validate test date
if (!empty($test_date)) {
    $selected_date = new DateTime($test_date);
    $tomorrow = new DateTime('+1 day');
    $max_date = new DateTime('+30 days');
    
    // Check if date is at least tomorrow
    if ($selected_date <= $tomorrow) {
        $errors[] = 'Test date must be at least H+1 from today';
    }
    
    // Check if date is within 30 days
    if ($selected_date > $max_date) {
        $errors[] = 'Test date must be within 30 days from today';
    }
    
    // Check if date is Tuesday, Thursday, or Saturday
    $day_of_week = $selected_date->format('N');
    if (!in_array($day_of_week, [2, 4, 6])) {
        $errors[] = 'Test is only available on Tuesday, Thursday, and Saturday';
    }
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check if user has pending registration
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM elpt_registrations 
        WHERE user_id = ? AND payment_status = 'pending'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $pending = $stmt->fetch();
    
    if ($pending['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'You already have a pending registration. Please complete payment first.'
        ]);
        exit;
    }
    
    // Check availability for the selected date
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM elpt_registrations 
        WHERE test_date = ? 
        AND payment_status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$test_date]);
    $registration_count = $stmt->fetch();
    
    $max_participants = getSystemSetting('max_participants_per_session', 30);
    
    if ($registration_count['count'] >= $max_participants) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Selected date is fully booked. Please choose another date.'
        ]);
        exit;
    }
    
    // Generate billing number
    $billing_number = generateBillingNumber();
    
    // Insert registration
    $stmt = $pdo->prepare("
        INSERT INTO elpt_registrations 
        (user_id, test_date, purpose, billing_number, payment_status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $test_date,
        $purpose,
        $billing_number
    ]);
    
    $registration_id = $pdo->lastInsertId();
    
    // Log activity
    logActivity('elpt_registration', "Registered for ELPT test on $test_date");
    
    // Get registration details
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.email 
        FROM elpt_registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch();
    
    // Send notification (optional - implement notification system)
    // sendNotification($user_id, 'ELPT Registration Successful', '...');
    
    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'ELPT registration successful',
        'data' => [
            'registration_id' => $registration_id,
            'billing_number' => $billing_number,
            'test_date' => $test_date,
            'purpose' => $purpose,
            'payment_amount' => ELPT_FEE,
            'payment_status' => 'pending',
            'slots_remaining' => $max_participants - $registration_count['count'] - 1
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("ELPT registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Registration failed. Please try again later.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}