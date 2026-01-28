<?php
// auth/login_action.php

// NÃO precisa de session_start() aqui porque o config.php já faz isso.
require '../config.php';

// Filtra os dados de entrada
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
$redirect = filter_input(INPUT_POST, 'redirect', FILTER_SANITIZE_URL);

// Validação básica
if (!$username || !$password) {
    header("Location: login.php?erro=1");
    exit;
}

try {
    // CORREÇÃO AQUI: Tabela 'user' e coluna 'password_hash'
    $stmt = $db_users->prepare("SELECT id, username, password_hash FROM user WHERE username = :u LIMIT 1");
    $stmt->bindValue(':u', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica a senha usando o hash correto
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // --- LOGIN SUCESSO ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirecionamento Inteligente
        if (!empty($redirect)) {
            header("Location: $redirect");
        } else {
            // Caminho padrão (ajuste se necessário)
            header("Location: ../Recebimentos/recebimentos.php");
        }
        exit;

    } else {
        // --- LOGIN FALHA ---
        // Mantém o redirect na URL para tentar de novo
        $url_volta = "login.php?erro=1";
        if (!empty($redirect)) {
            $url_volta .= "&redirect=" . urlencode($redirect);
        }
        
        header("Location: $url_volta");
        exit;
    }

} catch (PDOException $e) {
    // Em caso de erro no banco
    header("Location: login.php?erro=1");
    exit;
}
?>