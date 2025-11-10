<?php
// config.php (Versão ATUALIZADA com 3 BDs)

session_start();

$db_entregas_path = __DIR__ . '/entregas.db';
$db_users_path = __DIR__ . '/users.db';
$db_portal_path = __DIR__ . '/portal_access.db'; // BD Novo

try {
    // DB 1: Entregas (Dados das notas)
    $db_entregas = new PDO('sqlite:' . $db_entregas_path);
    $db_entregas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_entregas->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_entregas->exec("PRAGMA journal_mode = WAL;"); // Melhoria de performance

    // DB 2: Utilizadores (Logins das Lojas - user/pass, gerido pelo user via register.php)
    $db_users = new PDO('sqlite:' . $db_users_path);
    $db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_users->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_users->exec("PRAGMA journal_mode = WAL;");

    // DB 3: Portal (Logins RCKY - apenas códigos, gerido por si via admin_add_rcky.php)
    $db_portal = new PDO('sqlite:' . $db_portal_path);
    $db_portal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_portal->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_portal->exec("PRAGMA journal_mode = WAL;");

} catch (PDOException $e) {
    die("Erro fatal na conexão com uma das bases de dados: " . $e->getMessage());
}
?>