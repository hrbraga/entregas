<?php
// migrar_lojas.php (Rode apenas uma vez pelo navegador)
require 'config.php';

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>🚀 Gerador de Acessos Operacionais</h1>";

// Senha padrão para todos os novos usuários de loja
$senha_padrao = "loja123";
$hash_padrao = password_hash($senha_padrao, PASSWORD_DEFAULT);

try {
    // 1. Busca todos os utilizadores que NÃO são lojas e NÃO são o admin principal
    $stmt = $db_users->query("SELECT id, username FROM user WHERE username NOT LIKE 'loja-%' AND username != 'hugo_admin'");
    $franqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($franqueados as $franq) {
        $username_pai = $franq['username'];
        $username_filho = "loja-" . $username_pai;

        // Verifica se o filho já existe para não duplicar
        $check = $db_users->prepare("SELECT id FROM user WHERE username = ?");
        $check->execute([$username_filho]);
        
        if (!$check->fetch()) {
            // Se não existe, cria o utilizador da loja
            $insert = $db_users->prepare("INSERT INTO user (username, password_hash) VALUES (?, ?)");
            $insert->execute([$username_filho, $hash_padrao]);
            echo "<p style='color: green;'>✅ Criado: <b>$username_filho</b> (Senha: $senha_padrao)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Já existe: <b>$username_filho</b> (Ignorado)</p>";
        }
    }
    
    echo "<br><h3 style='color: blue;'>Migração concluída! Todos os franqueados agora possuem um acesso operacional.</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro: " . $e->getMessage() . "</h3>";
}
echo "</div>";
?>