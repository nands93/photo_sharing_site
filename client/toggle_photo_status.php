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

if (!validate_photo_id($photo_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid photo ID']);
    exit();
}

if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verifica se a foto pertence ao usuário
$stmt = mysqli_prepare($conn, "SELECT is_public FROM user_photos WHERE id = ? AND user_id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, "ii", $photo_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $is_public);
if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'Photo not found or access denied']);
    exit();
}
mysqli_stmt_close($stmt);

// Alterna o status
$new_status = $is_public ? 0 : 1;
$stmt = mysqli_prepare($conn, "UPDATE user_photos SET is_public = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ii", $new_status, $photo_id);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    security_log('photo_status_toggled', [
        'photo_id' => $photo_id,
        'new_status' => $new_status
    ]);
    echo json_encode(['success' => true, 'new_status' => $new_status]);
} else {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
}
?>