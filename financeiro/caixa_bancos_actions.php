<?php
require_once '../config.php';
require_once '../auth/auth_check.php'; 

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$id_usuario = $_SESSION['user_id']; 
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

try {
    if ($acao === 'listar') {
        $id_conta = $_GET['conta'] ?? 'todas';
        $dataInicio = $_GET['dataInicio'] ?? null;
        $dataFim = $_GET['dataFim'] ?? null;
        $tipo = $_GET['tipo'] ?? 'Todos';

        if ($id_conta === 'todas') {
            $stmt_conta = $db_financeiro->prepare("SELECT SUM(saldo_inicial) as saldo_inicial FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
            $stmt_conta->execute([$id_usuario]);
            $conta = $stmt_conta->fetch(PDO::FETCH_ASSOC);
            $saldo_base = $conta ? (float)$conta['saldo_inicial'] : 0.0;

            $sql_anterior = "SELECT 
                                SUM(CASE WHEN m.tipo = 'Entrada' THEN m.valor ELSE 0 END) as total_entradas,
                                SUM(CASE WHEN m.tipo = 'Saida' THEN m.valor ELSE 0 END) as total_saidas
                             FROM movimentacoes_caixa m
                             LEFT JOIN contas_bancarias cb ON m.id_conta = cb.id
                             WHERE m.id_usuario = ? AND m.data_movimento < ? 
                             AND (cb.status = 'Ativa' OR cb.status IS NULL)";
            $stmt_ant = $db_financeiro->prepare($sql_anterior);
            $stmt_ant->execute([$id_usuario, $dataInicio]);
            $mov_anteriores = $stmt_ant->fetch(PDO::FETCH_ASSOC);
            
            $sql = "SELECT m.*, c.nome as categoria_nome, cb.nome_conta as banco_nome 
                    FROM movimentacoes_caixa m
                    LEFT JOIN categorias_financeiras c ON m.id_categoria = c.id
                    LEFT JOIN contas_bancarias cb ON m.id_conta = cb.id
                    WHERE m.id_usuario = ? AND m.data_movimento BETWEEN ? AND ? 
                    AND (cb.status = 'Ativa' OR cb.status IS NULL)";
            $params = [$id_usuario, $dataInicio, $dataFim];

        } else {
            $stmt_conta = $db_financeiro->prepare("SELECT saldo_inicial FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
            $stmt_conta->execute([$id_conta, $id_usuario]);
            $conta = $stmt_conta->fetch(PDO::FETCH_ASSOC);
            $saldo_base = $conta ? (float)$conta['saldo_inicial'] : 0.0;

            $sql_anterior = "SELECT 
                                SUM(CASE WHEN tipo = 'Entrada' THEN valor ELSE 0 END) as total_entradas,
                                SUM(CASE WHEN tipo = 'Saida' THEN valor ELSE 0 END) as total_saidas
                             FROM movimentacoes_caixa 
                             WHERE id_conta = ? AND id_usuario = ? AND data_movimento < ?";
            $stmt_ant = $db_financeiro->prepare($sql_anterior);
            $stmt_ant->execute([$id_conta, $id_usuario, $dataInicio]);
            $mov_anteriores = $stmt_ant->fetch(PDO::FETCH_ASSOC);

            $sql = "SELECT m.*, c.nome as categoria_nome, cb.nome_conta as banco_nome 
                    FROM movimentacoes_caixa m
                    LEFT JOIN categorias_financeiras c ON m.id_categoria = c.id
                    LEFT JOIN contas_bancarias cb ON m.id_conta = cb.id
                    WHERE m.id_conta = ? AND m.id_usuario = ? AND m.data_movimento BETWEEN ? AND ?";
            $params = [$id_conta, $id_usuario, $dataInicio, $dataFim];
        }

        $saldo_inicial_periodo = $saldo_base + ($mov_anteriores['total_entradas'] ?? 0) - ($mov_anteriores['total_saidas'] ?? 0);

        if ($tipo !== 'Todos') {
            $sql .= " AND m.tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY m.data_movimento ASC, m.id ASC";
        $stmt = $db_financeiro->prepare($sql);
        $stmt->execute($params);
        $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entradas_periodo = 0; $saidas_periodo = 0;
        foreach ($movimentacoes as $mov) {
            if ($mov['tipo'] == 'Entrada') $entradas_periodo += (float)$mov['valor'];
            if ($mov['tipo'] == 'Saida') $saidas_periodo += (float)$mov['valor'];
        }

        $saldo_final = $saldo_inicial_periodo + $entradas_periodo - $saidas_periodo;

        echo json_encode([
            'sucesso' => true,
            'saldo_inicial' => $saldo_inicial_periodo,
            'entradas' => $entradas_periodo,
            'saidas' => $saidas_periodo,
            'saldo_final' => $saldo_final,
            'movimentacoes' => $movimentacoes
        ]);
        exit;
    }

    elseif ($acao === 'salvar_lancamento') {
        $id_movimento = $_POST['id_movimento'] ?? '';
        $id_conta = $_POST['id_conta'] ?? null;
        $data = $_POST['data'] ?? null;
        $tipo = $_POST['tipo'] ?? null;
        $valor = $_POST['valor'] ?? null;
        $descricao = $_POST['descricao'] ?? '';
        $id_categoria = !empty($_POST['categoria']) ? $_POST['categoria'] : null;

        if (!$id_conta || $id_conta === 'todas' || !$data || !$tipo || !$valor) {
            echo json_encode(['erro' => 'Preencha todos os campos corretamente.']);
            exit;
        }

        if ($id_movimento) {
            // VERIFICA SE É MANUAL OU IMPORTADO
            $check = $db_financeiro->prepare("SELECT origem FROM movimentacoes_caixa WHERE id = ? AND id_usuario = ?");
            $check->execute([$id_movimento, $id_usuario]);
            $origem_mov = $check->fetchColumn();

            if ($origem_mov === 'Importacao') {
                // SE FOR IMPORTAÇÃO, ATUALIZA APENAS A CATEGORIA! 
                $sql = "UPDATE movimentacoes_caixa SET id_categoria=? WHERE id=? AND id_usuario=? AND origem='Importacao'";
                $stmt = $db_financeiro->prepare($sql);
                $stmt->execute([$id_categoria, $id_movimento, $id_usuario]);
                $msg = 'Lançamento bancário classificado com sucesso!';
            } else {
                // SE FOR MANUAL, ATUALIZA TUDO
                $sql = "UPDATE movimentacoes_caixa SET data_movimento=?, tipo=?, valor=?, descricao=?, id_categoria=? WHERE id=? AND id_usuario=? AND origem='Manual'";
                $stmt = $db_financeiro->prepare($sql);
                $stmt->execute([$data, $tipo, $valor, $descricao, $id_categoria, $id_movimento, $id_usuario]);
                $msg = 'Lançamento manual atualizado com sucesso!';
            }

        } else {
            // É UM NOVO LANÇAMENTO
            $sql = "INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem) VALUES (?, ?, ?, ?, ?, ?, ?, 'Manual')";
            $stmt = $db_financeiro->prepare($sql);
            $stmt->execute([$id_usuario, $id_conta, $data, $tipo, $valor, $descricao, $id_categoria]);
            $msg = 'Lançamento salvo com sucesso!';
        }

        echo json_encode(['sucesso' => true, 'mensagem' => $msg]);
        exit;
    }

elseif ($acao === 'transferencia') {
        $id_origem = $_POST['id_origem'] ?? null;
        $id_destino = $_POST['id_destino'] ?? null;
        $data = $_POST['data'] ?? null;
        $valor = $_POST['valor'] ?? null;
        $obs = $_POST['obs'] ?? '';

        if (!$id_origem || !$id_destino || !$data || !$valor || $valor <= 0) {
            echo json_encode(['erro' => 'Preencha todos os campos corretamente com um valor maior que zero.']);
            exit;
        }

        if ($id_origem === $id_destino) {
            echo json_encode(['erro' => 'A conta de origem e destino não podem ser a mesma!']);
            exit;
        }

        try {
            // Inicia uma Transação de Banco de Dados (Garante que se uma falhar, a outra é cancelada para não sumir com o dinheiro)
            $db_financeiro->beginTransaction();

            // Buscar os nomes das contas para fazer uma descrição bonita
            $stmt_nomes = $db_financeiro->prepare("SELECT id, nome_conta FROM contas_bancarias WHERE id IN (?, ?) AND id_usuario = ?");
            $stmt_nomes->execute([$id_origem, $id_destino, $id_usuario]);
            $nomes = [];
            while($row = $stmt_nomes->fetch(PDO::FETCH_ASSOC)) {
                $nomes[$row['id']] = $row['nome_conta'];
            }
            
            $nome_origem = $nomes[$id_origem] ?? 'Desconhecida';
            $nome_destino = $nomes[$id_destino] ?? 'Desconhecida';
            
            $desc_saida = "Transferência enviada para: $nome_destino" . ($obs ? " ($obs)" : "");
            $desc_entrada = "Transferência recebida de: $nome_origem" . ($obs ? " ($obs)" : "");

            // 1. Gera a SAÍDA na Conta Origem
            $sql_saida = "INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, origem) VALUES (?, ?, ?, 'Saida', ?, ?, 'Transferencia')";
            $stmt_s = $db_financeiro->prepare($sql_saida);
            $stmt_s->execute([$id_usuario, $id_origem, $data, $valor, $desc_saida]);

            // 2. Gera a ENTRADA na Conta Destino
            $sql_entrada = "INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, origem) VALUES (?, ?, ?, 'Entrada', ?, ?, 'Transferencia')";
            $stmt_e = $db_financeiro->prepare($sql_entrada);
            $stmt_e->execute([$id_usuario, $id_destino, $data, $valor, $desc_entrada]);

            // Salva as duas definitivamente
            $db_financeiro->commit();
            
            echo json_encode(['sucesso' => true, 'mensagem' => 'Transferência efetuada com sucesso!']);
            exit;

        } catch (Exception $e) {
            $db_financeiro->rollBack();
            echo json_encode(['erro' => 'Erro ao processar transferência: ' . $e->getMessage()]);
            exit;
        }
    }

    elseif ($acao === 'importar_ofx') {
        $id_conta = $_POST['id_conta'] ?? null;
        
        if (!$id_conta || $id_conta === 'todas') {
            echo json_encode(['erro' => 'Conta de destino inválida.']);
            exit;
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['erro' => 'Falha ao receber o arquivo OFX.']);
            exit;
        }

        // Lê o conteúdo do arquivo
        $conteudo = file_get_contents($_FILES['arquivo']['tmp_name']);
        
        // Pega todos os blocos de transação <STMTTRN>
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $matches);
        $transacoes = $matches[1];
        
        if (empty($transacoes)) {
            echo json_encode(['erro' => 'Nenhuma transação encontrada. O arquivo é um OFX válido?']);
            exit;
        }
        
        $inseridos = 0;
        $ignorados = 0;
        
        foreach ($transacoes as $trn) {
            // Extrai os dados essenciais ignorando espaços extras
            preg_match('/<TRNTYPE>(.*?)(\r?\n|<)/', $trn, $tipo_match);
            preg_match('/<DTPOSTED>(.*?)(\r?\n|<)/', $trn, $data_match);
            preg_match('/<TRNAMT>(.*?)(\r?\n|<)/', $trn, $valor_match);
            preg_match('/<FITID>(.*?)(\r?\n|<)/', $trn, $id_match);
            preg_match('/<MEMO>(.*?)(\r?\n|<)/', $trn, $memo_match);
            
            $ofx_data = trim($data_match[1] ?? '');
            $ofx_valor = trim($valor_match[1] ?? '');
            $ofx_id = trim($id_match[1] ?? '');
            $ofx_memo = trim($memo_match[1] ?? 'Importação Bancária');
            
            if (empty($ofx_id) || empty($ofx_valor)) continue;
            
            // Converte a data do formato OFX (20260504120000) para Banco de Dados (2026-05-04)
            $data_formatada = substr($ofx_data, 0, 4) . '-' . substr($ofx_data, 4, 2) . '-' . substr($ofx_data, 6, 2);
            
            $valor = (float)$ofx_valor;
            $tipo = ($valor >= 0) ? 'Entrada' : 'Saida';
            $valor_absoluto = abs($valor); // Removemos o sinal de negativo para guardar no sistema
            
            // --- PROTEÇÃO ANTIDUPLICIDADE ---
            // Checa se o ID exato da transação do banco já foi importado nesta conta
            $check = $db_financeiro->prepare("SELECT id FROM movimentacoes_caixa WHERE hash_importacao = ? AND id_conta = ?");
            $check->execute([$ofx_id, $id_conta]);
            
            if ($check->fetch()) {
                $ignorados++; // Já existe, pula para o próximo!
                continue;
            }
            
            // Salva a transação novinha no banco
            $sql = "INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, origem, hash_importacao) VALUES (?, ?, ?, ?, ?, ?, 'Importacao', ?)";
            $stmt = $db_financeiro->prepare($sql);
            $stmt->execute([$id_usuario, $id_conta, $data_formatada, $tipo, $valor_absoluto, $ofx_memo, $ofx_id]);
            
            $inseridos++;
        }
        
        echo json_encode(['sucesso' => true, 'mensagem' => "Processo concluído!\n✔️ $inseridos lançamentos novos importados.\n⚠️ $ignorados ignorados (já existiam no sistema)."]);
        exit;
    }

    elseif ($acao === 'excluir') {
        $id_mov = $_POST['id'] ?? null;
        
        if ($id_mov) {
            // AGORA PERMITE EXCLUIR TANTO MANUAL QUANTO IMPORTADO!
            $stmt = $db_financeiro->prepare("DELETE FROM movimentacoes_caixa WHERE id = ? AND id_usuario = ? AND (origem = 'Manual' OR origem = 'Importacao')");
            $stmt->execute([$id_mov, $id_usuario]);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Excluído com sucesso!']);
        } else {
            echo json_encode(['erro' => 'ID de exclusão inválido.']);
        }
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>