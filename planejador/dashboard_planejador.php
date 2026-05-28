<?php
require '../config.php';
require_once '../auth/auth_check.php';

$page_title = "Dashboard";
$sessao_nome = "Dashboard Pedido Páscoa";

$loja_logada = $_SESSION['usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 'loja_nao_identificada';

$db_path = __DIR__ . '/../db/pascoa.db';

$db_pascoa = new PDO('sqlite:' . $db_path);
$db_pascoa->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db_pascoa->prepare("
    SELECT * 
    FROM pedidos_pascoa 
    WHERE loja_id = ?
");

$stmt->execute([$loja_logada]);

$dados_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';

?>

<link rel="stylesheet" href="../static/css/dashboard_planejador.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../static/js/arrays_pascoa.js"></script>

<div class="dashboard-container">

    <div class="topo-dashboard">

        <a href="planejador.php" class="btn-voltar">
            ← Voltar ao Planejador
        </a>

    </div>

    <h2>RESUMO DO PEDIDO</h2>

    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-title">SELL IN</span>
            <span class="kpi-value" id="kpi-selling">
                R$ 0,00
            </span>
        </div>

        <div class="kpi-card">
            <span class="kpi-title">SELL OUT</span>
            <span class="kpi-value" id="kpi-sellout">
                R$ 0,00
            </span>
        </div>

        <div class="kpi-card">
            <span class="kpi-title">TAXAS ADICIONAIS</span>
            <span class="kpi-value" id="kpi-add">
                R$ 0,00
            </span>
        </div>

        <div class="kpi-card">
            <span class="kpi-title">MÍDIA</span>
            <span class="kpi-value" id="kpi-midia">
                R$ 0,00
            </span>
        </div>

        <div class="kpi-card destaque">
            <span class="kpi-title">RIV</span>
            <span class="kpi-value" id="kpi-riv">
                R$ 0,00
            </span>
        </div>


        <div class="campanha-card">

            <div class="campanha-topo">

                <label class="vip-toggle">

                    <input type="checkbox" id="toggleVip">

                    Se for VIP selecione aqui

                </label>

            </div>

            <div id="card-condicao"></div>

        </div>

    </div>

    <div class="boleto-card">

        <div id="info-cluster"></div>

    </div>

</div>

<div id="comparativo-container"></div>

<!-- Gráficos -->
<div class="charts-grid">

    <div class="chart-card">

        <h3>Compra por Categoria</h3>

        <canvas id="graficoCategoria"></canvas>

    </div>

    <div class="chart-card">

        <h3>Compra por Canal</h3>

        <canvas id="graficoCanal"></canvas>

    </div>

</div>

<div class="chart-card" style="margin-bottom: 20px;">
    <h3>Pedido x Vendido 26 x Sugestão (Unidades)</h3>
    <canvas id="graficoComparativoUnidades" style="max-height: 300px;"></canvas>
</div>

<!-- TOP 10 -->
<div class="chart-card top10-card">

    <h3>Top 10 Produtos Comprados</h3>

    <canvas id="graficoTop10"></canvas>

</div>



<div class="charts-grid">

    <div class="chart-card">

        <h3>
            Top 10 Acima da Sugestão
        </h3>

        <canvas id="graficoAcimaSugestao"></canvas>

    </div>

    <div class="chart-card">

        <h3>
            Top 10 Abaixo da Sugestão
        </h3>

        <canvas id="graficoAbaixoSugestao"></canvas>

    </div>

</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
    const dadosBanco = <?= json_encode($dados_banco) ?>;
</script>

<script src="../static/js/dashboard_planejador.js"></script>

<?php include '../includes/footer.php'; ?>