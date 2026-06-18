<?php
// Puxa a sua conexão com o banco de dados
require '../config.php';

try {
    // Comando para adicionar a coluna nova na tabela do caixa
    $sql = "ALTER TABLE movimentacoes_caixa ADD COLUMN id_origem INT NULL";
    
    // Executa o comando
    $db_financeiro->exec($sql);
    
    echo "<h1>Sucesso!</h1>";
    echo "<p>A coluna 'id_origem' foi adicionada com sucesso na tabela movimentacoes_caixa.</p>";
    echo "<p>Você já pode fechar esta tela e deletar este arquivo (atualizar_banco.php) por segurança.</p>";

} catch (PDOException $e) {
    echo "<h1>Aviso</h1>";
    echo "<p>Se der erro, provavelmente a coluna já existe ou há um problema de conexão.</p>";
    echo "<p>Detalhe do erro: " . $e->getMessage() . "</p>";
}
?>