<?php

require_once 'vendor/autoload.php';
require_once 'backend.php';

use SendGrid\Mail\Mail;

function confirmation_email($email, $username, $token) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $api_key = $_ENV['SENDGRID_API_KEY'];
    if (!$api_key) {
        error_log("SendGrid API key not set in environment variables.");
        return false;
    }
    $confirmation_link = "http://localhost:8080/confirm.php?token=$token";
    
    $mail = new Mail();
    $mail->setFrom("femarquedev@gmail.com", "femarque");
    $mail->setSubject("Confirme seu cadastro no Camagru");
    $mail->addTo($email, $username);
    $mail->addContent(
        "text/html", 
        "
            <h2>Bem-vindo ao Camagru, $username!</h2>
            <p>Para ativar sua conta, clique no link abaixo:</p>
            <a href='$confirmation_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar Cadastro</a>
        "
    );

    $sendgrid = new \SendGrid($api_key);
    
    try {
        $response = $sendgrid->send($mail);
        
        if ($response->statusCode() == 202) {
            error_log("Email sent successfully to: $email");
            return true;
        } else {
            error_log("SendGrid API error. Status Code: " . $response->statusCode() . ", Body: " . $response->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("SendGrid exception: " . $e->getMessage());
        return false;
    }
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

// Função principal para enviar confirmação
function send_confirmation_email($email, $username, $token) {
    return confirmation_email($email, $username, $token);
}

function reset_password_email($email, $token) {
    global $conn;
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $api_key = $_ENV['SENDGRID_API_KEY'];
    if (!$api_key) {
        error_log("SendGrid API key not set in environment variables.");
        return false;
    }

    $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $username);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    if (!$username) {
        $username = 'User';
    }

    $reset_password_link = "http://localhost:8080/reset_password.php?token=$token";
    
    $mail = new Mail();
    $mail->setFrom("femarquedev@gmail.com", "femarque");
    $mail->setSubject("Reset your password on Camagru");
    $mail->addTo($email, $username);
    $mail->addContent(
        "text/html", 
        "
            <h2>Hello $username,</h2>
            <p>We received a request to reset your password. If you did not make this request, you can ignore this email.</p>
            <p>If you want to reset your password, click the link below:</p>
            <a href='$reset_password_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
            <p>If you have any questions, feel free to contact our support team.</p>
            <p>Thank you for using Camagru!</p>
        "
    );

    $sendgrid = new \SendGrid($api_key);
    
    try {
        $response = $sendgrid->send($mail);
        
        if ($response->statusCode() == 202) {
            error_log("Email sent successfully to: $email");
            return true;
        } else {
            error_log("SendGrid API error. Status Code: " . $response->statusCode() . ", Body: " . $response->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("SendGrid exception: " . $e->getMessage());
        return false;
    }
}

function comment_notification($owner_email, $photo_owner_username, $commenter_username, $comment_text) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $api_key = $_ENV['SENDGRID_API_KEY'];
    if (!$api_key) {
        error_log("SendGrid API key not set in environment variables.");
        return false;
    }

    $subject = "New comment on your photo - Camagru";
    
    $html_content = "
    <html>
    <head>
        <title>New Comment Notification</title>
    </head>
    <body>
        <h2>You have a new comment!</h2>
        <p>Hi {$photo_owner_username},</p>
        <p><strong>{$commenter_username}</strong> commented on your photo:</p>
        <blockquote style='background-color: #f9f9f9; padding: 10px; border-left: 4px solid #007bff;'>
            " . htmlspecialchars($comment_text) . "
        </blockquote>
        <p><a href='" . get_base_url() . "/index.php'>View your photos on Camagru</a></p>
        <hr>
        <small>You can disable these notifications in your <a href='" . get_base_url() . "/profile.php'>profile settings</a>.</small>
    </body>
    </html>
    ";
    
    $mail = new Mail();
    $mail->setFrom("femarquedev@gmail.com", "Camagru");
    $mail->setSubject($subject);
    $mail->addTo($owner_email, $photo_owner_username);
    $mail->addContent("text/html", $html_content);

    $sendgrid = new \SendGrid($api_key);
    
    try {
        $response = $sendgrid->send($mail);
        
        if ($response->statusCode() == 202) {
            error_log("Comment notification email sent successfully to: $owner_email");
            return true;
        } else {
            error_log("SendGrid API error. Status Code: " . $response->statusCode() . ", Body: " . $response->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("SendGrid exception: " . $e->getMessage());
        return false;
    }
}

function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}
?>