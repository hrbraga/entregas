<?php
// api/delete_nota.php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$numero_nota = $_GET['numero_nota'] ?? ''; // Recebe pela URL

if (empty($numero_nota)) {
    echo json_encode(['success' => false, 'message' => 'Número da nota não fornecido.']);
    exit;
}

$db_entregas->beginTransaction();
try {
    // 1. Encontrar a nota
    $stmt_find = $db_entregas->prepare("SELECT id FROM nota_fiscal WHERE numero_nota = ? AND user_id = ?");
    $stmt_find->execute([$numero_nota, $user_id]);
    $nota = $stmt_find->fetch();

    if (!$nota) {
        throw new Exception("Nota fiscal não encontrada ou não pertence ao seu usuário.");
    }
    $nota_id = $nota['id'];

    // 2. Preparar statements
    $stmt_find_itens = $db_entregas->prepare("SELECT codigo_sap, quantidade FROM item_nota_fiscal WHERE nota_id = ?");
    $stmt_update_stock = $db_entregas->prepare(
        "UPDATE item_entrega 
         SET recebido = recebido - ?, a_receber = a_receber + ?
         WHERE codigo_sap = ? AND user_id = ?"
    );
    $stmt_delete_itens_nota = $db_entregas->prepare("DELETE FROM item_nota_fiscal WHERE nota_id = ?");
    $stmt_delete_nota = $db_entregas->prepare("DELETE FROM nota_fiscal WHERE id = ?");

    // 3. Encontrar todos os itens da nota
    $stmt_find_itens->execute([$nota_id]);
    $itens_da_nota = $stmt_find_itens->fetchAll();

    // 4. Reverter o stock para cada item
    foreach ($itens_da_nota as $item) {
        $stmt_update_stock->execute([
            $item['quantidade'], 
            $item['quantidade'], 
            $item['codigo_sap'], 
            $user_id
        ]);
    }

    // 5. Apagar os registos da nota
    $stmt_delete_itens_nota->execute([$nota_id]);
    $stmt_delete_nota->execute([$nota_id]);

    $db_entregas->commit();
    echo json_encode(['success' => true, 'message' => "Nota Fiscal {$numero_nota} excluída! O stock foi ajustado."]);

} catch (Exception $e) {
    $db_entregas->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir a nota: ' . $e->getMessage()]);
}
?>