<?php
// zerar_usuarios_temp.php (Versão Corrigida)
require 'config.php'; // Liga-se às bases de dados

try {
    // O comando importante é este:
    $db_users->exec("DELETE FROM user");

    echo "<h1>SUCESSO!</h1>";
    echo "<p>Todos os utilizadores antigos foram apagados da 'users.db'.</p>";
    echo "<p><strong>IMPORTANTE:</strong> Apague este ficheiro (zerar_usuarios_temp.php) agora!</p>";
    echo '<hr>';
    echo '<h2><a href="register.php">Próximo Passo: Ir para a página de Registo</a></h2>';

} catch (PDOException $e) {
    die("Erro ao zerar utilizadores: " . $e->getMessage());
}
?>