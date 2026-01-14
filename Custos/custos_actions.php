<?php
require '../config.php';
require '../auth/custos_auth_check.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    // --- 1. SALVAR (Criar ou Editar) ---
    if ($action === 'salvar') {
        $id = $_POST['id'] ?? '';
        
        // Recebendo dados e garantindo números
        $qtCaixa = floatval($_POST['qtCaixa']);
        $valorUn = floatval($_POST['valorUn']);
        $preco = floatval($_POST['preco']);
        
        // Recalcula no backend para segurança
        $royalties = $valorUn * 0.50;
        $st = floatval($_POST['st']);
        $ipi = floatval($_POST['ipi']);
        $txs = floatval($_POST['txsAdicionais']);
        $midia = floatval($_POST['txMidia']);
        
        $custoCaixa = $valorUn + $royalties + $st + $ipi + $txs + $midia;
        $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
        
        // Margem Bruta
        $mbBruta = ($preco > 0) ? (1 - ($custoUn / $preco)) * 100 : 0;
        
        // Margem Líquida
        $baseLiq = $valorUn + $royalties;
        $custoBaseUn = ($qtCaixa > 0) ? ($baseLiq / $qtCaixa) : 0;
        $mbLiquida = ($preco > 0) ? (1 - ($custoBaseUn / $preco)) * 100 : 0;

        // Dados para o PDO
        $dados = [
            ':codigo' => $_POST['codigo'],
            ':descricao' => $_POST['descricao'],
            ':campanha' => $_POST['campanha'],
            ':qtCaixa' => $qtCaixa,
            ':valorUn' => $valorUn,
            ':royalties' => $royalties,
            ':st' => $st,
            ':ipi' => $ipi,
            ':txsAdicionais' => $txs,
            ':txMidia' => $midia,
            ':custoCaixa' => $custoCaixa,
            ':custoUn' => $custoUn,
            ':preco' => $preco,
            ':mbLiquida' => $mbLiquida, 
            ':mbBruta' => $mbBruta
        ];

        if (!empty($id)) {
            // UPDATE
            $sql = "UPDATE custos_produtos SET 
                    codigo=:codigo, descricao=:descricao, campanha=:campanha, qtCaixa=:qtCaixa, 
                    valorUn=:valorUn, royalties=:royalties, st=:st, ipi=:ipi, 
                    txsAdicionais=:txsAdicionais, txMidia=:txMidia, custoCaixa=:custoCaixa, 
                    custoUn=:custoUn, preco=:preco, mbLiquida=:mbLiquida, mbBruta=:mbBruta 
                    WHERE id = :id";
            $dados[':id'] = $id;
            $msg = "Produto atualizado com sucesso!";
        } else {
            // INSERT
            $sql = "INSERT INTO custos_produtos (
                    codigo, descricao, campanha, qtCaixa, valorUn, royalties, st, ipi, 
                    txsAdicionais, txMidia, custoCaixa, custoUn, preco, mbLiquida, mbBruta
                    ) VALUES (
                    :codigo, :descricao, :campanha, :qtCaixa, :valorUn, :royalties, :st, :ipi, 
                    :txsAdicionais, :txMidia, :custoCaixa, :custoUn, :preco, :mbLiquida, :mbBruta
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
        $stmt = $db_produtos->prepare("DELETE FROM custos_produtos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Item excluído.']);
    }

    // --- 3. IMPORTAÇÃO EM MASSA ---
    elseif ($action === 'importar_massa') {
        $json = $_POST['dados_json'];
        $listaProdutos = json_decode($json, true);

        if (!$listaProdutos) throw new Exception("Erro ao ler dados JSON.");

        $db_produtos->beginTransaction();
        
        // Prepara query de Inserção
        $sql = "INSERT INTO custos_produtos (
                codigo, descricao, campanha, qtCaixa, valorUn, royalties, st, ipi, 
                txsAdicionais, txMidia, custoCaixa, custoUn, preco, mbLiquida, mbBruta
                ) VALUES (
                :codigo, :descricao, :campanha, :qtCaixa, :valorUn, :royalties, :st, :ipi, 
                :txsAdicionais, :txMidia, :custoCaixa, :custoUn, :preco, :mbLiquida, :mbBruta
                )";
        $stmt = $db_produtos->prepare($sql);

        $count = 0;
        foreach ($listaProdutos as $p) {
            // Conversão segura de tipos
            $qtCaixa = floatval($p['qtCaixa'] ?? 0);
            $valorUn = floatval($p['valorUn'] ?? 0); // Valor CX
            $preco = floatval($p['preco'] ?? 0);     // Preço (CL)
            
            // --- CÁLCULOS AUTOMÁTICOS (Regra de Negócio) ---
            // Royalties sempre 50% do Valor CX
            $royalties = $valorUn * 0.50;
            
            // Impostos vindos da planilha
            $st = floatval($p['st'] ?? 0);
            $ipi = floatval($p['ipi'] ?? 0);
            $txs = floatval($p['txsAdicionais'] ?? 0);
            $midia = floatval($p['txMidia'] ?? 0);
            
            // Custo Caixa Total
            $custoCaixa = $valorUn + $royalties + $st + $ipi + $txs + $midia;
            
            // Custo Unitário
            $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
            
            // Margem Bruta
            $mbBruta = ($preco > 0) ? (1 - ($custoUn / $preco)) * 100 : 0;
            
            // Margem Líquida
            $baseLiq = $valorUn + $royalties;
            $custoBaseUn = ($qtCaixa > 0) ? ($baseLiq / $qtCaixa) : 0;
            $mbLiquida = ($preco > 0) ? (1 - ($custoBaseUn / $preco)) * 100 : 0;

            $stmt->execute([
                ':codigo' => $p['codigo'],
                ':descricao' => $p['descricao'],
                ':campanha' => $p['campanha'],
                ':qtCaixa' => $qtCaixa,
                ':valorUn' => $valorUn,
                ':royalties' => $royalties,
                ':st' => $st,
                ':ipi' => $ipi,
                ':txsAdicionais' => $txs,
                ':txMidia' => $midia,
                ':custoCaixa' => $custoCaixa,
                ':custoUn' => $custoUn,
                ':preco' => $preco,
                ':mbLiquida' => $mbLiquida,
                ':mbBruta' => $mbBruta
            ]);
            $count++;
        }

        $db_produtos->commit();
        echo json_encode(['status' => 'success', 'message' => "$count produtos importados com sucesso!"]);
    }
} catch (Exception $e) {
    if ($db_produtos->inTransaction()) $db_produtos->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>