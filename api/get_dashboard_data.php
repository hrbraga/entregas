<?php
// api/get_dashboard_data.php (Ficheiro Novo)
require '../config.php';
require '../auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

$stmt = $db_entregas->prepare("SELECT * FROM item_entrega WHERE user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$total_pedido = 0;
$total_recebido = 0;
$skus_nao_entregues = 0;
$skus_parcialmente_entregues = 0;
$skus_totalmente_entregues = 0;
$grupos = [];

foreach ($items as $item) {
    $total_pedido += (int)$item['total_caixa'];
    $total_recebido += (int)$item['recebido'];
    
    $recebido = (int)$item['recebido'];
    $total_caixa = (int)$item['total_caixa'];

    // Status por SKU
    if ($recebido == 0) {
        $skus_nao_entregues++;
    } elseif ($recebido < $total_caixa) {
        $skus_parcialmente_entregues++;
    } elseif ($recebido >= $total_caixa) {
        $skus_totalmente_entregues++;
    }
    
    // Status por Grupo
    $grupo = $item['grupo'];
    if (!isset($grupos[$grupo])) {
        $grupos[$grupo] = ['nao_entregues' => 0, 'parcialmente_entregues' => 0, 'totalmente_entregues' => 0];
    }
    
    if ($recebido == 0) {
        $grupos[$grupo]['nao_entregues'] += $total_caixa;
    } elseif ($recebido < $total_caixa) {
        $grupos[$grupo]['parcialmente_entregues'] += $total_caixa;
    } elseif ($recebido >= $total_caixa) {
        $grupos[$grupo]['totalmente_entregues'] += $total_caixa;
    }
}

$progresso_geral = ($total_pedido > 0) ? ($total_recebido / $total_pedido) * 100 : 0;

$data = [
    "progresso_geral" => round($progresso_geral, 2),
    "total_pedido" => $total_pedido,
    "total_recebido" => $total_recebido,
    "sku_status" => [
        "nao_entregues" => $skus_nao_entregues,
        "parcialmente_entregues" => $skus_parcialmente_entregues,
        "totalmente_entregues" => $skus_totalmente_entregues
    ],
    "grupos" => $grupos
];

echo json_encode($data);
?>