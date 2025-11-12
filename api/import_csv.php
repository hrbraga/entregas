<?php
// api/import_csv.php (Versão Corrigida)
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit;
}

$file = $_FILES['file']['tmp_name'];
$file_name = $_FILES['file']['name'];

if (!str_ends_with($file_name, '.csv')) {
    echo json_encode(['success' => false, 'message' => 'Formato de arquivo inválido. Use CSV.']);
    exit;
}

// Inicia a transação
$db_entregas->beginTransaction();
try {

    // 1. Limpa os dados de entrega anteriores do utilizador
    $stmt_delete = $db_entregas->prepare("DELETE FROM item_entrega WHERE user_id = ?");
    $stmt_delete->execute([$user_id]);

    // 2. Abre o arquivo CSV
    if (($handle = fopen($file, "r")) === FALSE) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    // 3. Pula APENAS a linha de cabeçalho
    fgetcsv($handle, 1000, ";");

    // 4. Prepara a query de inserção (SQL)
    $stmt_insert = $db_entregas->prepare(
        "INSERT INTO item_entrega 
         (codigo_sap, item, grupo, pedido_loja, pedido_vd, total_caixa, a_receber, recebido, user_id) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)"
    );

    // 5. Lê o CSV linha por linha
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (empty($data[0]) || empty($data[1])) {
            continue; // Pula linha mal formatada
        }

        $codigo_sap = ltrim(trim($data[0]), '0'); // Remove zeros à esquerda
        $item = trim($data[1]);
        $grupo = trim($data[2]);
        $pedido_loja = (int) ($data[3] ?? 0);
        $pedido_vd = (int) ($data[4] ?? 0);

        $total_caixa = $pedido_loja + $pedido_vd;
        $a_receber = $total_caixa;

        $stmt_insert->execute([
            $codigo_sap,
            $item,
            $grupo,
            $pedido_loja,
            $pedido_vd,
            $total_caixa,
            $a_receber,
            $user_id
        ]);
    }
    fclose($handle);

    // 6. Confirma as alterações
    $db_entregas->commit();
    echo json_encode(['success' => true, 'message' => 'Pedidos importados com sucesso! Os dados anteriores foram substituídos.']);

} catch (Exception $e) {
    // 7. Se algo deu errado, desfaz tudo
    $db_entregas->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()]);
}
?>