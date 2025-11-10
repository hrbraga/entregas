<?php
// api/upload_xml.php
require '../config.php';
require '../auth_check.php';

// ... (lógica para verificar $_FILES['file']) ...

$file_tmp = $_FILES['file']['tmp_name']; // Cuidado, 'file' é um array se for 'multiple'

// Carrega o XML
$xml = simplexml_load_file($file_tmp);

// REGISTA OS NAMESPACES (Obratório para NF-e)
$xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

// Acede às tags usando XPath (forma mais segura)
$n_nf_nodes = $xml->xpath('//nfe:infNFe/nfe:ide/nfe:nNF');
$n_nf = (string) $n_nf_nodes[0];

// ... (Verifique se $n_nf já existe na $db_entregas, como no seu app.py) ...

// Inicia a transação
$db_entregas->beginTransaction();
try {
    // ... (INSERT INTO nota_fiscal ...) ...
    $nota_id = $db_entregas->lastInsertId();

    $det_tags = $xml->xpath('//nfe:infNFe/nfe:det');
    foreach ($det_tags as $det_tag) {
        $c_prod_nodes = $det_tag->xpath('.//nfe:prod/nfe:cProd');
        $q_com_nodes = $det_tag->xpath('.//nfe:prod/nfe:qCom');

        $c_prod = ltrim((string) $c_prod_nodes[0], '0');
        $q_com = (float) $q_com_nodes[0];

        // ... (UPDATE item_entrega SET ... WHERE codigo_sap = ? AND user_id = ?) ...
        // ... (INSERT INTO item_nota_fiscal ...) ...
    }

    $db_entregas->commit();
    echo json_encode(['success' => true, 'message' => 'XML importado!']);

} catch (Exception $e) {
    $db_entregas->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>