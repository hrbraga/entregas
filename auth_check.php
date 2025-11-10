<?php
// auth_check.php (Versão Corrigida)

// O config.php já deve ter iniciado a sessão.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se não houver ID de utilizador na sessão...
if (!isset($_SESSION['user_id'])) {
    
    // Se for uma chamada de API, só queremos enviar o código de erro.
    // O JavaScript (em script.js) está programado para lidar com o 401.
    if (!headers_sent()) {
        http_response_code(401); // Não Autorizado
    }
    
    // Se for uma página (como recebimentos.php), o JS não está envolvido,
    // então podemos enviar o redirecionamento.
    // Mas como este ficheiro é usado por ambos, é mais seguro
    // APENAS parar a execução. O JavaScript vai tratar do redirecionamento.
    
    // Apenas paramos o script.
    exit;
}
?>