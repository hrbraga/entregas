<?php
require '../config.php';

try {
    $db_financeiro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Verificando e Corrigindo a Tabela Contas a Receber...</h2>";

    // 1. Garante que a tabela existe
    $db_financeiro->exec("
        CREATE TABLE IF NOT EXISTS contas_receber (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER
        )
    ");

    // 2. Lista de TODAS as colunas que o nosso sistema novo exige
    $colunas_necessarias = [
        'cliente' => 'TEXT',
        'descricao' => 'TEXT',
        'emissao' => 'DATE',
        'vencimento' => 'DATE',
        'valor' => 'REAL',
        'id_categoria' => 'INTEGER',
        'status' => "TEXT DEFAULT 'Pendente'",
        'nota_fiscal' => 'TEXT',
        'data_pagamento' => 'DATE',
        'forma_pagamento' => 'TEXT',
        'banco_pagamento' => 'TEXT',
        'valor_pago' => 'REAL'
    ];

    // 3. Tenta adicionar cada uma delas
    foreach ($colunas_necessarias as $coluna => $tipo) {
        try {
            $db_financeiro->exec("ALTER TABLE contas_receber ADD COLUMN $coluna $tipo");
            echo "<p style='color: green;'>✅ Coluna <b>$coluna</b> adicionada com sucesso!</p>";
        } catch (Throwable $e) {
            echo "<p style='color: gray;'>ℹ️ Coluna <b>$coluna</b> já existe.</p>";
        }
    }
    
    // 4. Se a tabela antiga tinha "fornecedor", migra os nomes para a coluna "cliente"
    try {
        $db_financeiro->exec("UPDATE contas_receber SET cliente = fornecedor WHERE cliente IS NULL AND fornecedor IS NOT NULL");
        echo "<p style='color: blue;'>🔄 Dados migrados de fornecedor para cliente (se aplicável).</p>";
    } catch (Throwable $e) {}

    echo "<hr><h3>Tudo pronto! Banco de dados 100% atualizado.</h3>";
    echo "<br><a href='contas_receber.php' style='padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Voltar para Contas a Receber</a>";

} catch (Throwable $e) {
    echo "<h3 style='color: red;'>Erro crítico: " . $e->getMessage() . "</h3>";
}
?>