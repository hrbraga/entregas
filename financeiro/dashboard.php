<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "Dashboard Gerencial";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];
$hoje = date('Y-m-d');
$mes_atual = date('Y-m');

try {
    // ==========================================
    // ROBÔ: ALERTAS DE CONTAS HOJE
    // ==========================================
    $stmt_robo = $db_financeiro->prepare("SELECT descricao, valor, fornecedor FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento = ?");
    $stmt_robo->execute([$id_usuario, $hoje]);
    $contas_hoje = $stmt_robo->fetchAll(PDO::FETCH_ASSOC);
    $tem_alerta_hoje = count($contas_hoje) > 0;

    // ==========================================
    // BLOCO 1: TERMÔMETROS
    // ==========================================
    $stmt_saldo_ini = $db_financeiro->prepare("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_saldo_ini->execute([$id_usuario]);
    $saldo_inicial = (float) $stmt_saldo_ini->fetchColumn();

    $stmt_mov = $db_financeiro->prepare("SELECT tipo, SUM(valor) FROM movimentacoes_caixa WHERE id_usuario = ? AND data_movimento <= ? GROUP BY tipo");
    $stmt_mov->execute([$id_usuario, $hoje]);
    $movs = $stmt_mov->fetchAll(PDO::FETCH_KEY_PAIR);
    $saldo_disponivel = $saldo_inicial + ($movs['Entrada'] ?? 0) - ($movs['Saida'] ?? 0);

    $stmt_rec = $db_financeiro->prepare("SELECT SUM(valor) FROM contas_receber WHERE id_usuario = ? AND status != 'Recebido' AND vencimento <= ?");
    $stmt_rec->execute([$id_usuario, $hoje]);
    $a_receber_imediato = (float) $stmt_rec->fetchColumn();

    $stmt_pag = $db_financeiro->prepare("SELECT SUM(valor) FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento <= ?");
    $stmt_pag->execute([$id_usuario, $hoje]);
    $a_pagar_imediato = (float) $stmt_pag->fetchColumn();

    $stmt_mes = $db_financeiro->prepare("SELECT tipo, SUM(valor) FROM movimentacoes_caixa WHERE id_usuario = ? AND data_movimento LIKE ? GROUP BY tipo");
    $stmt_mes->execute([$id_usuario, $mes_atual . '%']);
    $movs_mes = $stmt_mes->fetchAll(PDO::FETCH_KEY_PAIR);
    $geracao_caixa_mes = ($movs_mes['Entrada'] ?? 0) - ($movs_mes['Saida'] ?? 0);

    // ==========================================
    // BLOCO 2 & 3: GRÁFICOS
    // ==========================================
    
    // Gráfico 1: Evolução 6 Meses
    $meses_evolucao = []; $receitas_evolucao = []; $despesas_evolucao = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes_alvo = date('Y-m', strtotime("-$i months"));
        $mes_nome = date('M/Y', strtotime("-$i months"));
        
        $stmt_evo = $db_financeiro->prepare("SELECT tipo, SUM(valor) FROM movimentacoes_caixa WHERE id_usuario = ? AND data_movimento LIKE ? GROUP BY tipo");
        $stmt_evo->execute([$id_usuario, $mes_alvo . '%']);
        $dados_evo = $stmt_evo->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $meses_evolucao[] = $mes_nome;
        $receitas_evolucao[] = $dados_evo['Entrada'] ?? 0;
        $despesas_evolucao[] = $dados_evo['Saida'] ?? 0;
    }

    // Gráfico 2: Despesas do Mês (Limite Aumentado para 12)
    $stmt_rosca = $db_financeiro->prepare("
        SELECT COALESCE(cat.grupo, cat.nome, 'Outros') as rotulo, SUM(mc.valor) as total
        FROM movimentacoes_caixa mc
        LEFT JOIN categorias_financeiras cat ON mc.id_categoria = cat.id
        WHERE mc.id_usuario = ? AND mc.tipo = 'Saida' AND mc.data_movimento LIKE ?
        GROUP BY rotulo ORDER BY total DESC LIMIT 12
    ");
    $stmt_rosca->execute([$id_usuario, $mes_atual . '%']);
    $dados_rosca_brutos = $stmt_rosca->fetchAll(PDO::FETCH_ASSOC);
    $rosca_labels = array_column($dados_rosca_brutos, 'rotulo');
    $rosca_valores = array_column($dados_rosca_brutos, 'total');

    // Gráfico 3: Radar 30 Dias (Calcula todo dia, plota a cada 2)
    $dias_radar = []; $saldos_radar = [];
    $saldo_simulado = $saldo_disponivel;
    
    for ($i = 1; $i <= 30; $i++) {
        $dia_alvo = date('Y-m-d', strtotime("+$i days"));

        $stmt_ent = $db_financeiro->prepare("SELECT SUM(valor) FROM contas_receber WHERE id_usuario = ? AND status != 'Recebido' AND vencimento = ?");
        $stmt_ent->execute([$id_usuario, $dia_alvo]);
        $saldo_simulado += (float) $stmt_ent->fetchColumn();

        $stmt_sai = $db_financeiro->prepare("SELECT SUM(valor) FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento = ?");
        $stmt_sai->execute([$id_usuario, $dia_alvo]);
        $saldo_simulado -= (float) $stmt_sai->fetchColumn();

        // Armazena no array do gráfico apenas se for dia par (a cada 2 dias)
        if ($i % 2 == 0) {
            $dias_radar[] = date('d/m', strtotime("+$i days"));
            $saldos_radar[] = $saldo_simulado;
        }
    }

} catch (Exception $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}
?>

<!-- Importações com o novo nome _financeiro -->
<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">
<link rel="stylesheet" href="../static/css/dashboard_financeiro.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- MODAL DO ROBÔ -->
<?php if ($tem_alerta_hoje): ?>
<div id="modalRobo" class="modal-robo-overlay">
    <div class="modal-robo-box">
        <h2>🤖 Lembrete do Robô</h2>
        <p>Atenção! Você tem <strong><?= count($contas_hoje) ?> conta(s)</strong> vencendo hoje:</p>
        
        <ul class="lista-contas-hoje">
            <?php foreach ($contas_hoje as $conta): ?>
                <li>
                    <span><?= htmlspecialchars($conta['fornecedor'] ?: $conta['descricao']) ?></span>
                    <strong>R$ <?= number_format($conta['valor'], 2, ',', '.') ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <button onclick="fecharModalRobo()" class="btn btn-primary" style="width: 100%; height: 42px;">Entendido!</button>
    </div>
</div>
<?php endif; ?>

<div class="financeiro-nav" style="margin-bottom: 30px;">
    <a href="dashboard.php" style="font-weight: bold; background: #f8f9fa;">🏠 Dashboard</a>
    <a href="caixa_bancos.php">Caixa e Bancos</a>
    <a href="contas_pagar.php">Contas a Pagar</a>
    <a href="contas_receber.php">Contas a Receber</a>
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Relatórios ▾</button>
        <div class="nav-dropdown-content">
            <a href="dre.php">📊 DRE</a>
            <a href="fluxo_caixa.php">📈 Fluxo de Caixa</a>
        </div>
    </div>
</div>

<div class="financeiro-wrapper" style="max-width: 1200px; margin: 0 auto;">
    
    <!-- BLOCO 1: TERMÔMETROS -->
    <div class="dashboard-grid">
        <div class="card-dash borda-azul">
            <div class="titulo-card">Saldo em Caixa (Hoje)</div>
            <div class="valor-card" style="color: <?= $saldo_disponivel >= 0 ? '#007bff' : '#dc3545' ?>;">
                R$ <?= number_format($saldo_disponivel, 2, ',', '.') ?>
            </div>
            <div class="sub-info">Soma de todas as contas ativas</div>
        </div>
        <div class="card-dash borda-verde">
            <div class="titulo-card">A Receber (Imediato)</div>
            <div class="valor-card">R$ <?= number_format($a_receber_imediato, 2, ',', '.') ?></div>
            <div class="sub-info">Vencimentos hoje + Atrasados</div>
        </div>
        <div class="card-dash borda-vermelha">
            <div class="titulo-card">A Pagar (Imediato)</div>
            <div class="valor-card">R$ <?= number_format($a_pagar_imediato, 2, ',', '.') ?></div>
            <div class="sub-info">Vencimentos hoje + Atrasados</div>
        </div>
        <div class="card-dash borda-roxa">
            <div class="titulo-card">Geração de Caixa (Mês)</div>
            <div class="valor-card" style="color: <?= $geracao_caixa_mes >= 0 ? '#28a745' : '#dc3545' ?>;">
                R$ <?= number_format($geracao_caixa_mes, 2, ',', '.') ?>
            </div>
            <div class="sub-info">Receitas x Despesas realizadas</div>
        </div>
    </div>

    <!-- BLOCO 2: GRÁFICOS PRINCIPAIS -->
    <div class="graficos-row">
        <div class="grafico-box">
            <h3>Evolução Financeira (Últimos 6 meses)</h3>
            <div style="height: 280px;"><canvas id="graficoEvolucao"></canvas></div>
        </div>
        <div class="grafico-box">
            <h3>Para onde vai o dinheiro? (Neste Mês)</h3>
            <div style="height: 280px;">
                <?php if(empty($rosca_valores)): ?>
                    <p style="text-align: center; color: #999; margin-top: 100px;">Sem saídas neste mês ainda.</p>
                <?php else: ?>
                    <canvas id="graficoDespesas"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BLOCO 3: RADAR -->
    <div class="grafico-box" style="margin-bottom: 40px;">
        <h3>Radar de Sobrevivência (Previsão de Saldo 30 dias)</h3>
        <div style="height: 250px;"><canvas id="graficoRadar"></canvas></div>
    </div>

</div>

<!-- Injetando os dados do PHP para o JS de forma segura -->
<script>
    const dadosEvolucao = {
        meses: <?= json_encode($meses_evolucao) ?>,
        receitas: <?= json_encode($receitas_evolucao) ?>,
        despesas: <?= json_encode($despesas_evolucao) ?>
    };
    
    const dadosRosca = {
        categorias: <?= json_encode($rosca_labels) ?>,
        valores: <?= json_encode($rosca_valores) ?>
    };

    const dadosRadar = {
        dias: <?= json_encode($dias_radar) ?>,
        saldos: <?= json_encode($saldos_radar) ?>
    };
</script>

<!-- Chama o script com o novo nome _financeiro -->
<script src="../static/js/dashboard_financeiro.js"></script>

<?php require '../includes/footer.php'; ?>