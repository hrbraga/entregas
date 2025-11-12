<?php
// api/get_data.php (Versão Correta e Simples)
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

// Esta é a query simples que o script.js espera
// Apenas devolve a lista de itens como um array JSON
$stmt = $db_entregas->prepare("SELECT * FROM item_entrega WHERE user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

echo json_encode($items);
?>