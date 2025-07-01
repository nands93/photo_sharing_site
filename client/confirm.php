<?php
require_once 'backend.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET email_verified = 1, confirmation_token = NULL WHERE confirmation_token = ? AND email_verified = 0");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "E-mail confirmado com sucesso! Você já pode fazer login.";
                $messageType = 'success';
            } else {
                $message = "Token inválido ou conta já verificada.";
                $messageType = 'error';
            }
        } else {
            $message = "Erro ao confirmar e-mail.";
            $messageType = 'error';
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "Token não fornecido.";
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirmação de E-mail - Camagru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="form-container">
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <a href="login.php" class="btn-register">Ir para Login</a>
        </div>
    </div>
</body>
</html>