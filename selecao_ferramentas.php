<?php
require 'config.php';
require 'custos_auth_check.php'; // Protege esta página (Nível 1)
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
        <div style="position: absolute; top: 10px; right: 20px; font-size: 1.6rem; color: white; z-index: 100;">
            Acesso: <?php echo htmlspecialchars($_SESSION['rcky_code']); ?> | <a href="custos_logout.php"
                style="color: white; text-decoration: underline;">Sair</a>
        </div>
    </header>

    <main>
       
        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha"><a href="custos_selecao.php"><img
                            src="static/Custos/src/img/lojas.jfif" alt="Loja">
                        <p>Custo dos Produtos</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="recebimentos.php">
                        <img src="static/Custos/src/img/caminhoes.jfif" alt="Entregas">
                        <p>Controle de Entregas</p>
                    </a>
                </div>
                 <div class="campanha-2 campanha">
                    <a href="contrato/contratoPetit.php">
                        <img src="static/Custos/src/img/petitDeli.jpg" alt="Bombons Petit Deli">
                        <p>Gerador de Contrato Petit Deli</p>
                    </a>
                </div>
        </section>
    </main>

    <script src="static/Custos/js/campanha.js"></script>
</body>

</html>