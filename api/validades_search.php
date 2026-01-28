<?php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

if (strlen($q) < 3) { echo json_encode([]); exit; }

// Busca no produtos.db
$sql = "SELECT codigo_barras, codigo_interno, nome_produto 
        FROM produtos 
        WHERE codigo_barras LIKE ? OR codigo_interno LIKE ? OR nome_produto LIKE ? 
        LIMIT 5";

$stmt = $db_produtos->prepare($sql);
$term = "%$q%";
$stmt->execute([$term, $term, $term]);
echo json_encode($stmt->fetchAll());
?>