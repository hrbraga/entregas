<?php
session_start();
require '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão expirada.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];

try {
    $stmt = $db_financeiro->prepare("SELECT id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        echo json_encode(['success' => false, 'error' => 'Nenhum caixa em aberto foi encontrado para encerrar.']);
        exit;
    }

    // Cria dinamicamente as colunas de detalhamento no banco se não existirem
    $colunas = ['valor_fechamento', 'f_dinheiro', 'f_debito', 'f_credito', 'f_pix', 'f_alimentacao', 'f_outros'];
    foreach ($colunas as $col) {
        try { $db_financeiro->exec("ALTER TABLE pdv_turnos ADD COLUMN $col DECIMAL(10,2) DEFAULT 0.00"); } catch(PDOException $e) { /* Ignora se já existir */ }
    }

    // Salva os valores
    $stmtUpdate = $db_financeiro->prepare("
        UPDATE pdv_turnos 
        SET status = 'fechado', data_fechamento = CURRENT_TIMESTAMP, 
            valor_fechamento = ?, f_dinheiro = ?, f_debito = ?, f_credito = ?, f_pix = ?, f_alimentacao = ?, f_outros = ? 
        WHERE id = ?
    ");
    
    $stmtUpdate->execute([
        $data['valor_gaveta'] ?? 0,
        $data['f_dinheiro'] ?? 0,
        $data['f_debito'] ?? 0,
        $data['f_credito'] ?? 0,
        $data['f_pix'] ?? 0,
        $data['f_alimentacao'] ?? 0,
        $data['f_outros'] ?? 0,
        $turno['id']
    ]);
    
    echo json_encode(['success' => true, 'turno_id' => $turno['id']]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>