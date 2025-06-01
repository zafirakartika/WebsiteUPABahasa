<?php
// api/course/upload-payment-proof.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in as student
if (!isLoggedIn() || $_SESSION['user_role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get user's course registration
    $stmt = $pdo->prepare("
        SELECT * FROM courses 
        WHERE user_id = ? AND status = 'pending' 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $course = $stmt->fetch();
    
    if (!$course) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No pending course registration found']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['payment_proof'];
    
    // Validate file
    $max_size = getSystemSetting('max_payment_file_size', 5242880); // 5MB
    $allowed_types = explode(',', getSystemSetting('allowed_payment_file_types', 'jpg,jpeg,png,pdf'));
    
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds maximum limit (5MB)']);
        exit;
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed']);
        exit;
    }
    
    // Create upload directory
    $upload_dir = '../uploads/course_payments/' . date('Y') . '/' . date('m') . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_name = 'course_payment_' . $course['id'] . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;
    $relative_path = 'uploads/course_payments/' . date('Y') . '/' . date('m') . '/' . $file_name;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Save to database
    $pdo->beginTransaction();
    
    // Update course with payment proof
    $stmt = $pdo->prepare("
        UPDATE courses 
        SET payment_proof_file = ?, 
            payment_proof_uploaded_at = NOW(),
            status = 'payment_uploaded'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$relative_path, $course['id'], $_SESSION['user_id']]);
    
    $pdo->commit();
    
    // Log activity
    logActivity('course_payment_upload', "Uploaded payment proof for course ID: {$course['id']}");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Course payment proof uploaded successfully. Please wait for admin verification.',
        'data' => [
            'file_name' => $file_name,
            'uploaded_at' => date('Y-m-d H:i:s'),
            'status' => 'payment_uploaded'
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Delete uploaded file if exists
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    error_log("Course payment proof upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
}
?>