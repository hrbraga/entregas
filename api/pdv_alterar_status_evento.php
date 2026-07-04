<?php
session_start();
require '../config.php';
require '../auth/auth_franqueado_check.php'; // Só o franqueado pode encerrar

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

try {
    $novo_status = $data['status'];
    // Atualiza garantindo que o evento pertence ao usuário logado
    $stmt = $db_financeiro->prepare("UPDATE pdv_eventos SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$novo_status, $data['id'], $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>