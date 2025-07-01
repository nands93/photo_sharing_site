<?php
    session_start();
    require_once 'backend.php';

    $message = '';
    $messageType = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            $message = "Token de segurança inválido. Tente novamente.";
            $messageType = 'error';
        }
        elseif (!check_rate_limit('login', 5, 300)) {
            $message = "Muitas tentativas de login. Tente novamente em 5 minutos.";
            $messageType = 'error';
        }
        else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $message = "Por favor, preencha todos os campos.";
                $messageType = 'error';
            } elseif (!validate_username($username)) {
                $message = "Nome de usuário inválido.";
                $messageType = 'error';
            } else {
                $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($user = mysqli_fetch_assoc($result)) {
                        if (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            
                            session_regenerate_id(true);
                            
                            unset($_SESSION['login_attempts']);
                            unset($_SESSION['login_last_attempt']);
                            
                            header("Location: index.php");
                            exit();
                        } else {
                            $message = "Nome de usuário ou senha incorretos.";
                            $messageType = 'error';
                        }
                    } else {
                        $message = "Nome de usuário ou senha incorretos.";
                        $messageType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Prepare failed: " . mysqli_error($conn));
                    $message = "Erro interno. Tente novamente mais tarde.";
                    $messageType = 'error';
                }
            }
        }
    }
    $csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Camagru - Login</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1>Login</h1>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Cadastro</a></li>
            </ul>
        </nav>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="signup-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Nome de usuário</label>
                    <input type="text" id="username" name="username" required maxlength="30" pattern="[a-zA-Z0-9_]{3,30}" title="Nome de usuário deve ter 3-30 caracteres, apenas letras, números e underscore">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-register">
                    <span>Entrar</span>
                </button>
            </form>
            
            <div class="form-footer">
                <p>Não tem uma conta? <a href="signup.php">Cadastre-se</a></p>
            </div>
        </div>
    </div>
</body>
</html>