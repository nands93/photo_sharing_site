<?php
require_once 'backend.php';

$message = '';
$messageType = 'error';

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
            } elseif (empty($new_password) || empty($confirm_password)) {
                $message = "Please fill in all fields.";
            } elseif ($new_password !== $confirm_password) {
                $message = "Passwords do not match.";
            } elseif (!validate_password($new_password)) {
                $message = "Password must be at least 8 characters, with uppercase, lowercase, number, and special character.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=?, reset_password_token=NULL, reset_password_expires=NULL WHERE email=?");
                mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $message = "Your password has been reset. You can now <a href='login.php'>log in</a>.";
                $messageType = 'success';
            }
        }
    } else {
        $message = "Invalid or expired token.";
    }
} else {
    $message = "No token provided.";
}

include 'includes/header.php';
?>
<div class="form-container">
    <div class="message <?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
    <?php if (isset($email) && $email && $messageType !== 'success'): ?>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="form-group">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
        </div>
        <div class="form-group">
            <label for="confirm_password">Repeat new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>
        <button type="submit" class="btn-register">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>