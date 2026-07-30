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
    // 1. Adicionado o id_loja no SELECT para sabermos qual loja consultar
    $stmt = $db_users->prepare("SELECT id, username, password_hash, perfil, id_dono, id_loja FROM user WHERE username = :u LIMIT 1");
    $stmt->bindValue(':u', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {

        // ==========================================
        // TRAVA DE SEGURANÇA: LOJA INATIVA
        // ==========================================
        // Verifica se é colaborador e se tem um id_loja vinculado
        if ($user['perfil'] === 'colaborador' && !empty($user['id_loja'])) {
            
            $stmtLoja = $db_users->prepare("SELECT ativo FROM lojas WHERE id = ?");
            $stmtLoja->execute([$user['id_loja']]);
            $lojaAtiva = $stmtLoja->fetchColumn();

            // Se o status retornado for 0 (Inativo), barra o login
            if ($lojaAtiva !== false && $lojaAtiva == 0) {
                header('Location: login.php?erro=loja_inativa');
                exit;
            }
        }
        // ==========================================

        // 2. Grava os dados na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['perfil'] = $user['perfil'];

        // 3. Define o vínculo de dono
        $_SESSION['id_sessao'] = (!empty($user['id_dono'])) ? $user['id_dono'] : $user['id'];

        header('Location: ../inicio.php');
        exit;
    } else {
        // Login falhou
        header('Location: login.php?erro=1');
        exit;
    }
} catch (Exception $e) {
    die("Erro Interno: " . $e->getMessage());
}
?>