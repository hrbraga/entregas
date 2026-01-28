<?php
// Ajuste o caminho do config conforme a localização deste arquivo
require '../config.php'; 

try {
    // Cria a tabela no banco 'validades.db' (definido no config)
    $sql = "CREATE TABLE IF NOT EXISTS itens_validade (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        codigo_produto TEXT NOT NULL,
        nome_produto TEXT NOT NULL,
        data_validade DATE NOT NULL,
        quantidade INTEGER NOT NULL,
        data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $db_validades->exec($sql);
    echo "<h1>Sucesso!</h1><p>Banco de dados 'validades.db' e tabela criados em /db/.</p>";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>