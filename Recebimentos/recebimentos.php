<?php 
    // --- Configuração da Página ---
    $page_title = "Controle de Entregas";
    
    // CSS/JS específico desta página (do {% block head %})
    $additional_head_tags = '
        <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    ';

    // Scripts específicos desta página (do {% block scripts %})
    $additional_scripts = '
        <script src="static/js/script.js"></script>
        <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    ';
    // --- Fim da Configuração ---

    require '../config.php';       // 1. Inclui a configuração e sessão
    require '../auth/auth_check.php'; // 2. Protege a página
    require '../includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<div id="feedback-message" class="feedback-message" style="display: none;"></div>
<div id="loading-spinner" class="loading-spinner" style="display: none;"></div>

<section class="instructions">
    <h2>Instruções</h2>
    <p>1. **Importe seus pedidos** (arquivo .csv) para carregar sua lista de produtos.</p>
    <p>2. Importe os arquivos **XML (NF-e)** para registrar as entregas.</p>
    <p>3. Edite ou exclua itens conforme necessário.</p>
</section>


<div class="upload-area">
    <button id="import-csv-btn" class="import-csv-btn">Importar Pedidos (CSV)</button>
    <input type="file" id="csv-file-input" accept=".csv" style="display: none;">

    <button id="import-xml-btn">Importar XML (NF-e)</button>
    <input type="file" id="xml-file-input" accept=".xml" style="display: none;" multiple>

    <button id="print-list-btn" class="print-btn">Imprimir Listagem</button>
</div>

<div class="pesquisar">
    <h2>Pesquisar Produto:<h2>
            <div class="search-area">
                <input type="text" id="product-search"
                    placeholder="Buscar produto por Código ou Nome (mín. 3 caracteres)">
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
                    <th>ITEM PÁSCOA 2025</th>
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

<section class="delivery-tables-container">

    <section class="table-section items-to-receive">
        <h2>Itens A RECEBER</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO SAP</th>
                        <th>ITEM PÁSCOA 2025</th>
                        <th>GRUPO</th>
                        <th>PEDIDO LOJA</th>
                        <th>PEDIDO VD</th>
                        <th>TOTAL CAIXA</th>
                        <th>A RECEBER</th>
                        <th>RECEBIDO</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="table-body-to-receive">
                </tbody>
            </table>
        </div>
    </section>

    <section class="table-section items-received">
        <h2>Itens RECEBIDOS (Concluídos)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO SAP</th>
                        <th>ITEM PÁSCOA 2025</th>
                        <th>GRUPO</th>
                        <th>PEDIDO LOJA</th>
                        <th>PEDIDO VD</th>
                        <th>TOTAL CAIXA</th>
                        <th>A RECEBER</th>
                        <th>RECEBIDO</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="table-body-received">
                </tbody>
            </table>
        </div>
    </section>
</section>

<section class="group-summary-section">
    <h2>Resumo por Grupo</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>GRUPO</th>
                    <th>PEDIDO</th>
                    <th>À RECEBER</th>
                    <th>ENTREGUE</th>
                </tr>
            </thead>
            <tbody id="group-summary-body">
            </tbody>
        </table>
    </div>
</section>
<?php 
    require '../includes/footer.php'; // 4. Inclui o rodapé
?>