<?php 
    require '../config.php'; 
    require '../auth/custos_auth_check.php'; // MUDANÇA 1: Usar o novo "porteiro"
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/selecao.css">
    <link rel="shortcut icon" href="../static/img/chocolatinho.png" type="image/x-icon">
    
    <title>Campanhas anteriores</title>
</head>
<body>
    <header>
        <h1>Campanhas anteriores</h1>
    </header>

    <main>
        <section class="custos">
            <div class="campanhas">
                 <div class="campanha-2 campanha">
                    <a href="antigos/custos_natal_2025.php">
                        <img src="../static/img/natalCacauShow.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2025</p>
                    </a>                
                <div class="campanha-2 campanha">
                    <a href="antigos/custos_maes.php">
                        <img src="../static/img/mamaes.png" alt="Banner Campanha de Mães 2025">
                        <p>Mães e Namorados 2025</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="antigos/custos_pascoa.php">
                        <img src="../static/img/pascoa.webp" alt="Banner Campanha de Páscoa">
                        <p>Páscoa 2025</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="antigos/custos_canecas.php">
                        <img src="../static/img/canecas.jpg" alt="Banner Canecas">
                        <p>Canecas Oxford</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="antigos/custos_natal.php">
                        <img src="../static/img/magia-do-cacau.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2024</p>
                    </a>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <a href="../Custos/custos_selecao.php">
            <p>Voltar a Caixa de Ferramentas</p>
        </a>
    </footer>
    <script src="../static/js/campanha.js"></script>
</body>
</html>