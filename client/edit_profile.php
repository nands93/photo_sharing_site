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

function mask_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '[Email inválido]';
    }
    
    $hash = substr(hash('sha256', $email . 'camagru_salt'), 0, 8);
    
    return "****@****.*** (ID: {$hash})";
}

$masked_email = mask_email($current_user['email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $message = "Invalid CSRF token. Please try again.";
        $messageType = 'error';
    } elseif (!check_rate_limit('profile_update', 3, 600)) {
        $message = "Too many update attempts. Please try again in 10 minutes.";
        $messageType = 'error';
    } else {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (empty($new_username)) {
            $message = "Username cannot be empty.";
            $messageType = 'error';
        } elseif (!validate_username($new_username)) {
            $message = "Username must be 3-30 characters, letters, numbers, and underscores only.";
            $messageType = 'error';
        } else {
            if ($new_username !== $current_user['username']) {
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "si", $new_username, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $message = "Username already exists.";
                    $messageType = 'error';
                } else {
                    $updates[] = "username = ?";
                    $params[] = $new_username;
                    $types .= 's';
                }
                mysqli_stmt_close($stmt);
            }
            
            if (!empty($new_email) && $new_email !== $masked_email && $new_email !== $current_user['email']) {
                if ($new_email === $masked_email) {
                    $new_email = '';
                } elseif (!validate_email($new_email)) {
                    $message = "Invalid email format.";
                    $messageType = 'error';
                } elseif (strlen($new_email) > 254) { // Limite RFC 5321
                    $message = "Email address too long.";
                    $messageType = 'error';
                } else {
                    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "si", $new_email, $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $message = "Unable to update email. Please try a different address.";
                        $messageType = 'error';
                        security_log('email_change_blocked', [
                            'reason' => 'duplicate_email',
                            'attempted_email' => hash('sha256', $new_email)
                        ]);
                    } else {
                        $updates[] = "email = ?, email_verified = 0, confirmation_token = ?";
                        $confirmation_token = generate_token();
                        $params[] = $new_email;
                        $params[] = $confirmation_token;
                        $types .= 'ss';
                        
                        if (send_confirmation_email($new_email, $new_username, $confirmation_token)) {
                            $message = "Email updated. Please check your new email to confirm the change.";
                            $messageType = 'warning';
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $message = "Current password is required to change password.";
                    $messageType = 'error';
                } else {
                    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $stored_password);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);
                    
                    if (!password_verify($current_password, $stored_password)) {
                        $message = "Current password is incorrect.";
                        $messageType = 'error';
                    } elseif (!validate_password($new_password)) {
                        $message = "Password must be at least 8 characters, with uppercase, lowercase, number, and special character.";
                        $messageType = 'error';
                    } elseif ($new_password !== $confirm_password) {
                        $message = "New passwords do not match.";
                        $messageType = 'error';
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                        $updates[] = "password = ?";
                        $params[] = $hashed_password;
                        $types .= 's';
                    }
                }
            }
            
            if (empty($message) && !empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';
                
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                
                if (mysqli_stmt_execute($stmt)) {
                    if ($new_username !== $current_user['username']) {
                        $_SESSION['username'] = $new_username;
                        $current_user['username'] = $new_username;
                    }
                    
                    if (empty($message)) {
                        $message = "Profile updated successfully!";
                        $messageType = 'success';
                    }
                    
                    if (!empty($new_email) && $new_email !== $masked_email && $new_email !== $current_user['email']) {
                        $current_user['email'] = $new_email;
                        $masked_email = mask_email($new_email);
                    }
                } else {
                    error_log("Profile update failed: " . mysqli_stmt_error($stmt));
                    $message = "Failed to update profile. Please try again.";
                    $messageType = 'error';
                }
                mysqli_stmt_close($stmt);
            } elseif (empty($message) && empty($updates)) {
                $message = "No changes were made.";
                $messageType = 'warning';
            }
        }
    }
}

include 'includes/header.php';
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
                                required
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
                                value="<?php echo htmlspecialchars($masked_email); ?>"
                                placeholder="Enter new email or leave as is">
                            <small class="form-text text-muted">Current email is masked for security. Enter a new email to change it.</small>
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