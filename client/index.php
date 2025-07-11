<?php
    session_start();
    require_once 'backend.php';
    
    $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
    
    if ($is_logged_in) {
        $csrf_token = generate_csrf_token();
    }
    
    $message = '';
    if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
        $message = 'Logout realizado com sucesso!';
    }
    $page_title = 'Camagru';
    $page_name = 'Camagru';

    include 'includes/header.php';
?>
    <div class="content-wrapper">
        <div class="content">
            <?php if ($message): ?>
                <div class="message success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_logged_in): ?>
                <h2>Bem-vindo, <?php echo sanitize_input($_SESSION['username']); ?>!</h2>
                <p>Você está logado no sistema Camagru.</p>
            <?php else: ?>
                <h2>Bem-vindo ao Camagru</h2>
                <p>Faça login ou cadastre-se para começar a usar o sistema.</p>
            <?php endif; ?>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>