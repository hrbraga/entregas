<?php
require '../config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// --- 1. SALVAR NOVA CAMPANHA E NÍVEIS ---
if ($action === 'salvar') {
    try {
        $db_financeiro->beginTransaction();

        $nome_campanha = strtoupper(trim($_POST['nome_campanha']));
        
        $stmt = $db_financeiro->prepare("INSERT INTO campanhas (nome_campanha) VALUES (:nome)");
        $stmt->execute([':nome' => $nome_campanha]);
        $id_campanha = $db_financeiro->lastInsertId();

        $niveis = $_POST['nivel'] ?? [];
        $venc_nfs = $_POST['venc_nf'] ?? [];
        $venc_roys = $_POST['venc_roy'] ?? [];

        $stmt_nivel = $db_financeiro->prepare("
            INSERT INTO campanhas_niveis (id_campanha, nivel, vencimento_nf, vencimento_royalties) 
            VALUES (:id_campanha, :nivel, :venc_nf, :venc_roy)
        ");

        for ($i = 0; $i < count($niveis); $i++) {
            if (!empty($niveis[$i]) && !empty($venc_nfs[$i]) && !empty($venc_roys[$i])) {
                $stmt_nivel->execute([
                    ':id_campanha' => $id_campanha,
                    ':nivel' => $niveis[$i],
                    ':venc_nf' => $venc_nfs[$i],
                    ':venc_roy' => $venc_roys[$i]
                ]);
            }
        }

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => 'Campanha e níveis guardados com sucesso!']);
    } catch (PDOException $e) {
        $db_financeiro->rollBack();
        if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
            echo json_encode(['status' => 'error', 'message' => 'Erro: Esta campanha já está cadastrada.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro BD: ' . $e->getMessage()]);
        }
    }
    exit;
}

// --- 2. EDITAR NÍVEL EXISTENTE ---
if ($action === 'editar') {
    try {
        $db_financeiro->beginTransaction();
        
        $id_campanha = $_POST['id_campanha'];
        $id_nivel = $_POST['id_nivel'];
        $nome = strtoupper(trim($_POST['nome_campanha']));
        $nivel = $_POST['nivel_edit'];
        $venc_nf = $_POST['venc_nf_edit'];
        $venc_roy = $_POST['venc_roy_edit'];

        // Atualiza o nome da campanha geral
        $db_financeiro->prepare("UPDATE campanhas SET nome_campanha = ? WHERE id = ?")->execute([$nome, $id_campanha]);
        
        // Atualiza os dados específicos deste nível de royalty
        $db_financeiro->prepare("UPDATE campanhas_niveis SET nivel = ?, vencimento_nf = ?, vencimento_royalties = ? WHERE id = ?")->execute([$nivel, $venc_nf, $venc_roy, $id_nivel]);

        $db_financeiro->commit();
        echo json_encode(['status' => 'success', 'message' => 'Nível da campanha atualizado com sucesso!']);
    } catch (Exception $e) {
        $db_financeiro->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// --- 3. EXCLUIR NÍVEL ---
if ($action === 'excluir') {
    try {
        $id_nivel = $_POST['id_nivel'];
        $db_financeiro->prepare("DELETE FROM campanhas_niveis WHERE id = ?")->execute([$id_nivel]);
        
        // Se a campanha ficar sem nenhum nível (excluiu todos), limpa a campanha principal também
        $db_financeiro->exec("DELETE FROM campanhas WHERE id NOT IN (SELECT id_campanha FROM campanhas_niveis)");
        
        echo json_encode(['status' => 'success', 'message' => 'Nível removido com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
?>