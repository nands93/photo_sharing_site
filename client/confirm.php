<?php
require_once 'backend.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET email_verified = 1, confirmation_token = NULL WHERE confirmation_token = ? AND email_verified = 0");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Email confirmed successfully. You can now log in.";
                $messageType = 'success';
            } else {
                $message = "Invalid confirmation token or email already verified.";
                $messageType = 'error';
            }
        } else {
            $message = "Error confirming email. Please try again later.";
            $messageType = 'error';
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "Confirmation token not provided.";
    $messageType = 'error';
}

$page_title = 'Email Confirmation';
$page_name = 'Email Confirmation';

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
                    <span><?php echo $messageType === 'success' ? 'Login' : 'Try again'; ?></span>
                </a>
            </div>
            
            <div class="form-footer">
                <p>
                    <?php if ($messageType === 'success'): ?>
                        Account verified successfully! You can now log in.
                    <?php else: ?>
                        Need help? <a href="signup.php">Contact support</a>.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php include 'includes/footer.php'; ?>
    
    <?php if ($messageType == 'success'): ?>
    <script src="includes/js/timeout.js"></script>
    <?php endif; ?>