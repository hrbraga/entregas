<?php
// api/buscar_produto_pdv.php
session_start();
require '../config.php';
header('Content-Type: application/json');

$termo = $_GET['q'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if (empty(trim($termo))) {
    echo json_encode(['success' => false, 'error' => 'Termo vazio']);
    exit;
}

try {
    // 1. Descobre qual evento está rolando no caixa atual
    $stmtTurno = $db_financeiro->prepare("SELECT evento_id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
    $stmtTurno->execute([$user_id]);
    $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

    $controla_estoque = false;
    $evento_id = $turno ? (int)$turno['evento_id'] : 0;

    // 2. Verifica se o evento exige controle rigoroso de estoque
    if ($evento_id > 0) {
        $stmtEv = $db_financeiro->prepare("SELECT controla_estoque FROM pdv_eventos WHERE id = ?");
        $stmtEv->execute([$evento_id]);
        $ev = $stmtEv->fetch(PDO::FETCH_ASSOC);
        if ($ev && $ev['controla_estoque'] == 1) {
            $controla_estoque = true;
        }
    }

    // 3. Busca o Produto pelo Código ou Nome
    $stmt = $db_produtos->prepare("
        SELECT id, codigo_interno, nome_produto as nome, preco2 as preco 
        FROM produtos_unificados 
        WHERE codigo_barras = :termo 
           OR codigo_interno = :termo 
           OR nome_produto LIKE :termo_like 
        LIMIT 15
    ");
    $stmt->execute([':termo' => $termo, ':termo_like' => "%" . trim($termo) . "%"]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Injeta a trava de estoque na resposta
    if ($produtos) {
        foreach ($produtos as $key => $p) {
            if ($controla_estoque) {
                $stmtEst = $db_financeiro->prepare("SELECT quantidade_atual FROM pdv_estoque_evento WHERE evento_id = ? AND produto_id = ?");
                $stmtEst->execute([$evento_id, $p['id']]);
                $est = $stmtEst->fetch(PDO::FETCH_ASSOC);
                // Se não achou na tabela, o estoque é 0
                $produtos[$key]['estoque'] = $est ? (float)$est['quantidade_atual'] : 0;
            } else {
                // Se o evento for "Livre", a gente passa a tag 'ilimitado'
                $produtos[$key]['estoque'] = 'ilimitado';
            }
        }
    }

    if ($produtos && count($produtos) > 0) {
        echo json_encode(['success' => true, 'produtos' => $produtos]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Nenhum produto encontrado.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>