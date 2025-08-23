<?php
    session_start();
    require_once 'backend.php';
    require_once 'email.php';
    $page_title = 'Reset Password';
    $page_name = 'Reset Password';

    $message = '';
    $messageType = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verify_csrf_token($csrf_token)) {
            $message = "Invalid CSRF token.";
            $messageType = 'error';
        } elseif (!check_rate_limit('password_reset', 3, 300)) {
            $message = "Too many password reset attempts. Please try again in 5 minutes.";
            $messageType = 'error';
        } else {
            $email = sanitize_input($_POST['email'] ?? '');
            
            if (empty($email)) {
                $message = "Please enter your email.";
                $messageType = 'error';
            } elseif (!validate_email($email)) {
                $message = "Invalid email.";
                $messageType = 'error';
            } else {
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $reset_password_token = generate_token();
                        date_default_timezone_set('America/Sao_Paulo');
                        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        mysqli_stmt_close($stmt);
                        
                        $update_stmt = mysqli_prepare($conn, "UPDATE users SET reset_password_token=?, reset_password_expires=? WHERE email=?");
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "sss", $reset_password_token, $expires, $email);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                            
                            // Send email
                            reset_password_email($email, $reset_password_token);
                        } else {
                            error_log("Failed to prepare update statement: " . mysqli_error($conn));
                        }
                    } else {
                        mysqli_stmt_close($stmt);
                    }
                    
                    // Always show success message to prevent email enumeration
                    $message = "If the account exists, a password reset link has been sent to your email.";
                    $messageType = 'success';
                } else {
                    error_log("Failed to prepare select statement: " . mysqli_error($conn));
                    $message = "An error occurred. Please try again later.";
                    $messageType = 'error';
                }
            }
        }
    }

    include 'includes/header.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow custom-card">
                <div class="card-body">
                    <h2 class="mb-4 text-center">Reset Password</h2>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : $messageType; ?> text-center" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" required maxlength="100" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-camagru w-100">Reset Password</button>
                    </form>
                    <div class="form-footer text-center mt-3">
                        <a href="login.php" class="link-camagru">Back to login page</a>
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