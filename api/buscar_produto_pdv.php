<?php
// api/buscar_produto.php
header('Content-Type: application/json');

$termo = $_GET['q'] ?? '';

if (empty($termo)) {
    echo json_encode(['success' => false, 'error' => 'Termo vazio']);
    exit;
}

try {
    // Conecta ao banco SQLite de produtos
    // Ajuste o caminho '../db/produtos.db' conforme a sua pasta real
    $db_produtos = new PDO('sqlite:../db/produtos.db');
    $db_produtos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca exata pelo código de barras OU aproximada pelo nome
    $stmt = $db_produtos->prepare("SELECT id, nome, preco FROM produtos WHERE codigo_barras = :termo OR nome LIKE :termo_like LIMIT 1");
    $stmt->execute([
        ':termo' => $termo,
        ':termo_like' => "%$termo%"
    ]);

    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        echo json_encode(['success' => true, 'produto' => $produto]);
    } else {
        echo json_encode(['success' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>