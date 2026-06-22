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

function parseCsvFloat($val) {
    $val = trim((string)$val);
    $val = str_replace(['R$', ' ', "\xc2\xa0"], '', $val);
    if (empty($val)) return 0.0;
    if (strpos($val, ',') !== false) {
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);
    } 
    return (float) $val;
}

function obterCategoriaDRE($db, $nome, $tipo, $grupo) {
    $stmt = $db->prepare("SELECT id FROM categorias_financeiras WHERE nome = ?");
    $stmt->execute([$nome]);
    $res = $stmt->fetch();
    if ($res) return $res['id'];
    $db->prepare("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES (?, ?, ?)")->execute([$nome, $tipo, $grupo]);
    return $db->lastInsertId();
}

function integrarAoCaixa($db, $id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat) {
    if ($valor <= 0 || empty($id_conta)) return;
    $stmt = $db->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Contas a Receber')");
    $stmt->execute([$id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat]);
}

$colunas = [
    'data_pagamento' => 'DATE',
    'forma_pagamento' => 'TEXT',
    'banco_pagamento' => 'TEXT',
    'valor_pago' => 'REAL',
    'nota_fiscal' => 'TEXT',
    'taxa_importacao' => 'REAL DEFAULT 0'
];

foreach($colunas as $col => $tipo) {
    try { $db_financeiro->exec("ALTER TABLE contas_receber ADD COLUMN $col $tipo"); } catch (Throwable $e) {}
}

try {

    if ($action === 'preview_cielo') {
        if (!isset($_FILES['arquivo_csv'])) throw new Exception("Arquivo não enviado.");
        
        $arquivo = $_FILES['arquivo_csv']['tmp_name'];
        $linhas = file($arquivo);
        $is_data = false;
        $agrupados = [];
        
        $idx_pagamento = -1; $idx_venda = -1; $idx_tipo = -1; $idx_taxa = -1; $idx_liquido = -1;

        $amostra = implode(" ", array_slice($linhas, 0, 15));
        $delimiter = (substr_count($amostra, ';') > substr_count($amostra, ',')) ? ';' : ',';

        foreach ($linhas as $linha) {
            $linha = preg_replace('/^[\xef\xbb\xbf]/', '', $linha);
            $linha = trim(mb_convert_encoding($linha, 'UTF-8', mb_detect_encoding($linha, 'UTF-8, ISO-8859-1', true)));
            
            if (empty($linha)) continue;

            $cols = str_getcsv($linha, $delimiter);
            $str_linha = implode(' ', $cols);

            if (!$is_data) {
                if (stripos($str_linha, 'Filtros') === false && 
                    stripos($str_linha, 'Quantidade de lan') === false && 
                    stripos($str_linha, 'Data de pagamento') !== false && 
                    stripos($str_linha, 'Valor') !== false) {
                    
                    $headers = array_map('trim', $cols);
                    $is_data = true;

                    foreach ($headers as $i => $h) {
                        $h_clean = strtolower(trim($h));
                        if ($idx_pagamento === -1 && strpos($h_clean, 'data de pagamento') !== false && strpos($h_clean, 'prevista') === false) $idx_pagamento = $i;
                        if ($idx_venda === -1 && strpos($h_clean, 'data da venda') !== false) $idx_venda = $i;
                        if ($idx_tipo === -1 && strpos($h_clean, 'tipo de lan') !== false) $idx_tipo = $i;
                        if ($idx_taxa === -1 && (strpos($h_clean, 'taxa') !== false || strpos($h_clean, 'tarifa') !== false)) $idx_taxa = $i;
                        if ($idx_liquido === -1 && strpos($h_clean, 'valor l') !== false) $idx_liquido = $i;
                    }
                    
                    if ($idx_pagamento === -1) $idx_pagamento = 0;
                    if ($idx_venda === -1) $idx_venda = 1;
                    if ($idx_tipo === -1) $idx_tipo = 3;
                    if ($idx_taxa === -1) $idx_taxa = 7;
                    if ($idx_liquido === -1) $idx_liquido = 8;
                }
                continue;
            }

            if ($idx_pagamento === -1 || !isset($cols[$idx_pagamento])) continue;
            
            $col_pag = trim($cols[$idx_pagamento]);
            
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $col_pag)) {
                $p = explode('/', $col_pag);
                $data_vencimento = $p[2].'-'.$p[1].'-'.$p[0];

                $data_emissao = $data_vencimento;
                if (isset($cols[$idx_venda]) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim($cols[$idx_venda]))) {
                    $e = explode('/', trim($cols[$idx_venda]));
                    $data_emissao = $e[2].'-'.$e[1].'-'.$e[0];
                }

                $tipo_raw = strtolower($cols[$idx_tipo] ?? '');
                $tipo_limpo = (strpos($tipo_raw, 'débito') !== false) ? 'Débito' : 'Crédito';

                $taxa = abs(parseCsvFloat($cols[$idx_taxa] ?? '0'));
                $liquido = parseCsvFloat($cols[$idx_liquido] ?? '0');

                $key = $data_vencimento . '_' . $tipo_limpo;
                
                if (!isset($agrupados[$key])) {
                    $agrupados[$key] = [
                        'emissao' => $data_emissao,
                        'vencimento' => $data_vencimento,
                        'tipo' => $tipo_limpo,
                        'liquido' => 0,
                        'taxa' => 0
                    ];
                }

                $agrupados[$key]['liquido'] += $liquido;
                $agrupados[$key]['taxa'] += $taxa;
            }
        }

        if (empty($agrupados)) throw new Exception("Nenhum dado válido encontrado. Certifique-se de que é o CSV de Recebíveis Detalhado da Cielo.");

        echo json_encode(['status' => 'success', 'dados' => array_values($agrupados)]);
        exit;
    }

    if ($action === 'importar_cielo_confirmado') {
        $dados = json_decode($_POST['dados_importacao'], true);
        if (empty($dados)) throw new Exception("Nenhum dado para importar.");

        $id_cat_receita = obterCategoriaDRE($db_financeiro, 'Venda de Mercadorias', 'Receita', 'Receitas Operacionais');

        $db_financeiro->beginTransaction();
        $sql = "INSERT INTO contas_receber (id_usuario, cliente, emissao, vencimento, descricao, valor, taxa_importacao, id_categoria) VALUES (?, 'Cielo', ?, ?, ?, ?, ?, ?)";
        $stmt = $db_financeiro->prepare($sql);

        $count = 0;
        foreach ($dados as $d) {
            $descricao = "Cielo - Recebimento em " . $d['tipo'];
            $stmt->execute([$id_usuario, $d['emissao'], $d['vencimento'], $descricao, $d['liquido'], $d['taxa'], $id_cat_receita]);
            $count++;
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => "$count lotes importados com sucesso!"]);
        exit;
    }

    if ($action === 'excluir') {
        $id = $_POST['id'] ?? '';
        $stmt = $db_financeiro->prepare("DELETE FROM contas_receber WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        echo json_encode(['status' => 'success', 'message' => 'Lançamento removido.']);
        exit;
    }

    // ==========================================
    // ESTORNO DE RECEBIMENTO
    // ==========================================
    if ($action === 'estornar') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) throw new Exception("ID inválido.");

        $db_financeiro->beginTransaction();

        // Pega as informações da conta antes do estorno para saber o que deletar no caixa
        $stmt_dados = $db_financeiro->prepare("SELECT cliente, valor, descricao, data_pagamento FROM contas_receber WHERE id = ? AND id_usuario = ?");
        $stmt_dados->execute([$id, $id_usuario]);
        $conta = $stmt_dados->fetch();

        if ($conta) {
            // 1. Atualiza status de volta para Pendente
            $stmt_up = $db_financeiro->prepare("UPDATE contas_receber SET status = 'Pendente', data_pagamento = NULL, forma_pagamento = NULL, banco_pagamento = NULL, valor_pago = NULL WHERE id = ?");
            $stmt_up->execute([$id]);

            // 2. Remove a movimentação que foi gerada na tabela do caixa
            // A busca aqui é feita usando a mesma lógica de concatenação que usamos no momento de baixar a conta
            $desc_caixa = "Rec: " . $conta['cliente'] . " - " . $conta['descricao'];
            $stmt_del_caixa = $db_financeiro->prepare("DELETE FROM movimentacoes_caixa WHERE id_usuario = ? AND descricao = ? AND valor = ? AND data_movimento = ? AND origem = 'Contas a Receber' AND tipo = 'Entrada'");
            $stmt_del_caixa->execute([$id_usuario, $desc_caixa, $conta['valor'], $conta['data_pagamento']]);
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => 'Recebimento estornado com sucesso!']);
        exit;
    }

    // ==========================================
    // 4. DAR BAIXA (CORRIGIDO: Taxa invisível não vai para o Banco)
    // ==========================================
    if ($action === 'baixa_recebimento') {
        $data_pag = $_POST['data_pagamento'] ?? date('Y-m-d');
        $forma_pag = $_POST['forma_pagamento'] ?? '';
        $id_conta_bancaria = $_POST['banco_pagamento'] ?? '';

        $juros = max(0, converterMoeda($_POST['juros'] ?? '0'));
        $desconto = max(0, converterMoeda($_POST['desconto'] ?? '0'));
        $taxa_cartao_manual = max(0, converterMoeda($_POST['taxa_cartao'] ?? '0'));

        $db_financeiro->beginTransaction();
        $ids = explode(',', $_POST['id_baixa'] ?? '');
        $sql = "UPDATE contas_receber SET status = 'Recebido', data_pagamento = ?, forma_pagamento = ?, banco_pagamento = ?, valor_pago = valor WHERE id = ? AND id_usuario = ?";
        $stmt_update = $db_financeiro->prepare($sql);
        
        $count = 0;

        foreach ($ids as $id) {
            $id = trim($id);
            if (empty($id)) continue;

            $stmt_dados = $db_financeiro->prepare("SELECT cliente, valor, descricao, id_categoria FROM contas_receber WHERE id = ? AND id_usuario = ?");
            $stmt_dados->execute([$id, $id_usuario]);
            $conta_info = $stmt_dados->fetch();

            if ($conta_info) {
                $stmt_update->execute([$data_pag, $forma_pag, $id_conta_bancaria, $id, $id_usuario]);
                $count++;

                // Lança apenas o valor LÍQUIDO no banco, ignorando a taxa_importacao da Cielo
                integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $conta_info['valor'], "Rec: " . $conta_info['cliente'] . " - " . $conta_info['descricao'], $conta_info['id_categoria']);
            }
        }

        if ($juros > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Juros e Multas Recebidas', 'Receita', 'Receitas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $juros, "Juros - Recebimento", $id_cat);
        }
        if ($desconto > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Descontos Concedidos', 'Despesa', 'Despesas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $desconto, "Desconto Concedido", $id_cat);
        }
        
        // Se houver uma taxa preenchida MANUALMENTE, essa sim desconta do banco
        if ($taxa_cartao_manual > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Taxas de Cartão', 'Despesa', 'Despesas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $taxa_cartao_manual, "Taxa de Cartão - Recebimento", $id_cat);
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => "$count títulos recebidos com sucesso!"]);
        exit;
    }

    if ($action === 'salvar_receber') {
        $id = $_POST['id'] ?? '';
        $valor_conta = converterMoeda($_POST['valor'] ?? '0');
        $cliente = $_POST['cliente'] ?? 'Diversos';
        $emissao = $_POST['emissao'] ?? date('Y-m-d');
        $vencimento = $_POST['vencimento'] ?? date('Y-m-d');
        $nota_fiscal = trim($_POST['nota_fiscal'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $id_categoria = $_POST['id_categoria'] ?? null;

        if (!empty($id)) {
            $sql = "UPDATE contas_receber SET cliente=?, emissao=?, vencimento=?, nota_fiscal=?, descricao=?, valor=?, id_categoria=? WHERE id=? AND id_usuario=?";
            $db_financeiro->prepare($sql)->execute([$cliente, $emissao, $vencimento, $nota_fiscal, $descricao, $valor_conta, $id_categoria, $id, $id_usuario]);
            echo json_encode(['status' => 'success', 'message' => 'Lançamento atualizado com sucesso!']);
            exit;
        }

        $descricao_final = $descricao . (!empty($nota_fiscal) ? " | Doc " . $nota_fiscal : "");
        $sql = "INSERT INTO contas_receber (id_usuario, cliente, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $db_financeiro->prepare($sql)->execute([$id_usuario, $cliente, $emissao, $vencimento, $nota_fiscal, $descricao_final, $valor_conta, $id_categoria]);
        
        echo json_encode(['status' => 'success', 'message' => 'Recebimento salvo com sucesso!']);
        exit;
    }

} catch (Throwable $e) {
    if (isset($db_financeiro) && $db_financeiro->inTransaction()) $db_financeiro->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

if ($action === 'buscar_clientes') {
    $termo = trim($_POST['termo'] ?? '');
    $stmt = $db_financeiro->prepare("SELECT DISTINCT cliente FROM contas_receber WHERE id_usuario = ? AND cliente LIKE ? ORDER BY cliente ASC LIMIT 10");
    $stmt->execute([$id_usuario, "%{$termo}%"]);
    echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    exit;
}