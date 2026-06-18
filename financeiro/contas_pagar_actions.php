<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'] ?? 0;

function converterMoeda($valor_string) {
    if (empty($valor_string)) return 0;
    $valor_string = str_replace('.', '', (string)$valor_string);
    $valor_string = str_replace(',', '.', $valor_string);
    return (float)$valor_string;
}

function obterCategoriaDRE($db, $nome, $tipo, $grupo) {
    $stmt = $db->prepare("SELECT id FROM categorias_financeiras WHERE nome = ?");
    $stmt->execute([$nome]);
    $res = $stmt->fetch();
    if ($res) return $res['id'];
    $db->prepare("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES (?, ?, ?)")->execute([$nome, $tipo, $grupo]);
    return $db->lastInsertId();
}

function integrarAoCaixa($db, $id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat, $id_origem = null) {
    if ($valor <= 0 || empty($id_conta)) return;
    try {
        $stmt = $db->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem, id_origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Contas a Pagar', ?)");
        $stmt->execute([$id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat, $id_origem]);
    } catch (PDOException $e) {
        $stmt = $db->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Contas a Pagar')");
        $stmt->execute([$id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat]);
    }
}

try {



// --- LER ARQUIVO XML DA NOTA FISCAL ---
    if ($action === 'parse_xml') {
        if (!isset($_FILES['arquivo_xml']) || $_FILES['arquivo_xml']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer upload do arquivo XML.']);
            exit;
        }

        // Lê o conteúdo do arquivo
        $xml_content = file_get_contents($_FILES['arquivo_xml']['tmp_name']);
        
        // Remove os namespaces do XML para facilitar a leitura das tags
        $xml_content = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xml_content);
        $xml = @simplexml_load_string($xml_content);

        if (!$xml) {
            echo json_encode(['status' => 'error', 'message' => 'Arquivo XML inválido ou corrompido.']);
            exit;
        }

        // Identifica onde está o bloco principal da NFe
        $infNFe = null;
        if (isset($xml->NFe->infNFe)) {
            $infNFe = $xml->NFe->infNFe;
        } elseif (isset($xml->infNFe)) {
            $infNFe = $xml->infNFe;
        }

        if (!$infNFe) {
            echo json_encode(['status' => 'error', 'message' => 'Estrutura da NFe não encontrada neste XML.']);
            exit;
        }

        // Extrai os dados da NFe
        $fornecedor = (string) $infNFe->emit->xNome;
        $numero_nota = (string) $infNFe->ide->nNF;
        
        // Data de Emissão
        $emissao = '';
        if (isset($infNFe->ide->dhEmi)) {
            $emissao = substr((string) $infNFe->ide->dhEmi, 0, 10);
        } elseif (isset($infNFe->ide->dEmi)) {
            $emissao = substr((string) $infNFe->ide->dEmi, 0, 10);
        } else {
            $emissao = date('Y-m-d');
        }

        // Tentar pegar o Vencimento e Valor da primeira parcela (duplicata)
        $vencimento = $emissao;
        $valor_total = 0;

        if (isset($infNFe->cobr->dup[0])) {
            $vencimento = substr((string) $infNFe->cobr->dup[0]->dVenc, 0, 10);
            $valor_total = (float) $infNFe->cobr->dup[0]->vDup;
        } else {
            // Se não tiver duplicata/parcelas, pega o total da nota
            if (isset($infNFe->total->ICMSTot->vNF)) {
                $valor_total = (float) $infNFe->total->ICMSTot->vNF;
            }
        }

        // Prepara a resposta para o Javascript
        $dados = [
            'fornecedor' => $fornecedor,
            'emissao' => $emissao,
            'vencimento' => $vencimento,
            'numero_nota' => $numero_nota,
            'valor_total' => $valor_total
        ];

        echo json_encode(['status' => 'success', 'dados' => $dados]);
        exit;
    }

    if ($action === 'buscar_fornecedores') {
        $termo = $_POST['termo'] ?? '';
        if (strlen($termo) < 2) {
            echo json_encode(['status' => 'success', 'dados' => []]);
            exit;
        }
        
        $stmt = $db_financeiro->prepare("SELECT DISTINCT fornecedor FROM contas_pagar WHERE id_usuario = ? AND fornecedor LIKE ? ORDER BY fornecedor ASC LIMIT 10");
        $stmt->execute([$id_usuario, "%$termo%"]);
        $fornecedores = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['status' => 'success', 'dados' => $fornecedores]);
        exit;
    }

    // --- ESTORNO DE PAGAMENTO ---
    if ($action === 'estorno') {
        $id = trim($_POST['id'] ?? '');
        $db_financeiro->beginTransaction();
        $db_financeiro->prepare("UPDATE contas_pagar SET status = 'Pendente', data_pagamento = NULL, forma_pagamento = NULL, banco_pagamento = NULL, valor_pago = NULL WHERE id = ? AND id_usuario = ?")->execute([$id, $id_usuario]);
        try { $db_financeiro->prepare("DELETE FROM movimentacoes_caixa WHERE origem = 'Contas a Pagar' AND id_origem = ? AND id_usuario = ?")->execute([$id, $id_usuario]); } catch (Exception $e) {}
        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => 'Estorno realizado! Caixa atualizado.']);
        exit;
    }

    if ($action === 'baixa_pagamento') {
        $data_pag = $_POST['data_pagamento'] ?? date('Y-m-d');
        $forma_pag = $_POST['forma_pagamento'] ?? '';
        $id_conta_bancaria = $_POST['banco_pagamento'] ?? '';

        $juros = max(0, converterMoeda($_POST['juros'] ?? '0'));
        $desconto = max(0, converterMoeda($_POST['desconto'] ?? '0'));

        $db_financeiro->beginTransaction();
        $ids = explode(',', $_POST['id_baixa'] ?? '');
        $stmt_update = $db_financeiro->prepare("UPDATE contas_pagar SET status = 'Pago', data_pagamento = ?, forma_pagamento = ?, banco_pagamento = ?, valor_pago = valor WHERE id = ? AND id_usuario = ?");
        
        $count = 0;

        foreach ($ids as $id) {
            $id = trim($id);
            if (empty($id)) continue;

            $stmt_dados = $db_financeiro->prepare("SELECT fornecedor, valor, id_categoria FROM contas_pagar WHERE id = ? AND id_usuario = ?");
            $stmt_dados->execute([$id, $id_usuario]);
            $conta = $stmt_dados->fetch();

            if ($conta) {
                // 1. Atualiza o status na tabela contas_pagar
                $stmt_update->execute([$data_pag, $forma_pag, $id_conta_bancaria, $id, $id_usuario]);
                $count++;

                // 2. Integra no caixa individualmente (GARANTE O ESTORNO SEGURO)
                $desc_caixa = "Pgto Fornecedor: " . $conta['fornecedor'];
                integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $conta['valor'], $desc_caixa, $conta['id_categoria'], $id);
            }
        }

        // Lançamentos extras do lote (juros e descontos) ficam atrelados ao primeiro ID do lote para referência
        $primeiro_id_lote = $ids[0] ?? null;

        if ($juros > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Juros e Multas Pagas', 'Despesa', 'Despesas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $juros, "Juros Pagos Lote", $id_cat, $primeiro_id_lote);
        }
        if ($desconto > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Descontos Obtidos', 'Receita', 'Receitas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $desconto, "Desconto Obtido Lote", $id_cat, $primeiro_id_lote);
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => "$count contas pagas com sucesso!"]);
        exit;
    }

    // --- NOVA AÇÃO: ESTORNO DE PAGAMENTO ---
    if ($action === 'estorno') {
        $id = trim($_POST['id'] ?? '');
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido para estorno.']);
            exit;
        }

        $db_financeiro->beginTransaction();

        // 1. Volta a conta para Pendente e zera os dados de pagamento
        $stmt_update = $db_financeiro->prepare("UPDATE contas_pagar SET status = 'Pendente', data_pagamento = NULL, forma_pagamento = NULL, banco_pagamento = NULL, valor_pago = NULL WHERE id = ? AND id_usuario = ?");
        $stmt_update->execute([$id, $id_usuario]);

        // 2. Apaga rigorosamente o(s) lançamento(s) associado(s) a esta conta no Caixa
        try {
            $stmt_delete_caixa = $db_financeiro->prepare("DELETE FROM movimentacoes_caixa WHERE origem = 'Contas a Pagar' AND id_origem = ? AND id_usuario = ?");
            $stmt_delete_caixa->execute([$id, $id_usuario]);
        } catch (PDOException $e) {
            // Se a coluna id_origem faltar, a transação continua (mas não apaga o caixa)
        }

        $db_financeiro->commit();

        echo json_encode(['status' => 'success', 'message' => 'Estorno realizado! O valor foi removido do caixa.']);
        exit;
    }

    if ($action === 'excluir') {
        $id = $_POST['id'] ?? '';
        $stmt = $db_financeiro->prepare("DELETE FROM contas_pagar WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        echo json_encode(['status' => 'success', 'message' => 'Lançamento removido.']);
        exit;
    }

    if ($action === 'salvar_pagar') {
        $id = $_POST['id'] ?? '';
        $valor = converterMoeda($_POST['valor'] ?? '0');
        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $emissao = $_POST['emissao'] ?? date('Y-m-d');
        $vencimento = $_POST['vencimento'] ?? date('Y-m-d');
        $nota_fiscal = trim($_POST['nota_fiscal'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $id_categoria = $_POST['id_categoria'] ?? null;

        if (!empty($id)) {
            $sql = "UPDATE contas_pagar SET fornecedor=?, emissao=?, vencimento=?, nota_fiscal=?, descricao=?, valor=?, id_categoria=? WHERE id=? AND id_usuario=?";
            $db_financeiro->prepare($sql)->execute([$fornecedor, $emissao, $vencimento, $nota_fiscal, $descricao, $valor, $id_categoria, $id, $id_usuario]);
        } else {
            $sql = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db_financeiro->prepare($sql)->execute([$id_usuario, $fornecedor, $emissao, $vencimento, $nota_fiscal, $descricao, $valor, $id_categoria]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Salvo com sucesso!']);
        exit;
    }

} catch (Throwable $e) {
    if (isset($db_financeiro) && $db_financeiro->inTransaction()) $db_financeiro->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}