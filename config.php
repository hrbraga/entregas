<?php

session_start();

$db_entregas_path = __DIR__ . '/db/entregas.db';
$db_users_path = __DIR__ . '/db/users.db';
$db_portal_path = __DIR__ . '/db/portal_access.db'; // BD Novo
$db_produtos_path = __DIR__ . '/db/produtos.db';

try {
    // DB 1: Entregas (Dados das notas)
    $db_entregas = new PDO('sqlite:' . $db_entregas_path);
    $db_entregas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_entregas->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_entregas->exec("PRAGMA journal_mode = WAL;"); // Melhoria de performance

    // DB 2: Utilizadores (Logins das Lojas)
    $db_users = new PDO('sqlite:' . $db_users_path);
    $db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_users->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_users->exec("PRAGMA journal_mode = WAL;");

    // DB 3: Portal (Logins RCKY)
    $db_portal = new PDO('sqlite:' . $db_portal_path);
    $db_portal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_portal->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_portal->exec("PRAGMA journal_mode = WAL;");

    // ==========================================================
    //  AQUI ESTÁ A CORREÇÃO
    // ==========================================================
    // DB 4: Produtos (Catálogo de Etiquetas)
    $db_produtos = new PDO('sqlite:' . $db_produtos_path);
    $db_produtos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_produtos->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_produtos->exec("PRAGMA journal_mode = WAL;");
    // ==========================================================


} catch (PDOException $e) {
    die("Erro fatal na conexão com uma das bases de dados: " . $e->getMessage());
}

