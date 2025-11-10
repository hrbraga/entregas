<?php
// db_config.php

// Conecta-se à sua base de dados de entregas
$db_entregas = new PDO('sqlite:entregas.db');
$db_entregas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db_entregas->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Conecta-se à sua base de dados de utilizadores
$db_users = new PDO('sqlite:users.db');
$db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db_users->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Inicia a sessão em todas as páginas
session_start();
?>