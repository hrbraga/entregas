<?php
// config.php

// Inicia a sessão em TODAS as páginas. (Equivalente ao Flask-Login)
session_start();

// Caminhos das bases de dados
$db_entregas_path = __DIR__ . '/entregas.db';
$db_users_path = __DIR__ . '/users.db';

try {
    // Conexão à DB de Entregas (PDO)
    $db_entregas = new PDO('sqlite:' . $db_entregas_path);
    $db_entregas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_entregas->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Conexão à DB de Utilizadores (PDO)
    $db_users = new PDO('sqlite:' . $db_users_path);
    $db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_users->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}
?>