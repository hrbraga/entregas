<?php
require_once '../config.php';
require_once 'auth_franqueado_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'];

    try {
        $db_users->beginTransaction();

        // 1. Descobrir se tem loja vinculada antes de excluir
        $stmtGet = $db_users->prepare("SELECT id_loja FROM user WHERE id = :id_usuario");
        $stmtGet->execute([':id_usuario' => $id_usuario]);
        $id_loja = $stmtGet->fetchColumn();

        // 2. Excluir o login
        $stmtUser = $db_users->prepare("DELETE FROM user WHERE id = :id_usuario");
        $stmtUser->execute([':id_usuario' => $id_usuario]);

        // 3. Se tiver loja vinculada, excluir a loja também
        if ($id_loja) {
            $stmtLoja = $db_users->prepare("DELETE FROM lojas WHERE id = :id_loja");
            $stmtLoja->execute([':id_loja' => $id_loja]);
        }

        $db_users->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Excluído com sucesso!']);
    } catch (Exception $e) {
        if ($db_users->inTransaction()) {
            $db_users->rollBack();
        }
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir: ' . $e->getMessage()]);
    }
}
?>