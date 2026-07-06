<?php
require 'config.php'; // Ajuste o caminho se necessário

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS motor_promocoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome_campanha VARCHAR(100) NOT NULL,
            tipo_mecanica VARCHAR(50) NOT NULL, 
            qtd_gatilho INTEGER NOT NULL, 
            valor_beneficio DECIMAL(10,2) NOT NULL, 
            data_inicio DATE,
            data_fim DATE,
            status VARCHAR(20) DEFAULT 'ativo'
        )",
        
        "CREATE TABLE IF NOT EXISTS motor_promocoes_produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            promocao_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            FOREIGN KEY (promocao_id) REFERENCES motor_promocoes(id) ON DELETE CASCADE
        )"
    ];

    echo "<h1>Preparando o Motor de Promoções...</h1><ul>";
    foreach ($queries as $sql) {
        $db_financeiro->exec($sql);
        echo "<li>Tabela criada com sucesso.</li>";
    }
    echo "</ul><h2>Estrutura Pronta! Pode apagar este arquivo.</h2>";

} catch (Exception $e) {
    echo "<h1>Erro:</h1><pre>" . $e->getMessage() . "</pre>";
}
?>