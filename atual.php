<?php
require 'config.php';
// Muda o perfil do CP1871 para franqueado
$db_users->exec("UPDATE user SET perfil = 'franqueado' WHERE username = 'CP1871'");
echo "Perfil atualizado! Tente acessar o gerenciar_usuarios.php agora.";
?>