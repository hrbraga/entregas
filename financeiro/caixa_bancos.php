<?php
require_once '../config.php';
require_once '../auth/auth_check.php';
$page_title = "Caixa e Bancos";
$sessao_nome = "Caixa e Bancos";
require_once '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// Procurar as contas bancárias ATIVAS do utilizador para o seletor
$stmt_contas = $db_financeiro->prepare("SELECT id, nome_conta, banco FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
$stmt_contas->execute([$id_usuario]);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

// Procurar categorias para o formulário de lançamento manual e agrupá-las
$stmt_categorias = $db_financeiro->prepare("SELECT id, nome, tipo, grupo FROM categorias_financeiras ORDER BY tipo ASC, grupo ASC, nome ASC");
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Organizar categorias por Tipo -> Categoria Mãe -> Subcategorias
$categorias_organizadas = [];
foreach ($categorias as $cat) {
    $tipo = $cat['tipo'];
    $grupo = !empty($cat['grupo']) ? $cat['grupo'] : 'Outros';
    $categorias_organizadas[$tipo][$grupo][] = $cat;
}
?>

<link rel="stylesheet" href="../static/css/financeiro.css">
<link rel="stylesheet" href="../static/css/caixa_bancos.css">

<div class="container-dashboard painel-principal">

<?php require 'nav.php'; ?>

    <div class="cabecalho-pagina">
        <h2>Painel do Caixa</h2>
    </div>

    <div class="filtros-acoes">
        <button onclick="abrirModalLancamento()" class="btn btn-success">+ Novo Lançamento</button>
        <button onclick="abrirModalTransferencia()" class="btn btn-info" style="background-color: #17a2b8; border-color: #17a2b8; color: white; font-weight: 600;">🔄 Transferência</button>
        <button onclick="abrirModalImportacao()" class="btn btn-secondary">📥 Importar Extrato</button>
        <a href="conciliacao.php" class="btn btn-conciliacao">
            🤝 CONCILIAÇÃO BANCÁRIA
        </a>
    </div>
</div>

<div class="filtros-wrapper">
    <div class="filtros-inputs">
        <div class="grupo-filtro">
            <label>Conta Bancária</label>
            <select id="filtroConta" class="form-control select-conta" onchange="carregarMovimentacoes()">
                <option value="todas">Visão Geral (Todas as Contas)</option>
                <?php foreach ($contas as $conta): ?>
                    <option value="<?= $conta['id'] ?>"><?= htmlspecialchars($conta['nome_conta']) ?> (<?= htmlspecialchars($conta['banco']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupo-filtro">
            <label>Período</label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="date" id="filtroDataInicio" class="form-control" value="<?= date('Y-m-01') ?>" onchange="carregarMovimentacoes()">
                <span>até</span>
                <input type="date" id="filtroDataFim" class="form-control" value="<?= date('Y-m-t') ?>" onchange="carregarMovimentacoes()">
            </div>
        </div>

        <div class="grupo-filtro">
            <label>Tipo</label>
            <select id="filtroTipo" class="form-control" onchange="carregarMovimentacoes()">
                <option value="Todos">Todos</option>
                <option value="Entrada">Entradas</option>
                <option value="Saida">Saídas</option>
            </select>
        </div>
    </div>
</div>

<div class="resumo-cards">
    <div class="card-resumo card-inicial">
        <div class="card-info">
            <h4>Saldo Inicial</h4>
            <h3 id="cardSaldoInicial">R$ 0,00</h3>
        </div>
    </div>
    <div class="card-resumo card-entradas">
        <div class="card-info">
            <h4>Entradas</h4>
            <h3 id="cardEntradas">R$ 0,00</h3>
        </div>
    </div>
    <div class="card-resumo card-saidas">
        <div class="card-info">
            <h4>Saídas</h4>
            <h3 id="cardSaidas">R$ 0,00</h3>
        </div>
    </div>
    <div class="card-resumo card-final">
        <div class="card-info">
            <h4>Saldo Final</h4>
            <h3 id="cardSaldoFinal">R$ 0,00</h3>
        </div>
    </div>
</div>

<div class="tabela-container">
    <table class="table table-striped table-hover" id="tabelaCaixa">
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Origem</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Saldo</th>
                <th style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody id="corpoTabelaCaixa">
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">Carregando dados...</td>
            </tr>
        </tbody>
    </table>
</div>

<div id="modalLancamento" class="modal" style="display: none;">
    <div class="modal-content modal-pequeno">
        <div class="modal-header">
            <h3 id="tituloModalLancamento">Novo Lançamento Manual</h3>
            <span class="fechar-modal" onclick="fecharModais()">&times;</span>
        </div>
        <form id="formLancamento" onsubmit="salvarLancamento(event)">

            <input type="hidden" id="idMovimentoEdicao" value="">
            <input type="hidden" id="contaSelecionadaOculta" value="">

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Data da Movimentação:</label>
                <input type="date" id="novaData" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Tipo (Entrada ou Saída):</label>
                <select id="novoTipo" class="form-control" onchange="filtrarCategorias()" required>
                    <option value="">Selecione...</option>
                    <option value="Entrada">Entrada (Receita)</option>
                    <option value="Saida">Saída (Despesa)</option>
                </select>
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Valor (R$):</label>
                <input type="text" id="novoValor" class="form-control" oninput="formatarValorMonetario(this)" required placeholder="0,00">
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Descrição do Lançamento:</label>
                <input type="text" id="novaDescricao" class="form-control" required placeholder="Ex: Depósito, Pagamento de Luz...">
            </div>

            <div class="grupo-filtro" style="margin-bottom: 25px;">
                <label>Categoria Financeira:</label>
                <select id="novaCategoria" class="form-control" required>
                    <option value="">Selecione a Categoria...</option>
                    <?php foreach ($categorias_organizadas as $tipo => $grupos): ?>
                        <?php foreach ($grupos as $nome_grupo => $subcategorias): ?>
                            <optgroup label="<?= htmlspecialchars($nome_grupo) ?>" data-tipo="<?= $tipo ?>">
                                <?php foreach ($subcategorias as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['nome']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-botoes" style="border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" onclick="fecharModais()" class="btn btn-danger">Cancelar</button>
                <button type="submit" class="btn btn-success">💾 Salvar Lançamento</button>
            </div>
        </form>
    </div>
</div>

<div id="modalImportacao" class="modal" style="display: none;">
    <div class="modal-content modal-pequeno">
        <div class="modal-header">
            <h3>Importar Extrato Bancário</h3>
            <span class="fechar-modal" onclick="fecharModais()">&times;</span>
        </div>
        <p style="margin-bottom: 15px; color: #666;">Selecione o arquivo do banco (.csv, .txt, .ofx) para importar movimentações.</p>
        <form id="formImportacao" onsubmit="processarImportacao(event)">
            <label>Arquivo:</label>
            <input type="file" id="arquivoExtrato" class="form-control" accept=".csv, .txt, .ofx" required>

            <div class="modal-botoes">
                <button type="button" onclick="fecharModais()" class="btn btn-danger">Cancelar</button>
                <button type="submit" class="btn btn-primary">Processar Arquivo</button>
            </div>
        </form>
    </div>
</div>

<div id="modalTransferencia" class="modal" style="display: none;">
    <div class="modal-content modal-pequeno">
        <div class="modal-header">
            <h3>🔄 Transferência entre Contas</h3>
            <span class="fechar-modal" onclick="fecharModais()">&times;</span>
        </div>
        <form id="formTransferencia" onsubmit="salvarTransferencia(event)">
            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Data da Transferência:</label>
                <input type="date" id="transfData" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Conta de Origem (Sai o dinheiro):</label>
                <select id="transfOrigem" class="form-control" required>
                    <option value="">Selecione de onde o dinheiro sai...</option>
                    <?php foreach ($contas as $conta): ?>
                        <option value="<?= $conta['id'] ?>"><?= htmlspecialchars($conta['nome_conta']) ?> (<?= htmlspecialchars($conta['banco']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Conta de Destino (Entra o dinheiro):</label>
                <select id="transfDestino" class="form-control" required>
                    <option value="">Selecione para onde o dinheiro vai...</option>
                    <?php foreach ($contas as $conta): ?>
                        <option value="<?= $conta['id'] ?>"><?= htmlspecialchars($conta['nome_conta']) ?> (<?= htmlspecialchars($conta['banco']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grupo-filtro" style="margin-bottom: 15px;">
                <label>Valor a Transferir (R$):</label>
                <input type="number" id="transfValor" step="0.01" min="0.01" class="form-control" required placeholder="0.00">
            </div>

            <div class="grupo-filtro" style="margin-bottom: 25px;">
                <label>Observação (Opcional):</label>
                <input type="text" id="transfObs" class="form-control" placeholder="Ex: Reposição de caixa, sangria...">
            </div>

            <div class="modal-botoes" style="border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" onclick="fecharModais()" class="btn btn-danger">Cancelar</button>
                <button type="submit" class="btn btn-info" style="background-color: #17a2b8; border-color: #17a2b8; color: white;">🔄 Efetuar Transferência</button>
            </div>
        </form>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

<script src="../static/js/caixa_bancos.js?v=2"></script>

<?php require_once '../includes/footer.php'; ?>