<?php
// api/importar_metas_csv.php
ini_set('display_errors', 0);
ob_start();

require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null; 

if (!$user_id) {
    ob_clean();
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(['error' => 'Nenhum dado recebido.']);
    exit;
}

try {
    $db_financeiro->beginTransaction();

    // Lógica inteligente (sem depender de regras de conflito do SQLite)
    $stmtCheck = $db_financeiro->prepare("SELECT id FROM gestao_metas WHERE user_id = ? AND data = ?");
    $stmtUpdate = $db_financeiro->prepare("UPDATE gestao_metas SET meta_dia = ? WHERE id = ?");
    $stmtInsert = $db_financeiro->prepare("INSERT INTO gestao_metas (user_id, data, meta_dia) VALUES (?, ?, ?)");

    foreach ($data as $row) {
        $dataStr = $row['data'];
        $metaStr = $row['meta'];

        $dt_parts = explode('/', $dataStr);
        if (count($dt_parts) >= 3) {
            // Converte a data para o padrão do Banco
            $data_sql = $dt_parts[2] . '-' . $dt_parts[1] . '-' . $dt_parts[0];
            
            // Limpa formatação de grana
            $metaStr = str_replace('.', '', $metaStr);
            $metaStr = str_replace(',', '.', $metaStr);
            $meta_sql = (float) $metaStr;

            // Verifica se este dia já existe para este usuário
            $stmtCheck->execute([$user_id, $data_sql]);
            $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                // Se já tem meta cadastrada nesse dia, só atualiza o valor
                $stmtUpdate->execute([$meta_sql, $existe['id']]);
            } else {
                // Se o dia não existe, cria uma nova linha
                $stmtInsert->execute([$user_id, $data_sql, $meta_sql]);
            }
        }
    }

    $db_financeiro->commit();
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db_financeiro->rollBack();
    ob_clean();
    echo json_encode(['error' => 'Erro BD: ' . $e->getMessage()]);
}
?>