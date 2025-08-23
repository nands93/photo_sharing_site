<?php
    session_start();
    require_once 'backend.php';

    $message = '';
    $messageType = '';

    $csrf_token = '';

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
                $message = "Please fill in all fields.";
                $messageType = 'error';
            } elseif (!validate_username($username)) {
                $message = "Invalid username. Use 3-30 characters, letters, numbers, and underscores only.";
                $messageType = 'error';
            } else {
                // Adicione esta linha para chamar a função authenticate_user
                $auth_result = authenticate_user($conn, $username, $password);
                
                if ($auth_result === false) {
                    $message = "Username or password is incorrect.";
                    $messageType = 'error';
                } elseif (isset($auth_result['error']) && $auth_result['error'] === 'email_not_verified') {
                    $message = "Please verify your email before logging in.";
                    $messageType = 'error';
                } else {
                    // Agora $auth_result está definido corretamente
                    $_SESSION['user_id'] = $auth_result['id'];
                    $_SESSION['username'] = $auth_result['username'];

                    date_default_timezone_set('America/Sao_Paulo');
                    $now = date('Y-m-d H:i:s');
                    $update_stmt = mysqli_prepare($conn, "UPDATE users SET last_login=? WHERE id=?");
                    mysqli_stmt_bind_param($update_stmt, "si", $now, $auth_result['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                
                    session_regenerate_id(true);
                    
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['login_last_attempt']);
                    
                    header("Location: index.php");
                    exit();
                }
            }
        }
    }
    
    // Gerar novo token para o formulário se não foi definido via POST
    if (empty($csrf_token)) {
        $csrf_token = generate_csrf_token();
    }

    $page_title = 'Login';
    $page_name = 'Login';

    include 'includes/header.php';
?>
        
<div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow custom-card">
                    <div class="card-body">
                        <h2 class="mb-4 text-center">Login</h2>
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : $messageType; ?> text-center" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" required maxlength="30" pattern="[a-zA-Z0-9_]{3,30}" class="form-control" title="Nome de usuário deve ter 3-30 caracteres, apenas letras, números e underscore">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" required class="form-control">
                            </div>
                            <button type="submit" class="btn btn-camagru w-100">Login</button>
                        </form>
                        <div class="text-center mt-3">
                        <p class="mb-1">
                            New to Camagru?
                            <a href="signup.php" class="link-camagru">Sign up now!</a>
                        </p>
                        <a href="forgot_password.php" class="link-camagru">Forgot Password?</a>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>