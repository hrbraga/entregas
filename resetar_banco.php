<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

echo "<h1>Resetando Banco de Produtos...</h1>";

try {
    // 1. Apaga a tabela antiga
    $db_produtos->exec("DROP TABLE IF EXISTS produtos");
    echo "<p style='color: orange'>1. Tabela 'produtos' antiga foi apagada.</p>";

    // 2. Cria a tabela nova e limpa
    $sql_create = "
    CREATE TABLE produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo_barras TEXT UNIQUE,
        codigo_interno TEXT UNIQUE,
        nome_produto TEXT NOT NULL,
        preco1 REAL NOT NULL,
        preco2 REAL NOT NULL,
        CHECK (preco2 <= preco1),
        CHECK (codigo_barras IS NOT NULL OR codigo_interno IS NOT NULL)
    );";
    
    $db_produtos->exec($sql_create);
    echo "<p style='color: green'>2. Tabela 'produtos' recriada e pronta para uso!</p>";
    echo "<hr>";
    echo "<h3>Próximo passo: Corrija seu arquivo CSV e rode o importador.</h3>";

} catch (PDOException $e) {
    die("Erro ao resetar: " . $e->getMessage());
}
?>