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
                $message = "Please fill in all fields.";
                $messageType = 'error';
            } elseif (!validate_username($username)) {
                $message = "Ivalid username. Use 3-30 characters, letters, numbers, and underscores only.";
                $messageType = 'error';
            } else {
                $stmt = mysqli_prepare($conn, "SELECT id, username, password, email_verified FROM users WHERE username = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($user = mysqli_fetch_assoc($result)) {
                        if ($user && password_verify($password, $user['password'])) {
                            if (!$user['email_verified']) {
                                $message = "Please, verify your email address before logging in.";
                                $messageType = 'error';
                            } else {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];

                                date_default_timezone_set('America/Sao_Paulo');
                                $now = date('Y-m-d H:i:s');
                                $update_stmt = mysqli_prepare($conn, "UPDATE users SET last_login=? WHERE id=?");
                                mysqli_stmt_bind_param($update_stmt, "si", $now, $user['id']);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            
                                session_regenerate_id(true);
                                
                                unset($_SESSION['login_attempts']);
                                unset($_SESSION['login_last_attempt']);
                                
                                header("Location: index.php");
                                exit();
                            }
                        } else {
                            $message = "Username or password is incorrect.";
                            $messageType = 'error';
                        }
                    } else {
                        $message = "Username or password is incorrect.";
                        $messageType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Prepare failed: " . mysqli_error($conn));
                    $message = "Internal server error. Please try again later.";
                    $messageType = 'error';
                }
            }
        }
    }
    $csrf_token = generate_csrf_token();

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
                            <div class="alert alert-<?php echo $messageType; ?> text-center">
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
                            <a href="signup.php" style="color:#bfa76a; font-weight:600; text-decoration:underline;">Sign up now!</a>
                        </p>
                        <a href="forgot_password.php" class="link-secondary">Forgot Password?</a>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>