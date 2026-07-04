<?php
session_start();
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão expirada.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$fundo_caixa = isset($data['fundo_caixa']) ? (float)$data['fundo_caixa'] : 0.00;
$nome_operador = $data['nome_operador'] ?? 'Operador';
$evento_id = (int)($data['evento_id'] ?? 0);
$user_id = $_SESSION['user_id'];

try {
    // Insere o turno na tabela, agora com o nome e o evento atrelados
    $stmt = $db_financeiro->prepare("INSERT INTO pdv_turnos (user_id, fundo_caixa, status, nome_operador, evento_id) VALUES (?, ?, 'aberto', ?, ?)");
    $stmt->execute([$user_id, $fundo_caixa, $nome_operador, $evento_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>