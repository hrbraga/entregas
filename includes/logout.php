<?php
// logout.php
require 'config.php';
session_destroy();
header('Location: /'); // Redireciona para a página inicial
exit;
?>