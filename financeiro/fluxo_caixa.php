<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "Fluxo de Caixa";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// Datas padrão: Mês Atual
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

try {
    // 1. FILTRO DE CONTAS BANCÁRIAS
    $stmt_contas = $db_financeiro->prepare("SELECT id, nome_conta, banco FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_contas->execute([$id_usuario]);
    $todas_contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

    $contas_selecionadas = $_GET['contas'] ?? array_column($todas_contas, 'id');
    if (!is_array($contas_selecionadas)) $contas_selecionadas = [$contas_selecionadas];

    $saldo_inicial = 0;
    $movimentacoes = [];

    // --- GERADOR DE SEMANAS (COLUNAS) ---
    $semanas = [];
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $curr = clone $inicio;
    $i = 1;

    while ($curr <= $fim) {
        $week_start = clone $curr;
        $week_end = clone $curr;
        $week_end->modify('+6 days');
        if ($week_end > $fim) $week_end = clone $fim;

        $semanas["sem_$i"] = [
            'id' => "sem_$i",
            'label_curto' => 'Sem ' . $i,
            'label' => 'Sem ' . $i . '<br><small>' . $week_start->format('d/m') . ' - ' . $week_end->format('d/m') . '</small>',
            'start' => $week_start->format('Y-m-d'),
            'end' => $week_end->format('Y-m-d')
        ];
        $curr->modify('+7 days');
        $i++;
    }

    if (!empty($contas_selecionadas)) {
        $placeholders = implode(',', array_fill(0, count($contas_selecionadas), '?'));

        // CALCULAR SALDO INICIAL
        $stmt_saldo = $db_financeiro->prepare("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE id_usuario = ? AND id IN ($placeholders)");
        $params_saldo = array_merge([$id_usuario], $contas_selecionadas);
        $stmt_saldo->execute($params_saldo);
        $saldo_inicial += (float) $stmt_saldo->fetchColumn();

        $stmt_mov_ant = $db_financeiro->prepare("SELECT tipo, SUM(valor) as total FROM movimentacoes_caixa WHERE id_usuario = ? AND id_conta IN ($placeholders) AND data_movimento < ? GROUP BY tipo");
        $params_mov_ant = array_merge([$id_usuario], $contas_selecionadas, [$data_inicio]);
        $stmt_mov_ant->execute($params_mov_ant);
        $movs_ant = $stmt_mov_ant->fetchAll(PDO::FETCH_KEY_PAIR);
        $saldo_inicial += (($movs_ant['Entrada'] ?? 0) - ($movs_ant['Saida'] ?? 0));

        // QUERY MESTRE: Realizados + Previstos
        $sql_master = "
            SELECT mc.data_movimento as data, mc.tipo, mc.valor, COALESCE(cat.nome, 'Sem Categoria') as categoria
            FROM movimentacoes_caixa mc LEFT JOIN categorias_financeiras cat ON mc.id_categoria = cat.id
            WHERE mc.id_usuario = ? AND mc.id_conta IN ($placeholders) AND mc.data_movimento BETWEEN ? AND ?
            UNION ALL
            SELECT cp.vencimento as data, 'Saida' as tipo, cp.valor, COALESCE(cat.nome, 'Sem Categoria') as categoria
            FROM contas_pagar cp LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id
            WHERE cp.id_usuario = ? AND cp.status != 'Pago' AND cp.vencimento BETWEEN ? AND ?
            UNION ALL
            SELECT cr.vencimento as data, 'Entrada' as tipo, cr.valor, COALESCE(cat.nome, 'Sem Categoria') as categoria
            FROM contas_receber cr LEFT JOIN categorias_financeiras cat ON cr.id_categoria = cat.id
            WHERE cr.id_usuario = ? AND cr.status != 'Recebido' AND cr.vencimento BETWEEN ? AND ?
        ";
        
        $params_master = array_merge([$id_usuario], $contas_selecionadas, [$data_inicio, $data_fim], [$id_usuario, $data_inicio, $data_fim], [$id_usuario, $data_inicio, $data_fim]);
        $stmt_master = $db_financeiro->prepare($sql_master);
        $stmt_master->execute($params_master);
        $movimentacoes = $stmt_master->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- ESTRUTURA PARA A TABELA SINTÉTICA ---
    $receitas = []; 
    $despesas = [];
    $totais_semanais = [
        'receitas' => array_fill_keys(array_keys($semanas), 0),
        'despesas' => array_fill_keys(array_keys($semanas), 0),
        'saldo' => array_fill_keys(array_keys($semanas), 0),
        'acumulado' => array_fill_keys(array_keys($semanas), 0)
    ];
    $totais_semanais['receitas']['total'] = 0;
    $totais_semanais['despesas']['total'] = 0;

    foreach ($movimentacoes as $m) {
        $semana_key = null;
        foreach ($semanas as $k => $s) {
            if ($m['data'] >= $s['start'] && $m['data'] <= $s['end']) { $semana_key = $k; break; }
        }
        if (!$semana_key) continue;

        $cat = $m['categoria'];
        $val = (float) $m['valor'];

        if ($m['tipo'] === 'Entrada') {
            if (!isset($receitas[$cat])) { $receitas[$cat] = array_fill_keys(array_keys($semanas), 0); $receitas[$cat]['total'] = 0; }
            $receitas[$cat][$semana_key] += $val;
            $receitas[$cat]['total'] += $val;
            $totais_semanais['receitas'][$semana_key] += $val;
            $totais_semanais['receitas']['total'] += $val;
        } else {
            if (!isset($despesas[$cat])) { $despesas[$cat] = array_fill_keys(array_keys($semanas), 0); $despesas[$cat]['total'] = 0; }
            $despesas[$cat][$semana_key] += $val;
            $despesas[$cat]['total'] += $val;
            $totais_semanais['despesas'][$semana_key] += $val;
            $totais_semanais['despesas']['total'] += $val;
        }
    }

    // Calcular Saldos Acumulados
    $acumulado = $saldo_inicial;
    foreach ($semanas as $k => $s) {
        $saldo_semana = $totais_semanais['receitas'][$k] - $totais_semanais['despesas'][$k];
        $totais_semanais['saldo'][$k] = $saldo_semana;
        $acumulado += $saldo_semana;
        $totais_semanais['acumulado'][$k] = $acumulado;
    }
    $totais_semanais['saldo']['total'] = $totais_semanais['receitas']['total'] - $totais_semanais['despesas']['total'];
    $totais_semanais['acumulado']['total'] = $acumulado;

} catch (Exception $e) {
    die("Erro ao gerar fluxo de caixa: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">
<link rel="stylesheet" href="../static/css/fluxo_caixa.css">
<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <div class="financeiro-nav">
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
            <a href="dre.php">📊 DRE</a>
            <a href="fluxo_caixa.php" style="font-weight: bold; background: #f8f9fa;">📈 Fluxo de Caixa</a>
        </div>
    </div>
</div>

<div class="financeiro-wrapper" style="max-width: 1200px; margin: 20px auto;">
    
   <form method="GET" class="filtros-box">
        <div class="filtro-item">
            <label>Data Início</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control input-data" onclick="this.showPicker()">
        </div>
        <div class="filtro-item">
            <label>Data Fim</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control input-data" onclick="this.showPicker()">
        </div>
        <div class="filtro-item filtro-contas">
            <label>Contas Bancárias</label>
            <select id="contas_selecionadas" name="contas[]" multiple>
                <?php foreach ($todas_contas as $conta): ?>
                    <option value="<?= $conta['id'] ?>" <?= in_array($conta['id'], $contas_selecionadas) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($conta['nome_conta']) ?> (<?= htmlspecialchars($conta['banco']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filtro-item">
            <label style="color: transparent; user-select: none; pointer-events: none;">Ação</label>
            <button type="submit" class="btn btn-primary btn-gerar">GERAR</button>
        </div>
    </form>

    <div class="grafico-container">
        <canvas id="graficoFluxo"></canvas>
    </div>

    <div class="tabela-container">
        <table class="table-fluxo">
            <thead>
                <tr>
                    <th style="width: 25%;">Categoria</th>
                    <th style="width: 15%;">Saldo Inicial</th>
                    <?php foreach ($semanas as $sem): ?>
                        <th><?= $sem['label'] ?></th>
                    <?php endforeach; ?>
                    <th style="font-weight: 900;">Total no Período</th>
                </tr>
            </thead>
            <tbody>
                
                <tr style="background: #f8f9fa;">
                    <td style="font-weight: bold; color: #555;">💰 Saldo em Caixa (Início)</td>
                    <td class="text-right" style="font-weight: bold; color: <?= $saldo_inicial >= 0 ? '#28a745' : '#dc3545' ?>;">
                        R$ <?= number_format($saldo_inicial, 2, ',', '.') ?>
                    </td>
                    <?php foreach ($semanas as $sem): ?><td>-</td><?php endforeach; ?>
                    <td>-</td>
                </tr>

                <tr class="row-master" onclick="toggleExpander('grp_receitas')">
                    <td>
                        <span id="icon_grp_receitas" class="icon-expander">▶</span>
                        <span class="val-entrada">Total de Receitas</span>
                    </td>
                    <td>-</td>
                    <?php foreach ($semanas as $k => $sem): ?>
                        <td class="val-entrada"><?= number_format($totais_semanais['receitas'][$k], 2, ',', '.') ?></td>
                    <?php endforeach; ?>
                    <td class="val-entrada" style="font-weight: 900;">R$ <?= number_format($totais_semanais['receitas']['total'], 2, ',', '.') ?></td>
                </tr>
                
                <?php foreach ($receitas as $cat_nome => $valores): ?>
                    <tr class="row-child child-of-grp_receitas" style="display: none;">
                        <td>↳ <?= htmlspecialchars($cat_nome) ?></td>
                        <td>-</td>
                        <?php foreach ($semanas as $k => $sem): ?>
                            <td><?= number_format($valores[$k], 2, ',', '.') ?></td>
                        <?php endforeach; ?>
                        <td style="font-weight: bold; color: #555;">R$ <?= number_format($valores['total'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr class="row-master" onclick="toggleExpander('grp_despesas')" style="border-top: 1px solid #ddd;">
                    <td>
                        <span id="icon_grp_despesas" class="icon-expander">▶</span>
                        <span class="val-saida">Total de Despesas</span>
                    </td>
                    <td>-</td>
                    <?php foreach ($semanas as $k => $sem): ?>
                        <td class="val-saida"><?= number_format($totais_semanais['despesas'][$k], 2, ',', '.') ?></td>
                    <?php endforeach; ?>
                    <td class="val-saida" style="font-weight: 900;">- R$ <?= number_format($totais_semanais['despesas']['total'], 2, ',', '.') ?></td>
                </tr>

                <?php foreach ($despesas as $cat_nome => $valores): ?>
                    <tr class="row-child child-of-grp_despesas" style="display: none;">
                        <td>↳ <?= htmlspecialchars($cat_nome) ?></td>
                        <td>-</td>
                        <?php foreach ($semanas as $k => $sem): ?>
                            <td><?= number_format($valores[$k], 2, ',', '.') ?></td>
                        <?php endforeach; ?>
                        <td style="font-weight: bold; color: #555;">R$ <?= number_format($valores['total'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr style="background: #f1f3f5; border-top: 2px solid #ccc;">
                    <td style="font-weight: bold; color: #333;">📊 Geração de Caixa (Semana)</td>
                    <td>-</td>
                    <?php foreach ($semanas as $k => $sem): ?>
                        <td style="font-weight: bold; color: <?= $totais_semanais['saldo'][$k] >= 0 ? '#28a745' : '#dc3545' ?>;">
                            <?= number_format($totais_semanais['saldo'][$k], 2, ',', '.') ?>
                        </td>
                    <?php endforeach; ?>
                    <td style="font-weight: 900; color: <?= $totais_semanais['saldo']['total'] >= 0 ? '#28a745' : '#dc3545' ?>;">
                        R$ <?= number_format($totais_semanais['saldo']['total'], 2, ',', '.') ?>
                    </td>
                </tr>

                <tr>
                    <td style="font-weight: bold; color: #333; font-size: 14px;">🏦 Saldo Acumulado (Fim)</td>
                    <td>-</td>
                    <?php foreach ($semanas as $k => $sem): 
                        $cls = $totais_semanais['acumulado'][$k] >= 0 ? 'bg-saldo-positivo' : 'bg-saldo-negativo';
                    ?>
                        <td class="<?= $cls ?>">
                            <?= number_format($totais_semanais['acumulado'][$k], 2, ',', '.') ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="<?= $totais_semanais['acumulado']['total'] >= 0 ? 'bg-saldo-positivo' : 'bg-saldo-negativo' ?>" style="font-size: 16px;">
                        R$ <?= number_format($totais_semanais['acumulado']['total'], 2, ',', '.') ?>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<script>
    const graficoLabels = <?= json_encode(array_column($semanas, 'label_curto')) ?>;
    const graficoReceitas = <?= json_encode(array_values(array_filter($totais_semanais['receitas'], function($k) { return $k !== 'total'; }, ARRAY_FILTER_USE_KEY))) ?>;
    const graficoDespesas = <?= json_encode(array_values(array_filter($totais_semanais['despesas'], function($k) { return $k !== 'total'; }, ARRAY_FILTER_USE_KEY))) ?>;
</script>

<script src="../static/js/fluxo_caixa.js"></script>
<?php require '../includes/footer.php'; ?>