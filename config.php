<?php
// config.php (Versão Corrigida com __DIR__)

// Inicia a sessão (o equivalente ao Flask-Login)
session_start();

// Define os caminhos absolutos para as bases de dados
// __DIR__ refere-se à pasta onde este ficheiro (config.php) está
$db_entregas_path = __DIR__ . '/entregas.db';
$db_users_path = __DIR__ . '/users.db';

try {
    // Conexão à DB de Entregas (PDO_SQLite)
    $db_entregas = new PDO('sqlite:' . $db_entregas_path);
    $db_entregas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_entregas->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Conexão à DB de Utilizadores (PDO_SQLite)
    $db_users = new PDO('sqlite:' . $db_users_path);
    $db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_users->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se falhar a conexão, interrompe tudo e mostra o erro
    // Isto é muito melhor para depuração do que o erro data.forEach
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}
?>