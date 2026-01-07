<?php 
    require '../config.php';       // 1. Inclui a configuração e sessão
    require '../auth/auth_check.php'; // 2. Protege a página
    require '../includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://img.icons8.com/dusk/64/cafe.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/planilhas.css">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/style.css"> <title>Controle Recebimentos</title>
</head>

<body>
   <div id="feedback-message" class="feedback-message" style="display: none;"></div>
   <div id="loading-spinner" class="loading-spinner" style="display: none;"></div>

 <div class="upload-area">
            <button id="import-csv-btn" class="import-csv-btn">Importar Pedidos (CSV)</button>
            <input type="file" id="csv-file-input" accept=".csv" style="display: none;">

            <button id="import-xml-btn">Importar XML (NF-e)</button>
            <input type="file" id="xml-file-input" accept=".xml" style="display: none;" multiple>

            <button id="print-list-btn" class="print-btn" onclick="window.print()">Imprimir Relatório</button>
        </div>

   
    <div class="pesquisar">
        <div class="search-area">
            <input type="text" id="product-search" placeholder="Buscar produto por Código ou Nome (mín. 3 caracteres)">
            <button id="clear-search-btn" class="clear-search-btn" style="display: none;">Limpar Pesquisa</button>
        </div>
    </div>

    <div id="filtered-results-container" class="filtered-results-container" style="display: none;">
        <h2>Resultado da Busca</h2>
        <div class="table-container">
            <table id="filtered-results-table">
                <thead>
                    <tr>
                        <th>CÓDIGO SAP</th>
                        <th>ITEM</th>
                        <th>TOTAL CAIXA</th>
                        <th>RECEBIDO</th>
                        <th>A RECEBER</th>
                    </tr>
                </thead>
                <tbody id="filtered-results-body">
                </tbody>
            </table>
        </div>
    </div>

    <div class="tabs-container">
        <button class="tab-btn active" data-target="tab-to-receive">Itens A Receber</button>
        <button class="tab-btn" data-target="tab-received">Concluídos</button>
        <button class="tab-btn" data-target="tab-summary">Resumo por Grupo</button>
    </div>

    <div class="tab-content active" id="tab-to-receive">
        <section class="table-section items-to-receive">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO SAP</th>
                            <th>ITEM</th>
                            <th>GRUPO</th>
                            <th>PEDIDO LOJA</th>
                            <th>PEDIDO VD</th>
                            <th>TOTAL CAIXA</th>
                            <th>A RECEBER</th>
                            <th style="min-width: 150px;">PROGRESSO</th> <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="table-body-to-receive">
                        </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="tab-content" id="tab-received">
        <section class="table-section items-received">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO SAP</th>
                            <th>ITEM</th>
                            <th>GRUPO</th>
                            <th>TOTAL CAIXA</th>
                            <th>RECEBIDO</th>
                            <th>STATUS</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="table-body-received">
                        </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="tab-content" id="tab-summary">
        <section class="group-summary-section">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>GRUPO</th>
                            <th>PEDIDO TOTAL</th>
                            <th>A RECEBER</th>
                            <th>ENTREGUE</th>
                        </tr>
                    </thead>
                    <tbody id="group-summary-body">
                        </tbody>
                </table>
            </div>
        </section>
    </div>

</body>
<script src="../static/js/script.js"></script>
<?php 
    require '../includes/footer.php'; // 4. Inclui o rodapé
?>