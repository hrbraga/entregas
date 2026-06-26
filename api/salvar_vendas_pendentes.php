<?php
// api/salvar_vendas_pendentes.php
ini_set('display_errors', 0);
ob_start();

require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Nenhum dado enviado.']);
    exit;
}

try {
    $db_financeiro->beginTransaction();

    // AJUSTE: Alterado rcky_code para user_id no UPDATE
    $stmt = $db_financeiro->prepare("UPDATE gestao_metas SET venda_dia = ? WHERE user_id = ? AND data = ?");

    foreach ($data as $data_dia => $valor) {
        $stmt->execute([$valor, $user_id, $data_dia]);
    }

    $db_financeiro->commit();
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db_financeiro->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Erro SQL: ' . $e->getMessage()]);
}
?>