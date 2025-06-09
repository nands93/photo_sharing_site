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
            <h1>Cadastro</h1>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Cadastro</a></li>
            </ul>
        </nav>
        <form action="<?php htmlspecialchars(($_SERVER['PHP_SELF'])); ?>" method="post">
            <label>username</label>
            <input type="text" name="username"><br>
            <label>email</label>
            <input type="text" name="email"><br>
            <label>password</label>
            <input type="password" name="password"><br>
            <input type="submit" value="REGISTER"><br>
        </form>
    </div>
</body>
</html>
<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        if (empty($username) || empty($username) || empty($password)) {
            echo "Username or password are empty.";
        }
        else {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hash')";
           if (mysqli_query($conn, $sql)) {
                echo "User registered successfully.";
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    }
?>