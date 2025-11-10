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
                    <a href="custos_linha.php">
                        <img src="static/Custos/src/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                        <p>Produtos de Linha</p>
                    </a>
                </div>
                <div class="campanha-3 campanha">
                    <a href="custos_natal_2025.php">
                        <img src="static/Custos/src/img/natalCacauShow.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2025</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_bendito2025.php">
                        <img src="static/Custos/src/img/benditoCacao.jpg" alt="Banner Bendito Cacao">
                        <p>Bendito Cacao</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_criancas.php">
                        <img src="static/Custos/src/img/criancada.png" alt="Banner Campanha de Crianças">
                        <p>Crianças, Halloween e Professores</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_maes.php">
                        <img src="static/Custos/src/img/mamaes.png" alt="Banner Campanha de Mães 2025">
                        <p>Mães e Namorados</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="custos_pascoa.php">
                        <img src="static/Custos/src/img/pascoa.webp" alt="Banner Campanha de Páscoa">
                        <p>Páscoa 2025</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_canecas.php">
                        <img src="static/Custos/src/img/canecas.jpg" alt="Banner Canecas">
                        <p>Canecas Oxford</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="custos_natal.php">
                        <img src="static/Custos/src/img/magia-do-cacau.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2024</p>
                    </a>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <a href="selecao_ferramentas.php">
            <p>Voltar ao Início</p>
        </a>
    </footer>
    <script src="static/Custos/js/campanha.js"></script>
</body>
</html>