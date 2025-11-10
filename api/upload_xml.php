<?php
// api/upload_xml.php
require '../config.php';
require '../auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file']) && !isset($_FILES['file[]'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit;
}

// O JS envia 'file[]' (do script.js)
$files = $_FILES['file'] ?? $_FILES['file[]'];
$success_files = [];
$failed_files = [];

// Transforma o array de ficheiros num formato mais fácil de iterar
$file_list = [];
if (is_array($files['name'])) {
    foreach ($files['name'] as $key => $name) {
        $file_list[] = [
            'name' => $name,
            'tmp_name' => $files['tmp_name'][$key],
            'error' => $files['error'][$key]
        ];
    }
} else {
    $file_list[] = $files; // Se for só um ficheiro
}


foreach ($file_list as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $failed_files[] = "{$file['name']} (Erro no upload)";
        continue;
    }

    if (!str_ends_with($file['name'], '.xml')) {
        $failed_files[] = "{$file['name']} (Formato inválido)";
        continue;
    }

    $db_entregas->beginTransaction();
    try {
        $xml = simplexml_load_file($file['tmp_name']);
        if ($xml === false) {
            throw new Exception("Erro ao ler o XML.");
        }
        
        $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        
        $n_nf = (string) $xml->xpath('//nfe:infNFe/nfe:ide/nfe:nNF')[0];
        
        // Verifica se a nota já existe
        $stmt_check = $db_entregas->prepare("SELECT id FROM nota_fiscal WHERE numero_nota = ? AND user_id = ?");
        $stmt_check->execute([$n_nf, $user_id]);
        if ($stmt_check->fetch()) {
            throw new Exception("NF {$n_nf} já importada");
        }

        $v_nf = (string) $xml->xpath('//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vNF')[0];
        $data_emi_str = (string) $xml->xpath('//nfe:infNFe/nfe:ide/nfe:dhEmi')[0];
        $data_emi = explode('T', $data_emi_str)[0];
        $data_dd_mm_aaaa = date('d-m-Y', strtotime($data_emi));
        $data_importacao = date('d-m-Y');

        // 1. Insere a Nota Fiscal
        $stmt_nota = $db_entregas->prepare(
            "INSERT INTO nota_fiscal (numero_nota, data_emissao, data_importacao, valor_total, user_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt_nota->execute([$n_nf, $data_dd_mm_aaaa, $data_importacao, $v_nf, $user_id]);
        $nota_id = $db_entregas->lastInsertId();

        // 2. Prepara os updates e inserts dos itens
        $stmt_update_item = $db_entregas->prepare(
            "UPDATE item_entrega 
             SET recebido = recebido + ?, a_receber = a_receber - ?
             WHERE codigo_sap = ? AND user_id = ?"
        );
        $stmt_insert_item_nota = $db_entregas->prepare(
            "INSERT INTO item_nota_fiscal (nota_id, codigo_sap, quantidade) VALUES (?, ?, ?)"
        );
        
        $det_tags = $xml->xpath('//nfe:infNFe/nfe:det');
        foreach ($det_tags as $det_tag) {
            $c_prod = ltrim((string) $det_tag->xpath('.//nfe:prod/nfe:cProd')[0], '0');
            $q_com = (float) $det_tag->xpath('.//nfe:prod/nfe:qCom')[0];

            // 3. Atualiza o stock
            $stmt_update_item->execute([$q_com, $q_com, $c_prod, $user_id]);
            
            // 4. Regista o item na nota (para futura exclusão)
            $stmt_insert_item_nota->execute([$nota_id, $c_prod, $q_com]);
        }

        $db_entregas->commit();
        $success_files[] = $file['name'];

    } catch (Exception $e) {
        $db_entregas->rollBack();
        $failed_files[] = "{$file['name']} (Erro: {$e->getMessage()})";
    }
}

$message = "Processamento concluído. Sucessos: " . count($success_files) . ". Falhas: " . count($failed_files) . ".";
if (!empty($failed_files)) {
    $message .= " Detalhes das falhas: " . implode('; ', $failed_files);
}

echo json_encode(['success' => (count($success_files) > 0), 'message' => $message]);
?>