<?php 
    // --- Configuração da Página ---
    $page_title = "Transferência de Produtos Crianças"; // Mude o título para cada página
    
    // CSS/JS específico desta página
    $additional_head_tags = '
        <link rel="stylesheet" href="static/Custos/css/planilhas.css">
        <link rel="stylesheet" href="static/Custos/css/global.css">
    ';

    // Scripts específicos desta página
    $additional_scripts = '
        <script src="static/Custos/js/dados.js"></script>
        <script src="static/Custos/js/gerador.js"></script>
        <script src="static/Custos/js/btn-export.js"></script>
        <script src="static/Custos/js/filtro.js"></script>
        <script src="static/Custos/js/import-xls.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    ';
    // --- Fim da Configuração ---

    require 'config.php';       // 1. Inclui a configuração e sessão
    require 'auth_check.php'; // 2. Protege a página
    require 'includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<main>
    <div class="transferencia">
        <div class="lojas">
            <label class="remetente" for="remetente">Loja remetente:<input type="text" name="remetente"
                    id="remetente"></label>
            <label class="destino" for="destino">Loja destino:<input type="text" name="destino"
                    id="destino"></label>
            <label class="data" for="data">Data da Transferência:<input type="date" name="date" id="date"></label>
        </div>
        <div class="valores-transferencia">
            <p class="total-transferencia">Total Transferência:</p>
            <span class="vlr-transferencia" id="vlr-transferencia">0,00</span>
        </div>
    </div>
    <table class="tableizer-table">
        <thead>
            <tr class="tableizer-firstrow">
                <th>
                    Código<br>
                    <input type="text" id="filterCodigo" onkeyup="filterTable()" placeholder="Filtrar...">
                </th>
                <th>
                    Descrição do Material<br>
                    <input type="text" id="filterDescricao" onkeyup="filterTable()" placeholder="Filtrar...">
                </th>
                <th>QT caixa</th>
                <th>caixas</th>
                <th>unidades</th>
                <th>Valor Un</th>
                <th>Royalties</th>
                <th>ST</th>
                <th>IPI</th>
                <th>Txs Adicionais</th>
                <th>Tx Mídia</th>
                <th>Custo Caixa</th>
                <th>Custo UN</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1000323</td>
                <td>OVO CACAU MAGIA AO LEITE 80GX24UN</td>
                <td>24</td>
                <td><input type="number" class="caixas" min="0"></td>
                <td><input type="number" class="unidades" min="0"></td>
                <td>206,45</td>
                <td>103,23</td>
                <td>27,62</td>
                <td>6,71</td>
                <td>2,16</td>
                <td>0,00</td>
                <td>346,17</td>
                <td>14,42</td>
                <td><span class="total-item" id="total-valor">0.00</span></td>
            </tr>
            
            <tr>
                <td>1003617</td>
                <td>KIT BRINDE PASCOA 2025</td>
                <td>20</td>
                <td><input type="number" class="caixas" min="0"></td>
                <td><input type="number" class="unidades" min="0"></td>
                <td>166,67</td>
                <td>83,34</td>
                <td>0,00</td>
                <td>0,00</td>
                <td>1,80</td>
                <td>0,00</td>
                <td>251,81</td>
                <td>12,59</td>
                <td><span class="total-item" id="total-valor">0.00</span></td>
            </tr>

        </tbody>
    </table>
</main>


<?php 
    require 'includes/footer.php'; // 4. Inclui o rodapé
?>

<script>
        document.addEventListener('DOMContentLoaded', () => {
            gerarTabelaUniversal(produtos_criancas);
        });
    </script>