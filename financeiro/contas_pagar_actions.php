<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'];

// ==========================================
// FUNÇÃO GLOBAL DE CONVERSÃO DE MOEDA (BR -> SQL)
// ==========================================
function converterMoeda($valor_string) {
    if (empty($valor_string)) return 0;
    // Remove pontos de milhar e troca vírgula por ponto
    $valor_string = str_replace('.', '', $valor_string); 
    $valor_string = str_replace(',', '.', $valor_string); 
    return (float)$valor_string;
}

try {
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN data_pagamento DATE");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN forma_pagamento TEXT");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN banco_pagamento TEXT");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN valor_pago REAL");
} catch (Exception $e) { /* Colunas já existem */ }


try {
    // ==========================================
    // 1. LEITURA DE XML (PRÉ-VISUALIZAÇÃO)
    // ==========================================
    if ($action === 'parse_xml') {
        $xml = simplexml_load_file($_FILES['arquivo_xml']['tmp_name']);
        $nfe = $xml->NFe->infNFe;
        $primeira_parcela = $nfe->cobr->dup[0];
        echo json_encode(['status' => 'success', 'dados' => [
            'fornecedor' => (string) $nfe->emit->xNome,
            'numero_nota' => (string) $nfe->ide->nNF,
            'emissao' => explode('T', (string) $nfe->ide->dhEmi)[0],
            'vencimento' => (string) $primeira_parcela->dVenc,
            'valor_total' => (float) $nfe->total->ICMSTot->vNF
        ]]);
        exit;
    }

    // ==========================================
    // 2. EXCLUIR CONTA
    // ==========================================
    if ($action === 'excluir') {
        $stmt = $db_financeiro->prepare("DELETE FROM contas_pagar WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$_POST['id'], $id_usuario]);
        echo json_encode(['status' => 'success', 'message' => 'Conta removida com sucesso.']);
        exit;
    }

    // ==========================================
    // 3. DAR BAIXA (COM RATEIO NO DRE)
    // ==========================================
    if ($action === 'baixa') {
        $data_pag = $_POST['data_pagamento'];
        $forma_pag = $_POST['forma_pagamento'];
        $banco_pag = $_POST['banco_pagamento'];
        
        $juros = max(0, converterMoeda($_POST['juros']));
        $multa = max(0, converterMoeda($_POST['multa']));
        $desconto = max(0, converterMoeda($_POST['desconto']));
        $creditos = max(0, converterMoeda($_POST['creditos_cs']));
        
        $fornecedor_baixa = $_POST['fornecedor_baixa'] ?? 'Diversos';
        $descricao_baixa = $_POST['descricao_baixa'] ?? 'Baixa de Título';

        function obterCategoriaDRE($db, $nome, $tipo, $grupo) {
            $stmt = $db->prepare("SELECT id FROM categorias_financeiras WHERE nome = ?");
            $stmt->execute([$nome]);
            $res = $stmt->fetch();
            if ($res) return $res['id'];
            $db->prepare("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES (?, ?, ?)")->execute([$nome, $tipo, $grupo]);
            return $db->lastInsertId();
        }

        $db_financeiro->beginTransaction();

        try {
            if (!empty($_POST['vencimento_baixa'])) {
                $sql = "UPDATE contas_pagar 
                        SET status = 'Pago', data_pagamento = ?, forma_pagamento = ?, banco_pagamento = ?, valor_pago = valor 
                        WHERE id_usuario = ? AND vencimento = ? AND (descricao LIKE '%Royalt%' OR descricao LIKE '%royalt%') AND status != 'Pago'";
                $stmt = $db_financeiro->prepare($sql);
                $stmt->execute([$data_pag, $forma_pag, $banco_pag, $id_usuario, $_POST['vencimento_baixa']]);
                $count = $stmt->rowCount();
                $msg = "Todos os royalties do grupo ($count) foram baixados!";
            } else {
                $sql = "UPDATE contas_pagar 
                        SET status = 'Pago', data_pagamento = ?, forma_pagamento = ?, banco_pagamento = ?, valor_pago = valor 
                        WHERE id = ? AND id_usuario = ?";
                $stmt = $db_financeiro->prepare($sql);
                $stmt->execute([$data_pag, $forma_pag, $banco_pag, $_POST['id_baixa'], $id_usuario]);
                $msg = "Pagamento registado com sucesso!";
            }

            $sql_insert = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, descricao, valor, id_categoria, status, data_pagamento, forma_pagamento, banco_pagamento, valor_pago) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pago', ?, ?, ?, ?)";
            $stmt_insert = $db_financeiro->prepare($sql_insert);

            if ($juros > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Juros Pagos', 'Despesa', 'Despesas Financeiras');
                $stmt_insert->execute([$id_usuario, $fornecedor_baixa, $data_pag, $data_pag, "Juros Pagos - " . $descricao_baixa, $juros, $id_cat, $data_pag, $forma_pag, $banco_pag, $juros]);
            }
            if ($multa > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Multas Pagas', 'Despesa', 'Despesas Financeiras');
                $stmt_insert->execute([$id_usuario, $fornecedor_baixa, $data_pag, $data_pag, "Multas Pagas - " . $descricao_baixa, $multa, $id_cat, $data_pag, $forma_pag, $banco_pag, $multa]);
            }
            if ($desconto > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Descontos Recebidos', 'Receita', 'Receitas Financeiras');
                $stmt_insert->execute([$id_usuario, $fornecedor_baixa, $data_pag, $data_pag, "Desconto Recebido - " . $descricao_baixa, $desconto, $id_cat, $data_pag, $forma_pag, $banco_pag, $desconto]);
            }
            if ($creditos > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Créditos Cacau Show', 'Receita', 'Receitas Operacionais');
                $stmt_insert->execute([$id_usuario, $fornecedor_baixa, $data_pag, $data_pag, "Crédito Utilizado - " . $descricao_baixa, $creditos, $id_cat, $data_pag, $forma_pag, $banco_pag, $creditos]);
            }

            $db_financeiro->commit();
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } catch (Exception $e) {
            $db_financeiro->rollBack();
            echo json_encode(['status' => 'error', 'message' => "Erro na baixa: " . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 4. SALVAR / EDITAR / IMPORTAR XML
    // ==========================================
    if ($action === 'salvar') {
        $id = $_POST['id'] ?? '';
        
        // Converte o valor preenchido na máscara para salvar no BD
        $valor_conta = converterMoeda($_POST['valor']);
        
        if (empty($id) && !empty($_POST['nota_fiscal'])) {
            $check = $db_financeiro->prepare("SELECT id FROM contas_pagar WHERE id_usuario = ? AND nota_fiscal = ? AND fornecedor = ?");
            $check->execute([$id_usuario, $_POST['nota_fiscal'], $_POST['fornecedor']]);
            if ($check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Esta Nota Fiscal já foi lançada para este fornecedor!']);
                exit;
            }
        }

        if (!empty($id)) {
            $sql = "UPDATE contas_pagar SET fornecedor=?, emissao=?, vencimento=?, nota_fiscal=?, descricao=?, valor=?, id_categoria=? WHERE id=? AND id_usuario=?";
            $db_financeiro->prepare($sql)->execute([$_POST['fornecedor'], $_POST['emissao'], $_POST['vencimento'], $_POST['nota_fiscal'], $_POST['descricao'], $valor_conta, $_POST['id_categoria'], $id, $id_usuario]);
            echo json_encode(['status' => 'success', 'message' => 'Conta atualizada com sucesso!']);
            exit;
        } 
        
        $msg_extra = "";
        if (isset($_POST['gerar_royalties']) && $_POST['gerar_royalties'] == '1' && isset($_FILES['arquivo_xml']) && $_FILES['arquivo_xml']['error'] === UPLOAD_ERR_OK) {
            
            $xml = simplexml_load_file($_FILES['arquivo_xml']['tmp_name']);
            $nfe = $xml->NFe->infNFe;
            $emissao = explode('T', (string) $nfe->ide->dhEmi)[0];
            $vProd = (float) $nfe->total->ICMSTot->vProd;
            $infCpl = strtoupper((string) $nfe->infAdic->infCpl);
            $numero_nota = (string) $nfe->ide->nNF;

            $eh_campanha = false;
            $nome_campanha_ext = "";
            $dados_campanha = null;
            
            $stmt_campanhas = $db_financeiro->query("SELECT id, nome_campanha FROM campanhas");
            $lista_todas_campanhas = $stmt_campanhas->fetchAll();
            
            foreach ($lista_todas_campanhas as $camp) {
                if (strpos($infCpl, $camp['nome_campanha']) !== false) {
                    $eh_campanha = true;
                    $nome_campanha_ext = $camp['nome_campanha'];
                    $dados_campanha = $camp;
                    break;
                }
            }

            $desc_extra = $eh_campanha ? " ({$nome_campanha_ext})" : "";

            $stmt_cat = $db_financeiro->query("SELECT id, nome FROM categorias_financeiras");
            $cat_ids = [];
            while($row = $stmt_cat->fetch()) { $cat_ids[$row['nome']] = $row['id']; }
            
            $id_cat_merc = $cat_ids['Mercadoria para Revenda'] ?? $_POST['id_categoria'];
            if (!isset($cat_ids['Royalties'])) {
                $db_financeiro->exec("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES ('Royalties', 'Despesa', 'Custos Operacionais')");
                $id_cat_roy = $db_financeiro->lastInsertId();
            } else {
                $id_cat_roy = $cat_ids['Royalties'];
            }

            $duplicatas = [];
            if (isset($nfe->cobr->dup)) {
                foreach ($nfe->cobr->dup as $dup) {
                    $duplicatas[] = [ 'vencimento' => (string) $dup->dVenc, 'valor' => (float) $dup->vDup ];
                    $stmt_merc = $db_financeiro->prepare("INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_merc->execute([$id_usuario, $_POST['fornecedor'], $emissao, (string)$dup->dVenc, $numero_nota, "Mercadoria{$desc_extra} - Parcela " . (string)$dup->nDup, (float)$dup->vDup, $id_cat_merc]);
                }
            } else {
                $stmt_merc = $db_financeiro->prepare("INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_merc->execute([$id_usuario, $_POST['fornecedor'], $_POST['emissao'], $_POST['vencimento'], $_POST['nota_fiscal'], "Mercadoria{$desc_extra} - Parcela Única", $valor_conta, $_POST['id_categoria']]);
            }

            $valor_royalties_total = $vProd * 0.50;
            $data_parcela_base = count($duplicatas) >= 2 ? $duplicatas[1]['vencimento'] : ($duplicatas[0]['vencimento'] ?? $_POST['vencimento']);

            $inserir_royalty = $db_financeiro->prepare("INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if ($eh_campanha) {
                $stmt_nivel = $db_financeiro->prepare("SELECT nivel, vencimento_royalties FROM campanhas_niveis WHERE id_campanha = ? AND vencimento_nf = ?");
                $stmt_nivel->execute([$dados_campanha['id'], $data_parcela_base]);
                $nivel = $stmt_nivel->fetch();

                if ($nivel) {
                    $inserir_royalty->execute([$id_usuario, 'Cacau Show (Royalties)', $emissao, $nivel['vencimento_royalties'], $numero_nota, "Royalties - Campanha {$nome_campanha_ext} (Nível {$nivel['nivel']})", $valor_royalties_total, $id_cat_roy]);
                    $msg_extra = "\n✅ Mercadorias e Royalties da Campanha gerados!";
                } else {
                    $msg_extra = "\n⚠️ Campanha detetada, mas o cruzamento das datas de nível falhou.";
                }
            } else {
                $mes_seguinte = date('Y-m', strtotime("+1 month", strtotime($emissao)));
                $metade = $valor_royalties_total / 2;
                $inserir_royalty->execute([$id_usuario, 'Cacau Show (Royalties)', $emissao, $mes_seguinte . '-07', $numero_nota, "Royalties Linha - Parcela 1/2", $metade, $id_cat_roy]);
                $inserir_royalty->execute([$id_usuario, 'Cacau Show (Royalties)', $emissao, $mes_seguinte . '-21', $numero_nota, "Royalties Linha - Parcela 2/2", $metade, $id_cat_roy]);
                $msg_extra = "\n✅ Mercadorias e Royalties de Linha gerados!";
            }
        } else {
            $sql = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db_financeiro->prepare($sql)->execute([$id_usuario, $_POST['fornecedor'], $_POST['emissao'], $_POST['vencimento'], $_POST['nota_fiscal'], $_POST['descricao'], $valor_conta, $_POST['id_categoria']]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Conta salva com sucesso!' . $msg_extra]);
    }
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }

// ==========================================
// 5. AÇÕES DE CATEGORIA (Admin)
// ==========================================
if ($action === 'salvar_categoria') {
    $stmt = $db_financeiro->prepare("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['nome'], $_POST['tipo'], $_POST['grupo']]);
    echo json_encode(['status' => 'success', 'message' => 'Categoria cadastrada!']);
    exit;
}
if ($action === 'excluir_categoria') {
    $stmt = $db_financeiro->prepare("DELETE FROM categorias_financeiras WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => 'success', 'message' => 'Categoria removida!']);
    exit;
}