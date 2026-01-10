<?php
require 'config.php';

try {
    // Apaga a tabela se existir para recriar do zero (cuidado se já tiver dados úteis!)
    // Como você disse que não rodou ainda, isso garante que criaremos a versão correta.
    $db_produtos->exec("DROP TABLE IF EXISTS custos_produtos");

    $sql = "CREATE TABLE custos_produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo TEXT NOT NULL,
        descricao TEXT NOT NULL,
        campanha TEXT NOT NULL,
        qtCaixa INTEGER,
        valorUn REAL,         -- Valor CX
        royalties REAL,
        st REAL,
        ipi REAL,
        txsAdicionais REAL,
        txMidia REAL,
        custoCaixa REAL,      -- Custo Total Caixa
        custoUn REAL,         -- Custo Unitário
        preco REAL DEFAULT 0, -- Preço Cacau Lovers
        mbLiquida REAL,       -- Margem Líquida
        mbBruta REAL          -- Margem Bruta
    )";

    $db_produtos->exec($sql);
    echo "Tabela 'custos_produtos' criada com sucesso (Estrutura Completa)!";

} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
?>