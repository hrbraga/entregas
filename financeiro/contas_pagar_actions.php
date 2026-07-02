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
        $id = trim($_POST['id'] ?? '');
        $fornecedor = trim($_POST['fornecedor'] ?? '');
        $emissao = $_POST['emissao'] ?? date('Y-m-d');
        $nota_fiscal = trim($_POST['nota_fiscal'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        
        $id_categoria = !empty($_POST['id_categoria']) ? $_POST['id_categoria'] : null;
        $gerar_royalties = $_POST['gerar_royalties'] ?? '0';

        // --- NOVA TRAVA DE DUPLICIDADE ---
        // Só verifica duplicidade se o usuário tiver digitado ou importado uma Nota Fiscal
        if (!empty($nota_fiscal)) {
            $check_sql = "SELECT COUNT(*) FROM contas_pagar WHERE id_usuario = ? AND fornecedor = ? AND nota_fiscal = ?";
            $params = [$id_usuario, $fornecedor, $nota_fiscal];
            
            // Se estiver editando, exclui a própria conta da verificação para permitir salvar as edições
            if (!empty($id)) {
                $check_sql .= " AND id != ?";
                $params[] = $id;
            }
            
            $stmt_check = $db_financeiro->prepare($check_sql);
            $stmt_check->execute($params);
            
            if ($stmt_check->fetchColumn() > 0) {
                // Retorna um erro para o Front-end avisando que a nota já existe
                echo json_encode(['status' => 'error', 'message' => 'Esta Nota Fiscal já foi lançada para este fornecedor.']);
                exit;
            }
        }
        // ---------------------------------

        if (!empty($id)) {
            // MODO EDIÇÃO
            $valor = converterMoeda($_POST['valor'] ?? '0');
            $vencimento = $_POST['vencimento'] ?? date('Y-m-d');
            
            $sql = "UPDATE contas_pagar SET fornecedor=?, emissao=?, vencimento=?, nota_fiscal=?, descricao=?, valor=?, id_categoria=? WHERE id=? AND id_usuario=?";
            $db_financeiro->prepare($sql)->execute([$fornecedor, $emissao, $vencimento, $nota_fiscal, $descricao, $valor, $id_categoria, $id, $id_usuario]);
        } else {
            // MODO INSERÇÃO
            
            // 1. LÓGICA DE PARCELAMENTO
            if (isset($_POST['parcela_vencimento']) && is_array($_POST['parcela_vencimento']) && count($_POST['parcela_vencimento']) > 0) {
                $sql = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db_financeiro->prepare($sql);
                
                $total_parcelas = count($_POST['parcela_vencimento']);
                foreach ($_POST['parcela_vencimento'] as $index => $venc_parcela) {
                    $val_parcela = converterMoeda($_POST['parcela_valor'][$index] ?? '0');
                    $desc_parcela = $descricao . " (" . ($index + 1) . "/$total_parcelas)";
                    
                    $stmt->execute([$id_usuario, $fornecedor, $emissao, $venc_parcela, $nota_fiscal, $desc_parcela, $val_parcela, $id_categoria]);
                }
            } else {
                // Inserção Única Normal
                $valor = converterMoeda($_POST['valor'] ?? '0');
                $vencimento = $_POST['vencimento'] ?? date('Y-m-d');
                
                $sql = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $db_financeiro->prepare($sql)->execute([$id_usuario, $fornecedor, $emissao, $vencimento, $nota_fiscal, $descricao, $valor, $id_categoria]);
            }
            
            // 2. LÓGICA DOS ROYALTIES DA CACAU SHOW
            if ($gerar_royalties === '1') {
                $id_cat_royalties = obterCategoriaDRE($db_financeiro, 'Royalties', 'Despesa', 'Despesas Operacionais');
                
                // O valor base para calcular royalties é o total da nota que está sendo inserida
                $valor_total_nota = 0;
                
                if (isset($_POST['parcela_vencimento']) && is_array($_POST['parcela_vencimento']) && count($_POST['parcela_vencimento']) > 0) {
                     // Se parcelou a compra original, soma as parcelas para achar o valor total da nota
                     foreach ($_POST['parcela_valor'] as $val_parc) {
                         $valor_total_nota += converterMoeda($val_parc ?? '0');
                     }
                } else {
                     // Conta normal (1 parcela)
                     $valor_total_nota = converterMoeda($_POST['valor'] ?? '0');
                }

                $valor_royalties_total = $valor_total_nota * 0.50; // Royalties = 50% da nota

                // --- VERIFICA SE É NOTA DE CAMPANHA ---
                $is_campanha = false;
                $venc_roy_campanha = null;
                
                // Pega a data da primeira parcela que o usuário digitou (ou do vencimento unico)
                $vencimento_base = $_POST['vencimento'] ?? (isset($_POST['parcela_vencimento'][0]) ? $_POST['parcela_vencimento'][0] : date('Y-m-d'));

                try {
                    // Procura na tabela campanhas_niveis se existe um vencimento de NF igual ao digitado
                    $stmt_campanha = $db_financeiro->prepare("SELECT vencimento_royalties FROM campanhas_niveis WHERE vencimento_nf = ? LIMIT 1");
                    $stmt_campanha->execute([$vencimento_base]);
                    $res_campanha = $stmt_campanha->fetch();

                    if ($res_campanha && !empty($res_campanha['vencimento_royalties'])) {
                        $is_campanha = true;
                        $venc_roy_campanha = $res_campanha['vencimento_royalties'];
                    }
                } catch (Exception $e) {
                    // Se a tabela não existir, apenas segue como linha
                }

                $sql_roy = "INSERT INTO contas_pagar (id_usuario, fornecedor, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert_roy = $db_financeiro->prepare($sql_roy);

                if ($is_campanha) {
                    // REGRA 2: NOTA DE CAMPANHA (Lançamento único na data cadastrada no admin)
                    $desc_royalties = "Royalties Campanha | NF " . $nota_fiscal;
                    $stmt_insert_roy->execute([$id_usuario, $fornecedor, $emissao, $venc_roy_campanha, $nota_fiscal, $desc_royalties, $valor_royalties_total, $id_cat_royalties]);
                } else {
                    // REGRA 1: NOTA DE LINHA (2 parcelas nos dias 07 e 21 do mês SUBSEQUENTE ao FATURAMENTO/EMISSÃO)
                    
                    // Descobre o mês e ano seguintes baseados na data de emissão
                    $ano_emissao = date('Y', strtotime($emissao));
                    $mes_emissao = date('m', strtotime($emissao));
                    
                    $mes_subsequente = $mes_emissao + 1;
                    $ano_subsequente = $ano_emissao;
                    
                    if ($mes_subsequente > 12) {
                        $mes_subsequente = 1;
                        $ano_subsequente++;
                    }
                    
                    $mes_subsequente_formatado = str_pad($mes_subsequente, 2, "0", STR_PAD_LEFT);
                    
                    // Monta as datas 07 e 21
                    $data_parc_1 = "$ano_subsequente-$mes_subsequente_formatado-07";
                    $data_parc_2 = "$ano_subsequente-$mes_subsequente_formatado-21";
                    
                    // Divide o valor 50% / 50%
                    $valor_metade = $valor_royalties_total / 2;

                    // Insere Parcela 1 (dia 07)
                    $desc_roy_1 = "Royalties Linha (1/2) | NF " . $nota_fiscal;
                    $stmt_insert_roy->execute([$id_usuario, $fornecedor, $emissao, $data_parc_1, $nota_fiscal, $desc_roy_1, $valor_metade, $id_cat_royalties]);
                    
                    // Insere Parcela 2 (dia 21)
                    $desc_roy_2 = "Royalties Linha (2/2) | NF " . $nota_fiscal;
                    $stmt_insert_roy->execute([$id_usuario, $fornecedor, $emissao, $data_parc_2, $nota_fiscal, $desc_roy_2, $valor_metade, $id_cat_royalties]);
                }
            }
            
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Salvo com sucesso!']);
        exit;
    }
    
} catch (Throwable $e) {
    if (isset($db_financeiro) && $db_financeiro->inTransaction()) $db_financeiro->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}