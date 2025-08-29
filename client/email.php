<?php

require __DIR__ . '/vendor/autoload.php';
require_once 'backend.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function get_mailer() {
    static $mail = null;
    if ($mail === null) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->safeLoad();
            $gmail_email = $_ENV['GMAIL_EMAIL'];
            $gmail_password = $_ENV['GMAIL_APP_PASSWORD'];

            if (!$gmail_email || !$gmail_password) {
                error_log("Gmail credentials not found in environment variables.");
                return null;
            }
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $gmail_email;
            $mail->Password   = $gmail_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom($gmail_email, 'Camagru');
            
        } catch (Exception $e) {
            error_log("Error initializing PHPMailer: " . $e->getMessage());
            return null;
        }
    }
    return $mail;
}

function confirmation_email($email, $username, $token) {
    $mail = get_mailer();
    if (!$mail) {
        return false;
    }
    
    $confirmation_link = "http://localhost:8080/confirm.php?token=$token";
    
    try {
        $mail->clearAddresses();
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Confirm your email for Camagru';
        $mail->Body    = "
            <h2>Welcome to Camagru, $username!</h2>
            <p>Thank you for registering. Please confirm your email address by clicking the link below:</p>
            <a href='$confirmation_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirm Email</a>
            <p>If you did not sign up for this account, you can ignore this email.</p>
            <p>Thank you for using Camagru!</p>
        ";

        $mail->send();
        error_log("Email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer exception: " . $mail->ErrorInfo);
        return false;
    }
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

function send_confirmation_email($email, $username, $token) {
    return confirmation_email($email, $username, $token);
}

function reset_password_email($email, $token) {
    global $conn;
    $mail = get_mailer();
    if (!$mail) {
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

    try {
        $mail->clearAddresses();
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Reset your password on Camagru';
        $mail->Body    = "
            <h2>Hello $username,</h2>
            <p>We received a request to reset your password. If you did not make this request, you can ignore this email.</p>
            <p>If you want to reset your password, click the link below:</p>
            <a href='$reset_password_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
            <p>If you have any questions, feel free to contact our support team.</p>
            <p>Thank you for using Camagru!</p>
        ";
        
        $mail->send();
        error_log("Email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer exception: " . $mail->ErrorInfo);
        return false;
    }
}

function comment_notification($owner_email, $photo_owner_username, $commenter_username, $comment_text) {
    $mail = get_mailer();
    if (!$mail) {
        return false;
    }
    
   try {
        $mail->clearAddresses();
        $mail->addAddress($owner_email, $photo_owner_username);
        $mail->isHTML(true);
        $mail->Subject = "New comment on your photo - Camagru";
        $mail->Body    = "
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
        
        $mail->send();
        error_log("Comment notification email sent successfully to: $owner_email");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer exception: " . $mail->ErrorInfo);
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