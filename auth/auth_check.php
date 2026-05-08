<?php
// auth/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não houver sessão, descobre o caminho e manda pro login
if (!isset($_SESSION['user_id'])) {
    
    // A MÁGICA: Guarda na memória a página exata que você tentou acessar
    $_SESSION['redirect_apos_login'] = $_SERVER['REQUEST_URI'];
    
    if (file_exists('login.php')) {
        $caminho = 'login.php'; 
    } elseif (file_exists('../auth/login.php')) {
        $caminho = '../auth/login.php'; 
    } else {
        $caminho = 'auth/login.php'; 
    }
    
    header("Location: " . $caminho);
    exit;
}
?>