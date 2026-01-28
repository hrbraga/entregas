<?php
// auth/auth_check.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    
    // Se for uma requisição AJAX, retorna erro 401
    if (!headers_sent()) {
        // Verifica se é ajax (opcional, mas boa prática)
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            exit;
        }
    }

    // --- AQUI ESTÁ A MUDANÇA ---
    // Pega a URL que o usuário tentou acessar
    $pagina_atual = $_SERVER['REQUEST_URI'];
    
    // Manda para o login levando a URL junto
    header('Location: ../auth/login.php?redirect=' . urlencode($pagina_atual));
    exit;
}
?>