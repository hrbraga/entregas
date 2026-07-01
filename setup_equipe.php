<?php
require 'config.php';
try {
    // Adiciona a coluna que vai ligar o funcionário ao franqueado correto
    $db_users->exec("ALTER TABLE user ADD COLUMN id_dono INTEGER DEFAULT NULL");
    echo "Sucesso! O banco de dados agora suporta equipes por loja.";
} catch (Exception $e) {
    echo "Aviso (a coluna já deve existir): " . $e->getMessage();
}
?>