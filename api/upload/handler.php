<?php
// api/upload/handler.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Configuration
$config = [
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_types' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'document' => ['pdf', 'doc', 'docx'],
        'all' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
    ],
    'upload_paths' => [
        'payment_proof' => '../../uploads/payment-proofs/',
        'profile_picture' => '../../uploads/profile-pictures/',
        'certificate' => '../../uploads/certificates/',
        'course_material' => '../../uploads/course-materials/',
        'other' => '../../uploads/other/'
    ]
];

try {
    // Get upload parameters
    $upload_purpose = $_POST['purpose'] ?? 'other';
    $reference_type = $_POST['reference_type'] ?? null;
    $reference_id = $_POST['reference_id'] ?? null;
    
    // Validate upload purpose
    if (!array_key_exists($upload_purpose, $config['upload_paths'])) {
        throw new Exception('Invalid upload purpose');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $file = $_FILES['file'];
    
    // Validate file size
    if ($file['size'] > $config['max_file_size']) {
        throw new Exception('File size exceeds maximum limit (10MB)');
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_extensions = $config['allowed_types']['all'];
    if ($upload_purpose === 'payment_proof') {
        $allowed_extensions = array_merge($config['allowed_types']['image'], $config['allowed_types']['document']);
    } elseif ($upload_purpose === 'profile_picture') {
        $allowed_extensions = $config['allowed_types']['image'];
    }
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('File type not allowed');
    }
    
    // Determine file type category
    $file_type = 'other';
    if (in_array($file_extension, $config['allowed_types']['image'])) {
        $file_type = 'image';
    } elseif (in_array($file_extension, $config['allowed_types']['document'])) {
        $file_type = 'document';
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $config['upload_paths'][$upload_purpose];
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $full_path = $upload_path . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $full_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Additional processing for images
    if ($file_type === 'image') {
        // Resize image if needed
        resizeImage($full_path, $upload_purpose);
        
        // Create thumbnail for profile pictures
        if ($upload_purpose === 'profile_picture') {
            createThumbnail($full_path, $upload_path . 'thumb_' . $filename);
        }
    }
    
    // Save file information to database
    $stmt = $pdo->prepare("
        INSERT INTO file_uploads 
        (user_id, original_name, file_name, file_path, file_size, mime_type, file_type, upload_purpose, reference_type, reference_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $file['name'],
        $filename,
        $full_path,
        $file['size'],
        $file['type'],
        $file_type,
        $upload_purpose,
        $reference_type,
        $reference_id
    ]);
    
    $file_id = $pdo->lastInsertId();
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_id' => $file_id,
        'filename' => $filename,
        'original_name' => $file['name'],
        'file_size' => $file['size'],
        'file_type' => $file_type,
        'url' => '/uploads/' . str_replace('../../uploads/', '', $upload_path) . $filename
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Resize image based on upload purpose
 */
function resizeImage($file_path, $purpose) {
    $max_dimensions = [
        'profile_picture' => ['width' => 300, 'height' => 300],
        'payment_proof' => ['width' => 1200, 'height' => 1200],
        'other' => ['width' => 1200, 'height' => 1200]
    ];
    
    $max_width = $max_dimensions[$purpose]['width'] ?? 1200;
    $max_height = $max_dimensions[$purpose]['height'] ?? 1200;
    
    // Get image info
    $image_info = getimagesize($file_path);
    if (!$image_info) return;
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Check if resize is needed
    if ($width <= $max_width && $height <= $max_height) return;
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file_path);
            break;
        default:
            return;
    }
    
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefill($destination, 0, 0, $transparent);
    }
    
    // Resize
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $file_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $file_path, 6);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $file_path);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($destination);
}

/**
 * Create thumbnail for images
 */
function createThumbnail($source_path, $thumbnail_path, $size = 150) {
    $image_info = getimagesize($source_path);
    if (!$image_info) return false;
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Create source image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    // Calculate crop dimensions (square crop from center)
    $min_dimension = min($width, $height);
    $crop_x = ($width - $min_dimension) / 2;
    $crop_y = ($height - $min_dimension) / 2;
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    // Crop and resize
    imagecopyresampled($thumbnail, $source, 0, 0, $crop_x, $crop_y, $size, $size, $min_dimension, $min_dimension);
    
    // Save thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $thumbnail_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $thumbnail_path, 6);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnail, $thumbnail_path);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return true;
}
?>