<?php

// api/upload_xml.php
require '../config.php';
require '../auth/auth_check.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file']) && !isset($_FILES['file[]'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit;
}

$files = $_FILES['file'] ?? $_FILES['file[]'];
$success_files = [];
$failed_files = [];

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
    $file_list[] = $files; 
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
        
        // Registamos o namespace 'nfe' no documento principal
        $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        
        $n_nf_result = $xml->xpath('//nfe:infNFe/nfe:ide/nfe:nNF');
        if (empty($n_nf_result)) {
            throw new Exception("XML inválido: Tag <nNF> (número da nota) não encontrada.");
        }
        $n_nf = (string) $n_nf_result[0];
        
        $stmt_check = $db_entregas->prepare("SELECT id FROM nota_fiscal WHERE numero_nota = ? AND user_id = ?");
        $stmt_check->execute([$n_nf, $user_id]);
        if ($stmt_check->fetch()) {
            throw new Exception("NF {$n_nf} já importada");
        }

        $v_nf_result = $xml->xpath('//nfe:infNFe/nfe:total/nfe:ICMSTot/nfe:vNF');
        if (empty($v_nf_result)) {
            throw new Exception("XML inválido: Tag <vNF> (valor da nota) não encontrada.");
        }
        $v_nf = (string) $v_nf_result[0];

        $data_emi_result = $xml->xpath('//nfe:infNFe/nfe:ide/nfe:dhEmi');
         if (empty($data_emi_result)) {
            throw new Exception("XML inválido: Tag <dhEmi> (data de emissão) não encontrada.");
        }
        $data_emi_str = (string) $data_emi_result[0];

        $data_emi = explode('T', $data_emi_str)[0];
        $data_dd_mm_aaaa = date('d-m-Y', strtotime($data_emi));
        $data_importacao = date('d-m-Y');

        $stmt_nota = $db_entregas->prepare(
            "INSERT INTO nota_fiscal (numero_nota, data_emissao, data_importacao, valor_total, user_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt_nota->execute([$n_nf, $data_dd_mm_aaaa, $data_importacao, $v_nf, $user_id]);
        $nota_id = $db_entregas->lastInsertId();

        $stmt_update_item = $db_entregas->prepare(
            "UPDATE item_entrega 
             SET recebido = recebido + ?, a_receber = a_receber - ?
             WHERE codigo_sap = ? AND user_id = ?"
        );
        $stmt_insert_item_nota = $db_entregas->prepare(
            "INSERT INTO item_nota_fiscal (nota_id, codigo_sap, quantidade) VALUES (?, ?, ?)"
        );
        
        $det_tags = $xml->xpath('//nfe:infNFe/nfe:det');
        if (empty($det_tags)) {
            throw new Exception("XML inválido: Nenhum item <det> foi encontrado na nota.");
        }

        // --- INÍCIO DA CORREÇÃO ---
        foreach ($det_tags as $det_tag) {
            
            // CORREÇÃO: Re-registamos o namespace no elemento $det_tag
            // para que o xpath() funcione dentro deste contexto mais pequeno.
            $det_tag->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            // Agora usamos o xpath() original, que estava logicamente correto.
            $c_prod_result = $det_tag->xpath('.//nfe:prod/nfe:cProd');
            if (empty($c_prod_result)) {
                 throw new Exception("XML inválido: Item sem <cProd> (código do produto).");
            }
            $c_prod = ltrim((string) $c_prod_result[0], '0');

            $q_com_result = $det_tag->xpath('.//nfe:prod/nfe:qCom');
            if (empty($q_com_result)) {
                 throw new Exception("XML inválido: Item $c_prod sem <qCom> (quantidade).");
            }
            $q_com = (float) $q_com_result[0];
            // --- FIM DA CORREÇÃO ---

            // 3. Atualiza o stock
            $stmt_update_item->execute([$q_com, $q_com, $c_prod, $user_id]);
            
            // 4. Regista o item na nota (para futura exclusão)
            $stmt_insert_item_nota->execute([$nota_id, $c_prod, $q_com]);
        }

        $db_entregas->commit();
        $success_files[] = $file['name'];

    } catch (Throwable $e) { // Captura Erros e Exceções
        $db_entregas->rollBack();
        error_log("Erro no upload de XML: " . $e->getMessage() . " no ficheiro " . $e->getFile() . " na linha " . $e->getLine());
        $failed_files[] = "{$file['name']} (Erro: {$e->getMessage()})";
    }
}

$message = "Processamento concluído. Sucessos: " . count($success_files) . ". Falhas: " . count($failed_files) . ".";
if (!empty($failed_files)) {
    $message .= " Detalhes das falhas: " . implode('; ', $failed_files);
}

echo json_encode(['success' => (count($success_files) > 0), 'message' => $message]);
?>