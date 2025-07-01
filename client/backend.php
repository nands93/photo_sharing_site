<?php
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();

    $db_server = $_ENV["DB_SERVER"];
    $db_user = $_ENV["MYSQL_USER"];
    $db_password = $_ENV["MYSQL_PASSWORD"];
    $db_name = $_ENV["MYSQL_DATABASE"];
    $db_port = $_ENV["DB_PORT"];
    if (!$db_server || !$db_user || !$db_password || !$db_name || !$db_port) {
        die("Erro ao carregar as configurações do banco de dados.");
    }

    try {
        $conn = mysqli_connect($db_server, $db_user, $db_password, $db_name, $db_port);
        
        if (!$conn) {
            error_log("Database connection failed: " . mysqli_connect_error());
            die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
        }
        
        mysqli_set_charset($conn, "utf8mb4");
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
    }

    function validate_username($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username);
    }

    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    function validate_password($password) {
        return strlen($password) >= 8 && 
            preg_match('/[A-Z]/', $password) && 
            preg_match('/[a-z]/', $password) && 
            preg_match('/[0-9]/', $password) && 
            preg_match('/[^A-Za-z0-9]/', $password);
    }

    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    function user_exists($conn, $username, $email) {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Execute failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null; // Query error
        }

        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;

        mysqli_stmt_close($stmt);
        return $exists; // true if exists, false if not
    }

    function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
        $key = $action . '_attempts';
        $time_key = $action . '_last_attempt';
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
            $_SESSION[$time_key] = time();
        }
        
        if (time() - $_SESSION[$time_key] > $time_window) {
            $_SESSION[$key] = 0;
            $_SESSION[$time_key] = time();
        }
        
        $_SESSION[$key]++;
        $_SESSION[$time_key] = time();
        
        return $_SESSION[$key] <= $max_attempts;
    }

    function authenticate_user($conn, $username, $password) {
        $stmt = mysqli_prepare($conn, "SELECT id, username, email, password, email_verified FROM users WHERE username = ? OR email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($user && password_verify($password, $user['password'])) {
                // Verificar se o e-mail foi confirmado
                if (!$user['email_verified']) {
                    return ['error' => 'email_not_verified'];
                }
                return $user;
            }
        }
        return false;
    }
?>