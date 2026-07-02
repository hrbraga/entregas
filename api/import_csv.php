<?php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'Nenhum arquivo enviado.']);
    exit;
}

try {
    $handle = fopen($_FILES['file']['tmp_name'], "r");
    $data_importada = [];
    $timestamps = [];
    $row = 0;

    // Lê o arquivo CSV
    while (($coluna = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // Pula o cabeçalho

        // Coluna A (Índice 0) = Data | Coluna G (Índice 6) = Meta
        $data_raw = $coluna[0]; 
        $meta_raw = $coluna[6]; 

        if (empty($data_raw)) continue;

        // Formata data de DD/MM/YYYY para YYYY-MM-DD
        $dt = DateTime::createFromFormat('d/m/Y', $data_raw);
        if (!$dt) continue; 
        
        $data_iso = $dt->format('Y-m-d');
        
        // Limpa valor da meta (remove pontos e converte vírgula para ponto)
        // Ex: "4.270,00" vira 4270.00
        $meta_limpa = str_replace(['.', ','], ['', '.'], $meta_raw);
        
        $data_importada[$data_iso] = (float)$meta_limpa;
        $timestamps[] = strtotime($data_iso);
    }
    fclose($handle);

    // --- LÓGICA DO DRIBLE NOS DIAS VAZIOS (Backfill) ---
    $min_ts = min($timestamps);
    $max_ts = max($timestamps);
    $user_id = $_SESSION['user_id'];

    $db_financeiro->beginTransaction();
    
    $stmtCheck = $db_financeiro->prepare("SELECT id FROM gestao_metas WHERE user_id = ? AND data = ?");
    $stmtUpdate = $db_financeiro->prepare("UPDATE gestao_metas SET meta_dia = ? WHERE id = ?");
    $stmtInsert = $db_financeiro->prepare("INSERT INTO gestao_metas (user_id, data, meta_dia) VALUES (?, ?, ?)");

    // Percorre todos os dias entre a primeira e a última data da planilha
    for ($ts = $min_ts; $ts <= $max_ts; $ts += 86400) {
        $data_atual = date('Y-m-d', $ts);
        
        // Se o dia não estiver na planilha, o valor é 0
        $meta = isset($data_importada[$data_atual]) ? $data_importada[$data_atual] : 0;

        $stmtCheck->execute([$user_id, $data_atual]);
        $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            $stmtUpdate->execute([$meta, $existe['id']]);
        } else {
            $stmtInsert->execute([$user_id, $data_atual, $meta]);
        }
    }

    $db_financeiro->commit();
    echo json_encode(['success' => true, 'message' => 'CSV processado e dias vazios preenchidos!']);

} catch (Exception $e) {
    if(isset($db_financeiro)) $db_financeiro->rollBack();
    echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
}
?>