<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php'; // Inclui o config COM o $db_produtos

echo "<h1>Configurador da Tabela de Produtos (no produtos.db)</h1>";

// SQL para SQLite
$sql_create = "
CREATE TABLE IF NOT EXISTS produtos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_barras TEXT UNIQUE,
    codigo_interno TEXT UNIQUE,
    nome_produto TEXT NOT NULL,
    preco1 REAL NOT NULL,
    preco2 REAL NOT NULL,
    CHECK (preco2 <= preco1),
    CHECK (codigo_barras IS NOT NULL OR codigo_interno IS NOT NULL)
);
";

// SQL para inserir dados de exemplo
// Usamos 'INSERT OR IGNORE' para não dar erro se você rodar de novo
$sql_insert = "
INSERT OR IGNORE INTO produtos 
    (codigo_barras, codigo_interno, nome_produto, preco1, preco2) 
VALUES 
    ('7890001234567', 'TRUFA-INST', 'Cartao Trufa Inst Parabens', 2.99, 2.69),
    ('7890007654321', 'BOMBOM-CEREJA', 'Bombom Recheado Cereja', 1.50, 1.25),
    (NULL, 'BARRA-40P', 'Barra Chocolate 40% Cacau', 8.00, 7.50);
";

try {
    // **AQUI ESTÁ A MUDANÇA MAIS IMPORTANTE**
    // Usamos a variável de conexão correta: $db_produtos
    
    $db_produtos->exec($sql_create);
    echo "<p style='color: green;'>Tabela 'produtos' verificada/criada com sucesso no produtos.db!</p>";
    
    $db_produtos->exec($sql_insert);
    echo "<p style='color: green;'>Dados de exemplo inseridos com sucesso no produtos.db!</p>";

} catch (PDOException $e) {
    die("<p style='color: red;'>Erro ao executar SQL: " . $e->getMessage() . "</p>");
}

echo "<h3>Script finalizado. Você já pode apagar este arquivo.</h3>";
?>