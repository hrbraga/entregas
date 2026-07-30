<?php
// auth/atualizar_banco.php
require '../config.php';

try {
    $db_users->beginTransaction();

    // 1. Criar a tabela 'lojas' caso ela não exista no servidor online
    $sqlCriaLojas = "CREATE TABLE IF NOT EXISTS lojas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        id_franqueado INTEGER NOT NULL,
        ativo INTEGER DEFAULT 1
    )";
    $db_users->exec($sqlCriaLojas);

    // 2. Adicionar a coluna 'id_loja' na tabela 'user'
    // O SQLite lança erro se tentarmos adicionar uma coluna que já existe, então tratamos isso.
    try {
        $db_users->exec("ALTER TABLE user ADD COLUMN id_loja INTEGER DEFAULT NULL");
        $coluna_adicionada = true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            $coluna_adicionada = false; // Coluna já existia
        } else {
            throw $e; // Outro tipo de erro, interrompe o processo
        }
    }

    $db_users->commit();
    
    echo "<h3>✅ Estrutura do banco atualizada com sucesso!</h3>";
    if (isset($coluna_adicionada) && $coluna_adicionada) {
        echo "<p>➡️ Coluna 'id_loja' foi criada na tabela 'user'.</p>";
    }
    echo "<p>➡️ Tabela 'lojas' foi verificada e está pronta.</p>";
    echo "<br><p><strong>Próximo passo:</strong> <a href='corrigir_banco.php'>Clique aqui para rodar a Correção de Dados (corrigir_banco.php)</a></p>";

} catch (Exception $e) {
    if ($db_users->inTransaction()) {
        $db_users->rollBack();
    }
    echo "<h3>❌ Erro ao estruturar o banco:</h3> <p>" . $e->getMessage() . "</p>";
}
?>