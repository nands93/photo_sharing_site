<?php
session_start();
require_once 'backend.php';
require_once 'email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Edit Profile';
$page_name = 'Edit Profile';

$message = '';
$messageType = '';
$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT username, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

$masked_email = mask_email($current_user['email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $message = "Invalid CSRF token. Please try again.";
        $messageType = 'error';
    } elseif (!check_rate_limit('profile_update', 2, 300)) {
        $message = "Too many update attempts. Please try again in 10 minutes.";
        $messageType = 'error';
    } else {
        mysqli_autocommit($conn, FALSE);
        
        try {
            $updates = [];
            $params = [];
            $types = '';
            $email_changed = false;
            $new_email_for_confirmation = '';
            $confirmation_token_for_email = '';

            $new_username = trim($_POST['username'] ?? '');
            $new_email = trim($_POST['email'] ?? '');
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (!empty($new_username) && $new_username !== $current_user['username']) {
                if (!validate_username($new_username)) {
                    throw new Exception("Username must be 3-30 characters, letters, numbers, and underscores only.");
                }
                
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    mysqli_stmt_close($stmt);
                    throw new Exception("Username already exists.");
                }
                
                mysqli_stmt_close($stmt);
                $updates[] = "username = ?";
                $params[] = $new_username;
                $types .= 's';
            }

            if (!empty($new_email) && $new_email !== $masked_email && $new_email !== $current_user['email']) {
                if (!validate_email($new_email) || strlen($new_email) > 254) {
                    throw new Exception("Invalid email format or too long.");
                }
                
                if (!check_rate_limit('email_change', 1, 3600)) {
                    throw new Exception("Email can only be changed once per hour.");
                }
                
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    mysqli_stmt_close($stmt);
                    throw new Exception("Email address is already in use.");
                }
                
                mysqli_stmt_close($stmt);
                
                $confirmation_token = generate_token();
                $updates[] = "email = ?, email_verified = 0, confirmation_token = ?";
                $params[] = $new_email;
                $params[] = $confirmation_token;
                $types .= 'ss';
                
                $email_changed = true;
                $new_email_for_confirmation = $new_email;
                $confirmation_token_for_email = $confirmation_token;
            }
                
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    throw new Exception("Current password is required to change password.");
                }
                
                $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $stored_password);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                
                if (!password_verify($current_password, $stored_password)) {
                    throw new Exception("Current password is incorrect.");
                }
                
                if (!validate_password($new_password)) {
                    throw new Exception("Password must be at least 8 characters, with uppercase, lowercase, number, and special character.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match.");
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                $updates[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
            }
                
            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';
                
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update profile.");
                }
                
                mysqli_stmt_close($stmt);
                
                if (!empty($new_username) && $new_username !== $current_user['username']) {
                    $_SESSION['username'] = $new_username;
                    $current_user['username'] = $new_username;
                    
                    $update_photos_stmt = mysqli_prepare($conn, "UPDATE user_photos SET username = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($update_photos_stmt, "si", $new_username, $user_id);
                    mysqli_stmt_execute($update_photos_stmt);
                    mysqli_stmt_close($update_photos_stmt);
                }
                
                mysqli_commit($conn);
                
                if ($email_changed) {
                    if (send_confirmation_email($new_email_for_confirmation, $current_user['username'], $confirmation_token_for_email)) {
                        $message = "Profile updated successfully! Please check your new email for a confirmation link.";
                        $messageType = 'success';
                    } else {
                        $message = "Profile updated, but failed to send confirmation email. Please try again or contact support.";
                        $messageType = 'warning';
                    }
                    $current_user['email'] = $new_email_for_confirmation;
                    $masked_email = mask_email($new_email_for_confirmation);
                } else {
                    $message = "Profile updated successfully!";
                    $messageType = 'success';
                }
            } else {
                $message = "No changes were made.";
                $messageType = 'info';
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = $e->getMessage();
            $messageType = 'error';
            error_log("Profile update error: " . $e->getMessage());
        }
        
        mysqli_autocommit($conn, TRUE);
    }
}

include 'includes/header.php';
echo "<script>var maskedEmail = '" . htmlspecialchars($masked_email) . "';</script>";
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow custom-card">
                <div class="card-body">
                    <h2 class="mb-4 text-center">Edit Profile</h2>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : $messageType; ?> text-center" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                maxlength="30"
                                pattern="[a-zA-Z0-9_]{3,30}"
                                class="form-control"
                                value="<?php echo htmlspecialchars($current_user['username']); ?>"
                                title="Username must be 3-30 characters, letters, numbers, and underscores only">
                            <small class="form-text text-muted">3 characters minimum, only letters, numbers, and underscore</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="100"
                                class="form-control"
                                placeholder="Enter new email or leave as is">
                            <small class="form-text text-muted">
                                Current email is masked for security. Click to enter a new email address.
                            </small>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3">Change Password (Optional)</h5>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control"
                                placeholder="Required only if changing password">
                            <small class="form-text text-muted">Required only if you want to change your password</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                minlength="8"
                                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$"
                                class="form-control"
                                placeholder="Leave empty to keep current password"
                                title="Password must be at least 8 characters, including uppercase, lowercase, number, and special character">
                            <small class="form-text text-muted">8 characters minimum, with uppercase, lowercase, number, and special character</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                class="form-control"
                                placeholder="Repeat new password">
                        </div>
                        
                        <button type="submit" class="btn btn-camagru w-100">Update Profile</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="profile.php" class="link-camagru">Back to Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="includes/js/password_match.js"></script>

<?php include 'includes/footer.php'; ?>