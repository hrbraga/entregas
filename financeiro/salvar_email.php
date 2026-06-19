<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$id_usuario = $_SESSION['user_id'];
$acao = $_POST['acao'] ?? '';

// Segurança: Garante que a coluna de recusa existe na tabela usuarios
try {
    $db_financeiro->exec("ALTER TABLE usuarios ADD COLUMN recusou_alerta_email INTEGER DEFAULT 0");
} catch (Throwable $e) {}

if ($acao === 'salvar_email') {
    $email = $_POST['email'] ?? '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db_financeiro->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
        $stmt->execute([$email, $id_usuario]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'E-mail inválido.']);
    }
} elseif ($acao === 'recusar') {
    $stmt = $db_financeiro->prepare("UPDATE usuarios SET recusou_alerta_email = 1 WHERE id = ?");
    $stmt->execute([$id_usuario]);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ação não reconhecida.']);
}
?>