<?php
// api/get_notas.php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

$stmt = $db_entregas->prepare("SELECT numero_nota, valor_total, data_emissao, data_importacao FROM nota_fiscal WHERE user_id = ?");
$stmt->execute([$user_id]);
$notas = $stmt->fetchAll();

echo json_encode($notas);
?>