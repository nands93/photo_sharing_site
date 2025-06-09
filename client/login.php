<?php
    session_start();
    require_once 'database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Camagru</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="header">
        <nav>
            <img src="images/logo.png" alt="Camagru Logo" class="logo" width="100"/>
            <h1>Login</h1>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Cadastro</a></li>
            </ul>
        </nav>
        <form action="login.php" method="post">
            <label>username</label>
            <input type="text" name="username"><br>
            <label>password</label>
            <input type="password" name="password"><br>
            <input type="submit" value="Log in"><br>
        </form>
        <?php

        ?>
    </div>
</body>
</html>