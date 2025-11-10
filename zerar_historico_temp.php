<?php
// zerar_historico_temp.php
require 'config.php'; // Liga-se à base de dados 'entregas.db'

try {
    // Usa uma transação para garantir que ambas as tabelas sejam limpas
    $db_entregas->beginTransaction();

    // 1. Apaga primeiro os "filhos" (itens da nota)
    // (Esta tabela está ligada à 'nota_fiscal')
    $db_entregas->exec("DELETE FROM item_nota_fiscal");

    // 2. Apaga os "pais" (as notas fiscais)
    $db_entregas->exec("DELETE FROM nota_fiscal");

    // Confirma as alterações
    $db_entregas->commit();

    echo "<h1>SUCESSO!</h1>";
    echo "<p>O seu histórico de notas fiscais (tabelas 'nota_fiscal' e 'item_nota_fiscal') foi zerado com sucesso.</p>";
    echo "<p><strong>IMPORTANTE:</strong> Apague este ficheiro (zerar_historico_temp.php) agora!</p>";
    echo '<hr>';
    echo '<h2><a href="historico.php">Voltar para a página de Histórico</a></h2>';

} catch (PDOException $e) {
    // Desfaz tudo se der erro
    $db_entregas->rollBack();
    die("Erro ao zerar o histórico: " . $e->getMessage());
}
?>