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
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($message): ?>
                    <div class="alert alert-success text-center">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <div class="card shadow custom-card text-center">
                    <div class="card-body">
                        <?php if ($is_logged_in): ?>
                            <h2 class="card-title mb-3">Bem-vindo, <?php echo sanitize_input($_SESSION['username']); ?>!</h2>
                            <p class="card-text">Você está logado no sistema Camagru.</p>
                        <?php else: ?>
                            <h2 class="card-title mb-3">Bem-vindo ao Camagru</h2>
                            <p class="card-text">Faça login ou cadastre-se para começar a usar o sistema.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>