<?php
require '../config.php';
require '../auth/custos_auth_check.php';

try {
    $stmt = $db_produtos->query("SELECT * FROM custos_produtos ORDER BY campanha, descricao");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produtos = [];
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../static/img/coelho.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/planilhas.css">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/custos.css">
    <title>Custos dos Produtos</title>
</head>

<body>
    <header>
        <h1>Custos de Produtos</h1>


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
        </div>
    </header>

    <main>
        <div class="transferencia">
            <div class="lojas">
                <label class="remetente" for="remetente">Loja remetente:
                    <input type="text" name="remetente" id="remetente">
                </label>
                <label class="destino" for="destino">Loja destino:
                    <input type="text" name="destino" id="destino">
                </label>
                <label class="data" for="data">Data da Transferência:
                    <input type="date" name="date" id="date">
                </label>
            </div>
            <div class="valores-transferencia">
                <p class="total-transferencia">Total Transferência: </p>
                <span class="vlr-transferencia" id="vlr-transferencia"> 0,00</span>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <p style="color: red; text-align: center;"><?php echo $erro; ?></p>
        <?php endif; ?>

        <table class="tableizer-table">
            <thead>
                <tr class="tableizer-firstrow">
                    <th>
                        Campanha<br>
                        <select id="filterCampanha" onchange="filtrarTabela()">
                            <option class="filtroCampanha" value="">Todas</option>
                        </select>
                    </th>
                    <th>
                        Código<br>
                        <input type="text" id="filterCodigo" onkeyup="filtrarTabela()" placeholder="Filtrar..."
                            style="width:80px;">
                    </th>
                    <th>
                        Descrição do Material<br>
                        <input type="text" id="filterDescricao" onkeyup="filtrarTabela()" placeholder="Filtrar...">
                    </th>
                    <th>Preço (CL)</th>
                    <th>QT caixa</th>

                    <th>Caixas</th>
                    <th>Unidades</th>
                    <th>Valor CX</th>
                    <th>Royalties</th>
                    <th>ST</th>
                    <th>IPI</th>
                    <th>Taxas</th>
                    <th>Mídia</th>
                    <th>Custo CX</th>
                    <th>Custo UN</th>
                    <th>MB Líq (%)</th>
                    <th>MB Bruta (%)</th>
                    <th>Total R$</th>
                </tr>
            </thead>

            <tbody id="corpo-tabela">
            </tbody>
        </table>
    </main>

    <footer>
        <a href="custos_selecao.php">
            <p>Voltar ao Início</p>
        </a>
    </footer>

    <script src="../static/js/import-xls.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        const produtos_db = <?php echo json_encode($produtos); ?>;
    </script>

    <script src="../static/js/calculo_custos.js"></script>
</body>

</html>