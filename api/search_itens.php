<?php
// api/search_items.php
require '../config.php';
require '../auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

$search_term = '%' . $query . '%';
$codigo_query = '%' . ltrim($query, '0') . '%';

$stmt = $db_entregas->prepare(
    "SELECT codigo_sap, item, total_caixa, recebido, a_receber, id 
     FROM item_entrega 
     WHERE user_id = ? AND (codigo_sap LIKE ? OR item LIKE ?) 
     LIMIT 10"
);
$stmt->execute([$user_id, $codigo_query, $search_term]);
$items = $stmt->fetchAll();

// Renomeia 'total_caixa' para 'pedido_total' para bater com o JS
$results = [];
foreach ($items as $item) {
    $results[] = [
        'codigo_sap' => $item['codigo_sap'],
        'item' => $item['item'],
        'pedido_total' => $item['total_caixa'], // Renomeado
        'recebido' => $item['recebido'],
        'a_receber' => $item['a_receber'],
        'id' => $item['id']
    ];
}

echo json_encode($results);
?>