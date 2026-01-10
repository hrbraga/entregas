<?php
require '../config.php';
require '../auth/custos_auth_check.php';

$msg = "";
$msgType = "";

// Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- 1. Recebendo dados ---
        $campanha = $_POST['campanha'];
        $codigo = $_POST['codigo'];
        $descricao = $_POST['descricao'];
        $qtCaixa = floatval($_POST['qtCaixa']);
        
        // Valores Monetários
        $valorUn = floatval($_POST['valorUn']); // Valor CX Base
        $st = floatval($_POST['st']);
        $ipi = floatval($_POST['ipi']);
        $txsAdicionais = floatval($_POST['txsAdicionais']);
        $txMidia = floatval($_POST['txMidia']);
        $preco = floatval($_POST['preco']); // Novo Campo: Preço Cacau Lovers
        
        // --- 2. Cálculos de Segurança (Backend) ---
        // Royalties (50% do Valor CX)
        $royalties = $valorUn * 0.50;
        
        // Custo Total Caixa
        $custoCaixa = $valorUn + $royalties + $st + $ipi + $txsAdicionais + $txMidia;
        
        // Custo Unitário
        $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
        
        // Margem Bruta: 1 - (custo Un / preço)
        $mbBruta = ($preco > 0) ? (1 - ($custoUn / $preco)) * 100 : 0;
        
        // Margem Líquida: 1 - ((valor un + royalties) / qt caixa / preço)
        $baseLiquida = $valorUn + $royalties;
        $custoBaseUnitario = ($qtCaixa > 0) ? ($baseLiquida / $qtCaixa) : 0;
        $mbLiquida = ($preco > 0) ? (1 - ($custoBaseUnitario / $preco)) * 100 : 0;

        // --- 3. Inserção no Banco ---
        // Verifica se é atualização ou inserção (aqui estamos fazendo insert simples para exemplo)
        $sql = "INSERT INTO custos_produtos (
            codigo, descricao, campanha, qtCaixa, 
            valorUn, royalties, st, ipi, txsAdicionais, txMidia, 
            custoCaixa, custoUn, mbLiquida, mbBruta, preco
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db_produtos->prepare($sql);
        $stmt->execute([
            $codigo, $descricao, $campanha, $qtCaixa,
            $valorUn, $royalties, $st, $ipi, $txsAdicionais, $txMidia,
            $custoCaixa, $custoUn, $mbLiquida, $mbBruta, $preco
        ]);

        $msg = "Produto ($descricao) salvo com sucesso!";
        $msgType = "success";

    } catch (PDOException $e) {
        $msg = "Erro ao salvar: " . $e->getMessage();
        $msgType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Custos</title>
    <link rel="shortcut icon" href="../static/img/coelho.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/login.css">
    
    <style>
        .form-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; color: #4a2c2a; font-size: 0.9em; }
        .form-group input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        
        /* Estilo para campos automáticos */
        .readonly-field { background-color: #e9ecef; color: #495057; border-color: #ced4da; font-weight: bold; pointer-events: none; }
        .readonly-field:focus { outline: none; }

        .btn-submit { width: 100%; padding: 15px; background-color: #4a2c2a; color: white; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .btn-submit:hover { background-color: #6d423e; }

        .msg { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .msg.success { background-color: #d4edda; color: #155724; }
        .msg.error { background-color: #f8d7da; color: #721c24; }
        h2 { text-align: center; color: #4a2c2a; margin-bottom: 20px; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #4a2c2a; text-decoration: none; }
    </style>
</head>
<body>
    <header><h1>Gerenciamento de Produtos</h1></header>

    <main>
        <div class="form-container">
            <h2>Adicionar Novo Produto</h2>
            
            <?php if($msg): ?>
                <div class="msg <?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST" id="formCustos">
                <div class="form-row">
                
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Descrição</label>
                        <input type="text" name="descricao" required>
                    </div>
                        <div class="form-group">
                        <label>Campanha</label>
                        <input type="text" name="campanha" placeholder="Ex: LINHA" required>
                    </div>
                
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                <div class="form-row">
                    <div class="form-group">
                        <label>Qtd. Caixa</label>
                        <input type="number" name="qtCaixa" id="qtCaixa" step="1" required>
                    </div>
                    <div class="form-group">
                        <label>Valor CX (Base)</label>
                        <input type="number" name="valorUn" id="valorUn" step="0.01" required>
                    </div>
                     <div class="form-group">
                        <label style="color: #d35400;">Preço Cacau Lovers</label>
                        <input type="number" name="preco" id="preco" step="0.01" required style="border-color: #d35400;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>ST</label>
                        <input type="number" name="st" id="st" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>IPI</label>
                        <input type="number" name="ipi" id="ipi" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Taxas Adic.</label>
                        <input type="number" name="txsAdicionais" id="txsAdicionais" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Tx Mídia</label>
                        <input type="number" name="txMidia" id="txMidia" step="0.01" value="0">
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                <div class="form-row">
                    <div class="form-group">
                        <label>Royalties (50%)</label>
                        <input type="number" name="royalties" id="royalties" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label>Custo TOT Caixa</label>
                        <input type="number" name="custoCaixa" id="custoCaixa" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label>Custo TOT Unid.</label>
                        <input type="number" name="custoUn" id="custoUn" class="readonly-field" readonly>
                    </div>
                </div>
                
                 <div class="form-row" style="background: #fdf2f0; padding: 15px; border-radius: 8px;">
                    <div class="form-group">
                        <label>MB Líquida (%)</label>
                        <input type="number" name="mbLiquida" id="mbLiquida" class="readonly-field" readonly>
                        <small style="color: #666; font-size: 0.8em;">1 - ((ValCX + Roy) / Qtd / Preço)</small>
                    </div>
                    <div class="form-group">
                        <label>MB Bruta (%)</label>
                        <input type="number" name="mbBruta" id="mbBruta" class="readonly-field" readonly>
                        <small style="color: #666; font-size: 0.8em;">1 - (CustoUn / Preço)</small>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Salvar Produto</button>
            </form>
            
            <a href="custo_produtos.php" class="back-link">← Voltar para a Tabela Geral</a>
        </div>
    </main>

    <script>
        // Elementos
        const els = {
            qt: document.getElementById('qtCaixa'),
            valor: document.getElementById('valorUn'),
            preco: document.getElementById('preco'),
            st: document.getElementById('st'),
            ipi: document.getElementById('ipi'),
            txs: document.getElementById('txsAdicionais'),
            midia: document.getElementById('txMidia'),
            
            // Outputs
            roy: document.getElementById('royalties'),
            custoCx: document.getElementById('custoCaixa'),
            custoUn: document.getElementById('custoUn'),
            mbLiq: document.getElementById('mbLiquida'),
            mbBru: document.getElementById('mbBruta')
        };

        function calcular() {
            // Valores numéricos (padrão 0 se vazio)
            const qt = parseFloat(els.qt.value) || 0;
            const valor = parseFloat(els.valor.value) || 0;
            const preco = parseFloat(els.preco.value) || 0;
            const st = parseFloat(els.st.value) || 0;
            const ipi = parseFloat(els.ipi.value) || 0;
            const txs = parseFloat(els.txs.value) || 0;
            const midia = parseFloat(els.midia.value) || 0;

            // 1. Royalties: (valor * 50%)
            const royalties = valor * 0.50;
            els.roy.value = royalties.toFixed(2);

            // 2. Custo Caixa: Soma de tudo
            const custoCaixa = valor + royalties + st + ipi + txs + midia;
            els.custoCx.value = custoCaixa.toFixed(2);

            // 3. Custo Unidade: Custo Caixa / Qtd
            let custoUn = 0;
            if (qt > 0) {
                custoUn = custoCaixa / qt;
                els.custoUn.value = custoUn.toFixed(2);
            } else {
                els.custoUn.value = "0.00";
            }

            // 4. MB Bruta: 1 - (custo Un / preço)
            let mbBruta = 0;
            if (preco > 0) {
                mbBruta = (1 - (custoUn / preco)) * 100;
                els.mbBru.value = mbBruta.toFixed(2);
            } else {
                els.mbBru.value = "0.00";
            }

            // 5. MB Líquida: 1 - ((valor un + royalties) / qt caixa / preço)
            let mbLiquida = 0;
            if (preco > 0 && qt > 0) {
                const numerador = valor + royalties;
                const custoBaseUn = numerador / qt;
                mbLiquida = (1 - (custoBaseUn / preco)) * 100;
                els.mbLiq.value = mbLiquida.toFixed(2);
            } else {
                els.mbLiq.value = "0.00";
            }
        }

        // Adicionar Listeners em todos os inputs
        const inputs = [els.qt, els.valor, els.preco, els.st, els.ipi, els.txs, els.midia];
        inputs.forEach(input => input.addEventListener('input', calcular));
    </script>
</body>
</html>