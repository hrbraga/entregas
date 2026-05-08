<?php
require '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'salvar') {
    try {
        // Inicia uma transação (se der erro num nível, cancela tudo para não haver dados incompletos)
        $db_financeiro->beginTransaction();

        $nome_campanha = strtoupper(trim($_POST['nome_campanha']));
        
        // 1. Grava a Campanha
        $stmt = $db_financeiro->prepare("INSERT INTO campanhas (nome_campanha) VALUES (:nome)");
        $stmt->execute([':nome' => $nome_campanha]);
        $id_campanha = $db_financeiro->lastInsertId();

        // 2. Grava os Níveis associados a esta campanha
        $niveis = $_POST['nivel'] ?? [];
        $venc_nfs = $_POST['venc_nf'] ?? [];
        $venc_roys = $_POST['venc_roy'] ?? [];

        $stmt_nivel = $db_financeiro->prepare("
            INSERT INTO campanhas_niveis (id_campanha, nivel, vencimento_nf, vencimento_royalties) 
            VALUES (:id_campanha, :nivel, :venc_nf, :venc_roy)
        ");

        for ($i = 0; $i < count($niveis); $i++) {
            // Só grava se os 3 campos da linha estiverem preenchidos
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
}
?>