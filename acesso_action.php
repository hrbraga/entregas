<?php
require 'config.php'; // Liga-se ao config (sessão e $db_users)

$codigo_rcky = $_POST['codigo'] ?? '';

if (empty($codigo_rcky)) {
    header('Location: inicio.php?erro=Por favor, digite um código.');
    exit;
}

try {
    // Procura o código RCKY na coluna 'username' do banco de dados de usuários
    // Assumimos que o "código" é o "username" que você cadastra manualmente.
    $stmt = $db_users->prepare("SELECT id, username FROM user WHERE username = ?");
    $stmt->execute([$codigo_rcky]);
    $user = $stmt->fetch();

    if ($user) {
        // Usuário encontrado!
        session_regenerate_id(true); // Proteção contra fixação de sessão

        // Criamos uma SESSÃO SEPARADA para o sistema de custos
        $_SESSION['custos_user_id'] = $user['id'];
        $_SESSION['custos_username'] = $user['username'];

        // Redireciona para a nova página de ferramentas
        header('Location: selecao_ferramentas.php');
        exit;
    } else {
        // Usuário não encontrado
        header('Location: inicio.php?erro=Código de acesso inválido.');
        exit;
    }

} catch (Exception $e) {
    // Em caso de erro de DB, envia uma mensagem genérica
    error_log("Erro em acesso_action.php: " . $e->getMessage()); // Log para si
    header('Location: inicio.php?erro=Ocorreu um erro no servidor.');
    exit;
}
?>