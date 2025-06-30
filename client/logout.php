<?php
session_start();
require_once 'backend.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verificar CSRF token para logout seguro
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['token'])) {
    if (verify_csrf_token($_GET['token'])) {
        // Destruir todas as variáveis de sessão
        $_SESSION = array();
        
        // Destruir o cookie de sessão se existir
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir a sessão
        session_destroy();
        
        header("Location: index.php?logout=success");
        exit();
    }
}

// Se chegou aqui, redirecionar para index
header("Location: index.php");
exit();
?>
