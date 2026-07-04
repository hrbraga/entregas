<?php
ini_set('display_errors', 0); error_reporting(0); header('Content-Type: application/json');
require '../config.php';

try {
    $evento_id = $_GET['evento_id'] ?? 0;
    
    $caminho_produtos = str_replace('\\', '/', dirname(__DIR__)) . '/db/produtos.db';
    $db_financeiro->exec("ATTACH DATABASE '$caminho_produtos' AS p_db");
    
    $stmt = $db_financeiro->prepare("
        SELECT e.id, e.produto_id, p.nome_produto, p.codigo_interno, e.quantidade_atual 
        FROM pdv_estoque_evento e 
        JOIN p_db.produtos_unificados p ON e.produto_id = p.id 
        WHERE e.evento_id = ? 
        ORDER BY p.nome_produto ASC
    ");
    $stmt->execute([$evento_id]);
    
    echo json_encode(['success' => true, 'estoque' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>