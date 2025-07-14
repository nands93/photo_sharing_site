<?php
    session_start();
    require_once 'backend.php';
    require_once 'email.php';
    
    $message = '';
    $messageType = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            $message = "Token de segurança inválido. Tente novamente.";
            $messageType = 'error';
        }
        elseif (!check_rate_limit('signup', 3, 600)) {
            $message = "Muitas tentativas de cadastro. Tente novamente em 10 minutos.";
            $messageType = 'error';
        }
        else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                $message = "Por favor, preencha todos os campos.";
                $messageType = 'error';
            } elseif (!validate_username($username)) {
                $message = "Nome de usuário deve ter 3-30 caracteres, apenas letras, números e underscore.";
                $messageType = 'error';
            } elseif (!validate_email($email)) {
                $message = "E-mail inválido.";
                $messageType = 'error';
            } elseif (!validate_password($password)) {
                $message = "Senha deve ter pelo menos 8 caracteres, incluindo maiúscula, minúscula, número e caractere especial.";
                $messageType = 'error';
            } elseif (user_exists($conn, $username, $email)) {
                $message = "Nome de usuário ou e-mail já cadastrado.";
                $messageType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $confirmation_token = generate_confirmation_token();
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, confirmation_token, email_verified) VALUES (?, ?, ?, ?, 0)");
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $confirmation_token);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        if (send_confirmation_email($email, $username, $confirmation_token)) {
                            $message = "Usuário registrado com sucesso! Verifique seu e-mail para confirmar o cadastro antes de fazer login.";
                            $messageType = 'success';
                        } else {
                            $message = "Usuário registrado, mas houve erro ao enviar e-mail de confirmação. Entre em contato com o suporte.";
                            $messageType = 'warning';
                        }
                        
                        unset($_SESSION['signup_attempts']);
                        unset($_SESSION['signup_last_attempt']);
                    } else {
                        error_log("Insert failed: " . mysqli_stmt_error($stmt));
                        $message = "Erro ao registrar usuário. Tente novamente mais tarde.";
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

    $page_title = 'Sign up';
    $page_name = 'Sign up';

    include 'includes/header.php';
?>
        
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
                    <small>3-30 caracteres, apenas letras, números e underscore</small>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required minlength="8" title="Senha deve ter pelo menos 8 caracteres, incluindo maiúscula, minúscula, número e caractere especial">
                    <small>Mínimo 8 caracteres com maiúscula, minúscula, número e símbolo</small>
                </div>
                
                <button type="submit" class="btn-register">
                    <span>Criar Conta</span>
                </button>
            </form>
            
            <div class="form-footer">
                <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
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