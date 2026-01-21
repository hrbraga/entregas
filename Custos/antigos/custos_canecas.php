<?php 
    require '../../config.php'; // Volta duas pastas (sai de antigos, sai de Custos)
    require '../../auth/custos_auth_check.php'; // Mesma coisa para o auth
?>

<!DOCTYPE html>
<html lang="pr-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://img.icons8.com/dusk/64/cafe.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/planilhas.css">
    <link rel="stylesheet" href="../../static/css/global.css">
    <title>Canecas</title>
</head>

<body>
    <header>
        <h1>Canecas OXFORD</h1>

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
                <table class="tableizer-table">
                    <thead>
                        <tr class="tableizer-firstrow">
                            <th>
                                Código<br>
                                <input type="text" id="filterCodigo" onkeyup="filterTable()" placeholder="Filtrar...">
                            </th>
                            <th>
                                Descrição do Material<br>
                                <input type="text" id="filterDescricao" onkeyup="filterTable()"
                                    placeholder="Filtrar...">
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
                            <th> Total </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>156406</td>
                            <td>CANECAS MIMO 350ML - CS - URSINHOS CARINHOSOS - ANIMADINHA - A310-3OO7</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>175,68</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>11,42</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>187,10</td>
                            <td>15,59</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>156411</td>
                            <td>CANECAS MIMO 350ML - CS - URSINHOS CARINHOSOS - ZANGADINHO - A310-3OO7</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>175,68</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>11,42</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>187,10</td>
                            <td>15,59</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>156416</td>
                            <td>CANECAS MIMO 350ML - CS - URSINHOS CARINHOSOS - DIVERTIDO - A310-3OO7</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>175,68</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>11,42</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>187,10</td>
                            <td>15,59</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>154518 </td>
                            <td>CANECAS H10 300ML - CACAU SHOW - MIAU SILHUETAS - AMARELO - AG27-3OL9</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>157,80</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>10,26</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>168,06</td>
                            <td>14,00</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>154521</td>
                            <td>CANECAS H10 300ML - CACAU SHOW - MIAU SILHUETAS - BRANCO - AG27-3OL9</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>157,80</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>10,26</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>168,06</td>
                            <td>14,00</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>144716</td>
                            <td>CANECAS COM RELEVO 300ML - S/ COD. BARRA - CACAU SHOW - LA CREME - VAQUINHA - AH56-3NI2
                            </td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>156,60</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>10,18</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>166,78</td>
                            <td>13,90</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133825</td>
                            <td>CANECAS DROP 250ML - S/ COD. BARRA - CACAU SHOW - NUNCA E MUITO CEDO PARA COMER -
                                AJ57-1193</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>102,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>6,63</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>108,63</td>
                            <td>9,05</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133831</td>
                            <td>CANECAS DROP 250ML - S/ COD. BARRA - CACAU SHOW - SE A VIDA FOR UMA BARRA - AJ57-376T
                            </td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>102,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>6,63</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>108,63</td>
                            <td>9,05</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>23858</td>
                            <td>CANECAS 300ML COM RELEVO - CACAU SHOW - 2000753 - AO LEITE - AD69-3R75</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>150,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>9,75</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>159,75</td>
                            <td>13,31</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133885</td>
                            <td>CANECAS 300ML COM RELEVO - CACAU SHOW - MARROM - AD69-3ML1</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>150,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>9,75</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>159,75</td>
                            <td>13,31</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133871</td>
                            <td>CANECAS RYO 260ML - S/ COD. BARRA - CACAU SHOW - MARROM COM GRANILHA - AR01-3ML2</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>153,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>9,95</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>162,95</td>
                            <td>13,58</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133867</td>
                            <td>CANECAS RYO 260ML - S/ COD. BARRA - CACAU SHOW - AR01-3MK4</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>153,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>9,95</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>162,95</td>
                            <td>13,58</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133841</td>
                            <td>CANECAS JUMBO 740ML - S/ COD. BARRA - CACAU SHOW- METADE DE MIM - MARROM ESCURO -
                                AJ81-3MJ9</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>222,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>14,43</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>236,43</td>
                            <td>19,70</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133841</td>
                            <td>CANECAS JUMBO 740ML - S/ COD. BARRA - CACAU SHOW - VOCE NAO PODE COMPRAR - TRANSPARENTE
                                - AJ81-3MK2</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>222,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>14,43</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>236,43</td>
                            <td>19,70</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                        <tr>
                            <td>133841</td>
                            <td>CANECAS RYO 260ML - S/ COD. BARRA - CACAU SHOW - LANUT - AR01-3MM3</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>183,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>11,90</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>195,00</td>
                            <td>16,24</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                          <tr>
                            <td>133847</td>
                            <td>CANECAS TULIPA 330ML -  S/ COD. BARRA - CACAU SHOW - FELIZ HOJE - TRANSPARENTE</td>
                            <td>12</td>
                            <td><input type="number" class="caixas" min="0"></td>
                            <td><input type="number" class="unidades" min="0"></td>
                            <td>168,00</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>10,92</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>178,92</td>
                            <td>14,91</td>
                            <td><span class="total-item" >0.00</span></td>
                        </tr>
                    </tbody>
                </table>
    </main>

    <footer>
                <a href="../campanhas_anteriores.php">
            <p>Voltar ao Início</p>
        </a>
    </footer>
    <script src="../../static/js/dados.js"></script>
    <script src="../../static/js/gerador.js"></script>
    <script src="../../static/js/btn-export.js"></script>
    <script src="../../static/js/filtro.js"></script>
    <script src="../../static/js/import-xls.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleciona todas as linhas da tabela (ignorando o cabeçalho)
    const rows = document.querySelectorAll('.tableizer-table tbody tr');
    const totalGeralSpan = document.getElementById('vlr-transferencia');

    function calcularTotal() {
        let totalGeral = 0;
        
        rows.forEach(row => {
            // Pega os inputs de cada linha
            const cxInput = row.querySelector('.caixas');
            const unInput = row.querySelector('.unidades');
            
            // Se não tiver inputs nessa linha (ex: cabeçalho), pula
            if (!cxInput || !unInput) return;

            const qtdCx = parseFloat(cxInput.value) || 0;
            const qtdUn = parseFloat(unInput.value) || 0;

            // Pega os custos das colunas 11 e 12 (Baseado na sua estrutura atual)
            // Custo Caixa está na coluna 11 (índice começa em 0)
            // Custo UN está na coluna 12
            
            // Função para limpar o texto (tirar R$, pontos e trocar vírgula por ponto)
            const limparValor = (texto) => {
                return parseFloat(texto.replace('R$', '').trim().replace(/\./g, '').replace(',', '.')) || 0;
            };

            const custoCx = limparValor(row.cells[11].innerText);
            const custoUn = limparValor(row.cells[12].innerText);

            // Cálculo
            const totalLinha = (qtdCx * custoCx) + (qtdUn * custoUn);

            // Atualiza o total da linha
            const spanTotal = row.querySelector('.total-item');
            if (spanTotal) {
                spanTotal.innerText = totalLinha.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            totalGeral += totalLinha;
        });

        // Atualiza o Total Geral lá em cima
        if (totalGeralSpan) {
            totalGeralSpan.innerText = totalGeral.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    // Adiciona o evento de 'input' (digitação) em todos os campos
    const inputs = document.querySelectorAll('.caixas, .unidades');
    inputs.forEach(input => {
        input.addEventListener('input', calcularTotal);
    });
});
</script>

</body>

</html>