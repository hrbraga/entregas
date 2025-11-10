<?php 
    // 1. Inicia a sessão e 2. Protege a página
    require 'config.php'; 
    require 'auth_check.php'; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="static/Custos/css/global.css">
    <link rel="stylesheet" href="static/Custos/css/selecao.css">
    
    <link rel="shortcut icon" href="static/Custos/src/img/chocolatinho.png" type="image/x-icon">
    
    <title>Caixa de Ferramentas</title>
</head>
<body>
    <header>
        <h1>Caixa de ferramentas</h1>
    </header>

    <main>
        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha">
                    <a href="custos_selecao.php">
                        <img src="static/Custos/src/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                        <p>Custo dos Produtos</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="login.php">
                        <img src="static/Custos/src/img/caminhoes.jfif" alt="Banner Caminhões">
                        <p>Controle de Entregas</p>
                    </a>
                </div>

        </section>
    </main>
    <script src="static/Custos/js/campanha.js"></script>
</body>
</html>