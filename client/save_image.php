<?php
$upload_dir = __DIR__ . "/uploads";
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        error_log("Failed to create upload directory: " . $upload_dir);
        error_log("Current user: " . get_current_user());
        error_log("Directory permissions: " . (is_writable(__DIR__) ? 'writable' : 'not writable'));
        echo json_encode(['success' => false, 'error' => 'Upload directory not found and could not be created.']);
        exit();
    }
}
session_start();
require_once 'backend.php';

header('Content-Type: application/json; charset=utf-8');

error_log("Save image request started");

if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $stmt_user = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user_info = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);

    if (!$user_info) {
        throw new Exception('User not found');
    }
    $username = $user_info['username'];

    $image_binary = null;
    $upload_method = '';
    $original_mime = '';

    // Case 1: Handle file upload from FormData
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing uploaded file...");
        $upload_method = 'upload';
        $file = $_FILES['image_file'];
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('File too large (max 5MB)');
        }

        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false || !in_array($image_info['mime'], ['image/png', 'image/jpeg', 'image/gif'])) {
            throw new Exception('Invalid or corrupted image file. Only PNG, JPG, and GIF are allowed.');
        }
        $original_mime = $image_info['mime'];
        $image_binary = file_get_contents($file['tmp_name']);

    // Case 2: Handle base64 image data from webcam
    } else {
        error_log("Processing base64 image data...");
        $upload_method = 'webcam';
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['image'])) {
            error_log("No image data received");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No image data']);
            exit();
        }
        $image_data = $data['image'];
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image_data, $matches)) {
            throw new Exception('Invalid image format');
        }
        $original_mime = 'image/' . $matches[1];
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
        $image_binary = base64_decode($image_data);
    }

    if ($image_binary === false) {
        throw new Exception('Failed to read or decode image');
    }

    // --- Common Image Processing and Saving Logic ---

    $img_clean = imagecreatefromstring($image_binary);
    if ($img_clean === false) {
        throw new Exception('Could not process image - file may be corrupted');
    }
    
    $width = imagesx($img_clean);
    $height = imagesy($img_clean);
    if ($width > 2048 || $height > 2048 || $width < 1 || $height < 1) {
        imagedestroy($img_clean);
        throw new Exception('Image dimensions must be between 1x1 and 2048x2048 pixels');
    }
    
    $safe_username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    if (empty($safe_username)) {
        $safe_username = 'user';
    }
    
    $timestamp = time();
    $random_hash = bin2hex(random_bytes(16));
    $filename = "{$safe_username}_{$user_id}_{$timestamp}_{$random_hash}.png"; // Always save as PNG for consistency
    
    $file_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;
    
    error_log("Attempting to save to: " . $file_path);
    
    if (!imagepng($img_clean, $file_path, 9)) { // Save with max compression
        imagedestroy($img_clean);
        throw new Exception('Failed to save image to disk');
    }
    
    imagedestroy($img_clean);
    
    error_log("Image saved successfully");
    
    $relative_path = "uploads/" . $filename;
    $file_size = filesize($file_path);
    
    $ip_address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    $user_agent = substr(htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES, 'UTF-8'), 0, 255);
    
    error_log("Image info - Width: $width, Height: $height, Size: $file_size, Method: $upload_method");
    
    $is_public = (isset($_POST['make_public']) && $_POST['make_public'] == '1') ? 1 : 0;

    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_photos 
        (user_id, username, filename, file_path, file_size, mime_type, width, height, ip_address, user_agent, upload_method, is_active, was_posted, is_public) 
        VALUES (?, ?, ?, ?, ?, 'image/png', ?, ?, ?, ?, ?, 1, 1, ?)
    ");
    
    if (!$stmt) {
        unlink($file_path);
        throw new Exception('Failed to prepare database statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "isssiiisssi", 
        $user_id, $username, $filename, $relative_path, $file_size, 
        $width, $height, $ip_address, $user_agent, $upload_method,
        $is_public
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $photo_id = mysqli_insert_id($conn);
        error_log("Database insert successful, photo_id: $photo_id");
        
        security_log('photo_upload', [
            'photo_id' => $photo_id,
            'filename' => $filename,
            'file_size' => $file_size,
            'dimensions' => "{$width}x{$height}",
            'upload_method' => $upload_method
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Photo uploaded successfully',
            'photo' => [
                'id' => $photo_id,
                'file_path' => $relative_path,
                'filename' => $filename,
                'is_public' => $is_public,
                'was_posted' => 1
            ]
        ]);
    } else {
        unlink($file_path);
        throw new Exception('Failed to save to database: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error in save_image.php: " . $e->getMessage());
    
    security_log('photo_upload_failed', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'upload_method' => $upload_method ?? 'unknown'
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>