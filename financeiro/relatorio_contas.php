<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';
$page_title = "Relatório de Contas a Pagar";
$sessao_nome = "Pagamentos e Despesas";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// Filtros Iniciais (Padrão: Mês Atual)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$status_filtro = $_GET['status'] ?? 'Aberto';

try {
    if ($status_filtro === 'Aberto') {
        // 1. Busca dados APENAS do Contas a Pagar (Abertos) + TAXAS INVISÍVEIS DA CIELO (A Receber)
        $sql_base = "
            SELECT cp.vencimento as data_ref, cp.fornecedor, cp.descricao, cat.nome as categoria_nome, cp.valor
            FROM contas_pagar cp
            LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id
            WHERE cp.id_usuario = ? AND cp.status != 'Pago' AND cp.vencimento BETWEEN ? AND ?
            
            UNION ALL 
            
            SELECT cr.vencimento as data_ref, 'Cielo (Taxas)' as fornecedor, 'Taxa a Descontar: ' || cr.descricao as descricao, 'Taxas de Cartão' as categoria_nome, cr.taxa_importacao as valor
            FROM contas_receber cr
            WHERE cr.id_usuario = ? AND cr.status != 'Recebido' AND COALESCE(cr.taxa_importacao, 0) > 0 AND cr.vencimento BETWEEN ? AND ?
        ";

        $params = [
            $id_usuario,
            $data_inicio,
            $data_fim,
            $id_usuario,
            $data_inicio,
            $data_fim
        ];

        $sql_tabela = "SELECT data_ref, fornecedor, descricao, categoria_nome, valor FROM ($sql_base) as unificados ORDER BY data_ref ASC";
        $sql_grafico = "SELECT data_ref, SUM(valor) as total FROM ($sql_base) as unificados GROUP BY data_ref ORDER BY data_ref ASC";
        $sql_cat = "SELECT COALESCE(categoria_nome, 'Sem Categoria') as categoria, SUM(valor) as total FROM ($sql_base) as unificados GROUP BY categoria_nome ORDER BY total DESC";
    } else {
        // 2. Busca dados PAGOS + SAÍDAS (Caixa e Bancos) + TAXAS INVISÍVEIS DA CIELO (Já Recebidas)
        $sql_base_pago = "
            SELECT cp.data_pagamento as data_ref, cp.fornecedor, cp.descricao, cat.nome as categoria_nome, cp.valor
            FROM contas_pagar cp
            LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id
            WHERE cp.id_usuario = ? AND cp.status = 'Pago' AND cp.data_pagamento BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT mc.data_movimento as data_ref, 'Caixa / Bancos' as fornecedor, mc.descricao, cat.nome as categoria_nome, mc.valor
            FROM movimentacoes_caixa mc
            LEFT JOIN categorias_financeiras cat ON mc.id_categoria = cat.id
            WHERE mc.id_usuario = ? AND mc.origem IN ('Manual', 'Importacao', 'Contas a Receber') AND mc.tipo = 'Saida' AND mc.data_movimento BETWEEN ? AND ?

            UNION ALL
            
            SELECT cr.data_pagamento as data_ref, 'Cielo (Taxas)' as fornecedor, 'Taxa Oculta: ' || cr.descricao as descricao, 'Taxas de Cartão' as categoria_nome, cr.taxa_importacao as valor
            FROM contas_receber cr
            WHERE cr.id_usuario = ? AND cr.status = 'Recebido' AND COALESCE(cr.taxa_importacao, 0) > 0 AND cr.data_pagamento BETWEEN ? AND ?
        ";

        $params = [
            $id_usuario,
            $data_inicio,
            $data_fim,
            $id_usuario,
            $data_inicio,
            $data_fim,
            $id_usuario,
            $data_inicio,
            $data_fim
        ];

        $sql_tabela = "SELECT data_ref, fornecedor, descricao, categoria_nome, valor FROM ($sql_base_pago) as unificados ORDER BY data_ref ASC";
        $sql_grafico = "SELECT data_ref, SUM(valor) as total FROM ($sql_base_pago) as unificados GROUP BY data_ref ORDER BY data_ref ASC";
        $sql_cat = "SELECT COALESCE(categoria_nome, 'Sem Categoria') as categoria, SUM(valor) as total FROM ($sql_base_pago) as unificados GROUP BY categoria_nome ORDER BY total DESC";
    }

    $stmt = $db_financeiro->prepare($sql_tabela);
    $stmt->execute($params);
    $contas = $stmt->fetchAll();

    $stmt_g = $db_financeiro->prepare($sql_grafico);
    $stmt_g->execute($params);
    $dados_evolucao = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cat = $db_financeiro->prepare($sql_cat);
    $stmt_cat->execute($params);
    $dados_categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $contas = [];
    $dados_evolucao = [];
    $dados_categorias = [];
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php require 'nav.php'; ?>

<div class="financeiro-wrapper">
    <form class="composicao-box" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; padding: 20px;">
        <div class="form-group">
            <label>Início</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Fim</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="Aberto" <?= $status_filtro == 'Aberto' ? 'selected' : '' ?>>Em Aberto</option>
                <option value="Pago" <?= $status_filtro == 'Pago' ? 'selected' : '' ?>>Pagas e Efetivadas</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">FILTRAR</button>
    </form>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-top: 20px;">
        <div class="composicao-box" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0; font-size: 16px;">Evolução no Período</h3>
                <select id="viewType" class="form-control" style="width: 130px; font-size: 12px;" onchange="updateBarChart()">
                    <option value="monthly">Mensal</option>
                    <option value="fortnightly">Quinzenal</option>
                    <option value="weekly">Semanal</option>
                </select>
            </div>
            <canvas id="chartEvolucao" style="max-height: 300px;"></canvas>
        </div>

        <div class="composicao-box" style="padding: 20px;">
            <h3 style="margin:0 0 15px 0; font-size: 16px;">Distribuição por Categoria</h3>
            <canvas id="chartCategorias" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="composicao-box" style="margin-top: 20px; padding: 20px;">
        <h3 style="margin: 0 0 15px 0; font-size: 16px; border-bottom: 2px solid #f1f3f5; padding-bottom: 10px; color: #343a40;">
            📊 Resumo Sintético por Categoria
        </h3>

        <table class="table-financeiro" style="width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed;">
            <thead>
                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <th style="width: 75%; padding: 12px 15px; text-align: left; color: #495057; font-size: 13px;">Categoria</th>
                    <th style="width: 25%; padding: 12px 15px; text-align: right; color: #495057; font-size: 13px;">Valor Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $soma_resumo = 0; ?>
                <?php foreach ($dados_categorias as $cat_sum): ?>
                    <?php $soma_resumo += $cat_sum['total']; ?>
                    <tr style="border-bottom: 1px solid #f1f3f5;">
                        <td style="padding: 12px 15px; text-align: left; font-weight: 600; color: #495057; text-transform: uppercase; font-size: 13px; white-space: normal !important; word-break: break-word;">
                            <?= htmlspecialchars($cat_sum['categoria']) ?>
                        </td>
                        <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: #c62828; font-size: 14px;">
                            R$ <?= number_format($cat_sum['total'], 2, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($dados_categorias)): ?>
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 20px; color: #6c757d;">Nenhum dado encontrado para o período selecionado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f3f5; border-top: 2px solid #dee2e6;">
                    <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: #343a40;">TOTAL GERAL:</td>
                    <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: #c62828; font-size: 15px;">
                        R$ <?= number_format($soma_resumo, 2, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <table class="table-financeiro" style="margin-top: 20px;">
        <thead>
            <tr style="background:#f4f4f4;">
                <th>Data (<?= $status_filtro ?>)</th>
                <th>Origem / Fornecedor</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_geral = 0;
            foreach ($contas as $c):
                $total_geral += $c['valor'];
            ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($c['data_ref'])) ?></td>
                    <td>
                        <?php if ($c['fornecedor'] === 'Caixa / Bancos'): ?>
                            <span style="background: #1565c0; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px;">🏦 Caixa</span>
                        <?php elseif (strpos($c['fornecedor'], 'Cielo') !== false): ?>
                            <span style="background: #ff9800; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px;">💳 <?= htmlspecialchars($c['fornecedor']) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($c['fornecedor']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['descricao']) ?></td>
                    <td><?= htmlspecialchars($c['categoria_nome']) ?></td>
                    <td class="text-right" style="font-weight: bold; color: #c62828;">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold; background:#eee;">
                <td colspan="4" class="text-right" style="padding: 15px;">TOTAL NO PERÍODO:</td>
                <td class="text-right" style="color: #c62828; font-size: 16px; padding: 15px;">R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    const evolutionData = <?= json_encode($dados_evolucao) ?>;
    const categoriesData = <?= json_encode($dados_categorias) ?>;
    let barChart = null;

    function getWeekNumber(d) {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
        var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    function processEvolutionData(type) {
        const groups = {};
        evolutionData.forEach(item => {
            const date = new Date(item.data_ref + 'T12:00:00');
            let key = '';
            if (type === 'monthly') key = date.toLocaleString('pt-BR', {
                month: 'short',
                year: '2-digit'
            });
            else if (type === 'fortnightly') key = (date.getDate() <= 15 ? '1ª Q' : '2ª Q') + ' ' + date.toLocaleString('pt-BR', {
                month: 'short'
            });
            else if (type === 'weekly') key = 'Sem ' + getWeekNumber(date);
            groups[key] = (groups[key] || 0) + parseFloat(item.total);
        });
        return {
            labels: Object.keys(groups),
            values: Object.values(groups)
        };
    }

    function updateBarChart() {
        const processed = processEvolutionData(document.getElementById('viewType').value);
        if (barChart) barChart.destroy();
        barChart = new Chart(document.getElementById('chartEvolucao').getContext('2d'), {
            type: 'bar',
            data: {
                labels: processed.labels,
                datasets: [{
                    label: 'Total R$',
                    data: processed.values,
                    backgroundColor: '#c62828',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    function gerarCoresDinamicas(quantidade) {
        const cores = [];
        for (let i = 0; i < quantidade; i++) {
            const hue = (i * 137.5) % 360;
            cores.push(`hsl(${hue}, 70%, 55%)`);
        }
        return cores;
    }

    function initPieChart() {
        const labels = categoriesData.map(item => item.categoria);
        const values = categoriesData.map(item => parseFloat(item.total));
        const coresDinamicas = gerarCoresDinamicas(labels.length);

        new Chart(document.getElementById('chartCategorias').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: coresDinamicas,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    updateBarChart();
    initPieChart();
</script>

<?php require '../includes/footer.php'; ?>