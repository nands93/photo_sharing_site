<?php
session_start();
require_once 'backend.php';

// Log de debug
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
    
    // Validar formato
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image_data)) {
        throw new Exception('Invalid image format');
    }
    
    // Extrair dados base64
    $image_data = substr($image_data, strpos($image_data, ',') + 1);
    $image_binary = base64_decode($image_data);
    
    if ($image_binary === false) {
        throw new Exception('Failed to decode image');
    }
    
    error_log("Image decoded successfully");
    
    // Buscar informações do usuário
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
    
    // Gerar nome único e seguro
    $timestamp = time();
    $random_hash = bin2hex(random_bytes(8));
    $filename = "{$username}_{$user_id}_{$timestamp}_{$random_hash}.png";
    
    // Usar caminho absoluto para uploads
    $upload_dir = __DIR__ . "/uploads/";
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create uploads directory');
        }
        chmod($upload_dir, 0777);
    }
    
    $file_path = $upload_dir . $filename;
    error_log("Attempting to save to: " . $file_path);
    
    // Salvar arquivo
    $bytes_written = file_put_contents($file_path, $image_binary);
    if ($bytes_written === false) {
        throw new Exception('Failed to save file to disk');
    }
    
    error_log("File saved successfully, bytes written: " . $bytes_written);
    
    // Para o banco, usar caminho relativo
    $relative_path = "uploads/" . $filename;
    
    // Obter dimensões da imagem
    $image_info = getimagesize($file_path);
    $width = $image_info[0] ?? 0;
    $height = $image_info[1] ?? 0;
    $file_size = filesize($file_path);
    
    // Capturar informações adicionais
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $upload_method = 'webcam'; // Pode ser expandido para 'upload', 'webcam', etc.
    
    error_log("Image info - Width: $width, Height: $height, Size: $file_size");
    
    // Salvar no banco de dados com informações completas
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_photos 
        (user_id, username, filename, file_path, file_size, mime_type, width, height, ip_address, user_agent, upload_method) 
        VALUES (?, ?, ?, ?, ?, 'image/png', ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "isssiiisss", 
        $user_id, $username, $filename, $relative_path, $file_size, 
        $width, $height, $ip_address, $user_agent, $upload_method
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $photo_id = mysqli_insert_id($conn);
        error_log("Database insert successful, photo_id: $photo_id");
        
        // Log da atividade para auditoria
        error_log("Photo uploaded - User: $username (ID: $user_id), File: $filename, Size: $file_size bytes, IP: $ip_address");
        
        echo json_encode([
            'success' => true, 
            'filename' => $filename,
            'photo_id' => $photo_id,
            'message' => 'Photo uploaded successfully'
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error in save_image.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>