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
    <title>Custos de Produtos - Geral</title>
    <style>
        .col-campanha { font-weight: bold; color: #4a2c2a; }
    </style>
</head>

<body>
    <header>
        <h1>Custos de Produtos (Geral)</h1>
         <div class="botoes">
            <div class="btn-importar botoesImportacaoEExportacao">
                <p>GERENCIAR</p>
                <a href="gerenciar_custos.php"><button>EDICAO / ADICAO</button></a>
            </div>
            <div class="btn-exportar botoesImportacaoEExportacao">
                <p>EXPORTAR</p>
                <button onclick="exportToPDF()">PDF</button>
                <button onclick="exportToXLS()">XLS</button>
            </div>
        </div>
    </header>

    <main>
        <?php if(isset($erro)): ?>
            <p style="color: red; text-align: center;"><?php echo $erro; ?></p>
        <?php endif; ?>

        <table class="tableizer-table">
            <thead>
                <tr class="tableizer-firstrow">
                    <th>
                        Campanha<br>
                        <select id="filterCampanha" onchange="filterTableCampanha()">
                            <option value="">Todas</option>
                        </select>
                    </th>
                    <th>
                        Código<br>
                        <input type="text" id="filterCodigo" onkeyup="filterTable()" placeholder="Filtrar...">
                    </th>
                    <th>
                        Descrição do Material<br>
                        <input type="text" id="filterDescricao" onkeyup="filterTable()" placeholder="Filtrar...">
                    </th>
                    <th>QT caixa</th>
                    <th>Valor CX</th>
                    <th>Royalties</th>
                    <th>ST</th>
                    <th>IPI</th>
                    <th>Txs Adicionais</th>
                    <th>Tx Mídia</th>
                    <th>Custo Caixa</th>
                    <th>Custo UN</th>
                    <th>MB Líquida (%)</th>
                    <th>MB Bruta (%)</th>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/shim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        const produtos_db = <?php echo json_encode($produtos); ?>;

        function renderizarTabela(dados) {
            const tbody = document.getElementById('corpo-tabela');
            tbody.innerHTML = '';

            dados.forEach(p => {
                // Tratamento para evitar 'null' ou 'undefined' na tela
                const mbLiquida = p.mbLiquida ? parseFloat(p.mbLiquida).toFixed(2) : '0.00';
                const mbBruta = p.mbBruta ? parseFloat(p.mbBruta).toFixed(2) : '0.00';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="col-campanha">${p.campanha}</td>
                    <td>${p.codigo}</td>
                    <td>${p.descricao}</td>
                    <td>${p.qtCaixa}</td>
                    <td>R$ ${parseFloat(p.valorUn).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.royalties).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.st).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.ipi).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.txsAdicionais).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.txMidia).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.custoCaixa).toFixed(2)}</td>
                    <td>R$ ${parseFloat(p.custoUn).toFixed(2)}</td>
                    <td>${mbLiquida}%</td>
                    <td>${mbBruta}%</td>
                `;
                tbody.appendChild(tr);
            });
            preencherFiltroCampanha(dados);
        }

        function preencherFiltroCampanha(dados) {
            const select = document.getElementById('filterCampanha');
            if (select.options.length > 1) return;
            
            const campanhas = [...new Set(dados.map(item => item.campanha))];
            campanhas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                select.appendChild(opt);
            });
        }

        function filterTableCampanha() {
            const filtro = document.getElementById('filterCampanha').value;
            const linhas = document.querySelectorAll('#corpo-tabela tr');

            linhas.forEach(linha => {
                const colunaCampanha = linha.children[0].textContent; // Coluna 0 é Campanha
                if (filtro === "" || colunaCampanha === filtro) {
                    linha.style.display = "";
                } else {
                    linha.style.display = "none";
                }
            });
        }

        function filterTable() {
            // Se precisar implementar o filtro de texto
            const inputCodigo = document.getElementById("filterCodigo").value.toUpperCase();
            const inputDesc = document.getElementById("filterDescricao").value.toUpperCase();
            const rows = document.getElementById("corpo-tabela").getElementsByTagName("tr");

            for (let i = 0; i < rows.length; i++) {
                // Coluna 1 = Código, Coluna 2 = Descrição (Baseado no HTML acima)
                let tdCodigo = rows[i].getElementsByTagName("td")[1];
                let tdDesc = rows[i].getElementsByTagName("td")[2];
                
                if (tdCodigo && tdDesc) {
                    let txtCodigo = tdCodigo.textContent || tdCodigo.innerText;
                    let txtDesc = tdDesc.textContent || tdDesc.innerText;
                    
                    // Verifica se visibilidade foi alterada pelo filtro de campanha antes
                    let isVisible = rows[i].style.display !== "none"; 

                    // Lógica simples: Se já estava oculto pelo filtro de campanha, mantém oculto.
                    // Se estava visível, aplica filtro de texto.
                    // Para simplificar: O ideal é re-aplicar todos os filtros juntos, mas para uso rápido:
                    if (txtCodigo.toUpperCase().indexOf(inputCodigo) > -1 && txtDesc.toUpperCase().indexOf(inputDesc) > -1) {
                         // Só mostra se passar no filtro de campanha também
                         const filtroCampanha = document.getElementById('filterCampanha').value;
                         const campRow = rows[i].getElementsByTagName("td")[0].textContent;
                         if(filtroCampanha === "" || campRow === filtroCampanha){
                             rows[i].style.display = "";
                         }
                    } else {
                        rows[i].style.display = "none";
                    }
                }       
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderizarTabela(produtos_db);
        });
        
        function exportToXLS() {
             const ws = XLSX.utils.json_to_sheet(produtos_db);
             const wb = XLSX.utils.book_new();
             XLSX.utils.book_append_sheet(wb, ws, "Custos");
             XLSX.writeFile(wb, "custos_geral.xlsx");
        }
    </script>
</body>
</html>