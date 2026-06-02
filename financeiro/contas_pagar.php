<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "Contas a Pagar";
$sessao_nome = "Contas a Pagar"; // Isso 
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

try {
    $stmt = $db_financeiro->prepare("
        SELECT cp.*, cat.nome as categoria_nome 
        FROM contas_pagar cp 
        LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id 
        WHERE cp.id_usuario = ? AND cp.status != 'Pago'
        ORDER BY cp.vencimento ASC
    ");
    $stmt->execute([$id_usuario]);
    $contas = $stmt->fetchAll();

    $stmt_cat = $db_financeiro->query("SELECT * FROM categorias_financeiras WHERE tipo = 'Despesa' ORDER BY grupo ASC, nome ASC");
    $lista_categorias = $stmt_cat->fetchAll();

    // --- NOVA ALTERAÇÃO: BUSCAR CONTAS BANCÁRIAS (SOMENTE AS ATIVAS) ---
    $stmt_bancos_ativos = $db_financeiro->prepare("SELECT id, nome_conta, banco FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_bancos_ativos->execute([$id_usuario]);
    $bancos_cadastrados = $stmt_bancos_ativos->fetchAll(PDO::FETCH_ASSOC);
    // -----------------------------------------------

} catch (Exception $e) {
    $contas = [];
    $lista_categorias = [];
    $bancos_cadastrados = [];
}
$contas_finais = [];
$grupos_royalties = [];

foreach ($contas as $c) {
    if (strpos(strtolower($c['descricao']), 'royalt') !== false) {
        $data_venc = $c['vencimento'];
        if (!isset($grupos_royalties[$data_venc])) {
            $grupos_royalties[$data_venc] = ['master' => $c, 'detalhes' => []];
            $grupos_royalties[$data_venc]['master']['valor'] = 0;
            $grupos_royalties[$data_venc]['master']['descricao'] = "Royalties Agrupados - Vencimento " . date('d/m/Y', strtotime($data_venc));
            $grupos_royalties[$data_venc]['master']['is_group'] = true;
            $grupos_royalties[$data_venc]['master']['grupo_id'] = "g_" . str_replace('-', '', $data_venc);
        }
        $grupos_royalties[$data_venc]['master']['valor'] += $c['valor'];
        $grupos_royalties[$data_venc]['detalhes'][] = $c;
    } else {
        $contas_finais[] = $c;
    }
}
foreach ($grupos_royalties as $grupo) {
    $contas_finais[] = $grupo['master'];
}
usort($contas_finais, function ($a, $b) {
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
    <a href="#">Contas a Receber</a>
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Relatórios ▾</button>
        <div class="nav-dropdown-content">
            <a href="relatorio_contas.php">Pagamentos</a>
            <a href="#">Recebimentos</a>
        </div>
    </div>
</div>

<div style="display: flex; gap: 10px;">
    <button class="btn btn-primary" onclick="abrirModalConta()">+ INCLUIR CONTA</button>
</div>
<div class="financeiro-container financeiro-wrapper">

    <div class="header-actions">
        <button class="btn-column-filter" onclick="toggleColumnSidebar()">
            ⚙ Filtrar Colunas
        </button>
    </div>

    <table class="table-financeiro">
        <thead>
            <tr>
                <th class="text-center">
                    <input type="checkbox" id="selecionar_todos">
                </th>
                <th class="text-center">Ações</th>
                <th>Vencimento</th>
                <th class="col-fornecedor"> Fornecedor <span class="filtro-icon" onclick="toggleFiltro('fornecedor')"> 🔽 </span>
                    <div id="filtro-fornecedor" class="filtro-dropdown"></div>
                </th>
                <th class="col-nf">NF</th>
                <th class="col-descricao">Descrição</th>
                <th>Valor</th>
                <th class="col-categoria"> Categoria <span class="filtro-icon" onclick="toggleFiltro('categoria')"> 🔽 </span>
                    <div id="filtro-categoria" class="filtro-dropdown"></div>
                </th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($contas_finais) == 0): ?>
                <tr>
                    <td colspan="9" class="empty-state">Tudo limpo! Não há contas pendentes. 🎉</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($contas_finais as $c): ?>
                <tr class="<?= isset($c['is_group']) ? 'row-master' : '' ?>"
                    <?= isset($c['is_group']) ? 'data-grupo="' . $c['grupo_id'] . '"' : '' ?>>
                    <td class="text-center">
                        <input
                            type="checkbox"
                            class="check-titulo <?= isset($c['is_group']) ? 'check-master' : '' ?>"

                            <?php if (isset($c['is_group'])): ?>
                            data-ids="<?= implode(',', array_column($grupos_royalties[$c['vencimento']]['detalhes'], 'id')) ?>"
                            <?php else: ?>
                            data-id="<?= $c['id'] ?>"
                            <?php endif; ?>

                            data-valor="<?= $c['valor'] ?>">
                    </td>
                    <td class="text-center">
                        <?php if (isset($c['is_group'])): ?>
                            <button class="btn-acao" title="Baixar Grupo" onclick="abrirModalBaixa('', '<?= $c['vencimento'] ?>', <?= $c['valor'] ?>, 'Cacau Show', 'Royalties Agrupados - <?= date('d/m/Y', strtotime($c['vencimento'])) ?>')">✅</button>
                            <button class="btn-acao" title="Ver Detalhes" onclick="toggleGrupo('<?= $c['grupo_id'] ?>')">🔍</button>
                        <?php else: ?>
                            <button class="btn-acao" title="Baixa" onclick="abrirModalBaixa(<?= $c['id'] ?>, '', <?= $c['valor'] ?>, '<?= htmlspecialchars($c['fornecedor'], ENT_QUOTES) ?>', '<?= htmlspecialchars($c['descricao'], ENT_QUOTES) ?>')">✅</button>
                            <button class="btn-acao" title="Editar" onclick='editarConta(<?= json_encode($c) ?>)'>✏️</button>
                            <button class="btn-acao" title="Excluir" onclick="excluirConta(<?= $c['id'] ?>)">🗑️</button>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td>
                    <td class="col-fornecedor"><?= htmlspecialchars($c['fornecedor']) ?></td>
                    <td class="col-nf"><?= htmlspecialchars($c['nota_fiscal'] ?? '-') ?></td>
                    <td class="col-descricao">
                        <?= htmlspecialchars($c['descricao']) ?>
                        <?php if (isset($c['is_group'])): ?>
                            <br><small class="text-primary">(<?= count($grupos_royalties[$c['vencimento']]['detalhes']) ?> parcelas)</small>
                        <?php endif; ?>
                    </td>
                    <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                    <td class="col-categoria"><?= htmlspecialchars($c['categoria_nome']) ?></td>
                    <td class="col-status"><span class="status-badge pendente">Pendente</span></td>
                </tr>

                <?php if (isset($c['is_group'])): ?>
                    <?php foreach ($grupos_royalties[$c['vencimento']]['detalhes'] as $filha): ?>
                        <tr class="child-row <?= $c['grupo_id'] ?>"
                            data-parent="<?= $c['grupo_id'] ?>"
                            style="display:none;">
                            <td></td>
                            <td class="text-center">
                                <button class="btn-acao" onclick='editarConta(<?= json_encode($filha) ?>)'>✏️</button>
                                <button class="btn-acao" onclick="excluirConta(<?= $filha['id'] ?>)">🗑️</button>
                            </td>
                            <td><?= date('d/m/Y', strtotime($filha['vencimento'])) ?></td>
                            <td class="col-fornecedor"><?= htmlspecialchars($filha['fornecedor']) ?></td>
                            <td class="col-nf"><?= htmlspecialchars($filha['nota_fiscal'] ?? '-') ?></td>
                            <td class="col-descricao"> ↳ NF: <?= htmlspecialchars($filha['nota_fiscal']) ?>
                                <br>
                                <?= htmlspecialchars($filha['descricao']) ?>
                            </td>
                            <td>R$ <?= number_format($filha['valor'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($filha['categoria_nome']) ?></td>
                            <td> <span class="status-badge pendente">Pendente</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalBaixa" class="modal-financeiro dark-overlay">
    <div class="modal-content modal-md">
        <div class="modal-header success">
            <h2>Dar Baixa na Conta</h2>
            <button onclick="fecharModalBaixa()" class="close-modal">&times;</button>
        </div>

        <form id="formBaixa" class="form-body" onsubmit="salvarBaixa(event)">
            <input type="hidden" name="action" value="baixa">
            <input type="hidden" name="id_baixa" id="id_baixa">
            <input type="hidden" name="vencimento_baixa" id="vencimento_baixa">
            <input type="hidden" name="fornecedor_baixa" id="fornecedor_baixa">
            <input type="hidden" name="descricao_baixa" id="descricao_baixa">
            <input type="hidden" name="valor_base" id="valor_base_baixa">

            <div class="form-grid-3">
                <div class="form-group"><label>Data do Pagamento</label><input type="date" name="data_pagamento" id="data_pagamento" class="form-control" required></div>
                <div class="form-group"><label>Forma de Pagto</label><select name="forma_pagamento" class="form-control" required>
                        <option value="Boleto">Boleto</option>
                        <option value="PIX">PIX</option>
                        <option value="Transferência">Transferência</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Cartão">Cartão</option>
                    </select></div>

                <div class="form-group">
                    <label>Conta de Saída</label>
                    <select name="banco_pagamento" id="banco_pagamento" class="form-control" required>
                        <option value="">Selecione a Conta...</option>
                        <?php foreach ($bancos_cadastrados as $banco): ?>
                            <option value="<?= $banco['id'] ?>"><?= htmlspecialchars($banco['nome_conta']) ?> (<?= htmlspecialchars($banco['banco']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="composicao-box">
                <h4>Composição do Pagamento</h4>
                <div class="composicao-grid">
                    <div class="form-group"><label class="label-danger">+ Juros (R$)</label><input type="text" name="juros" id="juros_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorPago();"></div>
                    <div class="form-group"><label class="label-danger">+ Multa (R$)</label><input type="text" name="multa" id="multa_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorPago();"></div>
                    <div class="form-group"><label class="label-success">- Descontos (R$)</label><input type="text" name="desconto" id="desconto_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorPago();"></div>
                    <div class="form-group"><label class="label-success">- Créditos Cacau Show (R$)</label><input type="text" name="creditos_cs" id="creditos_cs_baixa" value="0,00" class="form-control text-right" onkeyup="mascararMoeda(this); calcularValorPago();"></div>
                </div>
            </div>

            <div class="total-container">
                <label>= VALOR TOTAL PAGO</label>
                <input type="text" id="valor_pago_display" class="input-total-pago" readonly>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="fecharModalBaixa()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-confirm">CONFIRMAR PAGAMENTO</button>
            </div>
        </form>
    </div>
</div>

<div id="modalConta" class="modal-financeiro">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 id="tituloModal">Incluir Conta a Pagar</h2>
            <button onclick="fecharModal()" class="close-modal">&times;</button>
        </div>

        <div class="import-xml-bar">
            <span>Deseja importar os dados de um XML?</span>
            <input type="file" id="import_xml_input" accept=".xml" style="display: none;" onchange="importarDadosXML()">
            <button class="btn-import-xml" onclick="document.getElementById('import_xml_input').click()">📂 SELECIONAR XML</button>
        </div>

        <form id="formConta" class="form-body" onsubmit="salvarConta(event)">
            <input type="hidden" name="action" value="salvar">
            <input type="hidden" name="id" id="conta_id">

            <div class="form-grid">
                <div class="form-group autocomplete-wrapper span-2">
                    <label>Fornecedor</label><input type="text" name="fornecedor" id="fornecedor" class="form-control" autocomplete="off" required>
                    <div id="fornecedor_sugestoes" class="autocomplete-list"></div>
                </div>
                <div class="form-group"><label>Data de Emissão (Primeira Parcela)</label><input type="date" name="emissao" id="emissao" class="form-control" required></div>
                <div class="form-group"><label>Vencimento Inicial</label><input type="date" name="vencimento" id="vencimento" class="form-control" required></div>
                <div class="form-group"><label>Nº Nota Fiscal</label><input type="text" name="nota_fiscal" id="nota_fiscal" class="form-control"></div>
                <div class="form-group"><label>Valor Total (R$)</label><input type="text" name="valor" id="valor" value="0,00" class="form-control text-right" required onkeyup="mascararMoeda(this)"></div>
                <div class="form-group span-2"><label>Descrição</label><input type="text" name="descricao" id="descricao" class="form-control" required></div>
                <div class="form-group span-2">
                    <label>Categoria (Apenas Despesas)</label>
                    <select name="id_categoria" id="id_categoria" class="form-control categoria-select" required>
                        <option value="">Selecione a Sub-categoria...</option>
                        <?php
                        $grupo_atual = null;
                        foreach ($lista_categorias as $cat):
                            if ($cat['grupo'] !== $grupo_atual):
                                if ($grupo_atual !== null) echo '</optgroup>';
                                $grupo_atual = $cat['grupo'];
                                $nome_grupo = empty($grupo_atual) ? 'Outras Despesas' : $grupo_atual;
                                echo '<optgroup label="📂 ' . htmlspecialchars($nome_grupo) . '">';
                            endif;
                        ?>
                            <option value="<?= $cat['id'] ?>">&nbsp;&nbsp;↳ <?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                        <?php if ($grupo_atual !== null) echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="form-group span-2" id="box_parcelamento" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" name="is_parcelado" id="is_parcelado" onchange="toggleParcelamento()" style="margin-right: 10px; width: 20px; height: 20px;">
                        <span style="font-weight:bold; color:#007bff; font-size: 15px;">Esta conta será parcelada?</span>
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
                <button type="submit" class="btn-confirm">SALVAR CONTA</button>
            </div>
        </form>
    </div>
</div>

<div id="footerLote" class="footer-lote">
    <div class="footer-lote-info">
        <strong id="qtdSelecionados">0 títulos</strong>
        <span id="valorSelecionado">R$ 0,00</span>
    </div>

    <div class="footer-lote-actions">
        <button class="btn-cancel" onclick="limparSelecaoLote()">
            Cancelar
        </button>

        <button class="btn-confirm" onclick="abrirBaixaLote()">
            Dar Baixa
        </button>
    </div>
</div>

<div id="columnSidebar" class="column-sidebar">
    <div class="sidebar-header">
        <h3>Exibir Colunas</h3>
        <button onclick="toggleColumnSidebar()">✕</button>
    </div>
    <div class="sidebar-content">
        <label><input type="checkbox" class="toggle-column" data-column="fornecedor" checked> Fornecedor</label>
        <label><input type="checkbox" class="toggle-column" data-column="nf" checked> Nota Fiscal</label>
        <label><input type="checkbox" class="toggle-column" data-column="descricao" checked> Descrição</label>
        <label><input type="checkbox" class="toggle-column" data-column="categoria" checked> Categoria</label>
        <label><input type="checkbox" class="toggle-column" data-column="status" checked> Status</label>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<div id="toast-notification" class="toast-notification"></div>
<script src="../static/js/contas_pagar.js"></script>

<?php require '../includes/footer.php'; ?>