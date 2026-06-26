<?php
require 'config.php';
require 'auth/custos_auth_check.php'; // Protege esta página (Nível 1)
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/global.css">
    <link rel="stylesheet" href="static/css/selecao.css">
    <link rel="shortcut icon" href="static/img/chocolatinho.png" type="image/x-icon">
    <title>Caixa de Ferramentas</title>
</head>

<body>
    <header>
        <h1>Caixa de Ferramentas</h1>
        <div style="position: absolute; top: 10px; right: 20px; font-size: 1.6rem; color: white; z-index: 100;">
            Acesso: <?php echo htmlspecialchars($_SESSION['rcky_code']); ?> | <a href="auth/custos_logout.php"
                style="color: white; text-decoration: underline;">Sair</a>
        </div>
    </header>

    <main>

        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha"><a href="auth/area_franqueado.php"><img src="static/img/franqueado.jfif"
                            alt="Loja">
                        <p>Área do Franqueado</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="etiquetas/etiquetas.php">
                        <img src="static/img/megaloja.webp" alt="Mega Loja">
                        <p>Etiquetas Prateleira</p>
                    </a>
                </div>
                  <div class="campanha-2 campanha">
                    <a href="gestao/quadro_gestao.php">
                        <img src="static/img/gestao.jfif" alt="Quadro de Gestão">
                        <p>Quadro de Gestão</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="validades/validades.php">
                        <img src="static/img/trufas.webp" alt="Controle de Validades">
                        <p>Controle de Validades</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="delivery/gerador_delivery.php">
                        <img src="static/img/delivery.jfif" alt="Delivery">
                        <p>Etiqueta Delivery</p>
                    </a>
                </div>
                 <div class="campanha-2 campanha">
                    <a href="contrato/contratoPetit.php">
                        <img src="static/img/petitDeli.jpg" alt="Bombons Petit Deli">
                        <p>Contrato Encomenda Petit Deli</p>
                    </a>
                </div>
        </section>
    </main>

    <script src="static/js/campanha.js"></script>
</body>

</html>