<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Camagru - Confirmação de E-mail</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1>Confirmação de E-mail</h1>
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
            
            <div class="form-group">
                <a href="login.php" class="btn-register" style="text-decoration: none; display: inline-block; text-align: center;">
                    <span><?php echo $messageType === 'success' ? 'Fazer Login' : 'Tentar Novamente'; ?></span>
                </a>
            </div>
            
            <div class="form-footer">
                <p>
                    <?php if ($messageType === 'success'): ?>
                        Sua conta foi ativada com sucesso!
                    <?php else: ?>
                        Precisa de ajuda? <a href="signup.php">Registrar novamente</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php include 'includes/footer.php'; ?>
    
    <?php if ($messageType == 'success'): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
    <?php endif; ?>