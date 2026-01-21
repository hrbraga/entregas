<?php 
    require '../../config.php'; // Volta duas pastas (sai de antigos, sai de Custos)
    require '../../auth/custos_auth_check.php'; // Mesma coisa para o auth
?>

<!DOCTYPE html>
<html lang="pr-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="shortcut icon" href="../../static/img/coelho.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/planilhas.css">
    <link rel="stylesheet" href="../../static/css/global.css">
    
    <title>Transferência de Páscoa</title>
</head>

<body>
    <header>
        <h1>Páscoa 2025</h1>
         <div class="botoes">
            <div class="btn-importar botoesImportacaoEExportacao">
                <p>IMPORTAR ARQUIVO</p>
                <button id="btn-importar">IMPORTAR</button>
                <input type="file" id="importXLS" accept=".xlsx, .xls" style="display: none;">
            </div>
          
            <div class="btn-exportar botoesImportacaoEExportacao">
                <p>EXPORTAR</p>
                <button onclick="exportToPDF()">PDF</button>
                <button onclick="exportToXLS()">XLS</button>
            </div>
            
            <div class="btn-limpar botoesImportacaoEExportacao">
                <p>LIMPAR TUDO</p>
                <button id="btn-limpar">LIMPAR</button>
            </div>
        </div>
    </header>

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
    
    <footer>
             <a href="../campanhas_anteriores.php">
            <p>Voltar ao Início</p>
        </a>
    </footer>

    <script src="../static/js/campanha.js"></script>
    <script src="../static/js/dados.js"></script>
    <script src="../static/js/gerador.js"></script>
    <script src="../static/js/btn-export.js"></script>
    <script src="../static/js/filtro.js"></script>
    <script src="../static/js/import-xls.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>