<?php
require_once '../config.php';
require_once 'auth_franqueado_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'];
    $novo_id_franqueado = $_POST['novo_id_franqueado'];

    try {
        $db_users->beginTransaction();

        // 1. Descobrir se este usuário tem uma loja vinculada
        $stmtGet = $db_users->prepare("SELECT id_loja FROM user WHERE id = :id_usuario");
        $stmtGet->execute([':id_usuario' => $id_usuario]);
        $id_loja = $stmtGet->fetchColumn();

        // 2. Atualizar o dono do login
        $stmtUser = $db_users->prepare("UPDATE user SET id_dono = :novo_id_franqueado WHERE id = :id_usuario");
        $stmtUser->execute([
            ':novo_id_franqueado' => $novo_id_franqueado, 
            ':id_usuario' => $id_usuario
        ]);

        // 3. Se tiver loja vinculada, atualiza também a loja
        if ($id_loja) {
            $stmtLoja = $db_users->prepare("UPDATE lojas SET id_franqueado = :novo_id_franqueado WHERE id = :id_loja");
            $stmtLoja->execute([
                ':novo_id_franqueado' => $novo_id_franqueado, 
                ':id_loja' => $id_loja
            ]);
        }

        $db_users->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Franqueado alterado com sucesso!']);
    } catch (Exception $e) {
        if ($db_users->inTransaction()) {
            $db_users->rollBack();
        }
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao transferir: ' . $e->getMessage()]);
    }
}
?>