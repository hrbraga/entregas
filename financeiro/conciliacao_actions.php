<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'];

function extrairTagOFX($tag, $bloco) {
    if (preg_match("/<{$tag}>([^<\r\n]+)/", $bloco, $matches)) return trim($matches[1]);
    return '';
}

try {
    if ($action === 'ler_ofx') {
        $id_conta = $_POST['id_conta'] ?? '';
        if (empty($id_conta) || !isset($_FILES['arquivo_ofx'])) throw new Exception("Dados inválidos.");

        $conteudo = file_get_contents($_FILES['arquivo_ofx']['tmp_name']);
        
        // 1. Deteção inteligente do CHARSET (Procura ativamente pelo 1252 do Sicoob)
        $encoding = 'UTF-8';
        if (preg_match('/CHARSET:(1252|ISO-8859-1)/i', $conteudo, $matches)) {
            $encoding = ($matches[1] == '1252') ? 'Windows-1252' : 'ISO-8859-1';
        } elseif (mb_detect_encoding($conteudo, 'UTF-8, ISO-8859-1, Windows-1252', true)) {
            $encoding = mb_detect_encoding($conteudo, 'UTF-8, ISO-8859-1, Windows-1252', true);
        }

        // 2. Converte para UTF-8 garantindo que acentos (É, ã, ç) fiquem perfeitos
        $conteudo = mb_convert_encoding($conteudo, 'UTF-8', $encoding);

        // 3. Limpeza preventiva de caracteres de controlo invisíveis que corrompem o JSON
        $conteudo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $conteudo);

        $transacoes_ofx = [];
        $ignoradas = 0;

        // OTIMIZAÇÃO: Buscar todos os FITIDs do banco numa ÚNICA consulta
        $stmt_existentes = $db_financeiro->prepare("SELECT id_transacao_banco FROM movimentacoes_caixa WHERE id_usuario = ? AND id_conta = ? AND id_transacao_banco IS NOT NULL AND id_transacao_banco != ''");
        $stmt_existentes->execute([$id_usuario, $id_conta]);
        $registos_banco = $stmt_existentes->fetchAll(PDO::FETCH_COLUMN);

        $fitids_ja_importados = [];
        foreach ($registos_banco as $linha_ids) {
            $ids_separados = explode(',', $linha_ids);
            foreach ($ids_separados as $id_unico) {
                $fitids_ja_importados[trim($id_unico)] = true;
            }
        }

        // Processamento do arquivo OFX na memória
        if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $blocos)) {
            foreach ($blocos[1] as $bloco) {
                $data_raw = extrairTagOFX('DTPOSTED', $bloco);
                $valor_raw = extrairTagOFX('TRNAMT', $bloco);
                $fitid = extrairTagOFX('FITID', $bloco);
                
                $descricao = extrairTagOFX('MEMO', $bloco);
                if (empty($descricao)) $descricao = extrairTagOFX('NAME', $bloco);

                // Garantia final: limpa qualquer lixo que tenha sobrado apenas na descrição
                $descricao = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $descricao));

                $data_formatada = substr($data_raw, 0, 4) . '-' . substr($data_raw, 4, 2) . '-' . substr($data_raw, 6, 2);
                $valor_float = (float) $valor_raw;
                $tipo = ($valor_float < 0) ? 'Saida' : 'Entrada';
                $valor_absoluto = abs($valor_float);

                if (isset($fitids_ja_importados[$fitid])) {
                    $ignoradas++;
                } elseif (!empty($fitid) && $valor_absoluto > 0) {
                    $transacoes_ofx[] = [
                        'fitid' => $fitid, 'data' => $data_formatada, 'valor' => $valor_absoluto, 'tipo' => $tipo, 'descricao' => $descricao
                    ];
                }
            }
        }

        if (empty($transacoes_ofx) && $ignoradas == 0) throw new Exception("Nenhuma transação encontrada no arquivo OFX.");

        $stmt_caixa = $db_financeiro->prepare("SELECT id, data_movimento, tipo, valor, descricao FROM movimentacoes_caixa WHERE id_usuario = ? AND id_conta = ? AND conciliado = 0 ORDER BY data_movimento ASC");
        $stmt_caixa->execute([$id_usuario, $id_conta]);
        $transacoes_sistema = $stmt_caixa->fetchAll(PDO::FETCH_ASSOC);

        $matches = []; $ofx_pendentes = []; $sistema_pendentes = $transacoes_sistema;

        foreach ($transacoes_ofx as $ofx) {
            $encontrou_match = false;
            foreach ($sistema_pendentes as $index => $sis) {
                if ($ofx['tipo'] == $sis['tipo'] && (string)$ofx['valor'] == (string)$sis['valor']) {
                    $diferenca_dias = abs(strtotime($ofx['data']) - strtotime($sis['data_movimento'])) / 86400;
                    if ($diferenca_dias <= 3) {
                        $matches[] = ['ofx' => $ofx, 'sistema' => $sis];
                        unset($sistema_pendentes[$index]);
                        $encontrou_match = true;
                        break;
                    }
                }
            }
            if (!$encontrou_match) $ofx_pendentes[] = $ofx;
        }

        echo json_encode([
            'status' => 'success', 'matches' => $matches, 'ofx_pendentes' => $ofx_pendentes, 
            'sistema_pendentes' => array_values($sistema_pendentes), 'ignoradas' => $ignoradas
        ]);
        exit;
    }

    if ($action === 'confirmar_match') {
        $id_caixa = $_POST['id_caixa'] ?? ''; $fitid = $_POST['fitid'] ?? '';
        $stmt = $db_financeiro->prepare("UPDATE movimentacoes_caixa SET conciliado = 1, id_transacao_banco = ?, data_conciliacao = datetime('now', 'localtime') WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$fitid, $id_caixa, $id_usuario]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'confirmar_match_multiplo') {
        $id_caixa = $_POST['id_caixa'] ?? '';
        $fitids = isset($_POST['fitids']) ? json_decode($_POST['fitids'], true) : [];

        if (empty($id_caixa) || empty($fitids)) {
            throw new Exception("Dados insuficientes para conciliação múltipla.");
        }

        $fitids_str = implode(',', $fitids);

        $stmt = $db_financeiro->prepare("UPDATE movimentacoes_caixa SET conciliado = 1, id_transacao_banco = ?, data_conciliacao = datetime('now', 'localtime') WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$fitids_str, $id_caixa, $id_usuario]);
        
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'adicionar_ofx') {
        $id_conta = $_POST['id_conta'] ?? ''; $fitid = $_POST['fitid'] ?? ''; $data = $_POST['data'] ?? '';
        $valor = $_POST['valor'] ?? 0; $tipo = $_POST['tipo'] ?? ''; $descricao = $_POST['descricao'] ?? ''; $id_categoria = $_POST['id_categoria'] ?? 1;

        $stmt = $db_financeiro->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem, conciliado, id_transacao_banco, data_conciliacao) VALUES (?, ?, ?, ?, ?, ?, ?, 'Conciliação Bancária', 1, ?, datetime('now', 'localtime'))");
        $stmt->execute([$id_usuario, $id_conta, $data, $tipo, $valor, $descricao, $id_categoria, $fitid]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'adicionar_ofx_lote') {
        $id_conta = $_POST['id_conta'] ?? ''; $id_categoria = $_POST['id_categoria'] ?? '';
        $transacoes = json_decode($_POST['transacoes'], true);

        $db_financeiro->beginTransaction();
        $stmt = $db_financeiro->prepare("INSERT INTO movimentacoes_caixa (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem, conciliado, id_transacao_banco, data_conciliacao) VALUES (?, ?, ?, ?, ?, ?, ?, 'Conciliação Bancária', 1, ?, datetime('now', 'localtime'))");
        
        foreach ($transacoes as $t) {
            $stmt->execute([$id_usuario, $id_conta, $t['data'], $t['tipo'], $t['valor'], $t['descricao'], $id_categoria, $t['fitid']]);
        }
        $db_financeiro->commit();
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Throwable $e) {
    if ($db_financeiro->inTransaction()) $db_financeiro->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}