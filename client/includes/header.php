<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; img-src \'self\' data:');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
if (!isset($page_title)) {
    $page_title = 'Camagru';
}
if (!isset($page_name)) {
    $page_name = 'Camagru';
}

if (!isset($csrf_token)) {
    if (isset($_SESSION['user_id'])) {
        // Para usuários logados, usar um token persistente durante a sessão
        if (!isset($_SESSION['current_csrf_token']) || 
            !isset($_SESSION['csrf_tokens'][$_SESSION['current_csrf_token']]) ||
            (time() - $_SESSION['csrf_tokens'][$_SESSION['current_csrf_token']]) > 3000) {
            
            $csrf_token = generate_csrf_token();
            $_SESSION['current_csrf_token'] = $csrf_token;
        } else {
            $csrf_token = $_SESSION['current_csrf_token'];
        }
    } else {
        // Para usuários não logados, gerar token único por página se não existir
        $csrf_token = generate_csrf_token();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php if (isset($_SESSION['user_id'])): ?>
    <meta name="current-user-id" content="<?php echo $_SESSION['user_id']; ?>">
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg custom-navbar sticky-top">
        <div class="container-fluid px-4">
             <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <img src="images/logo.png" alt="Camagru Logo" width="60" height="60" class="me-3">
                <h1 class="mb-0 h3 fw-bold text-dark"><?php echo htmlspecialchars($page_name); ?></h1>
            </a>
            <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
                <?php if (basename($_SERVER['PHP_SELF']) !== 'photo_edit.php'): ?>
                    <a href="photo_edit.php" class="btn btn-camagru btn-sm me-3">New Post</a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="d-flex align-items-center">
                <ul class="navbar-nav d-flex flex-row">
                    <li class="nav-item me-3">
                        <a class="nav-link" href="index.php">Camagru</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item me-3 d-flex align-items-center">
                        <span class="me-1">Olá,</span>
                        <a href="profile.php" class="nav-link fw-semibold p-0 text-dark text-decoration-none">
                            <?php echo sanitize_input($_SESSION['username']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                            <a class="nav-link text-danger fw-semibold" 
                               href="logout.php?token=<?php echo generate_csrf_token(); ?>" 
                               onclick="return confirm('Tem certeza que deseja sair?')">
                               Sair (<?php echo sanitize_input($_SESSION['username']); ?>)
                            </a>
                        </li>
                    <?php else: ?>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php">Sign Up</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid py-4">