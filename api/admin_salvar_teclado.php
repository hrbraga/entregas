<?php
ini_set('display_errors', 0); error_reporting(0); header('Content-Type: application/json');
require '../config.php';
session_start();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $acao = $data['acao'] ?? '';

    if ($acao === 'salvar') {
        $produto_id = $data['produto_id'];
        $posicao = $data['posicao'];
        
        // Se já existe um botão nessa posição, ele atualiza para o novo produto
        $stmtCheck = $db_financeiro->prepare("SELECT id FROM pdv_teclado_rapido WHERE posicao = ?");
        $stmtCheck->execute([$posicao]);
        $existe = $stmtCheck->fetch();

        if($existe) {
            $stmt = $db_financeiro->prepare("UPDATE pdv_teclado_rapido SET produto_id = ? WHERE posicao = ?");
            $stmt->execute([$produto_id, $posicao]);
        } else {
            $stmt = $db_financeiro->prepare("INSERT INTO pdv_teclado_rapido (produto_id, posicao) VALUES (?, ?)");
            $stmt->execute([$produto_id, $posicao]);
        }
    } 
    elseif ($acao === 'remover') {
        $stmt = $db_financeiro->prepare("DELETE FROM pdv_teclado_rapido WHERE id = ?");
        $stmt->execute([$data['id_registro']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>