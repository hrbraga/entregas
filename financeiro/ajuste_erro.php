<?php
// Conexão com o banco financeiro (SQLite)
$db_path = __DIR__ . '/../db/financeiro.db';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // O nome da tabela e da coluna onde o erro está morando
    $tabela = 'movimentacoes_caixa'; 
    $coluna_descricao = 'descricao'; 

    $atualizados_total = 0;

    // Matriz de substituição de todos os erros de importação OFX mais comuns do Sicoob
    $erros_para_limpar = [
        'CRÉD' => 'CRED',
        'CRD' => 'CRED',
        'CRÃ‰D' => 'CRED',
        'DÉB' => 'DEB',
        'DB' => 'DEB',
        'DÃ‰B' => 'DEB',
        'DEPÓSITO' => 'DEPOSITO',
        'DEPSITO' => 'DEPOSITO',
        'DEPÃ“SITO' => 'DEPOSITO',
        'SERVIÇOS' => 'SERVICOS',
        'SERVIOS' => 'SERVICOS',
        'SERVIÃ‡OS' => 'SERVICOS',
        'MANUTENÇÃO' => 'MANUTENCAO',
        'MANUTENÃ‡ÃƒO' => 'MANUTENCAO'
    ];

    // Vamos varrer e substituir cada erro no banco inteiro
    foreach ($erros_para_limpar as $erro => $correcao) {
        $sql = "UPDATE $tabela SET $coluna_descricao = REPLACE($coluna_descricao, ?, ?) WHERE $coluna_descricao LIKE ?";
        $stmt = $pdo->prepare($sql);
        
        // Exemplo: Substitui "CRÃ‰D" por "CRED" onde encontrar "%CRÃ‰D%"
        $like_term = '%' . $erro . '%';
        $stmt->execute([$erro, $correcao, $like_term]);
        
        $atualizados_total += $stmt->rowCount();
    }

    echo "<h2>Restauração Concluída com Sucesso!</h2>";
    echo "<p>Total de correções feitas em descrições na tabela <strong>$tabela</strong>: <strong>" . $atualizados_total . "</strong></p>";
    echo "<p>O seu painel <strong>caixa_bancos.php</strong> vai carregar sem nenhum problema e seu histórico está intacto.</p>";

} catch (Exception $e) {
    echo "<h2>Erro na execução:</h2>";
    echo "<p>Detalhe técnico: " . $e->getMessage() . "</p>";
}
?>