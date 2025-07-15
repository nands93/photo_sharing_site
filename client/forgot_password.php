<?php
    session_start();
    require_once 'backend.php';
    require_once 'email.php';
    $page_title = 'Reset Password';
    $page_name = 'Reset Password';

    $message = $message ?? '';
    $messageType = $messageType ?? '';

    $csrf_token = $_SESSION['csrf_token'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['csrf_token']) && verify_csrf_token($csrf_token)) {
            $email = sanitize_input($_POST['email'] ?? '');
            
            if (empty($email)) {
                $message = "Please enter your email.";
                $messageType = 'error';
            } elseif (!validate_email($email)) {
                $message = "Invalid email.";
                $messageType = 'error';
            } else {
                $reset_password_token = generate_token();
                date_default_timezone_set('America/Sao_Paulo');
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $stmt = mysqli_prepare($conn, "UPDATE users SET reset_password_token=?, reset_password_expires=? WHERE email=?");
                mysqli_stmt_bind_param($stmt, "sss", $reset_password_token, $expires, $email);
                mysqli_stmt_execute($stmt);
                reset_password_email($email, $reset_password_token);
                $message = "If the account exists, a password reset link has been sent to your email.";
                $messageType = 'success';
            }
        } else {
            $message = "Invalid CSRF token.";
            $messageType = 'error';
        }
    }

    include 'includes/header.php';
?>
    
        <div class="form-container">
            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required maxlength="100">
                </div>
                    <button type="submit" class="btn-register">
                        <span>Reset Password</span>
                    </button>
                    <div class="form-footer">
                <br>
                <a href="login.php">Back to login page</a>
            </div>
                </div>
                </p>
            </div>
        <?php include 'includes/footer.php'; ?>
    
    <?php if ($messageType == 'success'): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 3000);
    </script>
    <?php endif; ?>
