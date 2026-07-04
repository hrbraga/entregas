<?php
session_start();
require '../config.php';
require '../auth/auth_franqueado_check.php'; // A barreira aqui também protege contra injeções externas

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($_SESSION['user_id']) || !$data) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado ou dados vazios.']);
    exit;
}

if (empty($data['nome_evento']) || empty($data['data_evento'])) {
    echo json_encode(['success' => false, 'error' => 'Nome e data são obrigatórios.']);
    exit;
}

try {
    $stmt = $db_financeiro->prepare("INSERT INTO pdv_eventos (user_id, nome_evento, data_evento, controla_estoque, status) VALUES (?, ?, ?, ?, 'ativo')");
    $stmt->execute([
        $_SESSION['user_id'],
        $data['nome_evento'],
        $data['data_evento'],
        (int)$data['controla_estoque']
    ]);

    echo json_encode(['success' => true, 'evento_id' => $db_financeiro->lastInsertId()]);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>