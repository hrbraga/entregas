<?php
require '../config.php';
require '../auth/restrito_check.php'; // Esta é a sua nova e única barreira de segurança
require '../auth/auth_franqueado_check.php';
$sessao_nome = "Custos de Produtos"; 
$page_title = "Gerenciar Custos";
require '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/selecao.css">
    <link rel="shortcut icon" href="../static/img/chocolatinho.png" type="image/x-icon">
    
    <title>Custo dos Produtos</title>
</head>
<body>
   <main>
            <div class="campanhas">
                <div class="campanha-2 campanha">
                    <a href="custo_produtos.php">
                        <img src="../static/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                        <p>Geral Produtos</p>
                    </a>
                </div>
                <div class="campanha-3 campanha">
                    <a href="custos_pascoa_2026.php">
                        <img src="../static/img/Pascoa2026.JPG" alt="Campanha de Páscoa 2026">
                        <p>Páscoa 2026</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="campanhas_anteriores.php">
                        <img src="../static/img/cacau-show_antiga.jpg" alt="Campanhas anteriores">
                        <p>Campanhas anteriores</p>
                    </a>
                </div>
               
            </div>
    </main>
   <?php require '../includes/footer.php'; ?>