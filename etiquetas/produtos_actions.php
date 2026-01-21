<?php
require '../config.php';
require '../auth/custos_auth_check.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    // --- 1. SALVAR (Criar ou Editar) ---
    if ($action === 'salvar') {
        $id = $_POST['id'] ?? '';
        
        $dados = [
            ':codigo_barras'  => !empty($_POST['codigo_barras']) ? $_POST['codigo_barras'] : null,
            ':codigo_interno' => !empty($_POST['codigo_interno']) ? $_POST['codigo_interno'] : null,
            ':nome_produto'   => $_POST['nome_produto'],
            ':preco1'         => floatval($_POST['preco1']),
            ':preco2'         => floatval($_POST['preco2'])
        ];

        if (!empty($id)) {
            // UPDATE
            $sql = "UPDATE produtos SET 
                    codigo_barras=:codigo_barras, 
                    codigo_interno=:codigo_interno, 
                    nome_produto=:nome_produto, 
                    preco1=:preco1, 
                    preco2=:preco2 
                    WHERE id = :id";
            $dados[':id'] = $id;
            $msg = "Produto atualizado com sucesso!";
        } else {
            // INSERT
            $sql = "INSERT INTO produtos (codigo_barras, codigo_interno, nome_produto, preco1, preco2) 
                    VALUES (:codigo_barras, :codigo_interno, :nome_produto, :preco1, :preco2)";
            $msg = "Produto criado com sucesso!";
        }

        $stmt = $db_produtos->prepare($sql);
        $stmt->execute($dados);
        echo json_encode(['status' => 'success', 'message' => $msg]);
    }

    // --- 2. EXCLUIR ---
    elseif ($action === 'excluir') {
        $id = $_POST['id'];
        $stmt = $db_produtos->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Item excluído.']);
    }

    // --- 3. IMPORTAÇÃO EM MASSA ---
    elseif ($action === 'importar_massa') {
        $json = $_POST['dados_json'];
        $listaProdutos = json_decode($json, true);

        if (!$listaProdutos) throw new Exception("Erro ao ler dados JSON.");

        $db_produtos->beginTransaction();
        
        // Usamos INSERT OR REPLACE ou INSERT simples dependendo da lógica. 
        // Aqui usarei INSERT simples. Se quiser atualizar existentes, precisaria de lógica extra.
        $sql = "INSERT INTO produtos (codigo_barras, codigo_interno, nome_produto, preco1, preco2) 
                VALUES (:codigo_barras, :codigo_interno, :nome_produto, :preco1, :preco2)";
        $stmt = $db_produtos->prepare($sql);

        $count = 0;
        foreach ($listaProdutos as $p) {
            // Validação básica
            $barras = !empty($p['codigo_barras']) ? $p['codigo_barras'] : null;
            $interno = !empty($p['codigo_interno']) ? $p['codigo_interno'] : null;
            
            // Pula se não tiver nenhum código e nem nome
            if (!$barras && !$interno && empty($p['nome_produto'])) continue;

            $stmt->execute([
                ':codigo_barras'  => $barras,
                ':codigo_interno' => $interno,
                ':nome_produto'   => $p['nome_produto'],
                ':preco1'         => floatval($p['preco1']),
                ':preco2'         => floatval($p['preco2'])
            ]);
            $count++;
        }

        $db_produtos->commit();
        echo json_encode(['status' => 'success', 'message' => "$count produtos importados!"]);
    }

} catch (PDOException $e) {
    if ($db_produtos->inTransaction()) $db_produtos->rollBack();
    // Tratamento para erro de duplicidade (UNIQUE constraint)
    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: Código de Barras ou Interno já existe no banco.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro Banco: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>