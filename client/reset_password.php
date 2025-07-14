<?php
    $page_title = 'Reset Password';
    $page_name = 'Reset Password';

    include 'includes/header.php';
?>
    
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