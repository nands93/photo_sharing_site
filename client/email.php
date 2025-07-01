<?php

require_once 'vendor/autoload.php';

use SendGrid\Mail\Mail;

function send_confirmation_email_sendgrid($to, $username, $token) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    $api_key = $_ENV['SENDGRID_API_KEY'];
    if (!$api_key) {
        error_log("SendGrid API key not set in environment variables.");
        return false;
    }
    $confirmation_link = "http://localhost:8080/confirm.php?token=$token";
    
    $email = new Mail();
    $email->setFrom("femarquedev@gmail.com", "femarque");
    $email->setSubject("Confirme seu cadastro no Camagru");
    $email->addTo($to, $username);
    $email->addContent(
        "text/html", 
        "
            <h2>Bem-vindo ao Camagru, $username!</h2>
            <p>Para ativar sua conta, clique no link abaixo:</p>
            <a href='$confirmation_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Confirmar Cadastro</a>
        "
    );

    $sendgrid = new \SendGrid($api_key);
    
    try {
        $response = $sendgrid->send($email);
        
        if ($response->statusCode() == 202) {
            error_log("Email sent successfully to: $to");
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

function generate_confirmation_token() {
    return bin2hex(random_bytes(32));
}

// Função principal para enviar confirmação
function send_confirmation_email($to, $username, $token) {
    return send_confirmation_email_sendgrid($to, $username, $token);
}
?>