<?php
// api/get_dashboard_data.php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    // 1. Totais Gerais
    $stmt = $db_entregas->prepare("
        SELECT 
            SUM(total_caixa) as total_pedido, 
            SUM(recebido) as total_recebido 
        FROM item_entrega 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_pedido = (int)$totals['total_pedido'];
    $total_recebido = (int)$totals['total_recebido'];
    
    // Evita divisão por zero
    $progresso_geral = $total_pedido > 0 ? ($total_recebido / $total_pedido) * 100 : 0;

    // 2. Status por SKU (MANTIDO COMO ESTÁ: Contagem de linhas)
    // Nao Entregue: recebido = 0
    // Parcial: recebido > 0 AND recebido < total
    // Completo: recebido >= total
    
    $stmt_sku = $db_entregas->prepare("SELECT total_caixa, recebido FROM item_entrega WHERE user_id = ?");
    $stmt_sku->execute([$user_id]);
    $items = $stmt_sku->fetchAll(PDO::FETCH_ASSOC);

    $sku_status = [
        'nao_entregues' => 0,
        'parcialmente_entregues' => 0,
        'totalmente_entregues' => 0
    ];

    $grupos = [];

    // --- NOVO: Array para guardar os Top 5 ---
    $top_pendencias = [];

    foreach ($items as $item) {
        $t = (int)$item['total_caixa'];
        $r = (int)$item['recebido'];
        
        // Lógica SKU (Incrementa 1 por linha - MANTIDO)
        if ($r == 0 && $t > 0) {
            $sku_status['nao_entregues']++;
        } elseif ($r >= $t) {
            $sku_status['totalmente_entregues']++;
        } else {
            $sku_status['parcialmente_entregues']++;
        }
    }

    // 3. Status por Grupo (Query separada para facilitar)
    $stmt_grupos = $db_entregas->prepare("SELECT grupo, total_caixa, recebido FROM item_entrega WHERE user_id = ?");
    $stmt_grupos->execute([$user_id]);
    $items_grupo = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items_grupo as $it) {
        $g = $it['grupo'] ?: 'Sem Grupo';
        if (!isset($grupos[$g])) {
            $grupos[$g] = ['nao_entregues' => 0, 'parcialmente_entregues' => 0, 'totalmente_entregues' => 0];
        }
        
        $t = (int)$it['total_caixa'];
        $r = (int)$it['recebido'];

        // ALTERAÇÃO AQUI: Soma a quantidade ($t) ao invés de incrementar (++)
        if ($r == 0 && $t > 0) $grupos[$g]['nao_entregues'] += $t;
        elseif ($r >= $t)      $grupos[$g]['totalmente_entregues'] += $t;
        else                   $grupos[$g]['parcialmente_entregues'] += $t;
    }

    // 4. Buscar Top 5 Itens com maior saldo a receber (MANTIDO)
    // Ordena pelo cálculo (total_caixa - recebido) DECRESCENTE
    $stmt_top5 = $db_entregas->prepare("
        SELECT item, codigo_sap, (total_caixa - recebido) as falta
        FROM item_entrega 
        WHERE user_id = ? AND (total_caixa - recebido) > 0
        ORDER BY falta DESC
        LIMIT 5
    ");
    $stmt_top5->execute([$user_id]);
    $top_pendencias = $stmt_top5->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        'total_pedido' => $total_pedido,
        'total_recebido' => $total_recebido,
        'progresso_geral' => number_format($progresso_geral, 2),
        'sku_status' => $sku_status,
        'grupos' => $grupos,
        'top_pendencias' => $top_pendencias // Envia para o JS
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
?>