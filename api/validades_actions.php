<?php
require '../config.php';
require '../auth/auth_check.php';


header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Pega a data/hora atual CORRETA (Fuso Brasil configurado no config.php)
$agora = date('Y-m-d H:i:s'); 

// --- ADICIONAR ITEM ---
if ($action === 'add') {
    $codigo = $_POST['codigo'];
    $nome = $_POST['nome'];
    $validade = $_POST['validade'];
    $qtd = $_POST['qtd'];

    // Agora inserimos explicitamente a 'data_atualizacao' com $agora
    $sql = "INSERT INTO itens_validade (user_id, codigo_produto, nome_produto, data_validade, quantidade, data_atualizacao) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db_validades->prepare($sql);
    
    // Adicionamos $agora no array de execução
    $stmt->execute([$user_id, $codigo, $nome, $validade, $qtd, $agora]);
    
    echo json_encode(['success' => true]);
    exit;
}

// --- LISTAR ITENS ---
if ($action === 'list') {
    $sql = "SELECT * FROM itens_validade WHERE user_id = ? ORDER BY data_validade ASC";
    $stmt = $db_validades->prepare($sql);
    $stmt->execute([$user_id]);
    $itens = $stmt->fetchAll();
    echo json_encode($itens);
    exit;
}

// --- ATUALIZAR QUANTIDADE ---
if ($action === 'update_qtd') {
    $id = $_POST['id'];
    $qtd = $_POST['qtd'];

    if ($qtd <= 0) {
        $stmt = $db_validades->prepare("DELETE FROM itens_validade WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'deleted' => true]);
    } else {
        // Substituímos CURRENT_TIMESTAMP por ? e passamos $agora
        $stmt = $db_validades->prepare("UPDATE itens_validade SET quantidade = ?, data_atualizacao = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$qtd, $agora, $id, $user_id]);
        echo json_encode(['success' => true, 'deleted' => false]);
    }
    exit;
}

// --- EXCLUIR ---
if ($action === 'delete') {
    $id = $_POST['id'];
    $stmt = $db_validades->prepare("DELETE FROM itens_validade WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}
?>