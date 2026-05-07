<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');
require_once '../config.php'; 

$codigo = $_GET['codigo'] ?? null;

if (empty($codigo)) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['erro' => 'Nenhum código fornecido']);
    exit;
}

try {
    $sql = "SELECT nome_produto, preco_venda AS preco1, preco2 
            FROM produtos_unificados 
            WHERE codigo_barras = ? OR codigo_interno = ?";
    
    $stmt = $db_produtos->prepare($sql);
    $stmt->execute([$codigo, $codigo]);
    
    $produto = $stmt->fetch();

    if ($produto) {
        echo json_encode($produto);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['erro' => 'Produto não encontrado']);
    }

} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>