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
            Acesso: <?php echo htmlspecialchars($_SESSION['rcky_code']); ?> | <a href="custos_logout.php" style="color: white; text-decoration: underline;">Sair</a>
        </div>
    </header>

    <main>
        <hr>
        <h2>Custos Produtos</h2>
        <hr>
        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha"><a href="custos_linha.php"><img src="static/Custos/src/img/lojas-cacau.jpeg" alt="Loja">
                        <p>Produtos de Linha</p>
                    </a></div>
                <div class="campanha-3 campanha"><a href="custos_natal_2025.php"><img src="static/Custos/src/img/natalCacauShow.jpg" alt="Natal">
                        <p>Natal 2025</p>
                    </a></div>
                <div class="campanha-2 campanha"><a href="custos_bendito2025.php"><img src="static/Custos/src/img/benditoCacao.jpg" alt="Bendito Cacao">
                        <p>Bendito Cacao</p>
                    </a></div>
                <div class="campanha-2 campanha"><a href="custos_criancas.php"><img src="static/Custos/src/img/criancada.png" alt="Crianças">
                        <p>Crianças, Halloween e Professores</p>
                    </a></div>
                <div class="campanha-2 campanha"><a href="custos_maes.php"><img src="static/Custos/src/img/mamaes.png" alt="Mães">
                        <p>Mães e Namorados</p>
                    </a></div>
                <div class="campanha-1 campanha"><a href="custos_pascoa.php"><img src="static/Custos/src/img/pascoa.webp" alt="Páscoa">
                        <p>Páscoa 2025</p>
                    </a></div>
                <div class="campanha-2 campanha"><a href="custos_canecas.php"><img src="static/Custos/src/img/canecas.jpg" alt="Canecas">
                        <p>Canecas Oxford</p>
                    </a></div>
                <div class="campanha-1 campanha"><a href="custos_natal.php"><img src="static/Custos/src/img/magia-do-cacau.jpg" alt="Natal 2024">
                        <p>Natal 2024</p>
                    </a></div>
            </div>
        </section>
        <hr>
        <h2>Ferramentas de Entregas</h2>
        <hr>
        <section class="ferramentas">
            <div class="controleEntradas campanhas">
                <div class="campanha-2 campanha">
                    <a href="recebimentos.php">
                        <img src="static/Custos/src/img/caminhoes.jfif" alt="Entregas">
                        <p>Entregas de Natal 2025</p>
                    </a>
                </div>
            </div>
        </section>
    </main>
    
    <script src="static/Custos/js/campanha.js"></script>
</body>
</html>