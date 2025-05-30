<?php
// api/elpt/upload-payment-proof.php
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
    // Get user's confirmed registration
    $stmt = $pdo->prepare("
        SELECT * FROM elpt_registrations 
        WHERE user_id = ? AND payment_status = 'confirmed' 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $registration = $stmt->fetch();
    
    if (!$registration) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No confirmed registration found']);
        exit;
    }
    
    // Check if deadline has passed
    if ($registration['payment_proof_deadline'] && new DateTime() > new DateTime($registration['payment_proof_deadline'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment proof upload deadline has passed']);
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
    $upload_dir = '../uploads/payment_proofs/' . date('Y') . '/' . date('m') . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_name = 'payment_' . $registration['id'] . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;
    $relative_path = 'uploads/payment_proofs/' . date('Y') . '/' . date('m') . '/' . $file_name;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Save to database
    $pdo->beginTransaction();
    
    // Insert payment proof record
    $stmt = $pdo->prepare("
        INSERT INTO payment_proofs 
        (registration_id, file_name, file_path, file_size, file_type) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $registration['id'],
        $file_name,
        $relative_path,
        $file['size'],
        $file['type']
    ]);
    
    // Update registration status
    $stmt = $pdo->prepare("
        UPDATE elpt_registrations 
        SET payment_status = 'payment_uploaded', 
            payment_proof_file = ?,
            payment_proof_uploaded_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$relative_path, $registration['id']]);
    
    $pdo->commit();
    
    // Log activity
    logActivity('payment_proof_upload', "Uploaded payment proof for registration ID: {$registration['id']}");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment proof uploaded successfully. Please wait for admin verification.',
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
    
    error_log("Payment proof upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
}
?>