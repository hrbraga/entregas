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

    -- NOVAS TABELAS PARA CAIXA E BANCOS
    CREATE TABLE IF NOT EXISTS contas_bancarias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL,
        nome_conta TEXT NOT NULL,
        banco TEXT,
        saldo_inicial REAL DEFAULT 0.0,
        data_saldo_inicial DATE
    );

    CREATE TABLE IF NOT EXISTS movimentacoes_caixa (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL,
        id_conta INTEGER NOT NULL,
        data_movimento DATE NOT NULL,
        tipo TEXT CHECK(tipo IN ('Entrada', 'Saida')) NOT NULL,
        valor REAL NOT NULL,
        descricao TEXT,
        id_categoria INTEGER,
        origem TEXT DEFAULT 'Manual', -- 'Manual' ou 'Importacao'
        hash_importacao TEXT UNIQUE,  -- Crucial para evitar duplicar dados ao importar o mesmo extrato 2x
        FOREIGN KEY (id_conta) REFERENCES contas_bancarias(id) ON DELETE CASCADE,
        FOREIGN KEY (id_categoria) REFERENCES categorias_financeiras(id)
    );
    ";
    $db_financeiro->exec($sql_usuarios);
    
?>

