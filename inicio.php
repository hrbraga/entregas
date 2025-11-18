<?php
require 'config.php'; // Inicia a sessão


if (isset($_SESSION['rcky_code'])) {
    header('Location: selecao_ferramentas.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pr-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="static/img/chocolatinho.png" type="image/x-icon">
    <link rel="stylesheet" href="static/css/global.css">
    <link rel="stylesheet" href="static/css/index.css">
    <title>Caixa de Ferramentas</title>
</head>

<body>
    <header>
        <h1>Caixa de Ferramentas</h1>
    </header>
    <main>
        <div class="acesso">
            <h2>Login</h2>
            <p class="frase">Digite seu código RCKY para acessar</p>

            <form id="formularioAcesso" action="auth/acesso_action.php" method="POST">
                <input type="text" maxlength="4" placeholder="Código RCKY" id="codigo" name="codigo" required>
                <button class="btn-acessar" type="submit">Acessar</button>

                <?php if (isset($_GET['erro'])): ?>
                    <p id="mensagem" style="color: red; margin-top: 10px;"><?php echo htmlspecialchars($_GET['erro']); ?>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </main>
    <footer>
        <p class="titulo_footer">Desenvolvido por Hugo Braga</p>
    </footer>
</body>

</html>