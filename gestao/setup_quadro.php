<?php
// temp/setup_quadro_gestao.php

try {
    // Caminho para o banco de dados financeiro. Ajuste se for usar o users.db ou outro
    $db = new PDO('sqlite:../db/financeiro.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Comando SQL para criar a tabela
    $query = "CREATE TABLE IF NOT EXISTS gestao_metas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rcky_code TEXT NOT NULL,
        data DATE NOT NULL,
        meta_dia REAL DEFAULT 0,
        venda_dia REAL DEFAULT NULL,
        UNIQUE(rcky_code, data)
    )";

    // Executa a criação
    $db->exec($query);
    
    echo "<div style='background:#d1e7dd; color:#0f5132; padding:20px; border-radius:8px; font-family:sans-serif;'>
            <h3>✅ Sucesso!</h3>
            <p>A tabela <strong>gestao_metas</strong> foi criada com sucesso no banco de dados.</p>
            <p>Você já pode acessar essa página para garantir e depois deletar este arquivo por segurança.</p>
          </div>";

} catch (PDOException $e) {
    echo "<div style='background:#f8d7da; color:#842029; padding:20px; border-radius:8px; font-family:sans-serif;'>
            <h3>❌ Erro</h3>
            <p>Erro ao criar a tabela: " . $e->getMessage() . "</p>
          </div>";
}
?>