<?php
// auth/login_action.php
require '../config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? '';


if (empty($username) || empty($password)) {
    die("ERRO: Formulário vazio.");
}

try {
    $stmt = $db_users->prepare("SELECT id, username, password_hash FROM user WHERE username = :u LIMIT 1");
    $stmt->bindValue(':u', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        
        $id_real = $user['id'];
        $username_atual = $user['username'];
        
        // --- A MÁGICA DO VÍNCULO DE DADOS ---
        if (strpos($username_atual, 'loja-') === 0) {
            // É um funcionário! Vamos descobrir quem é o Franqueado Dono dele.
            $username_dono = substr($username_atual, 5); // Tira o 'loja-'
            
            $stmt_dono = $db_users->prepare("SELECT id FROM user WHERE username = ?");
            $stmt_dono->execute([$username_dono]);
            $dono = $stmt_dono->fetch(PDO::FETCH_ASSOC);
            
            // O ID da sessão passa a ser o do DONO, para que o funcionário veja as mesmas Validades
            $id_sessao = $dono ? $dono['id'] : $id_real; 
        } else {
            // É o Franqueado ou o Admin. O ID é o dele mesmo.
            $id_sessao = $id_real;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // As variáveis que controlam o sistema inteiro
        $_SESSION['user_id'] = $id_sessao; 
        $_SESSION['username'] = $username_atual; 
        $_SESSION['perfil'] = $user['perfil'];

        // --- REDIRECIONAMENTO INTELIGENTE ---
        $redirect_memoria = $_SESSION['redirect_apos_login'] ?? '';
        
        if (!empty($redirect_memoria)) {
            unset($_SESSION['redirect_apos_login']);
            header("Location: " . $redirect_memoria);
        } elseif (!empty($redirect)) {
            header("Location: $redirect");
        } else {
            if (strpos($username_atual, 'loja-') === 0) {
                header("Location: ../selecao_ferramentas.php"); // Loja vai para as ferramentas
            } else {
                header("Location: ../Recebimentos/recebimentos.php"); // Franqueado vai para o financeiro
            }
        }
        exit;

    } else {
        $url_volta = "login.php?erro=1";
        if (!empty($redirect)) $url_volta .= "&redirect=" . urlencode($redirect);
        header("Location: $url_volta");
        exit;
    }

} catch (Exception $e) {
    die("Erro Interno: " . $e->getMessage());
}
?>