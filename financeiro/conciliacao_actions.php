<?php
require '../config.php';
require '../auth/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_usuario = $_SESSION['user_id'];

// Função para extrair dados da tag OFX (lida com o padrão bagunçado dos bancos)
function extrairTagOFX($tag, $bloco) {
    if (preg_match("/<{$tag}>([^<\r\n]+)/", $bloco, $matches)) {
        return trim($matches[1]);
    }
    return '';
}

try {
    // ==========================================
    // 1. LER O FICHEIRO OFX E PROCURAR MATCHES
    // ==========================================
    if ($action === 'ler_ofx') {
        $id_conta = $_POST['id_conta'] ?? '';
        
        if (empty($id_conta) || !isset($_FILES['arquivo_ofx'])) {
            throw new Exception("Conta bancária ou ficheiro não enviados.");
        }

        $arquivo = $_FILES['arquivo_ofx']['tmp_name'];
        $conteudo = file_get_contents($arquivo);
        
        $conteudo = mb_convert_encoding($conteudo, 'UTF-8', mb_detect_encoding($conteudo, 'UTF-8, ISO-8859-1', true));

        $transacoes_ofx = [];
        if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $conteudo, $blocos)) {
            foreach ($blocos[1] as $bloco) {
                $data_raw = extrairTagOFX('DTPOSTED', $bloco);
                $valor_raw = extrairTagOFX('TRNAMT', $bloco);
                $fitid = extrairTagOFX('FITID', $bloco);
                
                $descricao = extrairTagOFX('MEMO', $bloco);
                if (empty($descricao)) {
                    $descricao = extrairTagOFX('NAME', $bloco);
                }

                $data_formatada = substr($data_raw, 0, 4) . '-' . substr($data_raw, 4, 2) . '-' . substr($data_raw, 6, 2);
                
                $valor_float = (float) $valor_raw;
                $tipo = ($valor_float < 0) ? 'Saida' : 'Entrada';
                $valor_absoluto = abs($valor_float);

                // Bloqueio para evitar ler transações que já foram conciliadas anteriormente
                $stmt_check = $db_financeiro->prepare("SELECT id FROM movimentacoes_caixa WHERE id_usuario = ? AND id_transacao_banco = ?");
                $stmt_check->execute([$id_usuario, $fitid]);
                $ja_existe = $stmt_check->fetch();

                if (!$ja_existe && !empty($fitid) && $valor_absoluto > 0) {
                    $transacoes_ofx[] = [
                        'fitid' => $fitid,
                        'data' => $data_formatada,
                        'valor' => $valor_absoluto,
                        'tipo' => $tipo,
                        'descricao' => $descricao
                    ];
                }
            }
        }

        if (empty($transacoes_ofx)) {
            throw new Exception("Nenhuma transação nova encontrada. Todas já podem ter sido conciliadas.");
        }

        $stmt_caixa = $db_financeiro->prepare("
            SELECT id, data_movimento, tipo, valor, descricao 
            FROM movimentacoes_caixa 
            WHERE id_usuario = ? AND id_conta = ? AND conciliado = 0
            ORDER BY data_movimento ASC
        ");
        $stmt_caixa->execute([$id_usuario, $id_conta]);
        $transacoes_sistema = $stmt_caixa->fetchAll(PDO::FETCH_ASSOC);

        $matches = [];
        $ofx_pendentes = [];
        $sistema_pendentes = $transacoes_sistema;

        foreach ($transacoes_ofx as $ofx) {
            $encontrou_match = false;
            
            foreach ($sistema_pendentes as $index => $sis) {
                if ($ofx['tipo'] == $sis['tipo'] && (string)$ofx['valor'] == (string)$sis['valor']) {
                    
                    $data_ofx = strtotime($ofx['data']);
                    $data_sis = strtotime($sis['data_movimento']);
                    $diferenca_dias = abs($data_ofx - $data_sis) / (60 * 60 * 24);

                    if ($diferenca_dias <= 3) {
                        $matches[] = [
                            'ofx' => $ofx,
                            'sistema' => $sis
                        ];
                        unset($sistema_pendentes[$index]);
                        $encontrou_match = true;
                        break;
                    }
                }
            }

            if (!$encontrou_match) {
                $ofx_pendentes[] = $ofx;
            }
        }

        $sistema_pendentes = array_values($sistema_pendentes);

        echo json_encode([
            'status' => 'success', 
            'matches' => $matches,
            'ofx_pendentes' => $ofx_pendentes,
            'sistema_pendentes' => $sistema_pendentes
        ]);
        exit;
    }

    // ==========================================
    // 2. CONFIRMAR MATCH (Conciliar transação existente)
    // ==========================================
    if ($action === 'confirmar_match') {
        $id_caixa = $_POST['id_caixa'] ?? '';
        $fitid = $_POST['fitid'] ?? '';

        if (empty($id_caixa) || empty($fitid)) {
            throw new Exception("Dados insuficientes para confirmar a conciliação.");
        }

        // Atualiza a transação do sistema marcando como conciliada e guardando o ID do banco
        $stmt = $db_financeiro->prepare("
            UPDATE movimentacoes_caixa 
            SET conciliado = 1, id_transacao_banco = ?, data_conciliacao = datetime('now', 'localtime') 
            WHERE id = ? AND id_usuario = ?
        ");
        $stmt->execute([$fitid, $id_caixa, $id_usuario]);

        echo json_encode(['status' => 'success', 'message' => 'Transação conciliada com sucesso!']);
        exit;
    }

    // ==========================================
    // 3. ADICIONAR NOVA AO CAIXA (Inserir já conciliada)
    // ==========================================
    if ($action === 'adicionar_ofx') {
        $id_conta = $_POST['id_conta'] ?? '';
        $fitid = $_POST['fitid'] ?? '';
        $data = $_POST['data'] ?? '';
        $valor = $_POST['valor'] ?? 0;
        $tipo = $_POST['tipo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $id_categoria = $_POST['id_categoria'] ?? 1;

        if (empty($id_conta) || empty($fitid) || empty($valor)) {
            throw new Exception("Dados insuficientes para adicionar a transação.");
        }

        // Insere a nova transação diretamente no caixa e já a marca como conciliada
        $stmt = $db_financeiro->prepare("
            INSERT INTO movimentacoes_caixa 
            (id_usuario, id_conta, data_movimento, tipo, valor, descricao, id_categoria, origem, conciliado, id_transacao_banco, data_conciliacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Conciliação Bancária', 1, ?, datetime('now', 'localtime'))
        ");
        $stmt->execute([
            $id_usuario, $id_conta, $data, $tipo, $valor, $descricao, $id_categoria, $fitid
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Transação adicionada e conciliada!']);
        exit;
    }

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}