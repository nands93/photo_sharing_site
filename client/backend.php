<?php
    require_once __DIR__ . '/vendor/autoload.php';
    
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

    function validate_photo_id($photo_id) {
        return is_numeric($photo_id) && $photo_id > 0 && $photo_id <= PHP_INT_MAX;
    }

    function validate_comment_text($comment) {
        $comment = trim($comment);
        
        // Verificar tamanho
        if (empty($comment) || strlen($comment) > 500) {
            return false;
        }
        
        // Verificar caracteres perigosos
        if (preg_match('/<script|javascript:|data:|vbscript:|on\w+\s*=/i', $comment)) {
            return false;
        }
        
        // Verificar SQL injection patterns
        if (preg_match('/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bUPDATE\b|\bDROP\b)/i', $comment)) {
            return false;
        }
        
        return true;
    }

    function sanitize_filename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = trim($filename, '.');
        return substr($filename, 0, 255);
    }

    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    function generate_csrf_token() {
        // Gerar token único por formulário
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time();
        
        // Limpar tokens expirados
        $now = time();
        foreach ($_SESSION['csrf_tokens'] as $stored_token => $timestamp) {
            if (($now - $timestamp) > 3600) {
                unset($_SESSION['csrf_tokens'][$stored_token]);
            }
        }
        
        return $token;
    }

    function verify_csrf_token($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        $timestamp = $_SESSION['csrf_tokens'][$token];
        if ((time() - $timestamp) > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        // Consider if this token cleanup is affecting other session data
        return true;
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

    function check_rate_limit($action, $max_attempts, $time_window) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $action . '_attempts_' . $ip;
        $time_key = $action . '_time_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
            $_SESSION[$time_key] = time();
        }
        
        $current_time = time();

        if ($current_time - $_SESSION[$time_key] > $time_window) {
            $_SESSION[$key] = 0;
            $_SESSION[$time_key] = $current_time;
        }

        if ($_SESSION[$key] >= $max_attempts) {
            error_log("Rate limit exceeded for action: $action, IP: $ip, Attempts: " . $_SESSION[$key] . ", Window: $time_window seconds");
            return false;
        }
        
        $_SESSION[$key]++;
        
        return true;
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
                error_log("Authentication success for user.");
                
                if (!$user['email_verified']) {
                    return ['error' => 'email_not_verified'];
                }
                return $user;
            } else {
                if ($user) {
                    error_log("Password verification failed.");
                }
            }
        }
        return false;
    }

    function security_log($action, $details = []) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $user_id = $_SESSION['user_id'] ?? 'anonymous';
        $timestamp = date('Y-m-d H:i:s');
        
        $log_entry = [
            'timestamp' => $timestamp,
            'action' => $action,
            'user_id' => $user_id,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'details' => $details
        ];
        
        error_log("SECURITY: " . json_encode($log_entry));
    }

    function cleanup_session() {
        if (isset($_SESSION['csrf_tokens'])) {
            $now = time();
            foreach ($_SESSION['csrf_tokens'] as $token => $timestamp) {
                if (($now - $timestamp) > 3600) {
                    unset($_SESSION['csrf_tokens'][$token]);
                }
            }
        }
        
        $current_time = time();
        foreach ($_SESSION as $key => $value) {
            if (preg_match('/_time_/', $key)) {
                $attempts_key = str_replace('_time_', '_attempts_', $key);
                if (isset($_SESSION[$key]) && ($current_time - $_SESSION[$key]) > 3600) {
                    unset($_SESSION[$key]);
                    if (isset($_SESSION[$attempts_key])) {
                        unset($_SESSION[$attempts_key]);
                    }
                }
            }
        }
    }
?>