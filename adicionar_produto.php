<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

echo "<h1>Ferramenta para Adicionar Produto</h1>";

// ****** EDITE AQUI OS DADOS DO SEU NOVO PRODUTO ******
$produto_novo = [
    'codigo_barras'  => '7891112223334',   // Use NULL se não tiver
    'codigo_interno' => 'TRUFA-BRANCA',    // Use NULL se não tiver
    'nome_produto'   => 'Cartao Trufa Branca Recheada',
    'preco1'         => 3.50,
    'preco2'         => 2.99
];
// ******************************************************

// Validação básica
if (empty($produto_novo['nome_produto']) || ($produto_novo['codigo_barras'] === NULL && $produto_novo['codigo_interno'] === NULL)) {
    die("<p style='color: red;'>Erro: O produto deve ter um NOME e pelo menos um CÓDIGO (interno ou barras).</p>");
}
if ($produto_novo['preco2'] > $produto_novo['preco1']) {
     die("<p style='color: red;'>Erro: O Preço 2 (R$ {$produto_novo['preco2']}) não pode ser maior que o Preço 1 (R$ {$produto_novo['preco1']}).</p>");
}

// SQL para inserir
$sql = "INSERT INTO produtos (codigo_barras, codigo_interno, nome_produto, preco1, preco2) 
        VALUES (?, ?, ?, ?, ?)";

try {
    $stmt = $db_produtos->prepare($sql);
    
    // Executa o SQL passando os valores do array na ordem correta
    $stmt->execute([
        $produto_novo['codigo_barras'],
        $produto_novo['codigo_interno'],
        $produto_novo['nome_produto'],
        $produto_novo['preco1'],
        $produto_novo['preco2']
    ]);

    echo "<p style='color: green;'>Sucesso! O produto '<strong>" . htmlspecialchars($produto_novo['nome_produto']) . "</strong>' foi criado.</p>";
    echo "<p>Você já pode ir na página de etiquetas e buscar pelo código '<strong>" . htmlspecialchars($produto_novo['codigo_interno'] ?? $produto_novo['codigo_barras']) . "</strong>'.</p>";

} catch (PDOException $e) {
    if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
        // Código 23000 (SQLite) é para violação de chave (UNIQUE)
        echo "<p style='color: red;'>Erro: O código de barras ou o código interno informado já existe no banco de dados.</p>";
    } else {
        echo "<p style='color: red;'>Erro ao inserir: " . $e->getMessage() . "</p>";
    }
}

?>