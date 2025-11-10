<?php
// api/get_data.php
require '../config.php';
require '../auth_check.php'; // Protege o endpoint!

$user_id = $_SESSION['user_id'];

$stmt = $db_entregas->prepare("SELECT * FROM item_entrega WHERE user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($items);
?>