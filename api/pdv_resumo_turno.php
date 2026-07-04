<?php
session_start();
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) exit;

try {
    // Acha o turno aberto
    $stmt = $db_financeiro->prepare("SELECT id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    if($turno) {
        // Soma tudo que foi vendido nesse turno
        $stmtTotal = $db_financeiro->prepare("SELECT SUM(total) as total_vendido FROM pdv_vendas WHERE turno_id = ? AND status = 'concluida'");
        $stmtTotal->execute([$turno['id']]);
        $res = $stmtTotal->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'total' => $res['total_vendido'] ?? 0]);
    } else {
        echo json_encode(['success' => false]);
    }
} catch(Exception $e) { echo json_encode(['success' => false]); }
?>