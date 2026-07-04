<?php
ini_set('display_errors', 0); error_reporting(0); header('Content-Type: application/json');
require '../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $acao = $data['acao'] ?? '';
    $id_registro = $data['id_registro'] ?? 0; // Este é o ID da tabela pdv_estoque_evento
    
    if ($acao === 'excluir') {
        $stmt = $db_financeiro->prepare("DELETE FROM pdv_estoque_evento WHERE id = ?");
        $stmt->execute([$id_registro]);
    } elseif ($acao === 'atualizar') {
        $nova_qtd = (float)$data['quantidade'];
        $stmt = $db_financeiro->prepare("UPDATE pdv_estoque_evento SET quantidade_atual = ? WHERE id = ?");
        $stmt->execute([$nova_qtd, $id_registro]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>