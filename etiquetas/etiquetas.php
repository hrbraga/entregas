<?php 
    require '../config.php'; 
    require '../auth/custos_auth_check.php'
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Etiquetas</title>
    
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/selecao.css">
    <link rel="shortcut icon" href="../static/img/chocolatinho.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/etiquetas.css">

</head>
<body>

    <header>
        <h1>Gerador de Etiquetas</h1>
    </header>

    <main>
        <div class="interface-usuario">
            <div class="controles">
                <label for="codigo_produto">Código (Barra ou Interno):</label>
                <input type="text" id="codigo_produto" autofocus>

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" value="1" min="1" max="100">
                
                <button id="adicionar_produto">Adicionar</button>
            </div>

            <h3>Lista para Impressão</h3>
            <table class="lista_produtos" id="lista_produtos">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço 1 (R$)</th>
                        <th>Preço 2 (R$)</th>
                        <th>Qtd.</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <button id="gerar_etiquetas" class="botao-gerar">Imprimir Etiquetas</button>
        </div>
    </main>

    <footer>
        <a href="../selecao_ferramentas.php">
            <p>Voltar a Caixa de Ferramentas</p>
        </a>
    </footer>

    <div id="container_impressao"></div>

    <script src="../static/js/etiquetas.js"></script>
</body>
</html>