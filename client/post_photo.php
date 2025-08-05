<?php
session_start();
require_once 'backend.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$photo_id = intval($input['photo_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

if ($photo_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid photo ID']);
    exit();
}

if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if (!check_rate_limit('post_photo', 10, 300)) {
    echo json_encode(['success' => false, 'message' => 'Too many posts. Please wait before posting again.']);
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id, file_path FROM user_photos WHERE id = ? AND user_id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, "ii", $photo_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$photo = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$photo) {
    echo json_encode(['success' => false, 'message' => 'Photo not found or access denied']);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT is_public FROM user_photos WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $photo_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $is_public);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($is_public) {
    echo json_encode(['success' => false, 'message' => 'Photo is already posted to main gallery']);
    exit();
}

// Marcar a foto como pública (postada na galeria principal) e registrar que já foi postada
$stmt = mysqli_prepare($conn, "UPDATE user_photos SET is_public = 1, was_posted = 1 WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $photo_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    
    // Log da atividade
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    error_log("Photo posted to gallery - User ID: $user_id, Photo ID: $photo_id, IP: $ip_address");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Photo posted successfully to main gallery!',
        'photo_id' => $photo_id
    ]);
} else {
    error_log("Failed to post photo to gallery: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Failed to post photo. Please try again.']);
}
?>