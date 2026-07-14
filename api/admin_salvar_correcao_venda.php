<?php
// Exibir todos os erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../config.php';
require_once '../auth/auth_franqueado_check.php';

try {
    // Pega o ID da loja (franqueado logado)
    $user_id = $_SESSION['user_id'];

    if (!$user_id) {
        throw new Exception('Usuário não autenticado.');
    }

    // Recebe o JSON enviado pelo JavaScript
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!isset($input['data']) || !isset($input['valor'])) {
         throw new Exception('Data e valor são obrigatórios.');
    }

    $data = $input['data']; // Formato esperado: YYYY-MM-DD
    $valor_venda = floatval($input['valor']);


    $sql = "INSERT INTO gestao_metas (user_id, data, venda_dia, meta_dia) 
            VALUES (:user_id, :data, :venda_dia, 0)
            ON CONFLICT(user_id, data) 
            DO UPDATE SET venda_dia = :venda_dia";

    $stmt = $db_financeiro->prepare($sql);
    
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':data', $data, PDO::PARAM_STR);
    $stmt->bindValue(':venda_dia', $valor_venda, PDO::PARAM_STR);

    $stmt->execute();

    echo json_json_encode(['success' => true]);

} catch (Exception $e) {
    // Retorna o erro em formato JSON
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>