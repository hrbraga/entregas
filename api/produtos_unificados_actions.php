<?php
require '../config.php'; // Ajusta o caminho conforme a pasta onde colocares o ficheiro
// require '../auth/auth_check.php'; // Descomenta se precisares de verificar o login

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

try {
    // --- 1. SALVAR (Criar ou Editar) ---
    if ($action === 'salvar') {
        $id = $_POST['id'] ?? '';
        
        // Dados Básicos e Etiquetas
        $codigo_barras = !empty($_POST['codigo_barras']) ? $_POST['codigo_barras'] : null;
        $codigo_interno = !empty($_POST['codigo_interno']) ? $_POST['codigo_interno'] : null;
        $nome_produto = $_POST['nome_produto'];
        $preco_venda = floatval($_POST['preco_venda']); // Preço Principal
        $preco2 = floatval($_POST['preco2'] ?? 0);
        $campanha = $_POST['campanha'] ?? '';

        // Dados de Custos
        $qtCaixa = floatval($_POST['qtCaixa'] ?? 0);
        $valorUn = floatval($_POST['valorUn'] ?? 0);
        
        // Cálculos Financeiros Automáticos
        $royalties = $valorUn * 0.50;
        $st = floatval($_POST['st'] ?? 0);
        $ipi = floatval($_POST['ipi'] ?? 0);
        $txs = floatval($_POST['txsAdicionais'] ?? 0);
        $midia = floatval($_POST['txMidia'] ?? 0);
        
        $custoCaixa = $valorUn + $royalties + $st + $ipi + $txs + $midia;
        $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
        
       // AQUI ESTÁ A MUDANÇA: Substituímos $preco_venda por $preco2 nos cálculos
        $mbBruta = ($preco2 > 0) ? (1 - ($custoUn / $preco2)) * 100 : 0;
        $baseLiq = $valorUn + $royalties;
        $custoBaseUn = ($qtCaixa > 0) ? ($baseLiq / $qtCaixa) : 0;
        $mbLiquida = ($preco2 > 0) ? (1 - ($custoBaseUn / $preco2)) * 100 : 0;

        $dados = [
            ':codigo_barras' => $codigo_barras,
            ':codigo_interno' => $codigo_interno,
            ':nome_produto' => $nome_produto,
            ':campanha' => $campanha,
            ':preco_venda' => $preco_venda,
            ':preco2' => $preco2,
            ':qtCaixa' => $qtCaixa,
            ':valorUn' => $valorUn,
            ':royalties' => $royalties,
            ':st' => $st,
            ':ipi' => $ipi,
            ':txsAdicionais' => $txs,
            ':txMidia' => $midia,
            ':custoCaixa' => $custoCaixa,
            ':custoUn' => $custoUn,
            ':mbLiquida' => $mbLiquida,
            ':mbBruta' => $mbBruta
        ];

        if (!empty($id)) {
            // UPDATE
            $sql = "UPDATE produtos_unificados SET 
                    codigo_barras=:codigo_barras, codigo_interno=:codigo_interno, 
                    nome_produto=:nome_produto, campanha=:campanha, preco_venda=:preco_venda, 
                    preco2=:preco2, qtCaixa=:qtCaixa, valorUn=:valorUn, royalties=:royalties, 
                    st=:st, ipi=:ipi, txsAdicionais=:txsAdicionais, txMidia=:txMidia, 
                    custoCaixa=:custoCaixa, custoUn=:custoUn, mbLiquida=:mbLiquida, mbBruta=:mbBruta 
                    WHERE id = :id";
            $msg = "Produto atualizado com sucesso!";
            $dados[':id'] = $id;
        } else {
            // INSERT
            $sql = "INSERT INTO produtos_unificados (
                    codigo_barras, codigo_interno, nome_produto, campanha, preco_venda, preco2, 
                    qtCaixa, valorUn, royalties, st, ipi, txsAdicionais, txMidia, 
                    custoCaixa, custoUn, mbLiquida, mbBruta
                    ) VALUES (
                    :codigo_barras, :codigo_interno, :nome_produto, :campanha, :preco_venda, :preco2, 
                    :qtCaixa, :valorUn, :royalties, :st, :ipi, :txsAdicionais, :txMidia, 
                    :custoCaixa, :custoUn, :mbLiquida, :mbBruta
                    )";
            $msg = "Produto criado com sucesso!";
        }

        $stmt = $db_produtos->prepare($sql);
        $stmt->execute($dados);
        echo json_encode(['status' => 'success', 'message' => $msg]);
    }

    // --- 2. EXCLUIR ---
    elseif ($action === 'excluir') {
        $id = $_POST['id'];
        $stmt = $db_produtos->prepare("DELETE FROM produtos_unificados WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Produto eliminado.']);
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: Código de Barras ou Interno já existe.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro BD: ' . $e->getMessage()]);
    }
}
?>