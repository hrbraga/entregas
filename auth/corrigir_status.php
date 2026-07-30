<?php
// auth/corrigir_banco.php
require '../config.php';

try {
    $db_users->beginTransaction();

    // 1. Busca todos os colaboradores que estão "órfãos" de loja
    $stmt = $db_users->query("SELECT id, username, id_dono FROM user WHERE perfil = 'colaborador' AND (id_loja IS NULL OR id_loja = 0)");
    $usuarios_sem_loja = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Define um dono padrão caso o usuário antigo não tenha (pega o ID da sua sessão atual)
    $dono_padrao = $_SESSION['user_id'] ?? 1;

    foreach ($usuarios_sem_loja as $u) {
        // 2. Define o id_franqueado (se tiver id_dono usa ele, senão usa o padrão)
        $id_franqueado = !empty($u['id_dono']) ? $u['id_dono'] : $dono_padrao;

        // 3. Cria uma loja para esse colaborador no banco
        $nome_loja = "Loja - " . $u['username'] . " (Antiga)";
        $stmtInsert = $db_users->prepare("INSERT INTO lojas (nome, id_franqueado, ativo) VALUES (?, ?, 1)");
        $stmtInsert->execute([$nome_loja, $id_franqueado]);
        
        $novo_id_loja = $db_users->lastInsertId();

        // 4. Atualiza o usuário com o ID correto da loja recém-criada
        $stmtUpdate = $db_users->prepare("UPDATE user SET id_loja = ? WHERE id = ?");
        $stmtUpdate->execute([$novo_id_loja, $u['id']]);
    }

    $db_users->commit();
    echo "<h3>✅ Correção concluída! Foram vinculadas " . count($usuarios_sem_loja) . " lojas antigas.</h3>";
    echo "<a href='minha_equipe.php'>Voltar para a equipe</a>";

} catch (Exception $e) {
    if ($db_users->inTransaction()) {
        $db_users->rollBack();
    }
    echo "Erro ao corrigir: " . $e->getMessage();
}
?>