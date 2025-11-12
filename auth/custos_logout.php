<?php
require '../config.php';

// Destrói a sessão (faz logout de AMBOS os sistemas, o que é mais seguro)
session_destroy();

// Redireciona para a página de login de "Custos"
header('Location: ../inicio.php');
exit;
?>