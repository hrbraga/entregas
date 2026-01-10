<?php
// resetar_entregas.php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php'; // Carrega a conexão $db_entregas

echo "<h1>Resetando Banco de Entregas (Recebimentos)...</h1>";

try {
    // 1. Apaga a tabela antiga de entregas
    $db_entregas->exec("DROP TABLE IF EXISTS item_entrega");
    echo "<p style='color: orange'>1. Tabela 'item_entrega' antiga foi apagada.</p>";

    // 2. Cria a tabela nova e limpa
    // A estrutura foi baseada no seu arquivo api/import_csv.php
    $sql_create = "
    CREATE TABLE item_entrega (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo_sap TEXT,
        item TEXT,
        grupo TEXT,
        pedido_loja INTEGER,
        pedido_vd INTEGER,
        total_caixa INTEGER,
        a_receber INTEGER,
        recebido INTEGER DEFAULT 0,
        user_id INTEGER
    );";
    
    $db_entregas->exec($sql_create);
    echo "<p style='color: green'>2. Tabela 'item_entrega' recriada e pronta para uso!</p>";
    echo "<hr>";
    echo "<h3>Próximo passo: Importe um novo CSV na página de Recebimentos.</h3>";

} catch (PDOException $e) {
    die("Erro ao resetar: " . $e->getMessage());
}
?>