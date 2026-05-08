<?php
require 'config.php';

echo "<h1>Configurando Banco Financeiro (Multi-usuário)...</h1>";

try {
    // 1. DADOS GLOBAIS (Gerenciados pelo Admin)
    $sql_globais = "
    CREATE TABLE IF NOT EXISTS categorias_financeiras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL UNIQUE,
        tipo TEXT CHECK(tipo IN ('Receita', 'Despesa')),
        grupo TEXT
    );

    CREATE TABLE IF NOT EXISTS campanhas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome_campanha TEXT NOT NULL UNIQUE
    );

    CREATE TABLE IF NOT EXISTS campanhas_niveis (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_campanha INTEGER,
        nivel TEXT,
        vencimento_nf DATE,
        vencimento_royalties DATE,
        FOREIGN KEY (id_campanha) REFERENCES campanhas(id) ON DELETE CASCADE
    );
    ";
    $db_financeiro->exec($sql_globais);

    // 2. DADOS DOS USUÁRIOS (Isolados por id_usuario)
    $sql_usuarios = "
    CREATE TABLE IF NOT EXISTS contas_pagar (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL, -- A chave mágica da separação!
        fornecedor TEXT,
        emissao DATE,
        vencimento DATE,
        nota_fiscal TEXT,
        descricao TEXT,
        valor REAL,
        id_categoria INTEGER,
        status TEXT DEFAULT 'Pendente',
        xml_referencia TEXT,
        FOREIGN KEY (id_categoria) REFERENCES categorias_financeiras(id)
    );
    ";
    $db_financeiro->exec($sql_usuarios);
    
    // Inserindo categorias padrão (Globais)
    $db_financeiro->exec("INSERT OR IGNORE INTO categorias_financeiras (nome, tipo, grupo) VALUES ('Royalties', 'Despesa', 'Custos Operacionais')");
    $db_financeiro->exec("INSERT OR IGNORE INTO categorias_financeiras (nome, tipo, grupo) VALUES ('Mercadoria para Revenda', 'Despesa', 'Custo de Mercadoria')");

    echo "<p style='color: green;'>Tabelas financeiras criadas com sucesso (com suporte multi-usuário)!</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro ao criar tabelas: " . $e->getMessage() . "</p>";
}
?>