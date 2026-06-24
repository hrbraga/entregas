<?php
// api/importar_metas_csv.php
ini_set('display_errors', 0);
ob_start();

require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

// Garante que pega a loja correta
$rcky_code = $_SESSION['user_rcky'] ?? $_SESSION['rcky_code'] ?? '0000'; 

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    ob_clean();
    echo json_encode(['error' => 'Nenhum dado recebido.']);
    exit;
}

try {
    $db_financeiro->beginTransaction();

    // Inserimos a meta. Se já houver (ON CONFLICT), nós apenas atualizamos.
    $stmt = $db_financeiro->prepare("
        INSERT INTO gestao_metas (rcky_code, data, meta_dia) 
        VALUES (?, ?, ?) 
        ON CONFLICT(rcky_code, data) DO UPDATE SET meta_dia = excluded.meta_dia
    ");

    foreach ($data as $row) {
        $dataStr = $row['data'];
        $metaStr = $row['meta'];

        // Converter DD/MM/YYYY para YYYY-MM-DD
        $dt_parts = explode('/', $dataStr);
        if (count($dt_parts) >= 3) {
            $data_sql = $dt_parts[2] . '-' . $dt_parts[1] . '-' . $dt_parts[0];
            
            // Tratamento da Moeda: "2433,79" -> "2433.79" ou "2.433,79" -> "2433.79"
            $metaStr = str_replace('.', '', $metaStr); // Tira ponto de milhar se houver
            $metaStr = str_replace(',', '.', $metaStr); // Troca virgula decimal por ponto
            $meta_sql = (float) $metaStr;

            $stmt->execute([$rcky_code, $data_sql, $meta_sql]);
        }
    }

    $db_financeiro->commit();
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db_financeiro->rollBack();
    ob_clean();
    echo json_encode(['error' => 'Erro interno BD: ' . $e->getMessage()]);
}
?>