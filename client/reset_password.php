<?php
session_start();
require_once 'backend.php';

$message = $message ?? '';
$messageType = $messageType ?? '';

if (isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE reset_password_token=? AND reset_password_expires > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $email);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($email) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';

            if (!verify_csrf_token($csrf_token)) {
                $message = "Invalid CSRF token.";
                $messageType = 'error';
            } elseif (empty($new_password) || empty($confirm_password)) {
                $message = "Please fill in all fields.";
                $messageType = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = "Passwords do not match.";
                $messageType = 'error';
            } elseif (!validate_password($new_password)) {
                $message = "Password must be at least 8 characters, with uppercase, lowercase, number, and special character.";
                $messageType = 'error';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=?, reset_password_token=NULL, reset_password_expires=NULL WHERE email=?");
                mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $message = "Your password has been reset. You can now <a href='login.php' class='link-camagru'>log in</a>.";
                $messageType = 'success';
            }
        }
    } else {
        $message = "Invalid or expired token.";
        $messageType = 'error';
    }
} else {
    $message = "No token provided.";
    $messageType = 'error';
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
                    <?php if (isset($email) && $email && $messageType !== 'success'): ?>
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Repeat new password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-camagru w-100">Reset Password</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($messageType === 'success'): ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-camagru">Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>