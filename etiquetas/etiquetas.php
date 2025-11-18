<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerador de Etiquetas</title>
    <link rel="stylesheet" href="../static/css/etiquetas.css">
</head>
<body>

    <div class="interface-usuario">
        <h2>Gerador de Etiquetas</h2>

        <div class="controles">
            <label for="codigo_produto">Código (Barra ou Interno):</label>
            <input type="text" id="codigo_produto" autofocus>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" value="1" min="1" max="100">
            
            <button id="adicionar_produto">Adicionar</button>
        </div>

        <h3>Lista para Impressão</h3>
        <table id="lista_produtos">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço NCL</th>
                    <th>Preço CL</th>
                    <th>Qtd.</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>

        <button id="gerar_etiquetas" class="botao-gerar">Imprimir Etiquetas</button>
    </div>

    <div id="container_impressao"></div>

    <script src="../static/js/etiquetas.js"></script>
</body>
</html>