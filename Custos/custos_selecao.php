<?php 
    require '../config.php'; 
    
    // --- INÍCIO DA PROTEÇÃO POR SENHA SIMPLES --
    $senha_da_pagina = "trufadebanana"; // <--- DEFINA SUA SENHA AQUI

    // Verifica se a senha foi enviada via POST
    if (isset($_POST['senha_acesso'])) {
        if ($_POST['senha_acesso'] === $senha_da_pagina) {
            $_SESSION['custos_acesso_liberado'] = true;
        } else {
            $erro_senha = "Senha incorreta!";
        }
    }

    // Se não estiver logado, mostra o formulário de senha e interrompe o script
    if (!isset($_SESSION['custos_acesso_liberado']) || $_SESSION['custos_acesso_liberado'] !== true) {
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito</title>
    <link rel="stylesheet" href="../static/css/login.css"> </head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Área Restrita</h2>
            <?php if (isset($erro_senha)) echo "<p style='color:red;text-align:center'>$erro_senha</p>"; ?>
            <div class="form-group">
                <label>Digite a senha de acesso:</label>
                <input type="password" name="senha_acesso" required autofocus>
            </div>
            <button type="submit" class="login-btn">Entrar</button>
        </form>
    </div>
</body>
</html>
<?php
        exit; // Impede que o restante da página carregue
    }
    // --- FIM DA PROTEÇÃO ---
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
    <header>
        <h1>Custo dos Produtos</h1>
    </header>

    <main>
        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha">
                    <a href="custo_produtos.php">
                        <img src="../static/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                        <p>Geral Produtos</p>
                    </a>
                </div>
                <div class="campanha-3 campanha">
                    <a href="custos_pascoa_2026.php">
                        <img src="../static/img/pascoa2026.jpg" alt="Banner Campanha de Páscoa 2026">
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
        </section>
    </main>
    <footer>
        <a href="../selecao_ferramentas.php">
            <p>Voltar a Caixa de Ferramentas</p>
        </a>
    </footer>
    <script src="../static/js/campanha.js"></script>
</body>
</html>