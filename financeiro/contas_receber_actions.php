<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'] ?? 0;

// Função Global de Conversão de Moeda blindada
function converterMoeda($valor_string)
{
    if (empty($valor_string)) return 0;
    $valor_string = str_replace('.', '', (string)$valor_string);
    $valor_string = str_replace(',', '.', $valor_string);
    return (float)$valor_string;
}

// Criação de colunas 100% silenciosa
$colunas = [
    'data_pagamento' => 'DATE',
    'forma_pagamento' => 'TEXT',
    'banco_pagamento' => 'TEXT',
    'valor_pago' => 'REAL',
    'nota_fiscal' => 'TEXT'
];

foreach ($colunas as $col => $tipo) {
    try {
        $db_financeiro->exec("ALTER TABLE contas_receber ADD COLUMN $col $tipo");
    } catch (Throwable $e) {
        // Ignora silenciosamente se a coluna já existir
    }
}

try {

    // 1. EXCLUIR CONTA A RECEBER
    if ($action === 'excluir') {
        $id = $_POST['id'] ?? '';
        $stmt = $db_financeiro->prepare("DELETE FROM contas_receber WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        echo json_encode(['status' => 'success', 'message' => 'Lançamento removido com sucesso.']);
        exit;
    }

    // 2. DAR BAIXA (RECEBER)
    if ($action === 'baixa_recebimento') {
        $data_pag = $_POST['data_pagamento'] ?? date('Y-m-d');
        $forma_pag = $_POST['forma_pagamento'] ?? '';
        $id_conta_bancaria = $_POST['banco_pagamento'] ?? '';

        $juros = max(0, converterMoeda($_POST['juros'] ?? '0'));
        $desconto = max(0, converterMoeda($_POST['desconto'] ?? '0'));
        $taxa_cartao = max(0, converterMoeda($_POST['taxa_cartao'] ?? '0'));

        function obterCategoriaDRE($db, $nome, $tipo, $grupo)
        {
            $stmt = $db->prepare("SELECT id FROM categorias_financeiras WHERE nome = ?");
            $stmt->execute([$nome]);
            $res = $stmt->fetch();
            if ($res) return $res['id'];

            $db->prepare("INSERT INTO categorias_financeiras (nome, tipo, grupo) VALUES (?, ?, ?)")->execute([$nome, $tipo, $grupo]);
            return $db->lastInsertId();
        }

        function integrarAoCaixa($db, $id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat)
        {
            if ($valor <= 0 || empty($id_conta)) return;
            $stmt = $db->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Contas a Receber')");
            $stmt->execute([$id_user, $id_conta, $data, $tipo, $valor, $desc, $id_cat]);
        }

        $db_financeiro->beginTransaction();

        try {
            $ids = explode(',', $_POST['id_baixa'] ?? '');
            $sql = "UPDATE contas_receber SET status = 'Recebido', data_pagamento = ?, forma_pagamento = ?, banco_pagamento = ?, valor_pago = valor WHERE id = ? AND id_usuario = ?";
            $stmt = $db_financeiro->prepare($sql);
            $count = 0;

            foreach ($ids as $id) {
                $id = trim($id);
                if (empty($id)) continue;

                $stmt_dados = $db_financeiro->prepare("SELECT cliente, valor, descricao, id_categoria FROM contas_receber WHERE id = ? AND id_usuario = ?");
                $stmt_dados->execute([$id, $id_usuario]);
                $conta_info = $stmt_dados->fetch();

                if ($conta_info) {
                    $stmt->execute([$data_pag, $forma_pag, $id_conta_bancaria, $id, $id_usuario]);
                    $count += $stmt->rowCount();

                    integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $conta_info['valor'], "Rec: " . $conta_info['cliente'] . " - " . $conta_info['descricao'], $conta_info['id_categoria']);
                }
            }

            $msg = $count > 1 ? "$count títulos recebidos e enviados ao Caixa!" : "Recebimento registrado no Caixa com sucesso!";

            if ($juros > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Juros e Multas Recebidas', 'Receita', 'Receitas Financeiras');
                integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Entrada', $juros, "Juros/Multas - Recebimento", $id_cat);
            }

            if ($desconto > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Descontos Concedidos', 'Despesa', 'Despesas Financeiras');
                integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $desconto, "Desconto Concedido - Recebimento", $id_cat);
            }

            if ($taxa_cartao > 0) {
                $id_cat = obterCategoriaDRE($db_financeiro, 'Taxas de Cartão', 'Despesa', 'Despesas Financeiras');
                integrarAoCaixa($db_financeiro, $id_usuario, $id_conta_bancaria, $data_pag, 'Saida', $taxa_cartao, "Taxa de Cartão - Recebimento", $id_cat);
            }

            $db_financeiro->commit();
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } catch (Throwable $e) {
            $db_financeiro->rollBack();
            echo json_encode(['status' => 'error', 'message' => "Erro no recebimento: " . $e->getMessage()]);
        }
        exit;
    }

    // 3. SALVAR / EDITAR / PARCELAR CONTA A RECEBER
    if ($action === 'salvar_receber') {
        $id = $_POST['id'] ?? '';
        $valor_conta = converterMoeda($_POST['valor'] ?? '0');
        $cliente = $_POST['cliente'] ?? 'Diversos';
        $emissao = $_POST['emissao'] ?? date('Y-m-d');
        $vencimento = $_POST['vencimento'] ?? date('Y-m-d');
        $nota_fiscal = trim($_POST['nota_fiscal'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $id_categoria = $_POST['id_categoria'] ?? null;

        if (empty($id)) {
            if (!empty($nota_fiscal)) {
                $check = $db_financeiro->prepare("SELECT id FROM contas_receber WHERE id_usuario = ? AND TRIM(UPPER(nota_fiscal)) = TRIM(UPPER(?)) AND TRIM(UPPER(cliente)) = TRIM(UPPER(?))");
                $check->execute([$id_usuario, $nota_fiscal, $cliente]);
                if ($check->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => '⚠️ Bloqueio: O Documento/NF já foi lançado para o cliente ' . $cliente . '!']);
                    exit;
                }
            } else {
                $check2 = $db_financeiro->prepare("SELECT id FROM contas_receber WHERE id_usuario = ? AND TRIM(UPPER(cliente)) = TRIM(UPPER(?)) AND valor = ? AND vencimento = ?");
                $check2->execute([$id_usuario, $cliente, $valor_conta, $vencimento]);
                if ($check2->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => '⚠️ Bloqueio: Já existe um lançamento sem Doc/NF deste cliente com exato valor e vencimento!']);
                    exit;
                }
            }
        }

        if (!empty($id)) {
            $sql = "UPDATE contas_receber SET cliente=?, emissao=?, vencimento=?, nota_fiscal=?, descricao=?, valor=?, id_categoria=? WHERE id=? AND id_usuario=?";
            $db_financeiro->prepare($sql)->execute([$cliente, $emissao, $vencimento, $nota_fiscal, $descricao, $valor_conta, $id_categoria, $id, $id_usuario]);
            echo json_encode(['status' => 'success', 'message' => 'Lançamento atualizado com sucesso!']);
            exit;
        }

        $msg_extra = "";

        if (isset($_POST['is_parcelado']) && $_POST['is_parcelado'] == 'on' && isset($_POST['parcela_vencimento'])) {
            $vencimentos = $_POST['parcela_vencimento'];
            $valores = $_POST['parcela_valor'];
            $qtd = count($vencimentos);

            $db_financeiro->beginTransaction();
            try {
                for ($i = 0; $i < $qtd; $i++) {
                    $val_parcela = converterMoeda($valores[$i]);
                    $desc_base = $descricao;
                    if (!empty($nota_fiscal)) {
                        $desc_base .= " | Doc " . $nota_fiscal;
                    }
                    $desc_parcela = $desc_base . " - Parcela " . ($i + 1) . "/" . $qtd;

                    $sql = "INSERT INTO contas_receber (id_usuario, cliente, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $db_financeiro->prepare($sql)->execute([$id_usuario, $cliente, $emissao, $vencimentos[$i], $nota_fiscal, $desc_parcela, $val_parcela, $id_categoria]);
                }
                $db_financeiro->commit();
                $msg_extra = " ($qtd parcelas cadastradas!)";
            } catch (Throwable $e) {
                $db_financeiro->rollBack();
                throw $e;
            }
        } else {
            $descricao_final = $descricao;
            if (!empty($nota_fiscal)) {
                $descricao_final .= " | Doc " . $nota_fiscal;
            }

            $sql = "INSERT INTO contas_receber (id_usuario, cliente, emissao, vencimento, nota_fiscal, descricao, valor, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db_financeiro->prepare($sql)->execute([$id_usuario, $cliente, $emissao, $vencimento, $nota_fiscal, $descricao_final, $valor_conta, $id_categoria]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Recebimento salvo com sucesso!' . $msg_extra]);
        exit;
    }

    // A MÁGICA ESTÁ AQUI: Capturamos qualquer erro Fatal, de Tipo ou PDO com 'Throwable'
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => "ERRO DETETADO (Linha " . $e->getLine() . "): " . $e->getMessage()
    ]);
}

// ==========================================
// AUTOCOMPLETE CLIENTES
// ==========================================
if ($action === 'buscar_clientes') {
    $termo = trim($_POST['termo'] ?? '');
    $stmt = $db_financeiro->prepare("
        SELECT DISTINCT cliente 
        FROM contas_receber 
        WHERE id_usuario = ? AND cliente LIKE ? 
        ORDER BY cliente ASC LIMIT 10
    ");
    $stmt->execute([$id_usuario, "%{$termo}%"]);
    echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    exit;
}
