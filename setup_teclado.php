<?php
require 'config.php'; // Certifique-se que o caminho está correto para o seu servidor

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS pdv_turnos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            nome_operador TEXT,
            evento_id INTEGER,
            data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_fechamento DATETIME,
            fundo_caixa DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(20) DEFAULT 'aberto'
        )",
        "CREATE TABLE IF NOT EXISTS pdv_teclado_rapido (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER NOT NULL,
            posicao INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo'
        )",
        "CREATE TABLE IF NOT EXISTS pdv_estoque_evento (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            evento_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade_inicial REAL DEFAULT 0,
            quantidade_atual REAL DEFAULT 0,
            UNIQUE(evento_id, produto_id)
        )",
        "CREATE TABLE IF NOT EXISTS pdv_eventos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome_evento TEXT,
            data_evento DATE,
            controla_estoque INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'ativo',
            user_id INTEGER
        )"
    ];

    echo "<h1>Iniciando Setup do Banco...</h1><ul>";
    foreach ($queries as $sql) {
        $db_financeiro->exec($sql);
        echo "<li>Tabela verificada/criada com sucesso.</li>";
    }
    echo "</ul><h2>Tudo pronto! Pode apagar este arquivo agora.</h2>";

} catch (Exception $e) {
    echo "<h1>Erro crítico:</h1><pre>" . $e->getMessage() . "</pre>";
}
?>