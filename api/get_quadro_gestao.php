<?php
// api/get_quadro_gestao.php
ini_set('display_errors', 0);
ob_start();

require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

// AJUSTE: Pegando o ID do usuário logado na sessão
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    ob_clean();
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$mes_atual = date('Y-m');
$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));

try {
    // 1. Verificar dias pendentes (Filtro por user_id)
    $stmtPendentes = $db_financeiro->prepare("
        SELECT data FROM gestao_metas 
        WHERE user_id = ? AND data >= ? AND data < ? AND (venda_dia IS NULL OR venda_dia = '') 
        ORDER BY data ASC
    ");
    $stmtPendentes->execute([$user_id, $mes_atual . '-01', $hoje]);
    $dias_pendentes = $stmtPendentes->fetchAll(PDO::FETCH_COLUMN);

    // 2. Buscar consolidados do mês (Filtro por user_id)
    $stmtMes = $db_financeiro->prepare("
        SELECT 
            SUM(meta_dia) as meta_total,
            SUM(CASE WHEN data < ? THEN meta_dia ELSE 0 END) as meta_acumulada,
            SUM(CASE WHEN data < ? THEN venda_dia ELSE 0 END) as venda_acumulada
        FROM gestao_metas
        WHERE user_id = ? AND data LIKE ?
    ");
    $stmtMes->execute([$hoje, $hoje, $user_id, $mes_atual . '-%']);
    $dadosMes = $stmtMes->fetch(PDO::FETCH_ASSOC);

    $meta_total = (float)($dadosMes['meta_total'] ?? 0);
    $meta_acumulada = (float)($dadosMes['meta_acumulada'] ?? 0);
    $venda_acumulada = (float)($dadosMes['venda_acumulada'] ?? 0);

    // 3. Atingimento e GAP
    $atingimento = $meta_acumulada > 0 ? ($venda_acumulada / $meta_acumulada) * 100 : 0;
    $gap = $meta_acumulada - $venda_acumulada;

    // ====================================================
    // CORREÇÃO: Buscar a Meta de Hoje ANTES de usar
    // ====================================================
    $stmtHoje = $db_financeiro->prepare("SELECT meta_dia FROM gestao_metas WHERE user_id = ? AND data = ?");
    $stmtHoje->execute([$user_id, $hoje]);
    $meta_hoje = (float)($stmtHoje->fetchColumn() ?: 0);

    // ====================================================
    // 4. Último Dia Útil (Substitui a lógica de "Ontem")
    // ====================================================
    
    // Busca o último dia antes de hoje que tenha uma meta maior que zero
    $stmtUltimoDia = $db_financeiro->prepare("
        SELECT data, meta_dia, venda_dia 
        FROM gestao_metas 
        WHERE user_id = ? AND data < ? AND data LIKE ? AND meta_dia > 0
        ORDER BY data DESC LIMIT 1
    ");
    $stmtUltimoDia->execute([$user_id, $hoje, $mes_atual . '-%']);
    $ultimo = $stmtUltimoDia->fetch(PDO::FETCH_ASSOC);

    if ($ultimo) {
        $meta_ontem = (float)$ultimo['meta_dia'];
        $venda_ontem = (float)$ultimo['venda_dia'];
        $data_ultimo_dia = date('d/m', strtotime($ultimo['data'])); 
    } else {
        $meta_ontem = 0;
        $venda_ontem = 0;
        $data_ultimo_dia = '';
    }

    // ====================================================
    // 5. Meta de Hoje Ajustada (Regra: Meta Hoje + (GAP / Dias Restantes))
    // ====================================================
    
    // Conta exatamente quantos dias restam no mês (incluindo hoje) que possuem meta cadastrada > 0
    $stmtDiasRestantes = $db_financeiro->prepare("
        SELECT COUNT(*) 
        FROM gestao_metas 
        WHERE user_id = ? AND data >= ? AND data LIKE ? AND meta_dia > 0
    ");
    $stmtDiasRestantes->execute([$user_id, $hoje, $mes_atual . '-%']);
    $dias_restantes_uteis = (int)$stmtDiasRestantes->fetchColumn();

    // Se existe um GAP positivo (está atrás da meta) e ainda há dias úteis
    if ($gap > 0 && $dias_restantes_uteis > 0) {
        $meta_ajustada = $meta_hoje + ($gap / $dias_restantes_uteis);
    } else {
        // Se bateu a meta (GAP <= 0) ou é o último dia, mantém a meta original do dia
        $meta_ajustada = $meta_hoje;
    }
    
    // 6. Dados do Gráfico (Filtro por user_id)
    $stmtGrafico = $db_financeiro->prepare("SELECT data, meta_dia, venda_dia FROM gestao_metas WHERE user_id = ? AND data LIKE ? ORDER BY data ASC");
    $stmtGrafico->execute([$user_id, $mes_atual . '-%']);

    $grafico_datas = [];
    $grafico_metas = [];
    $grafico_vendas = [];
    $acumulado_m = 0;
    
    // CORREÇÃO: Inicializar a variável de venda acumulada do gráfico
    $acumulado_v = 0; 

    while ($row = $stmtGrafico->fetch(PDO::FETCH_ASSOC)) {
        $grafico_datas[] = date('d/m', strtotime($row['data']));
        $acumulado_m += (float)$row['meta_dia'];
        $grafico_metas[] = $acumulado_m;

        if ($row['data'] < $hoje) {
            $acumulado_v += (float)$row['venda_dia'];
            $grafico_vendas[] = $acumulado_v;
        } else {
            $grafico_vendas[] = null;
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'dias_pendentes' => $dias_pendentes,
        'meta_total' => $meta_total,
        'meta_acumulada' => $meta_acumulada,
        'venda_acumulada' => $venda_acumulada,
        'atingimento' => $atingimento,
        'gap' => $gap,
        'meta_ontem' => $meta_ontem,
        'venda_ontem' => $venda_ontem,
        'data_ultimo_dia' => $data_ultimo_dia, // CORREÇÃO: Adicionada a data aqui
        'meta_hoje' => $meta_hoje,
        'meta_ajustada' => $meta_ajustada,
        'grafico_datas' => $grafico_datas,
        'grafico_metas' => $grafico_metas,
        'grafico_vendas' => $grafico_vendas
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Erro interno BD: ' . $e->getMessage()]);
}
?>