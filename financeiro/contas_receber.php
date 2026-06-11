<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "Contas a Receber";
$sessao_nome = "Contas a Receber";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// ==========================================
// 1. BUSCA CONTAS A RECEBER (ISOLADO)
// ==========================================
try {
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';

    $sql_contas = "
        SELECT cr.*, cat.nome as categoria_nome 
        FROM contas_receber cr 
        LEFT JOIN categorias_financeiras cat ON cr.id_categoria = cat.id 
        WHERE cr.id_usuario = ? AND cr.status != 'Recebido'
    ";
    $params = [$id_usuario];

    if (!empty($data_inicio)) {
        $sql_contas .= " AND cr.vencimento >= ?";
        $params[] = $data_inicio;
    }
    if (!empty($data_fim)) {
        $sql_contas .= " AND cr.vencimento <= ?";
        $params[] = $data_fim;
    }

    $sql_contas .= " ORDER BY cr.vencimento ASC";

    $stmt = $db_financeiro->prepare($sql_contas);
    $stmt->execute($params);
    $contas = $stmt->fetchAll();
} catch (Exception $e) {
    $contas = [];
}

// ==========================================
// 2. BUSCA CATEGORIAS (ISOLADO E BLINDADO)
// ==========================================
try {
    // Usamos LIKE para evitar qualquer problema com espaços ou letras minúsculas
    $stmt_cat = $db_financeiro->query("SELECT * FROM categorias_financeiras WHERE tipo LIKE '%Receita%' OR tipo LIKE '%receita%' ORDER BY grupo ASC, nome ASC");
    $lista_categorias = $stmt_cat->fetchAll();
} catch (Exception $e) {
    $lista_categorias = [];
}

// ==========================================
// 3. BUSCA CONTAS BANCÁRIAS (ISOLADO)
// ==========================================
try {
    $stmt_bancos_ativos = $db_financeiro->prepare("SELECT id, nome_conta, banco FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_bancos_ativos->execute([$id_usuario]);
    $bancos_cadastrados = $stmt_bancos_ativos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bancos_cadastrados = [];
}

// Ordenação final
usort($contas, function ($a, $b) {
    return strtotime($a['vencimento']) - strtotime($b['vencimento']);
});
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<div class="financeiro-nav">
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Cadastros ▾</button>
        <div class="nav-dropdown-content">
            <a href="gerenciar_contas.php">Contas Correntes</a>
            <a href="#">Fornecedores</a>
            <a href="#">Clientes</a>
        </div>
    </div>
    <a href="caixa_bancos.php">Caixa e Bancos</a>
    <a href="contas_pagar.php">Contas a Pagar</a>
    <a href="contas_receber.php">Contas a Receber</a>
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Relatórios ▾</button>
        <div class="nav-dropdown-content">
            <a href="relatorio_contas.php">Pagamentos</a>
            <a href="#">Recebimentos</a>
        </div>
    </div>
</div>

<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
    <button class="btn btn-primary" onclick="abrirModalConta()">+ INCLUIR CONTA</button>
    <button class="btn" onclick="abrirModalImportar()" style="background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px;">
        📂 IMPORTAR CONTAS
    </button>
</div>

<div class="financeiro-container financeiro-wrapper">

    <div class="header-actions" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 15px;">
        <form method="GET" action="contas_receber.php" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <label style="font-size: 14px; font-weight: bold;">Período:</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" style="max-width: 140px; cursor: pointer;" onclick="this.showPicker()">
            <span style="font-size: 14px;">até</span>
            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" style="max-width: 140px; cursor: pointer;" onclick="this.showPicker()">
            <button type="submit" class="btn btn-primary" style="margin: 0; padding: 8px 15px;">🔍 Buscar</button>
            <?php if(!empty($data_inicio) || !empty($data_fim)): ?>
                <a href="contas_receber.php" class="btn-cancel" style="padding: 8px 15px; text-decoration: none; display: flex; align-items: center;">Limpar</a>
            <?php endif; ?>
        </form>

        <button class="btn-column-filter" onclick="toggleColumnSidebar()">
            ⚙ Filtrar Colunas
        </button>
    </div>

    <table class="table-financeiro">
        <thead>
            <tr>
                <th class="text-center"><input type="checkbox" id="selecionar_todos"></th>
                <th class="text-center">Ações</th>
                <th>Vencimento</th>
                <th class="col-cliente"> Cliente <span class="filtro-icon" onclick="toggleFiltro('cliente')"> 🔽 </span>
                    <div id="filtro-cliente" class="filtro-dropdown"></div>
                </th>
                <th class="col-nf">Doc/NF</th>
                <th class="col-descricao">Descrição</th>
                <th>Valor</th>
                <th class="col-categoria"> Categoria <span class="filtro-icon" onclick="toggleFiltro('categoria')"> 🔽 </span>
                    <div id="filtro-categoria" class="filtro-dropdown"></div>
                </th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($contas) == 0): ?>
                <tr><td colspan="9" class="empty-state">Nenhum lançamento a receber encontrado para este período. 🎉</td></tr>
            <?php endif; ?>

            <?php foreach ($contas as $c): ?>
                <tr>
                    <td class="text-center"><input type="checkbox" class="check-titulo" data-id="<?= $c['id'] ?>" data-valor="<?= $c['valor'] ?>"></td>
                    <td class="text-center">
                        <button class="btn-acao" title="Liquidar/Receber" onclick="abrirModalBaixa(<?= $c['id'] ?>, '', <?= $c['valor'] ?>, '<?= htmlspecialchars($c['cliente'], ENT_QUOTES) ?>', '<?= htmlspecialchars($c['descricao'], ENT_QUOTES) ?>')">✅</button>
                        <button class="btn-acao" title="Editar" onclick='editarConta(<?= json_encode($c) ?>)'>✏️</button>
                        <button class="btn-acao" title="Excluir" onclick="excluirConta(<?= $c['id'] ?>)">🗑️</button>
                    </td>
                    <td><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td>
                    <td class="col-cliente"><?= htmlspecialchars($c['cliente']) ?></td>
                    <td class="col-nf"><?= htmlspecialchars($c['nota_fiscal'] ?? '-') ?></td>
                    <td class="col-descricao"><?= htmlspecialchars($c['descricao']) ?></td>
                    <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                    <td class="col-categoria"><?= htmlspecialchars($c['categoria_nome']) ?></td>
                    <td class="col-status"><span class="status-badge pendente">Pendente</span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalBaixa" class="modal-financeiro dark-overlay">
    <div class="modal-content modal-md">
        <div class="modal-header success">
            <h2>Confirmar Recebimento</h2>
            <button onclick="fecharModalBaixa()" class="close-modal">&times;</button>
        </div>
        <form id="formBaixa" class="form-body" onsubmit="salvarBaixa(event)">
            <input type="hidden" name="action" value="baixa_recebimento">
            <input type="hidden" name="id_baixa" id="id_baixa">
            <input type="hidden" name="valor_base" id="valor_base_baixa">

            <div class="form-grid-3">
                <div class="form-group"><label>Data do Recebimento</label><input type="date" name="data_pagamento" id="data_pagamento" class="form-control" required></div>
                <div class="form-group"><label>Forma de Recebimento</label>
                    <select name="forma_pagamento" class="form-control" required>
                        <option value="PIX">PIX</option>
                        <option value="Cartão Crédito">Cartão Crédito</option>
                        <option value="Cartão Débito">Cartão Débito</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Boleto">Boleto</option>
                        <option value="Transferência">Transferência</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Conta de Entrada</label>
                    <select name="banco_pagamento" id="banco_pagamento" class="form-control" required>
                        <option value="">Selecione a Conta...</option>
                        <?php foreach ($bancos_cadastrados as $banco): ?>
                            <option value="<?= $banco['id'] ?>"><?= htmlspecialchars($banco['nome_conta']) ?> (<?= htmlspecialchars($banco['banco']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="composicao-box">
                <h4>Composição do Recebimento</h4>
                <div class="composicao-grid">
                    <div class="form-group"><label class="label-success">+ Juros / Multa (R$)</label><input type="text" name="juros" id="juros_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorRecebido();"></div>
                    <div class="form-group"><label class="label-danger">- Descontos (R$)</label><input type="text" name="desconto" id="desconto_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorRecebido();"></div>
                    <div class="form-group"><label class="label-danger">- Taxas de Cartão (R$)</label><input type="text" name="taxa_cartao" id="taxa_cartao" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorRecebido();"></div>
                </div>
            </div>

            <div class="total-container">
                <label>= VALOR TOTAL RECEBIDO</label>
                <input type="text" id="valor_pago_display" class="input-total-pago" readonly>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="fecharModalBaixa()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-confirm">CONFIRMAR RECEBIMENTO</button>
            </div>
        </form>
    </div>
</div>

<div id="modalConta" class="modal-financeiro">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 id="tituloModal">Incluir Conta a Receber</h2>
            <button onclick="fecharModal()" class="close-modal">&times;</button>
        </div>

        <form id="formConta" class="form-body" onsubmit="salvarConta(event)">
            <input type="hidden" name="action" value="salvar_receber">
            <input type="hidden" name="id" id="conta_id">

            <div class="form-grid">
                <div class="form-group autocomplete-wrapper span-2">
                    <label>Cliente</label><input type="text" name="cliente" id="cliente" class="form-control" autocomplete="off" required>
                    <div id="cliente_sugestoes" class="autocomplete-list"></div>
                </div>
                <div class="form-group"><label>Data de Emissão</label><input type="date" name="emissao" id="emissao" class="form-control" required></div>
                <div class="form-group"><label>Vencimento</label><input type="date" name="vencimento" id="vencimento" class="form-control" required></div>
                <div class="form-group"><label>Nº Documento / NF</label><input type="text" name="nota_fiscal" id="nota_fiscal" class="form-control"></div>
                <div class="form-group"><label>Valor (R$)</label><input type="text" name="valor" id="valor" value="0,00" class="form-control text-right" required onkeyup="mascararMoeda(this)"></div>
                <div class="form-group span-2"><label>Descrição</label><input type="text" name="descricao" id="descricao" class="form-control" required></div>
                
               <div class="form-group span-2">
                    <label>Categoria (Apenas Receitas)</label>
                    <select name="id_categoria" id="id_categoria" class="form-control" required>
                        <option value="">Selecione a Categoria...</option>
                        <?php
                        // Se houver um erro no banco de dados e ele não achar nada, mostrará isso:
                        if (empty($lista_categorias)) {
                            echo '<option value="" disabled>🚨 ERRO: Nenhuma categoria "Receita" encontrada!</option>';
                        } else {
                            $grupo_atual = null;
                            foreach ($lista_categorias as $cat) {
                                $grupo_linha = trim((string)$cat['grupo']);
                                
                                if (empty($grupo_linha)) {
                                    $grupo_linha = 'Outras Receitas';
                                }
                                
                                if ($grupo_linha !== $grupo_atual) {
                                    if ($grupo_atual !== null) echo '</optgroup>';
                                    $grupo_atual = $grupo_linha;
                                    // Adicionando o ícone de pasta no Grupo
                                    echo '<optgroup label="📂 ' . htmlspecialchars($grupo_atual) . '">';
                                }
                                
                                // Adicionando os espaços e a setinha na Subcategoria
                                echo '<option value="' . $cat['id'] . '">&nbsp;&nbsp;↳ ' . htmlspecialchars(trim($cat['nome'])) . '</option>';
                            }
                            if ($grupo_atual !== null) echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group span-2" id="box_parcelamento" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" name="is_parcelado" id="is_parcelado" onchange="toggleParcelamento()" style="margin-right: 10px; width: 20px; height: 20px;">
                        <span style="font-weight:bold; color:#007bff; font-size: 15px;">Este recebimento será parcelado?</span>
                    </label>

                    <div id="config_parcelas" style="display: none; margin-top: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end;">
                            <div><label>Nº de Parcelas</label><input type="number" id="qtd_parcelas" min="2" max="120" value="2" class="form-control"></div>
                            <div>
                                <label>Intervalo</label>
                                <select id="intervalo_parcelas" class="form-control">
                                    <option value="30">Mensal</option>
                                    <option value="15">Quinzenal</option>
                                    <option value="7">Semanal</option>
                                </select>
                            </div>
                            <div><button type="button" class="btn-import-xml" onclick="gerarPreviewParcelas()" style="margin:0; background:#28a745;">Gerar Lista</button></div>
                        </div>
                        <div id="lista_parcelas" style="margin-top: 15px;"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="fecharModal()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-confirm">SALVAR LANÇAMENTO</button>
            </div>
        </form>
    </div>
</div>

<div id="modalImportar" class="modal-financeiro dark-overlay">
    <div class="modal-content modal-md">
        <div class="modal-header" style="background: #28a745;">
            <h2>Importar Lançamentos a Receber</h2>
            <button onclick="fecharModalImportar()" class="close-modal">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Selecione o arquivo de integração ou planilha de vendas para processar os recebimentos em lote:</p>
            <input type="file" id="arquivo_importacao" class="form-control" style="margin-bottom: 15px;">
        </div>
        <div class="modal-footer">
            <button type="button" onclick="fecharModalImportar()" class="btn-cancel">Cancelar</button>
            <button type="button" class="btn-confirm" style="background: #28a745;" onclick="processarImportacao()">PROCESSAR ARQUIVO</button>
        </div>
    </div>
</div>

<div id="footerLote" class="footer-lote">
    <div class="footer-lote-info">
        <strong id="qtdSelecionados">0 títulos</strong>
        <span id="valorSelecionado">R$ 0,00</span>
    </div>
    <div class="footer-lote-actions">
        <button class="btn-cancel" onclick="limparSelecaoLote()">Cancelar</button>
        <button class="btn-confirm" onclick="abrirBaixaLote()">Confirmar Recebimento em Lote</button>
    </div>
</div>

<div id="columnSidebar" class="column-sidebar">
    <div class="sidebar-header">
        <h3>Exibir Colunas</h3>
        <button onclick="toggleColumnSidebar()">✕</button>
    </div>
    <div class="sidebar-content">
        <label><input type="checkbox" class="toggle-column" data-column="cliente" checked> Cliente</label>
        <label><input type="checkbox" class="toggle-column" data-column="nf" checked> Doc/NF</label>
        <label><input type="checkbox" class="toggle-column" data-column="descricao" checked> Descrição</label>
        <label><input type="checkbox" class="toggle-column" data-column="categoria" checked> Categoria</label>
        <label><input type="checkbox" class="toggle-column" data-column="status" checked> Status</label>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<div id="toast-notification" class="toast-notification"></div>

<script src="../static/js/contas_receber.js"></script>

<?php require '../includes/footer.php'; ?>