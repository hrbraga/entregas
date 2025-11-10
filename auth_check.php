<?php
// auth_check.php (Versão Corrigida)

// O 'config.php' já deve ter sido incluído pela página que chamou este script.
// Apenas verificamos se a sessão existe.
if (session_status() == PHP_SESSION_NONE) {
    // Se a sessão não foi iniciada, algo está errado.
    // Inicia-a, mas isto é uma salvaguarda.
    session_start();
}

// Se não houver ID de utilizador na sessão, redireciona para o login e para a execução.
if (!isset($_SESSION['user_id'])) {
    // Se for uma chamada de API (como o get_data.php), devolve um erro 401
    // Se for uma página normal, redireciona.
    if (!headers_sent()) {
        http_response_code(401); // Não Autorizado
    }
    // Para chamadas de API, o JS vai tratar o 401. Para páginas, fazemos o header.
    header('Location: login.php');
    exit;
}
?>