<?php
    session_start();
    require_once 'backend.php';
    
    $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
    
    if ($is_logged_in) {
        $csrf_token = generate_csrf_token();
    }
    
    $message = '';
    if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
        $message = 'Logout realizado com sucesso!';
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Camagru</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1>Camagru</h1>
            <ul>
                <li><a href="index.php">Início</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="profile.php">Perfil</a></li>
                    <li><a href="logout.php?token=<?php echo $csrf_token; ?>" onclick="return confirm('Tem certeza que deseja sair?')">Sair (<?php echo sanitize_input($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_logged_in): ?>
                <h2>Bem-vindo, <?php echo sanitize_input($_SESSION['username']); ?>!</h2>
                <p>Você está logado no sistema Camagru.</p>
            <?php else: ?>
                <h2>Bem-vindo ao Camagru</h2>
                <p>Faça login ou cadastre-se para começar a usar o sistema.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>