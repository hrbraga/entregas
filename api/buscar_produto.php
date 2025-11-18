<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Inclui seu arquivo de configuração
// Sobe um nível (de 'api' para 'entradas') e pega o config.php
require_once '../config.php'; 

// Pega o código enviado pelo JavaScript (via GET)
$codigo = $_GET['codigo'] ?? null; // Usamos ?? null para evitar erro se não for enviado

if (empty($codigo)) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['erro' => 'Nenhum código fornecido']);
    exit;
}

try {
 
    $sql = "SELECT nome_produto, preco1, preco2 FROM produtos WHERE codigo_barras = ? OR codigo_interno = ?";
    
    $stmt = $db_produtos->prepare($sql);
    $stmt->execute([$codigo, $codigo]);
    
    $produto = $stmt->fetch();

    if ($produto) {
        // Se encontrou, devolve os dados em JSON
        echo json_encode($produto);
    } else {
        // Se não encontrou, devolve um erro 404
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['erro' => 'Produto não encontrado']);
    }

} catch (PDOException $e) {
    // Em caso de erro de banco, informa o servidor
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>