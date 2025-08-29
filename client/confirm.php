<?php
require_once 'backend.php';

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    error_log("Token received: " . $token);
    
    $check_stmt = mysqli_prepare($conn, "SELECT email_verified FROM users WHERE confirmation_token = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $token);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_bind_result($check_stmt, $email_verified);
    $token_exists = mysqli_stmt_fetch($check_stmt);
    mysqli_stmt_close($check_stmt);
    
    if (!$token_exists) {
        $message = "Invalid confirmation token. Please check the link or request a new confirmation email.";
        $messageType = 'error';
    } elseif ($email_verified) {
        $message = "Email already verified. You can now log in.";
        $messageType = 'success';
    } else {
        // Token exists and email is not verified, proceed with update
        $stmt = mysqli_prepare($conn, "UPDATE users SET email_verified = 1, confirmation_token = NULL WHERE confirmation_token = ? AND email_verified = 0");
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Email confirmed successfully. You can now log in.";
                $messageType = 'success';
            } else {
                $message = "Confirmation failed. Please try again or contact support.";
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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow custom-card">
                <div class="card-body text-center">
                    <!-- Icon based on success/error -->
                    <div class="mb-4">
                        <?php if ($messageType === 'success'): ?>
                            <div class="text-success mb-3">
                                <i style="font-size: 4rem;">✅</i>
                            </div>
                            <h2 class="text-success">Email Confirmed!</h2>
                        <?php else: ?>
                            <div class="text-danger mb-3">
                                <i style="font-size: 4rem;">❌</i>
                            </div>
                            <h2 class="text-danger">Confirmation Failed</h2>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : $messageType; ?> text-center" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Action Button -->
                    <div class="d-grid gap-2 mb-4">
                        <?php if ($messageType === 'success'): ?>
                            <a href="login.php" class="btn btn-camagru btn-lg">
                                🔐 Login to Your Account
                            </a>
                        <?php else: ?>
                            <a href="signup.php" class="btn btn-camagru btn-lg">
                                🔄 Try Again
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Additional Help -->
                    <div class="text-muted">
                        <?php if ($messageType === 'success'): ?>
                            <p class="mb-2">🎉 Your account is now fully activated!</p>
                            <small>You can now access all features of Camagru.</small>
                        <?php else: ?>
                            <p class="mb-2">Need help?</p>
                            <small>
                                Contact support or 
                                <a href="signup.php" class="link-camagru">create a new account</a>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Additional Links -->
            <div class="text-center mt-4">
                <a href="index.php" class="link-camagru">
                    <i>🏠</i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php if ($messageType == 'success'): ?>
<script src="includes/js/timeout.js"></script>
<?php endif; ?>