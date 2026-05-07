<?php
require 'config.php';
try {
    $stmt = $db_produtos->query("SELECT * FROM produtos_unificados ORDER BY id DESC");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produtos = [];
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Unificada de Produtos</title>
    <link rel="stylesheet" href="../static/css/global.css">
    <style>
        /* Estilos base para a modal e tabela de gestão */
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; }
        .cabecalho-gestao { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal { display: none; position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: #fff; margin: 2% auto; padding: 25px; width: 90%; max-width: 800px; border-radius: 8px; max-height: 90vh; overflow-y: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; font-size: 14px;}
        .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .seccao-titulo { grid-column: 1 / -1; margin-top: 15px; margin-bottom: 5px; border-bottom: 2px solid #eee; padding-bottom: 5px; color: #555; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-primary { background: #28a745; color: white; }
        .btn-primary:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-edit { background: #007bff; color: white; padding: 6px 10px; margin-right: 5px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <div class="container">
        <div class="cabecalho-gestao">
            <h1>Produtos Unificados</h1>
            <button class="btn btn-primary" onclick="abrirModal()">+ Novo Produto</button>
        </div>

        <?php if (isset($erro)): ?>
            <p style="color: red;"><?= $erro ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Cód. Interno</th>
                    <th>Cód. Barras</th>
                    <th>Nome do Produto</th>
                    <th>Campanha</th>
                    <th>Preço Venda</th>
                    <th>Custo UN</th>
                    <th>MB Líquida</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-produtos">
                </tbody>
        </table>
    </div>

    <div id="modalProduto" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Novo Produto</h2>
            <form id="formProduto" onsubmit="salvarProduto(event)">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" id="produto_id" name="id">

                <div class="form-grid">
                    <div class="seccao-titulo"><h4>Dados Principais e Etiquetas</h4></div>
                    
                    <div class="form-group"><label>Nome do Produto</label><input type="text" id="nome_produto" name="nome_produto" required></div>
                    <div class="form-group"><label>Preço Lovers (R$)</label><input type="number" step="0.01" id="preco_venda" name="preco_venda" required></div>
                    <div class="form-group"><label>Cód. Interno</label><input type="text" id="codigo_interno" name="codigo_interno"></div>
                    <div class="form-group"><label>Cód. Barras</label><input type="text" id="codigo_barras" name="codigo_barras"></div>
                    <div class="form-group"><label>Preço Não Lovers (R$)</label><input type="number" step="0.01" id="preco2" name="preco2"></div>
                    
                    <div class="seccao-titulo"><h4>Dados Financeiros e Custos</h4></div>
                    
                    <div class="form-group"><label>Campanha</label><input type="text" id="campanha" name="campanha"></div>
                    <div class="form-group"><label>Quantidade por Caixa</label><input type="number" step="0.01" id="qtCaixa" name="qtCaixa"></div>
                    <div class="form-group"><label>Valor da Caixa (R$)</label><input type="number" step="0.01" id="valorUn" name="valorUn"></div>
                    <div class="form-group"><label>ST (R$)</label><input type="number" step="0.01" id="st" name="st"></div>
                    <div class="form-group"><label>IPI (R$)</label><input type="number" step="0.01" id="ipi" name="ipi"></div>
                    <div class="form-group"><label>Taxas Adicionais (R$)</label><input type="number" step="0.01" id="txsAdicionais" name="txsAdicionais"></div>
                    <div class="form-group"><label>Taxa de Mídia (R$)</label><input type="number" step="0.01" id="txMidia" name="txMidia"></div>
                </div>
                
                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const produtos = <?= json_encode($produtos) ?>;
    </script>
   <script src="static/js/gerenciar_produtos_unificados.js"></script>
</body>
</html>