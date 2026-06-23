<?php
require '../config.php'; 

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

// FUNÇÃO NOVA: Transforma vírgula em ponto para aceitar valores quebrados corretamente
function converterDecimal($valor) {
    if (empty($valor)) return 0;
    $valor = str_replace(' ', '', $valor); // Remove espaços acidentais
    $valor = str_replace(',', '.', $valor); // Troca vírgula por ponto
    return floatval($valor);
}

try {
    // --- 1. SALVAR (Criar ou Editar) ---
    if ($action === 'salvar') {
        $id = $_POST['id'] ?? '';
        
        // Dados Básicos e Etiquetas
        $codigo_barras = !empty($_POST['codigo_barras']) ? $_POST['codigo_barras'] : null;
        $codigo_interno = !empty($_POST['codigo_interno']) ? $_POST['codigo_interno'] : null;
        $nome_produto = $_POST['nome_produto'];
        $preco_venda = converterDecimal($_POST['preco_venda']); 
        $preco2 = converterDecimal($_POST['preco2'] ?? 0);
        $campanha = $_POST['campanha'] ?? '';

        // Dados de Custos
        $qtCaixa = converterDecimal($_POST['qtCaixa'] ?? 0);
        $valorUn = converterDecimal($_POST['valorUn'] ?? 0);
        
        // Cálculos Financeiros Automáticos
        $royalties = $valorUn * 0.50;
        $st = converterDecimal($_POST['st'] ?? 0);
        $ipi = converterDecimal($_POST['ipi'] ?? 0);
        $txs = converterDecimal($_POST['txsAdicionais'] ?? 0);
        $midia = converterDecimal($_POST['txMidia'] ?? 0);
        
        $custoCaixa = $valorUn + $royalties + $st + $ipi + $txs + $midia;
        $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
        
        $mbBruta = ($preco2 > 0) ? (1 - ($custoUn / $preco2)) * 100 : 0;
        $baseLiq = $valorUn + $royalties;
        $custoBaseUn = ($qtCaixa > 0) ? ($baseLiq / $qtCaixa) : 0;
        $mbLiquida = ($preco2 > 0) ? (1 - ($custoBaseUn / $preco2)) * 100 : 0;

        $dados = [
            ':codigo_barras' => $codigo_barras,
            ':codigo_interno' => $codigo_interno,
            ':nome_produto' => $nome_produto,
            ':campanha' => $campanha,
            ':preco_venda' => $preco_venda,
            ':preco2' => $preco2,
            ':qtCaixa' => $qtCaixa,
            ':valorUn' => $valorUn,
            ':royalties' => $royalties,
            ':st' => $st,
            ':ipi' => $ipi,
            ':txsAdicionais' => $txs,
            ':txMidia' => $midia,
            ':custoCaixa' => $custoCaixa,
            ':custoUn' => $custoUn,
            ':mbLiquida' => $mbLiquida,
            ':mbBruta' => $mbBruta
        ];

        if (!empty($id)) {
            $sql = "UPDATE produtos_unificados SET 
                    codigo_barras=:codigo_barras, codigo_interno=:codigo_interno, 
                    nome_produto=:nome_produto, campanha=:campanha, preco_venda=:preco_venda, 
                    preco2=:preco2, qtCaixa=:qtCaixa, valorUn=:valorUn, royalties=:royalties, 
                    st=:st, ipi=:ipi, txsAdicionais=:txsAdicionais, txMidia=:txMidia, 
                    custoCaixa=:custoCaixa, custoUn=:custoUn, mbLiquida=:mbLiquida, mbBruta=:mbBruta 
                    WHERE id = :id";
            $msg = "Produto atualizado com sucesso!";
            $dados[':id'] = $id;
        } else {
            $sql = "INSERT INTO produtos_unificados (
                    codigo_barras, codigo_interno, nome_produto, campanha, preco_venda, preco2, 
                    qtCaixa, valorUn, royalties, st, ipi, txsAdicionais, txMidia, 
                    custoCaixa, custoUn, mbLiquida, mbBruta
                    ) VALUES (
                    :codigo_barras, :codigo_interno, :nome_produto, :campanha, :preco_venda, :preco2, 
                    :qtCaixa, :valorUn, :royalties, :st, :ipi, :txsAdicionais, :txMidia, 
                    :custoCaixa, :custoUn, :mbLiquida, :mbBruta
                    )";
            $msg = "Produto criado com sucesso!";
        }

        $stmt = $db_produtos->prepare($sql);
        $stmt->execute($dados);
        echo json_encode(['status' => 'success', 'message' => $msg]);
    }

    // --- 2. EXCLUIR ---
    elseif ($action === 'excluir') {
        $id = $_POST['id'];
        $stmt = $db_produtos->prepare("DELETE FROM produtos_unificados WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Produto eliminado.']);
    }

// --- 3. IMPORTAR XML DE CUSTOS ---
    elseif ($action === 'importar_xml') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Erro no upload do ficheiro.']);
            exit;
        }

        $xml = simplexml_load_file($_FILES['file']['tmp_name']);
        if ($xml === false) {
            echo json_encode(['status' => 'error', 'message' => 'Ficheiro XML inválido.']);
            exit;
        }

        $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $det_tags = $xml->xpath('//nfe:infNFe/nfe:det');
        
        if (empty($det_tags)) {
            echo json_encode(['status' => 'error', 'message' => 'Nenhum item encontrado no XML.']);
            exit;
        }

        // AQUI: Trocámos o contador por um Array para guardar os nomes
        $nomes_atualizados = [];
        $faltantes = [];

        foreach ($det_tags as $det_tag) {
            $det_tag->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            
            $c_prod = ltrim((string) $det_tag->xpath('.//nfe:prod/nfe:cProd')[0], '0');
            $x_prod = (string) $det_tag->xpath('.//nfe:prod/nfe:xProd')[0];
            $v_un_com = (float) $det_tag->xpath('.//nfe:prod/nfe:vUnCom')[0]; 
            
            // --- EXTRAIR QUANTIDADE POR CAIXA DA DESCRIÇÃO (Ex: X14UN) ---
            $qtCaixa_xml = 0;
            if (preg_match('/X\s*(\d+)\s*UN/i', $x_prod, $matches)) {
                $qtCaixa_xml = (float) $matches[1];
            }

            $q_com_result = $det_tag->xpath('.//nfe:prod/nfe:qCom');
            $q_com = !empty($q_com_result) ? (float) $q_com_result[0] : 1; 
            
            $v_icms_st = 0;
            $st_node = $det_tag->xpath('.//nfe:imposto/nfe:ICMS//nfe:vICMSST');
            if (!empty($st_node)) {
                $st_total = (float) $st_node[0];
                $v_icms_st = $st_total / $q_com;
            }

            $v_ipi = 0;
            $ipi_node = $det_tag->xpath('.//nfe:imposto/nfe:IPI//nfe:vIPI');
            if (!empty($ipi_node)) {
                $ipi_total = (float) $ipi_node[0];
                $v_ipi = $ipi_total / $q_com;
            }

            $stmt = $db_produtos->prepare("SELECT * FROM produtos_unificados WHERE codigo_interno = ?");
            $stmt->execute([$c_prod]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($produto) {
                $qtCaixa = ($qtCaixa_xml > 0) ? $qtCaixa_xml : floatval($produto['qtCaixa']);
                if ($qtCaixa <= 0) $qtCaixa = 1;

                $preco2 = floatval($produto['preco2']);
                
                $royalties = $v_un_com * 0.50;
                $txs = floatval($produto['txsAdicionais']);
                $midia = floatval($produto['txMidia']);
                
                $custoCaixa = $v_un_com + $royalties + $v_icms_st + $v_ipi + $txs + $midia;
                $custoUn = ($qtCaixa > 0) ? ($custoCaixa / $qtCaixa) : 0;
                
                $mbBruta = ($preco2 > 0) ? (1 - ($custoUn / $preco2)) * 100 : 0;
                $baseLiq = $v_un_com + $royalties;
                $custoBaseUn = ($qtCaixa > 0) ? ($baseLiq / $qtCaixa) : 0;
                $mbLiquida = ($preco2 > 0) ? (1 - ($custoBaseUn / $preco2)) * 100 : 0;

                $stmt_update = $db_produtos->prepare("
                    UPDATE produtos_unificados 
                    SET qtCaixa = :qtCaixa, valorUn = :valorUn, royalties = :royalties, st = :st, ipi = :ipi, 
                        custoCaixa = :custoCaixa, custoUn = :custoUn, mbLiquida = :mbLiquida, mbBruta = :mbBruta
                    WHERE id = :id
                ");
                $stmt_update->execute([
                    ':qtCaixa' => $qtCaixa,
                    ':valorUn' => $v_un_com,
                    ':royalties' => $royalties,
                    ':st' => $v_icms_st,
                    ':ipi' => $v_ipi,
                    ':custoCaixa' => $custoCaixa,
                    ':custoUn' => $custoUn,
                    ':mbLiquida' => $mbLiquida,
                    ':mbBruta' => $mbBruta,
                    ':id' => $produto['id']
                ]);
                
                // AQUI: Guardamos o nome do produto atualizado na lista
                $nomes_atualizados[] = $produto['nome_produto'];
            } else {
                $faltantes[] = [
                    'codigo_interno' => $c_prod,
                    'nome_produto' => $x_prod,
                    'valorUn' => $v_un_com,
                    'st' => $v_icms_st,
                    'ipi' => $v_ipi,
                    'qtCaixa' => ($qtCaixa_xml > 0) ? $qtCaixa_xml : 1
                ];
            }
        }

        // AQUI: Devolvemos a lista de nomes e a quantidade
        echo json_encode([
            'status' => 'success', 
            'atualizados' => count($nomes_atualizados),
            'nomes_atualizados' => $nomes_atualizados,
            'faltantes' => $faltantes
        ]);
        exit;
    }

    // --- 4. CADASTRAR LOTE DE FALTANTES ---
    elseif ($action === 'cadastrar_lote') {
        $produtos = json_decode($_POST['produtos'], true);
        
        $sql = "INSERT INTO produtos_unificados (
                    codigo_interno, nome_produto, valorUn, royalties, st, ipi, 
                    custoCaixa, custoUn, preco_venda, preco2, qtCaixa, txsAdicionais, txMidia
                ) VALUES (
                    :codigo_interno, :nome_produto, :valorUn, :royalties, :st, :ipi, 
                    :custoCaixa, :custoUn, 0, 0, :qtCaixa, 0, 0
                )"; 
        
        $stmt = $db_produtos->prepare($sql);

        foreach ($produtos as $p) {
            $royalties = $p['valorUn'] * 0.50;
            $custoCaixa = $p['valorUn'] + $royalties + $p['st'] + $p['ipi'];
            $qtCaixa = $p['qtCaixa'] > 0 ? $p['qtCaixa'] : 1;
            $custoUn = $custoCaixa / $qtCaixa;

            $check = $db_produtos->prepare("SELECT id FROM produtos_unificados WHERE codigo_interno = ?");
            $check->execute([$p['codigo_interno']]);
            if (!$check->fetch()) {
                $stmt->execute([
                    ':codigo_interno' => $p['codigo_interno'],
                    ':nome_produto' => $p['nome_produto'],
                    ':valorUn' => $p['valorUn'],
                    ':royalties' => $royalties,
                    ':st' => $p['st'],
                    ':ipi' => $p['ipi'],
                    ':custoCaixa' => $custoCaixa,
                    ':custoUn' => $custoUn,
                    ':qtCaixa' => $qtCaixa
                ]);
            }
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: Código de Barras ou Interno já existe.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro BD: ' . $e->getMessage()]);
    }
}
?>