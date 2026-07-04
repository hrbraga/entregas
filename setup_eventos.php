<?php
require 'config.php';

echo "<h2>Iniciando atualização do banco para o Módulo de Eventos...</h2>";

try {
    // 1. Cria a tabela de Eventos
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
    echo "✅ Tabela 'pdv_eventos' criada com sucesso.<br>";

    // 2. Adiciona o Evento e o Nome do Operador na tabela de Turnos
    try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN evento_id INTEGER DEFAULT 0"); } catch(Exception $e) {}
    try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN nome_operador VARCHAR(100)"); } catch(Exception $e) {}
    echo "✅ Tabela 'pdv_turnos' atualizada.<br>";

    // 3. Adiciona o Evento na tabela de Vendas (para facilitar seu relatório futuro)
    try { $db_financeiro->exec("ALTER TABLE pdv_vendas ADD COLUMN evento_id INTEGER DEFAULT 0"); } catch(Exception $e) {}
    echo "✅ Tabela 'pdv_vendas' atualizada.<br>";

    echo "<h3 style='color:green;'>Atualização concluída!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Erro: " . $e->getMessage() . "</h3>";
}
?>