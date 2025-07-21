<?php
if (!isset($page_title)) {
    $page_title = 'Camagru';
}
if (!isset($page_name)) {
    $page_name = 'Camagru';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <style>
        body {
            background: linear-gradient(135deg, #f0ece1 0%, #dad1bf 100%);
            min-height: 100vh;
        }
        .custom-navbar {
            background: rgba(240, 236, 225, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .custom-card {
            background: rgba(240, 236, 225, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .navbar-brand img {
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .navbar-brand img:hover {
            transform: scale(1.05);
        }
        .nav-link {
            font-weight: 500;
            color: #222 !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #b37e03 !important;
        }

        .btn-camagru {
            background: #bfa76a;
            color: #222;
            border: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-camagru:hover, .btn-camagru:focus {
            background: #a68d4a;
            color: #222;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg custom-navbar sticky-top">
        <div class="container-fluid px-4">
             <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <img src="images/logo.png" alt="Camagru Logo" width="60" height="60" class="me-3">
                <h1 class="mb-0 h3 fw-bold text-dark"><?php echo htmlspecialchars($page_name); ?></h1>
            </a>
            <div class="d-flex align-items-center">
                <ul class="navbar-nav d-flex flex-row">
                    <li class="nav-item me-3">
                        <a class="nav-link" href="index.php">Início</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="profile.php">Perfil</a>
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
                        <a class="nav-link" href="signup.php">Cadastro</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid py-4">