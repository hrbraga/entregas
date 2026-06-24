<?php
// api/get_dashboard_data.php

// 1. Impede que erros do PHP imprimam HTML e quebrem o JSON do Front-end
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 2. Inicia o buffer para "segurar" qualquer texto (ou erro) impresso acidentalmente
ob_start();

require '../config.php';
require '../auth/auth_check.php';

// Verifica se a sessão do usuário existe para evitar o aviso "Undefined array key"
if (!isset($_SESSION['user_id'])) {
    ob_clean(); // Limpa o lixo de texto
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado ou sessão expirada.']);
    exit;
}

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

    // Validação: Se não achar nada, retorna 0 (evita erro de ler offset em booleano)
    $total_pedido = $totals && $totals['total_pedido'] !== null ? (int)$totals['total_pedido'] : 0;
    $total_recebido = $totals && $totals['total_recebido'] !== null ? (int)$totals['total_recebido'] : 0;
    
    // Evita divisão por zero
    $progresso_geral = $total_pedido > 0 ? ($total_recebido / $total_pedido) * 100 : 0;

    // 2. Status por SKU
    $stmt_sku = $db_entregas->prepare("SELECT total_caixa, recebido FROM item_entrega WHERE user_id = ?");
    $stmt_sku->execute([$user_id]);
    $items = $stmt_sku->fetchAll(PDO::FETCH_ASSOC);

    $sku_status = [
        'nao_entregues' => 0,
        'parcialmente_entregues' => 0,
        'totalmente_entregues' => 0
    ];

    $grupos = [];

    foreach ($items as $item) {
        $t = (int)$item['total_caixa'];
        $r = (int)$item['recebido'];
        
        if ($r == 0 && $t > 0) {
            $sku_status['nao_entregues']++;
        } elseif ($r >= $t) {
            $sku_status['totalmente_entregues']++;
        } else {
            $sku_status['parcialmente_entregues']++;
        }
    }

    // 3. Status por Grupo
    $stmt_grupos = $db_entregas->prepare("SELECT grupo, total_caixa, recebido FROM item_entrega WHERE user_id = ?");
    $stmt_grupos->execute([$user_id]);
    $items_grupo = $stmt_grupos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items_grupo as $it) {
        // Usa null coalescing para garantir a string
        $g = isset($it['grupo']) && $it['grupo'] ? $it['grupo'] : 'Sem Grupo';
        
        if (!isset($grupos[$g])) {
            $grupos[$g] = ['nao_entregues' => 0, 'parcialmente_entregues' => 0, 'totalmente_entregues' => 0];
        }
        
        $t = (int)$it['total_caixa'];
        $r = (int)$it['recebido'];

        if ($r == 0 && $t > 0) $grupos[$g]['nao_entregues'] += $t;
        elseif ($r >= $t)      $grupos[$g]['totalmente_entregues'] += $t;
        else                   $grupos[$g]['parcialmente_entregues'] += $t;
    }

    // 4. Buscar Top 5 Itens com maior saldo a receber
    $stmt_top5 = $db_entregas->prepare("
        SELECT item, codigo_sap, (total_caixa - recebido) as falta
        FROM item_entrega 
        WHERE user_id = ? AND (total_caixa - recebido) > 0
        ORDER BY falta DESC
        LIMIT 5
    ");
    $stmt_top5->execute([$user_id]);
    $top_pendencias = $stmt_top5->fetchAll(PDO::FETCH_ASSOC);

    // 3. Monta o pacote final e limpa a sujeira do buffer
    $resposta = json_encode([
        'total_pedido' => $total_pedido,
        'total_recebido' => $total_recebido,
        'progresso_geral' => number_format($progresso_geral, 2),
        'sku_status' => $sku_status,
        'grupos' => $grupos,
        'top_pendencias' => $top_pendencias
    ]);

    ob_clean(); // Joga fora os possíveis HTMLs de erro gerados
    header('Content-Type: application/json');
    echo $resposta;

} catch (PDOException $e) {
    ob_clean(); // Apaga lixo HTML
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno de banco de dados.']);
    // Opcional: error_log($e->getMessage()); para ver no log do servidor depois
}
?>