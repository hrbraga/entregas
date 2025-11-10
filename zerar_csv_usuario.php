<?php
// zerar_csv_usuario.php
require 'config.php'; // Liga-se às bases de dados $db_users e $db_entregas

// Define o cabeçalho como HTML para uma visualização amigável
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Limpar Dados de CSV por Usuário</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #d9534f; }
        h2 { color: #0275d8; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        .success { color: #5cb85c; font-weight: bold; }
        .error { color: #d9534f; font-weight: bold; }
        li { margin-bottom: 5px; }
        code { background-color: #f4f4f4; padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">

<?php
// Pega o nome de usuário da URL (ex: ?username=hugo)
$username_to_delete = $_GET['username'] ?? null;

// SE NENHUM USUÁRIO FOI ESPECIFICADO:
if (empty($username_to_delete)) {
    echo "<h1>Erro: Usuário não especificado</h1>";
    echo "<p class='error'>Por favor, especifique um usuário para limpar.</p>";
    echo "<p>Adicione <code>?username=NOME_DO_USUARIO</code> ao final do URL no seu navegador.</p>";
    echo "<hr>";
    echo "<h2>Usuários Registrados:</h2>";

    try {
        // Busca todos os usuários no users.db para ajudar
        $stmt = $db_users->query("SELECT id, username FROM user");
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            echo "<p>Nenhum usuário encontrado no 'users.db'.</p>";
        } else {
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>ID: <strong>" . htmlspecialchars($user['id']) . "</strong>, Username: <strong>" . htmlspecialchars($user['username']) . "</strong></li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erro ao ler 'users.db': " . $e->getMessage() . "</p>";
    }

// SE UM USUÁRIO FOI ESPECIFICADO:
} else {
    echo "<h1>Limpeza de Dados de CSV</h1>";
    echo "<p>Tentando apagar todos os dados de pedidos (CSV) do usuário: <strong>" . htmlspecialchars($username_to_delete) . "</strong></p>";

    try {
        // Usamos transações para garantir que nada seja feito se o usuário não existir
        $db_users->beginTransaction();
        $db_entregas->beginTransaction();

        // 1. Encontrar o ID do usuário no 'users.db'
        $stmt_find = $db_users->prepare("SELECT id FROM user WHERE username = ?");
        $stmt_find->execute([$username_to_delete]);
        $user = $stmt_find->fetch();
        
        $db_users->commit(); // Terminamos a operação no users.db

        if (!$user) {
            throw new Exception("Usuário '" . htmlspecialchars($username_to_delete) . "' não foi encontrado no 'users.db'. Nenhuma ação foi tomada.");
        }

        $user_id = $user['id'];
        echo "<p>Usuário encontrado. ID: <strong>$user_id</strong></p>";

        // 2. Apagar os dados do 'item_entrega' (onde o CSV é importado) no 'entregas.db'
        $stmt_delete = $db_entregas->prepare("DELETE FROM item_entrega WHERE user_id = ?");
        $stmt_delete->execute([$user_id]);
        
        $rows_affected = $stmt_delete->rowCount();

        // Confirma a remoção
        $db_entregas->commit();

        echo "<hr>";
        echo "<h2 class='success'>SUCESSO!</h2>";
        echo "<p class='success'>Foram apagados <strong>$rows_affected</strong> registos de pedidos (CSV) da conta de <strong>" . htmlspecialchars($username_to_delete) . "</strong>.</p>";
        echo '<p><a href="recebimentos.php">Voltar para a página de Recebimentos</a></p>';
        echo "<p><strong>AVISO:</strong> É seguro manter este ficheiro, mas lembre-se que ele pode apagar dados permanentemente se usado incorretamente.</p>";

    } catch (Exception $e) {
        // Desfaz tudo se algo deu errado
        $db_users->rollBack();
        $db_entregas->rollBack();
        
        echo "<hr>";
        echo "<h1>ERRO!</h1>";
        echo "<p class='error'>Ocorreu um erro: " . $e->getMessage() . "</p>";
        echo "<p>Nenhuma alteração foi feita na base de dados.</p>";
    }
}
?>

    </div>
</body>
</html>