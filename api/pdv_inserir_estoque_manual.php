<?php
ini_set('display_errors', 0); 
error_reporting(0); 
header('Content-Type: application/json');

require '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || empty($data['evento_id']) || empty($data['itens'])) {
        throw new Exception("Dados incompletos.");
    }
    
    $evento_id = $data['evento_id'];
    
    $db_financeiro->beginTransaction();
    
    foreach($data['itens'] as $item) {
        $produto_id = $item['id'];
        $qtd = $item['qtd'];

        // 1. Verifica se o produto já existe neste evento
        $stmtCheck = $db_financeiro->prepare("SELECT id FROM pdv_estoque_evento WHERE evento_id = ? AND produto_id = ?");
        $stmtCheck->execute([$evento_id, $produto_id]);
        $estoqueAtual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($estoqueAtual) {
            // 2. Se já existe, apenas SOMA as quantidades (UPDATE)
            $stmtUp = $db_financeiro->prepare("
                UPDATE pdv_estoque_evento 
                SET quantidade_inicial = quantidade_inicial + ?,
                    quantidade_atual = quantidade_atual + ?
                WHERE id = ?
            ");
            $stmtUp->execute([$qtd, $qtd, $estoqueAtual['id']]);
        } else {
            // 3. Se não existe, cria um registro novo (INSERT)
            $stmtIns = $db_financeiro->prepare("
                INSERT INTO pdv_estoque_evento (evento_id, produto_id, quantidade_inicial, quantidade_atual) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtIns->execute([$evento_id, $produto_id, $qtd, $qtd]);
        }
    }
    
    $db_financeiro->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if($db_financeiro->inTransaction()) $db_financeiro->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>