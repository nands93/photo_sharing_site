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

if (!check_rate_limit('delete_photo', 10, 300)) {
    echo json_encode(['success' => false, 'error' => 'Too many delete attempts. Please wait.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verificar se a foto pertence ao usuário
$stmt = mysqli_prepare($conn, "SELECT id, file_path, filename FROM user_photos WHERE id = ? AND user_id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, "ii", $photo_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$photo = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$photo) {
    echo json_encode(['success' => false, 'error' => 'Photo not found or access denied']);
    exit();
}

// Iniciar transação para garantir consistência
mysqli_begin_transaction($conn);

try {
    // Marcar foto como inativa (soft delete)
    $stmt = mysqli_prepare($conn, "UPDATE user_photos SET is_active = 0, is_public = 0 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $photo_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to mark photo as deleted');
    }
    mysqli_stmt_close($stmt);
    
    // Deletar comentários associados (soft delete)
    $stmt = mysqli_prepare($conn, "UPDATE comments SET is_active = 0 WHERE photo_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $photo_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Deletar likes associados
    $stmt = mysqli_prepare($conn, "DELETE FROM likes WHERE photo_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $photo_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Commit da transação
    mysqli_commit($conn);
    
    // Tentar deletar arquivo físico (não crítico se falhar)
    $file_path = __DIR__ . '/' . $photo['file_path'];
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            error_log("Warning: Could not delete physical file: " . $file_path);
        }
    }
    
    // Log de segurança
    security_log('photo_deleted', [
        'photo_id' => $photo_id,
        'filename' => $photo['filename'],
        'file_path' => $photo['file_path']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Photo deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback da transação em caso de erro
    mysqli_rollback($conn);
    
    error_log("Error deleting photo: " . $e->getMessage());
    
    security_log('photo_delete_failed', [
        'photo_id' => $photo_id,
        'error' => $e->getMessage()
    ]);
    
    echo json_encode(['success' => false, 'error' => 'Failed to delete photo. Please try again.']);
}
?>