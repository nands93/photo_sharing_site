<?php
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

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['image'])) {
    error_log("No image data received");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image data']);
    exit();
}

try {
    $image_data = $data['image'];
    error_log("Image data length: " . strlen($image_data));
    
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image_data)) {
        throw new Exception('Invalid image format');
    }
    
    $image_data = substr($image_data, strpos($image_data, ',') + 1);
    $image_binary = base64_decode($image_data);
    if ($image_binary === false) {
        throw new Exception('Failed to decode image');
    }
    
    $temp_file = tempnam(sys_get_temp_dir(), 'upload_validate_');
    file_put_contents($temp_file, $image_binary);
    
    $image_info = getimagesize($temp_file);
    if ($image_info === false || !in_array($image_info['mime'], ['image/png', 'image/jpeg'])) {
        unlink($temp_file);
        throw new Exception('Invalid or corrupted image file');
    }
    
    $img_clean = imagecreatefromstring($image_binary);
    if ($img_clean === false) {
        unlink($temp_file);
        throw new Exception('Could not process image - file may be corrupted');
    }
    
    $width = imagesx($img_clean);
    $height = imagesy($img_clean);
    if ($width > 2048 || $height > 2048 || $width < 1 || $height < 1) {
        imagedestroy($img_clean);
        unlink($temp_file);
        throw new Exception('Image dimensions must be between 1x1 and 2048x2048 pixels');
    }
    
    if (strlen($image_binary) > 5 * 1024 * 1024) {
        imagedestroy($img_clean);
        unlink($temp_file);
        throw new Exception('File too large (max 5MB)');
    }
    
    unlink($temp_file);
    
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
    
    $safe_username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    if (empty($safe_username)) {
        $safe_username = 'user';
    }
    
    $timestamp = time();
    $random_hash = bin2hex(random_bytes(16)); // Aumentar entropia
    $filename = "{$safe_username}_{$user_id}_{$timestamp}_{$random_hash}.png";
    
    if (strlen($filename) > 255) {
        throw new Exception('Generated filename too long');
    }
    
    $upload_dir = realpath(__DIR__ . "/uploads/");
    if (!$upload_dir) {
        throw new Exception('Upload directory not found');
    }
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create uploads directory');
        }
        $upload_dir = realpath($upload_dir);
    }
    
    $file_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;
    
    $real_file_path = realpath(dirname($file_path)) . DIRECTORY_SEPARATOR . basename($filename);
    if (strpos($real_file_path, $upload_dir) !== 0) {
        throw new Exception('Invalid file path - security violation');
    }
    
    error_log("Attempting to save to: " . $file_path);
    
    if (!imagepng($img_clean, $file_path, 9)) {
        imagedestroy($img_clean);
        throw new Exception('Failed to save image to disk');
    }
    
    imagedestroy($img_clean);
    
    error_log("Image saved successfully");
    
    if (!file_exists($file_path)) {
        throw new Exception('File was not saved properly');
    }
    
    $final_image_info = getimagesize($file_path);
    if ($final_image_info === false) {
        unlink($file_path);
        throw new Exception('Saved file is not a valid image');
    }
    
    $relative_path = "uploads/" . $filename;
    
    $final_width = $final_image_info[0] ?? 0;
    $final_height = $final_image_info[1] ?? 0;
    $file_size = filesize($file_path);
    
    $ip_address = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';
    $user_agent = substr(htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES, 'UTF-8'), 0, 255);
    $upload_method = 'webcam';
    
    error_log("Image info - Width: $final_width, Height: $final_height, Size: $file_size");
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_photos 
        (user_id, username, filename, file_path, file_size, mime_type, width, height, ip_address, user_agent, upload_method, is_active) 
        VALUES (?, ?, ?, ?, ?, 'image/png', ?, ?, ?, ?, ?, 1)
    ");
    
    if (!$stmt) {
        // Se falhar no banco, remover arquivo
        unlink($file_path);
        throw new Exception('Failed to prepare database statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "isssiiisss", 
        $user_id, $username, $filename, $relative_path, $file_size, 
        $final_width, $final_height, $ip_address, $user_agent, $upload_method
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $photo_id = mysqli_insert_id($conn);
        error_log("Database insert successful, photo_id: $photo_id");
        
        // Log de auditoria de segurança
        security_log('photo_upload', [
            'photo_id' => $photo_id,
            'filename' => $filename,
            'file_size' => $file_size,
            'dimensions' => "{$final_width}x{$final_height}",
            'upload_method' => $upload_method
        ]);
        
        echo json_encode([
            'success' => true, 
            'filename' => $filename,
            'photo_id' => $photo_id,
            'message' => 'Photo uploaded successfully'
        ]);
    } else {
        // Se falhar no banco, remover arquivo
        unlink($file_path);
        throw new Exception('Failed to save to database: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error in save_image.php: " . $e->getMessage());
    
    // Log de tentativa de upload suspeita
    security_log('photo_upload_failed', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'data_length' => isset($data['image']) ? strlen($data['image']) : 0
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>