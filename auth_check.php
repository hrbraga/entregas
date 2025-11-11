<?php
// auth_check.php (Versão ATUALIZADA)
// Protege o Nível 2 (Aplicação de Entregas)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Aqui SÓ precisamos de verificar o login Nível 2 (o da loja).
// A proteção de Nível 1 (RCKY) já foi feita pelo 'selecao_ferramentas.php'.

if (!isset($_SESSION['user_id'])) {
    
    // Se o utilizador não está logado numa loja, mandamos para o login.php
    if (!headers_sent()) {
        http_response_code(401); // Para o script.js
    }
    header('Location: login.php'); // Para o navegador
    exit;
}
?>