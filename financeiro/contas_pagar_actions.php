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

function integrarAoCaixa($db, $id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat) {
    if ($valor <= 0 || empty($id_conta)) return;
    $stmt = $db->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Contas a Pagar')");
    $stmt->execute([$id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat]);
}

try {
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
        $valor_total_lote = 0;
        $descricao_lote = "Pgto Lote: ";
        $id_categoria_mestre = null;

        foreach ($ids as $id) {
            $id = trim($id);
            if (empty($id)) continue;

            $stmt_dados = $db_financeiro->prepare("SELECT fornecedor, valor, id_categoria FROM contas_pagar WHERE id = ? AND id_usuario = ?");
            $stmt_dados->execute([$id, $id_usuario]);
            $conta = $stmt_dados->fetch();

            if ($conta) {
                $stmt_update->execute([$data_pag, $forma_pag, $id_conta_bancaria, $id, $id_usuario]);
                $count++;

                // Agrupa para lançar 1 vez só no caixa
                $valor_total_lote += $conta['valor'];
                // Só adiciona a descrição se ainda não ficou gigante
                if (strlen($descricao_lote) < 100) {
                    $descricao_lote .= $conta['fornecedor'] . ", ";
                }
                if (!$id_categoria_mestre) $id_categoria_mestre = $conta['id_categoria'];
            }
        }

        // Lançamento Mestre (agrupado) no Caixa
        if ($valor_total_lote > 0) {
            $descricao_lote = rtrim($descricao_lote, ", ");
            // Se a string ficou muito grande, coloca "..."
            if (strlen($descricao_lote) >= 100) $descricao_lote .= "...";
            
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $valor_total_lote, $descricao_lote, $id_categoria_mestre);
        }

        if ($juros > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Juros e Multas Pagas', 'Despesa', 'Despesas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $juros, "Juros Pagos Lote", $id_cat);
        }
        if ($desconto > 0) {
            $id_cat = obterCategoriaDRE($db_financeiro, 'Descontos Obtidos', 'Receita', 'Receitas Financeiras');
            integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $desconto, "Desconto Obtido Lote", $id_cat);
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => "$count contas pagas com sucesso!"]);
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