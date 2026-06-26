<?php
// api/resetar_mes.php
ini_set('display_errors', 0);
ob_start();

require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

// Pega o ID da sessão do auth_check
$user_id = $_SESSION['user_id'] ?? null; 
$mes_atual = date('Y-m'); 

if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

try {
    $db_financeiro->beginTransaction();

    // AQUI ESTAVA O ERRO: TEM QUE SER user_id e não rcky_code
    $stmt = $db_financeiro->prepare("DELETE FROM gestao_metas WHERE user_id = ? AND data LIKE ?");
    $stmt->execute([$user_id, $mes_atual . '-%']);

    $db_financeiro->commit();
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db_financeiro->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Erro Banco de Dados: ' . $e->getMessage()]);
}
?>