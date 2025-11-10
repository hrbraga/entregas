<?php
// api/delete_item.php
require '../config.php';
require '../auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$item_id = $_GET['id'] ?? 0;

if (empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do item não fornecido.']);
    exit;
}

try {
    $stmt = $db_entregas->prepare("DELETE FROM item_entrega WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Item excluído com sucesso!']);
    } else {
        throw new Exception("Item não encontrado ou não pertence ao seu usuário.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>