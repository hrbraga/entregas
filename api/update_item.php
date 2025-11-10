<?php
// api/update_item.php
require '../config.php';
require '../auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

// Lê o JSON enviado pelo JS
$data = json_decode(file_get_contents('php://input'), true);

$item_id = $data['id'] ?? 0;
$pedido_loja = $data['pedido_loja'] ?? 0;
$pedido_vd = $data['pedido_vd'] ?? 0;

if (empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do item não fornecido.']);
    exit;
}

$db_entregas->beginTransaction();
try {
    // 1. Busca o valor 'recebido' atual
    $stmt_find = $db_entregas->prepare("SELECT recebido FROM item_entrega WHERE id = ? AND user_id = ?");
    $stmt_find->execute([$item_id, $user_id]);
    $item = $stmt_find->fetch();

    if (!$item) {
        throw new Exception("Item não encontrado.");
    }
    $recebido = (int)$item['recebido'];
    
    // 2. Calcula os novos totais
    $total_caixa = $pedido_loja + $pedido_vd;
    $a_receber = $total_caixa - $recebido; // O 'a_receber' é recalculado

    // 3. Atualiza o item
    $stmt_update = $db_entregas->prepare(
        "UPDATE item_entrega 
         SET pedido_loja = ?, pedido_vd = ?, total_caixa = ?, a_receber = ?
         WHERE id = ? AND user_id = ?"
    );
    $stmt_update->execute([$pedido_loja, $pedido_vd, $total_caixa, $a_receber, $item_id, $user_id]);
    
    $db_entregas->commit();
    echo json_encode(['success' => true, 'message' => 'Item atualizado com sucesso!']);

} catch (Exception $e) {
    $db_entregas->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
?>