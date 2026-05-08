<?php
require '../config.php';
require '../auth/auth_check.php';
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// Filtros Iniciais (Padrão: Mês Atual)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$status_filtro = $_GET['status'] ?? 'Aberto';

try {
    $coluna_data = ($status_filtro === 'Pago') ? 'data_pagamento' : 'vencimento';
    $condicao_status = ($status_filtro === 'Pago') ? "status = 'Pago'" : "status != 'Pago'";

    // 1. Busca dados para a tabela
    $sql_tabela = "
        SELECT cp.*, cat.nome as categoria_nome 
        FROM contas_pagar cp
        LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id
        WHERE cp.id_usuario = ? 
          AND $condicao_status
          AND cp.$coluna_data BETWEEN ? AND ?
        ORDER BY cp.$coluna_data ASC
    ";
    $stmt = $db_financeiro->prepare($sql_tabela);
    $stmt->execute([$id_usuario, $data_inicio, $data_fim]);
    $contas = $stmt->fetchAll();

    // 2. Busca dados para o Gráfico de Evolução (Barras)
    $sql_grafico = "
        SELECT $coluna_data as data_ref, SUM(valor) as total
        FROM contas_pagar
        WHERE id_usuario = ? 
          AND $condicao_status
          AND $coluna_data BETWEEN ? AND ?
        GROUP BY $coluna_data
        ORDER BY $coluna_data ASC
    ";
    $stmt_g = $db_financeiro->prepare($sql_grafico);
    $stmt_g->execute([$id_usuario, $data_inicio, $data_fim]);
    $dados_evolucao = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca dados para o Gráfico de Categorias (Pizza)
    $sql_cat = "
        SELECT COALESCE(cat.nome, 'Sem Categoria') as categoria, SUM(cp.valor) as total
        FROM contas_pagar cp
        LEFT JOIN categorias_financeiras cat ON cp.id_categoria = cat.id
        WHERE cp.id_usuario = ? 
          AND $condicao_status
          AND cp.$coluna_data BETWEEN ? AND ?
        GROUP BY cat.nome
        ORDER BY total DESC
    ";
    $stmt_cat = $db_financeiro->prepare($sql_cat);
    $stmt_cat->execute([$id_usuario, $data_inicio, $data_fim]);
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

<div class="financeiro-wrapper">
    <div class="header-actions">
        <h1>Relatório de Contas a Pagar</h1>
        <a href="contas_pagar.php" class="btn btn-secondary" style="text-decoration: none;">← VOLTAR</a>
    </div>

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
                <option value="Pago" <?= $status_filtro == 'Pago' ? 'selected' : '' ?>>Pagas</option>
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

    <table class="table-financeiro" style="margin-top: 20px;">
        <thead>
            <tr style="background:#f4f4f4;">
                <th>Data (<?= $status_filtro ?>)</th>
                <th>Fornecedor</th>
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
                    <td><?= date('d/m/Y', strtotime($c[$coluna_data])) ?></td>
                    <td><?= htmlspecialchars($c['fornecedor']) ?></td>
                    <td><?= htmlspecialchars($c['descricao']) ?></td>
                    <td><?= htmlspecialchars($c['categoria_nome']) ?></td>
                    <td class="text-right">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold; background:#eee;">
                <td colspan="4" class="text-right">TOTAL NO PERÍODO:</td>
                <td class="text-right">R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    const evolutionData = <?= json_encode($dados_evolucao) ?>;
    const categoriesData = <?= json_encode($dados_categorias) ?>;
    let barChart = null;

    // --- LÓGICA DO GRÁFICO DE BARRAS (EVOLUÇÃO) ---
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
        groupByCategorias
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
                    backgroundColor: '#007bff',
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

    // --- LÓGICA DO GRÁFICO DE PIZZA (CATEGORIAS) ---
    // --- LÓGICA DO GRÁFICO DE PIZZA (CATEGORIAS) ---

    // Função para gerar cores infinitas e contrastantes
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

        // Chama o gerador automático baseado no número de categorias que o banco de dados retornou
        const coresDinamicas = gerarCoresDinamicas(labels.length);

        new Chart(document.getElementById('chartCategorias').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: coresDinamicas, // Usa as cores infinitas aqui
                    borderWidth: 2,
                    borderColor: '#ffffff' // Uma bordinha branca separa melhor as fatias
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
    // Inicializa tudo
    updateBarChart();
    initPieChart();
</script>

<?php require '../includes/footer.php'; ?>