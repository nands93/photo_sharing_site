<?php
session_start();
require_once 'backend.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$photo_id = intval($input['photo_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if (!validate_photo_id($photo_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid photo ID']);
    exit();
}

if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

if (!check_rate_limit('like', 20, 300)) {
    echo json_encode(['success' => false, 'error' => 'Too many like attempts. Please wait.']);
    exit();
}

// Verificar se a foto existe e está pública
$stmt = mysqli_prepare($conn, "SELECT id FROM user_photos WHERE id = ? AND is_public = 1 AND is_active = 1");
mysqli_stmt_bind_param($stmt, "i", $photo_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'Photo not found']);
    exit();
}
mysqli_stmt_close($stmt);

// Verificar se o usuário já curtiu esta foto
$stmt = mysqli_prepare($conn, "SELECT id FROM likes WHERE photo_id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $photo_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$already_liked = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if ($already_liked) {
    // Remover like
    $stmt = mysqli_prepare($conn, "DELETE FROM likes WHERE photo_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $photo_id, $user_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($success) {
        security_log('like_removed', ['photo_id' => $photo_id]);
        $action = 'removed';
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove like']);
        exit();
    }
} else {
    // Adicionar like
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO likes (photo_id, user_id, username, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iiss", $photo_id, $user_id, $username, $ip_address);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($success) {
        security_log('like_added', ['photo_id' => $photo_id]);
        $action = 'added';
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add like']);
        exit();
    }
}

// Buscar novo total de likes
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as like_count FROM likes WHERE photo_id = ?");
mysqli_stmt_bind_param($stmt, "i", $photo_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $like_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'action' => $action,
    'like_count' => $like_count,
    'liked' => !$already_liked
]);
?>