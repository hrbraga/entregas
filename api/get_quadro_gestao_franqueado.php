<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';

header('Content-Type: application/json');

$franqueado_id = $_SESSION['user_id'];
$lojas_input = $_GET['lojas'] ?? 'todas';

// 1. Busca os IDs das lojas permitidas no banco de usuários
$stmt_ids = $db_users->prepare("SELECT id, username FROM user WHERE id_dono = ?");
$stmt_ids->execute([$franqueado_id]);

$lojas = $stmt_ids->fetchAll(PDO::FETCH_ASSOC);

$ids_lojas = [];
$nomesLojas = [];

foreach ($lojas as $loja) {
    $ids_lojas[] = $loja['id'];
    $nomesLojas[$loja['id']] = $loja['username'];
}

// Se não tiver lojas, bloqueia
if (empty($ids_lojas)) {
    echo json_encode(['error' => 'Nenhuma loja encontrada.']);
    exit;
}

// 2. Prepara a lista de IDs para o SQL do financeiro
$ids_string = implode(',', $ids_lojas);

// Agora filtramos usando a lista de IDs que acabamos de buscar
if ($lojas_input === 'todas') {
    $condicao_lojas = "user_id IN ($ids_string)";
} else {
    // Filtro manual para garantir que o usuário só peça o que é dele
    $ids_filtrados = array_intersect(explode(',', $lojas_input), array_map('strval', $ids_lojas));
    $condicao_lojas = "user_id IN (" . implode(',', array_map('intval', $ids_filtrados)) . ")";
}

$mes_atual = date('Y-m');
$hoje = date('Y-m-d');

try {
    // Busca dados somados de todas as lojas selecionadas
    $stmtMes = $db_financeiro->prepare("
        SELECT 
            SUM(meta_dia) as meta_total,
            SUM(CASE WHEN data < ? THEN meta_dia ELSE 0 END) as meta_acumulada,
            SUM(CASE WHEN data < ? THEN venda_dia ELSE 0 END) as venda_acumulada
        FROM gestao_metas
        WHERE $condicao_lojas AND data LIKE ?
    ");
    $stmtMes->execute([$hoje, $hoje, $mes_atual . '-%']);
    $dadosMes = $stmtMes->fetch(PDO::FETCH_ASSOC);

    $meta_total = (float)($dadosMes['meta_total'] ?? 0);
    $meta_acumulada = (float)($dadosMes['meta_acumulada'] ?? 0);
    $venda_acumulada = (float)($dadosMes['venda_acumulada'] ?? 0);

    $atingimento = $meta_acumulada > 0 ? ($venda_acumulada / $meta_acumulada) * 100 : 0;
    $gap = $meta_acumulada - $venda_acumulada;

    // 2. Meta de Hoje
    $stmtHoje = $db_financeiro->prepare("SELECT SUM(meta_dia) FROM gestao_metas WHERE $condicao_lojas AND data = ?");
    $stmtHoje->execute([$hoje]);
    $meta_hoje = (float)($stmtHoje->fetchColumn() ?: 0);

    // 3. Último Dia Útil (Agrupa por data e soma)
    $stmtUltimoDia = $db_financeiro->prepare("
        SELECT data, SUM(meta_dia) as meta_dia, SUM(venda_dia) as venda_dia 
        FROM gestao_metas 
        WHERE $condicao_lojas AND data < ? AND data LIKE ? 
        GROUP BY data 
        HAVING SUM(meta_dia) > 0
        ORDER BY data DESC LIMIT 1
    ");
    $stmtUltimoDia->execute([$hoje, $mes_atual . '-%']);
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

    // 4. Meta de Hoje Ajustada
    $stmtDiasRestantes = $db_financeiro->prepare("
        SELECT COUNT(DISTINCT data) 
        FROM gestao_metas 
        WHERE $condicao_lojas AND data >= ? AND data LIKE ? AND meta_dia > 0
    ");
    $stmtDiasRestantes->execute([$hoje, $mes_atual . '-%']);
    $dias_restantes_uteis = (int)$stmtDiasRestantes->fetchColumn();

    $meta_ajustada = ($gap > 0 && $dias_restantes_uteis > 0) ? ($meta_hoje + ($gap / $dias_restantes_uteis)) : $meta_hoje;

    // 5. Dados do Gráfico
    $stmtGrafico = $db_financeiro->prepare("
        SELECT data, SUM(meta_dia) as meta_dia, SUM(venda_dia) as venda_dia 
        FROM gestao_metas 
        WHERE $condicao_lojas AND data LIKE ? 
        GROUP BY data 
        ORDER BY data ASC
    ");
    $stmtGrafico->execute([$mes_atual . '-%']);

    $grafico_datas = [];
    $grafico_metas = [];
    $grafico_vendas = [];
    $daily_metas_brutas = []; // NOVO
    $daily_vendas_brutas = []; // NOVO
    $acumulado_m = 0;
    $acumulado_v = 0;

        while ($row = $stmtGrafico->fetch(PDO::FETCH_ASSOC)) {
        $grafico_datas[] = $row['data'];

        // Adiciona os valores brutos do dia
        $daily_metas_brutas[] = (float)$row['meta_dia'];
        $daily_vendas_brutas[] = (float)$row['venda_dia'];

        // Acumulados para o gráfico
        $acumulado_m += (float)$row['meta_dia'];
        $grafico_metas[] = $acumulado_m;

        if ($row['data'] < $hoje) {
            $acumulado_v += (float)$row['venda_dia'];
            $grafico_vendas[] = $acumulado_v;
        } else {
            $grafico_vendas[] = null;
        }
    }

// ======================================================================
// DADOS DETALHADOS PARA AUDITORIA
// ======================================================================

$auditoria = [];

$stmtAuditoria = $db_financeiro->prepare("
    SELECT
        data,
        user_id,
        meta_dia,
        venda_dia
    FROM gestao_metas
    WHERE $condicao_lojas
      AND data LIKE ?
    ORDER BY data ASC, user_id ASC
");

$stmtAuditoria->execute([$mes_atual . '-%']);

while ($row = $stmtAuditoria->fetch(PDO::FETCH_ASSOC)) {

    $data = $row['data'];

    if (!isset($auditoria[$data])) {
        $auditoria[$data] = [
            'data' => $data,
            'lojas' => []
        ];
    }

    $auditoria[$data]['lojas'][] = [
        'id'    => (int)$row['user_id'],
        'nome'  => $nomesLojas[$row['user_id']] ?? ('Loja '.$row['user_id']),
        'meta'  => (float)$row['meta_dia'],
        'venda' => (float)$row['venda_dia']
    ];
}

$auditoria = array_values($auditoria);

    ob_clean();
    echo json_encode([
        'success' => true,
        'dias_pendentes' => [], // Franqueado não preenche pendências no master
        'meta_total' => $meta_total,
        'meta_acumulada' => $meta_acumulada,
        'venda_acumulada' => $venda_acumulada,
        'atingimento' => $atingimento,
        'gap' => $gap,
        'meta_ontem' => $meta_ontem,
        'venda_ontem' => $venda_ontem,
        'data_ultimo_dia' => $data_ultimo_dia,
        'meta_hoje' => $meta_hoje,
        'meta_ajustada' => $meta_ajustada,
        'daily_metas' => $daily_metas_brutas,
        'daily_vendas' => $daily_vendas_brutas,
        'grafico_datas' => $grafico_datas,
        'grafico_metas' => $grafico_metas,
        'grafico_vendas' => $grafico_vendas,
        'auditoria' => $auditoria
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Erro interno BD: ' . $e->getMessage()]);
}
