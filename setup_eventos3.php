<?php
require 'config.php';
try {
    // Tabela de estoque por evento
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_estoque_evento (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            evento_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade_inicial DECIMAL(10,2) DEFAULT 0,
            quantidade_atual DECIMAL(10,2) DEFAULT 0,
            UNIQUE(evento_id, produto_id)
        )
    ");
    // Garante as colunas extras necessárias
    try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN evento_id INTEGER DEFAULT 0"); } catch(Exception $e) {}
    try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN nome_operador VARCHAR(100)"); } catch(Exception $e) {}
    try { $db_financeiro->exec("ALTER TABLE pdv_vendas ADD COLUMN evento_id INTEGER DEFAULT 0"); } catch(Exception $e) {}
    
    echo "✅ Setup concluído com sucesso!";
} catch (Exception $e) { echo "Erro: " . $e->getMessage(); }
?>