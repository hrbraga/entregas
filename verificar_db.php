<?php
require 'config.php';

try {
    // Lista todas as tabelas do arquivo users.db
    $stmt = $db_users->query("SELECT name FROM sqlite_master WHERE type='table';");
    $tabelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tabelas encontradas no users.db:<br>";
    foreach ($tabelas as $tabela) {
        echo "- " . $tabela['name'] . "<br>";
    }
} catch (Exception $e) {
    echo "Erro ao ler o banco: " . $e->getMessage();
}
?>