<?php
session_start();
require_once 'backend.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Profile';
$page_name = 'Profile';

$message = '';
$messageType = '';
$user_id = $_SESSION['user_id'];

// Buscar dados completos do usuário
$stmt = mysqli_prepare($conn, "SELECT username, email, notify_comments, created_at, last_login, email_verified FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user_data) {
    header("Location: logout.php");
    exit();
}

// Processar atualização das preferências
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $message = "Invalid CSRF token. Please try again.";
        $messageType = 'error';
    } elseif (!check_rate_limit('preferences_update', 5, 300)) {
        $message = "Too many update attempts. Please try again in 5 minutes.";
        $messageType = 'error';
    } else {
        // Checkbox - se não estiver marcada, não vem no POST
        $notify_comments = isset($_POST['notify_comments']) ? 1 : 0;
        
        $stmt = mysqli_prepare($conn, "UPDATE users SET notify_comments = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $notify_comments, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Preferences updated successfully!";
            $messageType = 'success';
            // Atualizar os dados exibidos
            $user_data['notify_comments'] = $notify_comments;
        } else {
            error_log("Preferences update failed: " . mysqli_stmt_error($stmt));
            $message = "Failed to update preferences. Please try again.";
            $messageType = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

// Função para mascarar o email
function mask_email($email) {
    $parts = explode('@', $email);
    if (count($parts) != 2) return $email;
    
    $username = $parts[0];
    $domain = $parts[1];
    
    $masked_username = substr($username, 0, 2) . str_repeat('*', max(2, strlen($username) - 2));
    $masked_domain = substr($domain, 0, 2) . str_repeat('*', max(2, strlen($domain) - 2));
    
    return $masked_username . '@' . $masked_domain;
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow custom-card">
                <div class="card-body">
                    <h2 class="mb-4 text-center">My Profile</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : $messageType; ?> text-center" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Informações do Usuário -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-muted mb-3">User Information</h5>
                                    
                                    <div class="mb-3">
                                        <strong>Username:</strong>
                                        <p class="mb-1"><?php echo htmlspecialchars($user_data['username']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Email:</strong>
                                        <p class="mb-1"><?php echo htmlspecialchars(mask_email($user_data['email'])); ?></p>
                                        <small class="text-muted">
                                            <?php if ($user_data['email_verified']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Verified</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Member since:</strong>
                                        <p class="mb-1"><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                                    </div>
                                    
                                    <?php if ($user_data['last_login']): ?>
                                    <div class="mb-0">
                                        <strong>Last login:</strong>
                                        <p class="mb-0"><?php echo date('F j, Y - H:i', strtotime($user_data['last_login'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-muted mb-3">Account Settings</h5>
                                    
                                    <!-- Formulário de Preferências -->
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        
                                        <div class="form-check mb-3">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox" 
                                                id="notify_comments" 
                                                name="notify_comments"
                                                <?php echo $user_data['notify_comments'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notify_comments">
                                                <strong>Email Notifications</strong><br>
                                                <small class="text-muted">Receive email notifications when someone comments on your photos</small>
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-camagru btn-sm">
                                            Save Preferences
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="edit_profile.php" class="btn btn-camagru btn-lg me-md-2">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos customizados para a página de profile */
.bg-light {
    background-color: rgba(248, 249, 250, 0.7) !important;
}

.badge {
    font-size: 0.7em;
}

.card-title {
    border-bottom: 2px solid #bfa76a;
    padding-bottom: 8px;
}

.btn-outline-secondary {
    border-color: #bfa76a;
    color: #bfa76a;
}

.btn-outline-secondary:hover {
    background-color: #bfa76a;
    border-color: #bfa76a;
    color: #222;
}

.form-check-input:checked {
    background-color: #bfa76a;
    border-color: #bfa76a;
}

.form-check-input:focus {
    border-color: #bfa76a;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(191, 167, 106, 0.25);
}
</style>

<script src="includes/js/preference_confirm.js"></script>

<?php include 'includes/footer.php'; ?>