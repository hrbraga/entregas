<?php
// Inclui sua conexão de banco de dados
// Ajuste o caminho do config.php se você colocar esse arquivo em outra pasta
require 'config.php'; 

echo "<h2>Iniciando o setup do banco de dados do PDV...</h2>";

try {
    // 1. Criando a tabela de Turnos
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_turnos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_fechamento DATETIME,
            fundo_caixa DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(20) DEFAULT 'aberto'
        )
    ");
    echo "✅ Tabela <strong>'pdv_turnos'</strong> criada ou já existente.<br>";

    // 2. Criando a tabela de Vendas (Capa)
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            turno_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
            subtotal DECIMAL(10,2) NOT NULL,
            desconto DECIMAL(10,2) DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL,
            forma_pagamento VARCHAR(50),
            status VARCHAR(20) DEFAULT 'concluida'
        )
    ");
    echo "✅ Tabela <strong>'pdv_vendas'</strong> criada ou já existente.<br>";

    // 3. Criando a tabela de Itens
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venda_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL,
            preco_unitario DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL
        )
    ");
    echo "✅ Tabela <strong>'pdv_itens'</strong> criada ou já existente.<br>";

    // 4. Criando a tabela do Teclado Rápido
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS pdv_teclado (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER NOT NULL,
            ordem INTEGER NOT NULL,
            cor_fundo VARCHAR(20) DEFAULT '#e9ecef'
        )
    ");
    echo "✅ Tabela <strong>'pdv_teclado'</strong> criada ou já existente.<br><br>";

    echo "<h3 style='color: green;'>🎉 Setup concluído com sucesso! Seu PDV já tem onde morar.</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Erro ao criar as tabelas: " . $e->getMessage() . "</h3>";
}
?>