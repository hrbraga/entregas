<?php
require 'config.php';
// Certifique-se que o usuário rodando isso tem permissão de administrador

try {
    // 1. Tabela de Eventos (Se não existir)
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_eventos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            nome_evento VARCHAR(255) NOT NULL,
            data_evento DATE,
            controla_estoque INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. Tabela de Estoque por Evento (O que vamos usar no próximo passo)
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_estoque_evento (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            evento_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade_inicial DECIMAL(10,2) DEFAULT 0,
            quantidade_atual DECIMAL(10,2) DEFAULT 0,
            FOREIGN KEY(evento_id) REFERENCES pdv_eventos(id)
        )
    ");

    // 3. Garantir as colunas em pdv_turnos (se o ALTER falhar, o script continua)
    $cols = ['evento_id' => 'INTEGER DEFAULT 0', 'nome_operador' => 'VARCHAR(100)'];
    foreach($cols as $col => $type) {
        try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN $col $type"); } catch(Exception $e) {}
    }

    // 4. Garantir colunas na pdv_vendas
    try { $db_financeiro->exec("ALTER TABLE pdv_vendas ADD COLUMN evento_id INTEGER DEFAULT 0"); } catch(Exception $e) {}
    try { $db_financeiro->exec("ALTER TABLE pdv_vendas ADD COLUMN acrescimo DECIMAL(10,2) DEFAULT 0.00"); } catch(Exception $e) {}
    try { $db_financeiro->exec("ALTER TABLE pdv_vendas ADD COLUMN desconto DECIMAL(10,2) DEFAULT 0.00"); } catch(Exception $e) {}

    echo "✅ Setup concluído com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>