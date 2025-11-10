<!DOCTYPE html>
<html lang="pr-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../src/img/coelho.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/planilhas.css">
    <link rel="stylesheet" href="../css/global.css">
    <title>Bendito Cacao 2025</title>
</head>

<body>
    <header>
        <h1>Bendito Cacao 2025</h1>
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
      <th>Valor CX </th>
      <th>Royalties</th>
      <th>ST</th>
      <th>IPI</th>
      <th>Txs Adicionais</th>
      <th>Tx Mídia</th>
      <th>Custo Caixa</th>
      <th>Custo UN </th>
      <th>Total </th>
      <th>MB Líquida (%)</th>
      <th>MB Bruta (%)</th>
    </tr>
  </thead>
  <tbody id="corpo-tabela">
  </tbody>
</table>

    </main>
    
    <footer>
        <a href="..\html\selecao.html">
            <p>Voltar ao Início</p>
        </a>
    </footer>
    
    <script src="../js/dados.js"></script>
    <script src="../js/gerador.js"></script>
    <script src="../js/btn-export.js"></script>
    <script src="../js/filtro.js"></script>
    <script src="../js/import-xls.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            gerarTabelaUniversal(produtos_benditoCacao);
        });
    </script>
</body>
</html>
