<?php
require '../config.php';

try {
    $db_financeiro->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Preparando Banco de Dados para a Conciliação Bancária...</h2>";

    // Vamos adicionar os campos na tabela de Caixa
    $colunas_necessarias = [
        'conciliado' => 'INTEGER DEFAULT 0', // 0 = Pendente, 1 = Conciliado
        'id_transacao_banco' => 'TEXT',      // ID único que vem do arquivo OFX (FITID)
        'data_conciliacao' => 'DATETIME'     // Quando foi feito o "match"
    ];

    foreach ($colunas_necessarias as $coluna => $tipo) {
        try {
            $db_financeiro->exec("ALTER TABLE movimentacoes_caixa ADD COLUMN $coluna $tipo");
            echo "<p style='color: green;'>✅ Coluna <b>$coluna</b> adicionada com sucesso na tabela de Caixa!</p>";
        } catch (Throwable $e) {
            echo "<p style='color: gray;'>ℹ️ Coluna <b>$coluna</b> já existe (Tudo certo!).</p>";
        }
    }

    echo "<hr><h3>Tudo pronto para iniciarmos a programação da tela!</h3>";
    echo "<br><a href='caixa_bancos.php' style='padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Voltar para o Sistema</a>";

} catch (Throwable $e) {
    echo "<h3 style='color: red;'>Erro crítico: " . $e->getMessage() . "</h3>";
}
?>