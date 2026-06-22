<?php
// Ligar a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... aqui continua o seu require 'phpmailer/Exception.php'; etc ...
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // ==========================================
    // 1. CONFIGURAÇÕES DO SERVIDOR HOSTGATOR
    // ==========================================
    $mail->isSMTP();

    // Na Hostgator, o Host costuma ser mail.teudominio.com.br (ou o nome do servidor cPanel, ex: br123.hostgator.com.br)
    $mail->Host       = 'sh00102.hostgator.com.br';

    $mail->SMTPAuth   = true;
    $mail->Username   = 'robozinho@caixadeferramentascs.online'; // O teu e-mail completo da Hostgator
    $mail->Password   = 'Cshugo*20'; // A senha normal que usas para aceder a esse e-mail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    // ==========================================
    // 2. REMETENTE E DESTINATÁRIO
    // ==========================================
    // Quem está a enviar (Deve ser o mesmo e-mail do Username acima)
    $mail->setFrom('robozinho@caixadeferramentascs.online', 'Robozinho da Extranet');

    // Para onde o alerta vai ser enviado (pode ser o teu Gmail pessoal, para leres no telemóvel)
    $mail->addAddress('cacaushowcp@gmail.com', 'Hugo');

    // ==========================================
    // 3. CONTEÚDO DA MENSAGEM
    // ==========================================
    $mail->isHTML(true);
    $mail->Subject = '🚀 Teste Hostgator Bem-Sucedido!';
    $mail->Body    = '<b>Olá Hugo!</b> Se estás a ler isto, o PHPMailer conectou-se à Hostgator e o envio está a funcionar perfeitamente!';

    // Dispara o e-mail
    $mail->send();
    echo "<h2 style='color: green;'>✅ Sucesso Absoluto! O e-mail foi enviado de verdade pela Hostgator.</h2>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Falha no envio.</h2>";
    echo "Detalhe do erro: {$mail->ErrorInfo}";
}
