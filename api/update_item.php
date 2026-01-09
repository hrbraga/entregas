<?php
// api/update_item.php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

// Lê o JSON enviado pelo JS
$data = json_decode(file_get_contents('php://input'), true);

$item_id = $data['id'] ?? 0;

// Recebe os valores do JSON (se não vierem, definimos como null para tratar depois)
$pedido_loja_input = isset($data['pedido_loja']) ? $data['pedido_loja'] : null;
$pedido_vd_input   = isset($data['pedido_vd']) ? $data['pedido_vd'] : null;
$grupo_input       = isset($data['grupo']) ? $data['grupo'] : null;
$item_nome_input   = isset($data['item']) ? $data['item'] : null;
$codigo_sap_input  = isset($data['codigo_sap']) ? $data['codigo_sap'] : null;

if (empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do item não fornecido.']);
    exit;
}

$db_entregas->beginTransaction();
try {
    // 1. Busca os dados ATUAIS do item no banco (incluindo textos e recebido)
    // Isso é importante para manter o valor antigo caso o JSON não envie algum campo
    $stmt_find = $db_entregas->prepare("SELECT recebido, pedido_loja, pedido_vd, grupo, item, codigo_sap FROM item_entrega WHERE id = ? AND user_id = ?");
    $stmt_find->execute([$item_id, $user_id]);
    $item_atual = $stmt_find->fetch();

    if (!$item_atual) {
        throw new Exception("Item não encontrado.");
    }

    // 2. Define os valores finais (Usa o que veio do input, senão usa o que já estava no banco)
    // Para números, usamos input ou 0. Para texto, usamos input ou o valor do banco.
    $novo_pedido_loja = $pedido_loja_input !== null ? $pedido_loja_input : $item_atual['pedido_loja'];
    $novo_pedido_vd   = $pedido_vd_input   !== null ? $pedido_vd_input   : $item_atual['pedido_vd'];
    
    $novo_grupo       = $grupo_input       !== null ? $grupo_input       : $item_atual['grupo'];
    $novo_nome_item   = $item_nome_input   !== null ? $item_nome_input   : $item_atual['item'];
    $novo_codigo_sap  = $codigo_sap_input  !== null ? $codigo_sap_input  : $item_atual['codigo_sap'];

    $recebido = (int)$item_atual['recebido'];
    
    // 3. Recalcula os totais matemáticos
    $total_caixa = $novo_pedido_loja + $novo_pedido_vd;
    $a_receber = $total_caixa - $recebido; // O 'a_receber' é recalculado automaticamente

    // 4. Atualiza TUDO no banco de dados (Números e Textos)
    $stmt_update = $db_entregas->prepare(
        "UPDATE item_entrega 
         SET pedido_loja = ?, 
             pedido_vd = ?, 
             total_caixa = ?, 
             a_receber = ?,
             grupo = ?,
             item = ?,
             codigo_sap = ?
         WHERE id = ? AND user_id = ?"
    );

    $stmt_update->execute([
        $novo_pedido_loja, 
        $novo_pedido_vd, 
        $total_caixa, 
        $a_receber, 
        $novo_grupo,      // Salva o Grupo
        $novo_nome_item,  // Salva o Nome do Item
        $novo_codigo_sap, // Salva o Código SAP
        $item_id, 
        $user_id
    ]);
    
    $db_entregas->commit();
    echo json_encode(['success' => true, 'message' => 'Item atualizado com sucesso!']);

} catch (Exception $e) {
    $db_entregas->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
?>