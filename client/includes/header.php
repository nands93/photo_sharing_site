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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1><?php echo htmlspecialchars($page_name); ?></h1>
            <ul>
                <li><a href="index.php">Início</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="profile.php">Perfil</a></li>
                    <li><a href="logout.php?token=<?php echo generate_csrf_token(); ?>" onclick="return confirm('Tem certeza que deseja sair?')">Sair (<?php echo sanitize_input($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </nav>