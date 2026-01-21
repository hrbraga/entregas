<?php
require '../config.php';
require '../auth/custos_auth_check.php';

// Busca os produtos da tabela específica de etiquetas (definida em criar_tabela_produtos.php)
$stmt = $db_produtos->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos (Etiquetas)</title>
    <link rel="shortcut icon" href="../static/img/etiqueta.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/login.css">
    <link rel="stylesheet" href="../static/css/planilhas.css"> 
    <link rel="stylesheet" href="../static/css/custos.css"> <style>
        /* Ajustes específicos para esta página se necessário */
        .col-preco { color: #d35400; font-weight: bold; }
        .col-codigo { color: #555; font-family: monospace; }
    </style>
</head>
<body>
    <header><h1>Gerenciamento de Etiquetas</h1></header>

    <main style="max-width: 95%; margin: 0 auto;">
        <div id="loading" style="display:none;">Processando...</div>

        <div class="painel-controles">
            <div>
                <input type="text" id="buscaLocal" placeholder="Buscar por nome ou código...">
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="abrirModalImportacao()" class="btn-import">📁 Importar Excel</button>
                <button onclick="abrirModalProduto()" class="btn-novo">+ Novo Produto</button>
            </div>
        </div>

        <table class="tableizer-table" id="tabelaProdutos">
            <thead>
                <tr class="tableizer-firstrow">
                    <th width="100">Ações</th>
                    <th>Cód. Barras</th>
                    <th>Cód. Interno</th>
                    <th>Nome do Produto</th>
                    <th>Preço Cheio (R$)</th>
                    <th>Preço Lovers (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($produtos as $p): ?>
                <tr data-search="<?php echo strtolower($p['nome_produto'] . ' ' . $p['codigo_barras'] . ' ' . $p['codigo_interno']); ?>">
                    <td>
                        <button class="btn-acao btn-edit" onclick='editarProduto(<?php echo json_encode($p); ?>)'>✎</button>
                        <button class="btn-acao btn-del" onclick="excluirProduto(<?php echo $p['id']; ?>)">🗑</button>
                    </td>
                    <td class="col-codigo"><?php echo $p['codigo_barras']; ?></td>
                    <td class="col-codigo"><?php echo $p['codigo_interno']; ?></td>
                    <td style="text-align: left; padding-left: 10px;"><?php echo $p['nome_produto']; ?></td>
                    <td>R$ <?php echo number_format($p['preco1'], 2, ',', '.'); ?></td>
                    <td class="col-preco">R$ <?php echo number_format($p['preco2'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <br>
        <a href="etiquetas.php" class="back-link">← Voltar para o Gerador de Etiquetas</a>
    </main>

    <div id="modalProduto" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close-modal" onclick="fecharModalProduto()">&times;</span>
            
            <div class="form-container">
                <h2 id="modalTitle">Novo Produto</h2>
                
                <form method="POST" id="formProdutos" onsubmit="salvarProduto(event)">
                    <input type="hidden" name="action" value="salvar">
                    <input type="hidden" name="id" id="prodId">

                    <div class="form-group">
                        <label>Nome do Produto</label>
                        <input type="text" name="nome_produto" id="nome_produto" required placeholder="Ex: Trufa de Chocolate">
                    </div>

                    <div class="form-row" style="margin-top: 15px;">
                        <div class="form-group">
                            <label>Cód. Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras" placeholder="EAN / GTIN">
                        </div>
                        <div class="form-group">
                            <label>Cód. Interno</label>
                            <input type="text" name="codigo_interno" id="codigo_interno" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Preço Cheio (R$)</label>
                            <input type="number" name="preco1" id="preco1" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label style="color: #d35400;">Preço Lovers (R$)</label>
                            <input type="number" name="preco2" id="preco2" step="0.01" required style="border-color: #d35400;">
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
            <p style="font-size: 0.8em; color: #666;">Colunas sugeridas: Barras, Interno, Nome, Preco1, Preco2</p>
            <br>
            <input type="file" id="arquivoImportacao" accept=".xlsx, .xls, .csv">
            <br><br>
            <button onclick="processarImportacao()" class="btn-import" style="width:100%">PROCESSAR ARQUIVO</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="../static/js/gerenciar_etiquetas.js"></script>
</body>
</html>