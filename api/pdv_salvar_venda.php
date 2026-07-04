<?php
session_start();
require '../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($_SESSION['user_id']) || !$data) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado ou dados vazios.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Acha o turno que está aberto e pega o evento_id
    $stmtTurno = $db_financeiro->prepare("SELECT id, evento_id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
    $stmtTurno->execute([$user_id]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    
    if(!$turno) throw new Exception("Nenhum caixa aberto encontrado.");
    
    $evento_id = (int)$turno['evento_id'];

    // 2. Salva a "Capa" da Venda com o evento_id
    $stmtVenda = $db_financeiro->prepare("INSERT INTO pdv_vendas (turno_id, user_id, subtotal, desconto, acrescimo, total, forma_pagamento, evento_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtVenda->execute([
        $turno['id'], 
        $user_id, 
        $data['subtotal'], 
        $data['desconto'], 
        $data['acrescimo'], 
        $data['total_final'], 
        $data['metodo'],
        $evento_id
    ]);
    
    $venda_id = $db_financeiro->lastInsertId();

    // 3. Prepara as queries de Itens e de Baixa de Estoque
    $stmtItem = $db_financeiro->prepare("INSERT INTO pdv_itens (venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmtUpEstoque = $db_financeiro->prepare("UPDATE pdv_estoque_evento SET quantidade_atual = quantidade_atual - ? WHERE evento_id = ? AND produto_id = ?");

    // 4. Loop para salvar itens e diminuir o estoque
    foreach($data['itens'] as $item) {
        $produto_id = isset($item['id']) && is_numeric($item['id']) ? $item['id'] : 0;
        $qtd = $item['quantidade'];
        
        // Salva o item no cupom da venda
        $stmtItem->execute([$venda_id, $produto_id, $qtd, $item['preco'], $item['subtotal']]);
        
        // Se a venda pertencer a um evento, dá a baixa no estoque daquele evento
        if ($evento_id > 0 && $produto_id > 0) {
            $stmtUpEstoque->execute([$qtd, $evento_id, $produto_id]);
        }
    }

    echo json_encode(['success' => true, 'venda_id' => $venda_id]);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>