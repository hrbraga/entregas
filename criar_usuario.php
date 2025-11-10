<?php
// criar_usuario.php (Execute isto uma vez no seu browser)
require 'config.php';

$username = 'hugo'; // O seu novo utilizador
$password = 'sua-nova-senha-segura'; 

// Hash da senha com o padrão do PHP
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db_users->prepare("INSERT INTO user (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $password_hash]);

echo "Utilizador $username criado com sucesso!";
?>