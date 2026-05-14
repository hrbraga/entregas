<?php
// financeiro/admin_categorias_actions.php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    // CADASTRAR
    if ($action === 'salvar_categoria') {
        $tipo = $_POST['tipo'];
        $grupo = $_POST['grupo'];
        $nome = $_POST['nome'];
        $stmt = $db_financeiro->prepare("INSERT INTO categorias_financeiras (tipo, grupo, nome) VALUES (?, ?, ?)");
        $stmt->execute([$tipo, $grupo, $nome]);
        echo json_encode(['status' => 'success', 'message' => 'Categoria cadastrada com sucesso!']);
        exit;
    }

    // EDITAR
    if ($action === 'editar_categoria') {
        $id = $_POST['id'];
        $tipo = $_POST['tipo'];
        $grupo = $_POST['grupo'];
        $nome = $_POST['nome'];
        $stmt = $db_financeiro->prepare("UPDATE categorias_financeiras SET tipo = ?, grupo = ?, nome = ? WHERE id = ?");
        $stmt->execute([$tipo, $grupo, $nome, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Categoria atualizada com sucesso!']);
        exit;
    }

    // EXCLUIR
    if ($action === 'excluir_categoria') {
        $id = $_POST['id'];
        $stmt = $db_financeiro->prepare("DELETE FROM categorias_financeiras WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Categoria removida com sucesso!']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>