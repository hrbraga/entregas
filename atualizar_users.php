<?php
require 'config.php'; // Verifica se o caminho do seu config.php está correto

try {
    // Adiciona a coluna 'perfil'. O padrão será 'colaborador' para todo mundo novo.
    // Use a variável de conexão correta do seu users.db (ex: $db_users ou $pdo)
    $db_users->exec("ALTER TABLE user ADD COLUMN perfil TEXT DEFAULT 'colaborador'");
    
    // Define VOCÊ como franqueado. (Ajuste o ID '1' para o seu ID real no banco)
    $db_users->exec("UPDATE user SET perfil = 'franqueado' WHERE id = 1");
    
    echo "Sucesso! O banco foi atualizado e seu usuário agora é o Franqueado.";
} catch (Exception $e) {
    echo "Aviso (a coluna já deve existir): " . $e->getMessage();
}
?>