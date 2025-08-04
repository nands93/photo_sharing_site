<?php
    session_start();
    require_once 'backend.php';
    require_once 'email.php';
    
    $message = '';
    $messageType = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($csrf_token)) {
            $message = "Invalid CSRF token. Please try again.";
            $messageType = 'error';
        }
        elseif (!check_rate_limit('signup', 3, 600)) {
            $message = "Too many signup attempts. Please try again in 10 minutes.";
            $messageType = 'error';
        }
        else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $message = "Please fill in all fields.";
                $messageType = 'error';
            } elseif (!validate_username($username)) {
                $message = "Username must be 3-30 characters, letters, numbers, and underscores only.";
                $messageType = 'error';
            } elseif (!validate_email($email)) {
                $message = "Invalid email format.";
                $messageType = 'error';
            } elseif (!validate_password($password)) {
                $message = "Password must be at least 8 characters, with uppercase, lowercase, number, and special character.";
                $messageType = 'error';
            } elseif (user_exists($conn, $username, $email)) {
                $message = "Email or username already exists.";
                $messageType = 'error';
            } elseif ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $messageType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $confirmation_token = generate_token();
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, confirmation_token, email_verified) VALUES (?, ?, ?, ?, 0)");
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $confirmation_token);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        if (send_confirmation_email($email, $username, $confirmation_token)) {
                            $message = "Registration successful! Please check your email to confirm your account.";
                            $messageType = 'success';
                        } else {
                            $message = "Failed to send confirmation email. Please try again.";
                            $messageType = 'warning';
                        }
                        
                        unset($_SESSION['signup_attempts']);
                        unset($_SESSION['signup_last_attempt']);
                    } else {
                        error_log("Insert failed: " . mysqli_stmt_error($stmt));
                        $message = "Internal error. Please try again later.";
                        $messageType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Prepare failed: " . mysqli_error($conn));
                    $message = "Internal error. Please try again later.";
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
        
<div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow custom-card">
                    <div class="card-body">
                        <h2 class="mb-4 text-center">Sign up</h2>
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> text-center">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    required
                                    maxlength="30"
                                    pattern="[a-zA-Z0-9_]{3,30}"
                                    class="form-control"
                                    title="Nome de usuário deve ter 3-30 caracteres, apenas letras, números e underscore">
                                <div class="invalid-feedback">
                                    3 characters minimum, only letters, numbers, and underscore
                                </div>
                                <small class="form-text text-muted">3 characters minimum, only letters, numbers, and underscore
                                </small>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" id="email" name="email" required maxlength="100" class="form-control">
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    minlength="8"
                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$"
                                    class="form-control"
                                    title="Senha deve ter pelo menos 8 caracteres, incluindo maiúscula, minúscula, número e caractere especial">
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.
                                </div>
                                <small class="form-text text-muted">8 characters minimum, with uppercase, lowercase, number, and special character</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Repeat Password</label>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    required
                                    minlength="8"
                                    class="form-control"
                                    title="Repita a senha">
                                <div class="invalid-feedback">
                                    As senhas devem coincidir.
                                </div>
                        </div>
                            <button type="submit" class="btn btn-camagru w-100">Sign up</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account?
                                <a href="login.php" class="link-camagru">Login</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>
<?php if ($messageType == 'success'): ?>
<script src="includes/js/timeout.js"></script>
<?php endif; ?>