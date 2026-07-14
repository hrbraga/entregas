<?php
// api/admin_motor_promocoes.php
ini_set('display_errors', 0); error_reporting(0); header('Content-Type: application/json');
require '../config.php';
session_start();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $acao = $data['acao'] ?? '';

    if ($acao === 'criar_campanha') {
        $stmt = $db_financeiro->prepare("INSERT INTO motor_promocoes (nome_campanha, tipo_mecanica, qtd_gatilho, valor_beneficio, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['mecanica'], $data['gatilho'], $data['beneficio'], $data['inicio'], $data['fim']]);
    }
    elseif ($acao === 'editar_campanha') {
        $stmt = $db_financeiro->prepare("UPDATE motor_promocoes SET nome_campanha = ?, tipo_mecanica = ?, qtd_gatilho = ?, valor_beneficio = ?, data_inicio = ?, data_fim = ? WHERE id = ?");
        $stmt->execute([$data['nome'], $data['mecanica'], $data['gatilho'], $data['beneficio'], $data['inicio'], $data['fim'], $data['id']]);
    }
    elseif ($acao === 'excluir_campanha') {
        $stmt = $db_financeiro->prepare("DELETE FROM motor_promocoes WHERE id = ?");
        $stmt->execute([$data['id']]);
    }
    elseif ($acao === 'listar_produtos') {
        $caminho_produtos = str_replace('\\', '/', dirname(__DIR__)) . '/db/produtos.db';
        $db_financeiro->exec("ATTACH DATABASE '$caminho_produtos' AS p_db");
        
        $stmt = $db_financeiro->prepare("SELECT mp.id, p.nome_produto, p.codigo_interno FROM motor_promocoes_produtos mp JOIN p_db.produtos_unificados p ON mp.produto_id = p.id WHERE mp.promocao_id = ? ORDER BY p.nome_produto ASC");
        $stmt->execute([$data['promocao_id']]);
        echo json_encode(['success' => true, 'produtos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    elseif ($acao === 'add_produto') {
        $stmtCheck = $db_financeiro->prepare("SELECT id FROM motor_promocoes_produtos WHERE promocao_id = ? AND produto_id = ?");
        $stmtCheck->execute([$data['promocao_id'], $data['produto_id']]);
        if(!$stmtCheck->fetch()) {
            $stmt = $db_financeiro->prepare("INSERT INTO motor_promocoes_produtos (promocao_id, produto_id) VALUES (?, ?)");
            $stmt->execute([$data['promocao_id'], $data['produto_id']]);
        }
    }
    elseif ($acao === 'del_produto') {
        $stmt = $db_financeiro->prepare("DELETE FROM motor_promocoes_produtos WHERE id = ?");
        $stmt->execute([$data['id_registro']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>