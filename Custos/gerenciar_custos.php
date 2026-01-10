<?php
require '../config.php';
require '../auth/custos_auth_check.php';

// Busca para a lista
$stmt = $db_produtos->query("SELECT * FROM custos_produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="../static/css/planilhas.css"> 
    <link rel="stylesheet" href="../static/css/custos.css">
    <style>
        /* Estilos do Painel e Tabela */
        .painel-controles { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #fff; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px; }
        .btn-acao { cursor: pointer; padding: 5px 10px; border: none; border-radius: 4px; color: white; font-weight: bold; margin-right: 5px;}
        .btn-edit { background-color: #f39c12; }
        .btn-del { background-color: #c0392b; }
        .btn-novo { background-color: #27ae60; padding: 10px 20px; font-size: 1em; color: white; border:none; border-radius: 5px; cursor: pointer;}
        .btn-import { background-color: #2980b9; padding: 10px 20px; font-size: 1em; color: white; border:none; border-radius: 5px; cursor: pointer; }
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 95%; max-width: 900px; max-height: 90vh; overflow-y: auto; position: relative; }
        .close-modal { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #666; }
        
        /* Loading */
        #loading { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 2000; text-align: center; padding-top: 20%; font-size: 2em; color: #4a2c2a; font-weight: bold; }

        /* SEUS ESTILOS ORIGINAIS (Mantidos para o form) */
        .form-container { width: 100%; margin: 0 auto; /* Ajustado para caber no modal */ }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; color: #4a2c2a; font-size: 0.9em; }
        .form-group input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .readonly-field { background-color: #e9ecef; color: #495057; border-color: #ced4da; font-weight: bold; pointer-events: none; }
        .readonly-field:focus { outline: none; }
        .btn-submit { width: 100%; padding: 15px; background-color: #4a2c2a; color: white; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .btn-submit:hover { background-color: #6d423e; }
    </style>
</head>
<body>
    <header><h1>Gerenciamento de Produtos</h1></header>

    <main style="max-width: 95%; margin: 0 auto;">
        <div id="loading">Processando...</div>

        <div class="painel-controles">
            <div>
                <input type="text" id="buscaLocal" placeholder="Buscar na lista..." style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="abrirModalImportacao()" class="btn-import">📁 Importar Planilha</button>
                <button onclick="abrirModalProduto()" class="btn-novo">+ Novo Produto</button>
            </div>
        </div>

        <table class="tableizer-table" id="tabelaProdutos">
            <thead>
                <tr class="tableizer-firstrow">
                    <th>Ações</th>
                    <th>Campanha</th>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Preço (CL)</th>
                    <th>Custo UN</th>
                    <th>MB Liq (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($produtos as $p): ?>
                <tr data-search="<?php echo strtolower($p['codigo'] . ' ' . $p['descricao'] . ' ' . $p['campanha']); ?>">
                    <td>
                        <button class="btn-acao btn-edit" onclick='editarProduto(<?php echo json_encode($p); ?>)'>✎</button>
                        <button class="btn-acao btn-del" onclick="excluirProduto(<?php echo $p['id']; ?>)">🗑</button>
                    </td>
                    <td><?php echo $p['campanha']; ?></td>
                    <td><?php echo $p['codigo']; ?></td>
                    <td><?php echo $p['descricao']; ?></td>
                    <td>R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($p['custoUn'], 2, ',', '.'); ?></td>
                    <td><?php echo number_format($p['mbLiquida'], 2, ',', '.'); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <br>
        <a href="custo_produtos.php" class="back-link">← Voltar para a Tabela Geral</a>
    </main>

    <div id="modalProduto" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalProduto()">&times;</span>
            
            <div class="form-container">
                <h2 id="modalTitle">Adicionar Novo Produto</h2>
                
                <form method="POST" id="formCustos" onsubmit="salvarProduto(event)">
                    <input type="hidden" name="action" value="salvar">
                    <input type="hidden" name="id" id="prodId">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Campanha</label>
                            <input type="text" name="campanha" id="campanha" placeholder="Ex: LINHA" required>
                        </div>
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" name="codigo" id="codigo" required>
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label>Descrição</label>
                            <input type="text" name="descricao" id="descricao" required>
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
            </div>
        </div>
    </div>

    <div id="modalImport" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; text-align: center;">
            <span class="close-modal" onclick="document.getElementById('modalImport').style.display='none'">&times;</span>
            <h2>Importar Planilha</h2>
            <p>Selecione um arquivo Excel (.xlsx) ou CSV.</p>
            <p style="font-size: 0.8em; color: #666;">Colunas: Campanha, Codigo, Descricao, QtCaixa, ValorCX, Preco</p>
            <br>
            <input type="file" id="arquivoImportacao" accept=".xlsx, .xls, .csv">
            <br><br>
            <button onclick="processarImportacao()" class="btn-import" style="width:100%">PROCESSAR ARQUIVO</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="../static/js/gerenciar_custos.js"></script>
</body>
</html>