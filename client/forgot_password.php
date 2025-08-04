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
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) > 0) {
                    mysqli_stmt_close($stmt);
                    $reset_password_token = generate_token();
                    date_default_timezone_set('America/Sao_Paulo');
                    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $stmt = mysqli_prepare($conn, "UPDATE users SET reset_password_token=?, reset_password_expires=? WHERE email=?");
                    mysqli_stmt_bind_param($stmt, "sss", $reset_password_token, $expires, $email);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    reset_password_email($email, $reset_password_token);
                } else {
                    mysqli_stmt_close($stmt);
                }
                $message = "If the account exists, a password reset link has been sent to your email.";
                $messageType = 'success';
            }
            } else {
            $message = "Invalid CSRF token.";
            $messageType = 'error';
            mysqli_stmt_close($stmt);
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