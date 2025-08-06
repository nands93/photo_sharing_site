<?php
session_start();
require_once 'backend.php';
require_once 'email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

switch ($action) {
    case 'get':
        $photo_id = intval($_GET['photo_id'] ?? 0);
        
        if (!validate_photo_id($photo_id)) {
            echo json_encode(['success' => false, 'error' => 'Invalid photo ID']);
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
        
        // Buscar comentários
        $stmt = mysqli_prepare($conn, "
            SELECT c.id, c.username, c.comment_text, c.created_at, c.user_id
            FROM comments c 
            WHERE c.photo_id = ? AND c.is_active = 1 
            ORDER BY c.created_at ASC 
            LIMIT 50
        ");
        mysqli_stmt_bind_param($stmt, "i", $photo_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $comments = [];
        while ($comment = mysqli_fetch_assoc($result)) {
            $comments[] = [
                'id' => $comment['id'],
                'username' => htmlspecialchars($comment['username']),
                'comment_text' => htmlspecialchars($comment['comment_text']),
                'created_at' => date('M j, H:i', strtotime($comment['created_at'])),
                'user_id' => $comment['user_id'] // Adicione isso
            ];
        }
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;
        
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $photo_id = intval($input['photo_id'] ?? 0);
        $comment_text = trim($input['comment_text'] ?? '');
        $csrf_token = $input['csrf_token'] ?? '';
        
        if (!validate_photo_id($photo_id)) {
            echo json_encode(['success' => false, 'error' => 'Invalid photo ID']);
            exit();
        }
        
        if (!verify_csrf_token($csrf_token)) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit();
        }
        
        if (!check_rate_limit('comment', 10, 300)) {
            echo json_encode(['success' => false, 'error' => 'Too many comments. Please wait before commenting again.']);
            exit();
        }
        
        // APLICAR A VALIDAÇÃO DE COMENTÁRIO
        if (!validate_comment_text($comment_text)) {
            echo json_encode(['success' => false, 'error' => 'Invalid comment. Please check your text and try again.']);
            exit();
        }
        
        // Verificar se a foto existe e está pública
        $stmt = mysqli_prepare($conn, "SELECT user_id, username FROM user_photos WHERE id = ? AND is_public = 1 AND is_active = 1");
        mysqli_stmt_bind_param($stmt, "i", $photo_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $photo_owner_id, $photo_owner_username);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$photo_owner_id) {
            echo json_encode(['success' => false, 'error' => 'Photo not found']);
            exit();
        }
        
        // Inserir comentário
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($conn, "
            INSERT INTO comments (photo_id, user_id, username, comment_text, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iissss", $photo_id, $user_id, $username, $comment_text, $ip_address, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            $comment_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Enviar notificação por email se habilitado e não for o próprio usuário
            if ($photo_owner_id != $user_id) {
                $stmt = mysqli_prepare($conn, "SELECT email, notify_comments FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $photo_owner_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $owner_email, $notify_comments);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                
                if ($notify_comments) {
                    comment_notification($owner_email, $photo_owner_username, $username, $comment_text);
                }
            }
            
            // Log de segurança
            security_log('comment_added', [
                'comment_id' => $comment_id,
                'photo_id' => $photo_id,
                'photo_owner_id' => $photo_owner_id,
                'comment_length' => strlen($comment_text)
            ]);
            
            echo json_encode([
                'success' => true, 
                'comment' => [
                    'id' => $comment_id,
                    'username' => htmlspecialchars($username),
                    'comment_text' => htmlspecialchars($comment_text),
                    'created_at' => date('M j, H:i')
                ]
            ]);
        } else {
            error_log("Failed to insert comment: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $comment_id = intval($input['comment_id'] ?? 0);
        $csrf_token = $input['csrf_token'] ?? '';
        
        if (!verify_csrf_token($csrf_token)) {
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit();
        }
        
        if ($comment_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
            exit();
        }
        
        // Verificar se o comentário existe e pertence ao usuário atual
        $stmt = mysqli_prepare($conn, "SELECT id, photo_id FROM comments WHERE id = ? AND user_id = ? AND is_active = 1");
        mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $found_id, $photo_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$found_id) {
            echo json_encode(['success' => false, 'error' => 'Comment not found or you do not have permission to delete it']);
            exit();
        }
        
        // Marcar comentário como inativo (soft delete)
        $stmt = mysqli_prepare($conn, "UPDATE comments SET is_active = 0 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // Log de segurança
            security_log('comment_deleted', [
                'comment_id' => $comment_id,
                'photo_id' => $photo_id
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            error_log("Failed to delete comment: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'error' => 'Failed to delete comment']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>