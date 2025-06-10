<?php
    session_start();
    require_once 'database.php';
    
    $message = '';
    $messageType = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        
        if (empty($username) || empty($email) || empty($password)) {
            $message = "Por favor, preencha todos os campos.";
            $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hash')";
            if (mysqli_query($conn, $sql)) {
                $message = "Usuário registrado com sucesso!";
                $messageType = 'success';
            } else {
                $message = "Erro: " . mysqli_error($conn);
                $messageType = 'error';
            }
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Camagru - Cadastro</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1>Cadastro</h1>
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
                <div class="form-group">
                    <label for="username">Nome de usuário</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-register">
                    <span>Criar Conta</span>
                </button>
            </form>
            
            <div class="form-footer">
                <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
            </div>
        </div>
    </div>
</body>
</html>