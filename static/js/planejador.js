// O array "dadosBanco" é injetado pelo PHP antes de chamar este arquivo

const dbMap = {};

if (typeof dadosBanco !== 'undefined') {

    dadosBanco.forEach(row => {

        dbMap[row.codigo_sap] = row;

    });
}

// ========================================
// RENDERIZAÇÃO DAS TABELAS
// ========================================

function renderizarTabelas() {

    const tbodyLoja =
        document.getElementById('tbody-loja');

    const tbodyVd =
        document.getElementById('tbody-vd');

    const tbodyTotal =
        document.getElementById('tbody-total');

    let htmlLoja = '';
    let htmlVd = '';
    let htmlTotal = '';

    masterProdutosPascoa.forEach(prod => {

        const dbInfo = dbMap[prod.sap] || {

            sugestao_loja: 0,
            sugestao_vd: 0,

            pedido_loja: 0,
            pedido_vd: 0,

            un_vend_ant_loja: 0,
            un_vend_ant_vd: 0
        };

        const precoFormatado =
            'R$ ' +
            prod.precoVenda
                .toFixed(2)
                .replace('.', ',');

        // ========================================
        // ABA LOJA
        // ========================================

        htmlLoja += `
            <tr>

                <td class="text-left">
                    ${prod.sap}
                </td>

                <td class="text-left">
                    ${prod.descricao}
                </td>

                <td>
                    ${prod.grupo}
                </td>

                <td>
                    ${precoFormatado}
                </td>

               <td>
                 <strong>
        ${dbInfo.un_vend_ant_loja || 0}
                </strong>
                </td>

                <td>
                    <strong>
                        ${dbInfo.sugestao_loja}
                    </strong>
                </td>

                <td>
                    <input
                        type="number"
                        class="input-editavel"
                        data-sap="${prod.sap}"
                        data-campo="pedido_loja"
                        value="${dbInfo.pedido_loja}"
                        onchange="atualizarLocal(this)"
                    >
                </td>

            </tr>
        `;

        // ========================================
        // ABA VD
        // ========================================

        htmlVd += `
            <tr>

                <td class="text-left">
                    ${prod.sap}
                </td>

                <td class="text-left">
                    ${prod.descricao}
                </td>

                <td>
                    ${prod.grupo}
                </td>

                <td>
                    ${precoFormatado}
                </td>

              <td>
                <strong>
                    ${dbInfo.un_vend_ant_vd || 0}
                </strong>
              </td>

                <td>
                    <strong>
                        ${dbInfo.sugestao_vd}
                    </strong>
                </td>

                <td>
                    <input
                        type="number"
                        class="input-editavel"
                        data-sap="${prod.sap}"
                        data-campo="pedido_vd"
                        value="${dbInfo.pedido_vd}"
                        onchange="atualizarLocal(this)"
                    >
                </td>

            </tr>
        `;

        // ========================================
        // RESUMO CONSOLIDADO
        // ========================================

        const totalSugerido =
            dbInfo.sugestao_loja +
            dbInfo.sugestao_vd;

        const totalVendidoAnt =
            dbInfo.un_vend_ant_loja +
            dbInfo.un_vend_ant_vd;

        const totalPedido =
            dbInfo.pedido_loja +
            dbInfo.pedido_vd;

        const faturamentoPrevisto =
            totalPedido *
            prod.qtdCx *
            prod.precoVenda;

        const fatFormatado =
            'R$ ' +
            faturamentoPrevisto
                .toFixed(2)
                .replace('.', ',')
                .replace(
                    /(\d)(?=(\d{3})+(?!\d))/g,
                    '$1.'
                );

        htmlTotal += `
            <tr id="total-row-${prod.sap}">

                <td class="text-left">
                    ${prod.sap}
                </td>

                <td class="text-left">
                    ${prod.descricao}
                </td>

                <td>
                    ${prod.grupo}
                </td>

                <td>
                    ${precoFormatado}
                </td>

                <td class="tot-vend-ant">
                    ${totalVendidoAnt}
                </td>

                <td>
                    ${totalSugerido}
                </td>

                <td
                    class="tot-pedido"
                    style="font-weight:bold;"
                >
                    ${totalPedido}
                </td>

                <td class="tot-faturamento">
                    ${fatFormatado}
                </td>

            </tr>
        `;
    });

    if (tbodyLoja) {
        tbodyLoja.innerHTML = htmlLoja;
    }

    if (tbodyVd) {
        tbodyVd.innerHTML = htmlVd;
    }

    if (tbodyTotal) {
        tbodyTotal.innerHTML = htmlTotal;
    }

    calcularTotaisGlobais();
}

// ========================================
// ATUALIZAÇÃO LOCAL
// ========================================

function atualizarLocal(inputElement) {

    const sap =
        inputElement.getAttribute('data-sap');

    const campo =
        inputElement.getAttribute('data-campo');

    const valor =
        parseInt(inputElement.value) || 0;

    if (!dbMap[sap]) {

        dbMap[sap] = {

            sugestao_loja: 0,
            sugestao_vd: 0,

            pedido_loja: 0,
            pedido_vd: 0,

            un_vend_ant_loja: 0,
            un_vend_ant_vd: 0
        };
    }

    dbMap[sap][campo] = valor;

    // ========================================
    // ATUALIZA RESUMO
    // ========================================

    const prodMestre =
        masterProdutosPascoa.find(
            p => p.sap === sap
        );

    if (prodMestre) {

        const rowTotal =
            document.getElementById(
                `total-row-${sap}`
            );

        if (rowTotal) {

            const totalVendidoAnt =
                (dbMap[sap].un_vend_ant_loja || 0) +
                (dbMap[sap].un_vend_ant_vd || 0);

            const totalPedido =
                (dbMap[sap].pedido_loja || 0) +
                (dbMap[sap].pedido_vd || 0);

            const novoFaturamento =
                totalPedido *
                prodMestre.qtdCx *
                prodMestre.precoVenda;

            rowTotal.querySelector(
                '.tot-vend-ant'
            ).textContent =
                totalVendidoAnt;

            rowTotal.querySelector(
                '.tot-pedido'
            ).textContent =
                totalPedido;

            rowTotal.querySelector(
                '.tot-faturamento'
            ).textContent =
                formatR$(novoFaturamento);
        }
    }

    calcularTotaisGlobais();

    // ========================================
    // AJAX
    // ========================================

    inputElement.style.backgroundColor =
        '#ffffcc';

    const formData = new FormData();

    formData.append('ajax_update', '1');

    formData.append('codigo_sap', sap);

    formData.append('campo', campo);

    formData.append('valor', valor);

    fetch(
        'planejador.php',
        {
            method: 'POST',
            body: formData
        }
    )
        .then(() =>
            setTimeout(() => {

                inputElement.style.backgroundColor =
                    '#fff';

            }, 400)
        );
}

// ========================================
// TOTAIS GLOBAIS
// ========================================

function calcularTotaisGlobais() {

    let sellInSugestao = 0;
    let sellInPedido = 0;
    let pedidoTotal = 0;

    const abaAtiva =
        document.querySelector(
            '.conteudo-aba.ativo'
        ).id;

    masterProdutosPascoa.forEach(prod => {

        const dbInfo = dbMap[prod.sap] || {

            sugestao_loja: 0,
            sugestao_vd: 0,

            pedido_loja: 0,
            pedido_vd: 0,

            un_vend_ant_loja: 0,
            un_vend_ant_vd: 0
        };

        // ========================================
        // LOJA
        // ========================================

        if (abaAtiva === 'aba-loja') {

            const sugestao =
                parseInt(
                    dbInfo.sugestao_loja || 0
                );

            const pedido =
                parseInt(
                    dbInfo.pedido_loja || 0
                );

            sellInSugestao +=
                sugestao *
                prod.custoCx;

            sellInPedido +=
                pedido *
                prod.custoCx;

            pedidoTotal +=
                pedido *
                prod.qtdCx *
                prod.precoVenda;
        }

        // ========================================
        // VD
        // ========================================

        else if (abaAtiva === 'aba-vd') {

            const sugestao =
                parseInt(
                    dbInfo.sugestao_vd || 0
                );

            const pedido =
                parseInt(
                    dbInfo.pedido_vd || 0
                );

            sellInSugestao +=
                sugestao *
                prod.custoCx;

            sellInPedido +=
                pedido *
                prod.custoCx;

            pedidoTotal +=
                pedido *
                prod.qtdCx *
                prod.precoVenda;
        }

        // ========================================
        // TOTAL
        // ========================================

        else {

            const sugestao =
                (parseInt(
                    dbInfo.sugestao_loja || 0
                ) +

                    parseInt(
                        dbInfo.sugestao_vd || 0
                    ));

            const pedido =
                (parseInt(
                    dbInfo.pedido_loja || 0
                ) +

                    parseInt(
                        dbInfo.pedido_vd || 0
                    ));

            sellInSugestao +=
                sugestao *
                prod.custoCx;

            sellInPedido +=
                pedido *
                prod.custoCx;

            pedidoTotal +=
                pedido *
                prod.qtdCx *
                prod.precoVenda;
        }
    });

    // ========================================
    // PERFORMANCE
    // ========================================

    let percentual = 0;

    if (sellInSugestao > 0) {

        percentual =
            (
                (
                    sellInPedido -
                    sellInSugestao
                )
                /
                sellInSugestao
            ) * 100;
    }

    const clusterInfo =
        document.getElementById(
            'cluster-info'
        );

    // ========================================
    // CLUSTERS
    // ========================================

    if (abaAtiva === 'aba-vd') {

        const vip =
            calcularCluster(
                percentual,
                'vip'
            );

        clusterInfo.innerHTML = `

            <div class="cluster-box vip">

                <strong>
                    Cluster VIP:
                </strong>

                ${vip.cluster}

                <br>

                Pedido vs Sugestão:
                ${percentual.toFixed(2)}%

                <br>

                ${vip.falta > 0

                ? `Faltam +${vip.falta}% para subir de nível`

                : 'Nível máximo atingido'
            }

            </div>
        `;
    }

    else if (abaAtiva === 'aba-loja') {

        const standard =
            calcularCluster(
                percentual,
                'standard'
            );

        const vip =
            calcularCluster(
                percentual,
                'vip'
            );

        clusterInfo.innerHTML = `

            <div class="clusters-grid">

                <div class="cluster-box standard">

                    <strong>
                        Cluster Standard:
                    </strong>

                    ${standard.cluster}

                    <br>

                    Performance:
                    ${percentual.toFixed(2)}%

                    <br>

                    ${standard.falta > 0

                ? `Faltam +${standard.falta}% para subir de nível`

                : 'Nível máximo atingido'
            }

                </div>

                <div class="cluster-box vip">

                    <strong>
                        Cluster VIP:
                    </strong>

                    ${vip.cluster}

                    <br>

                    Performance:
                    ${percentual.toFixed(2)}%

                    <br>

                    ${vip.falta > 0

                ? `Faltam +${vip.falta}% para subir de nível`

                : 'Nível máximo atingido'
            }

                </div>

            </div>
        `;
    }

    else {

        const standard =
            calcularCluster(
                percentual,
                'standard'
            );

        const vip =
            calcularCluster(
                percentual,
                'vip'
            );

        clusterInfo.innerHTML = `

        <div class="clusters-grid">

            <div class="cluster-box standard">

                <strong>
                    Cluster Standard:
                </strong>

                ${standard.cluster}

                <br>

                Performance:
                ${percentual.toFixed(2)}%

                <br>

                ${standard.falta > 0

                ? `Faltam +${standard.falta}% para subir de nível`

                : 'Nível máximo atingido'
            }

            </div>

            <div class="cluster-box vip">

                <strong>
                    Cluster VIP:
                </strong>

                ${vip.cluster}

                <br>

                Performance:
                ${percentual.toFixed(2)}%

                <br>

                ${vip.falta > 0

                ? `Faltam +${vip.falta}% para subir de nível`

                : 'Nível máximo atingido'
            }

            </div>

        </div>
    `;
    }

    // ========================================
    // FOOTER
    // ========================================

    document.getElementById(
        'foot-vendido'
    ).textContent =
        formatR$(sellInSugestao);

    document.getElementById(
        'foot-sugestao'
    ).textContent =
        formatR$(sellInPedido);

    document.getElementById(
        'foot-pedido'
    ).textContent =
        formatR$(pedidoTotal);
}

// ========================================
// FORMATADOR
// ========================================

function formatR$(valor) {

    return 'R$ ' +
        valor
            .toFixed(2)
            .replace('.', ',')
            .replace(
                /(\d)(?=(\d{3})+(?!\d))/g,
                '$1.'
            );
}

// ========================================
// CLUSTER
// ========================================

function calcularCluster(
    percentual,
    tipo = 'standard'
) {

    if (tipo === 'vip') {

        if (percentual >= 2) {

            return {
                cluster: 'Ouro',
                falta: 0
            };
        }

        else if (percentual >= 0) {

            return {
                cluster: 'Prata',
                falta: (
                    2 - percentual
                ).toFixed(2)
            };
        }

        else if (percentual >= -5) {

            return {
                cluster: 'Bronze',
                falta: (
                    0 - percentual
                ).toFixed(2)
            };
        }

        else {

            return {
                cluster: 'Cobre',
                falta: (
                    -5 - percentual
                ).toFixed(2)
            };
        }
    }

    // ========================================
    // STANDARD
    // ========================================

    if (percentual >= 5) {

        return {
            cluster: 'Ouro',
            falta: 0
        };
    }

    else if (percentual >= 0) {

        return {
            cluster: 'Prata',
            falta: (
                5 - percentual
            ).toFixed(2)
        };
    }

    else if (percentual >= -5) {

        return {
            cluster: 'Bronze',
            falta: (
                0 - percentual
            ).toFixed(2)
        };
    }

    else {

        return {
            cluster: 'Cobre',
            falta: (
                -5 - percentual
            ).toFixed(2)
        };
    }
}

// ========================================
// ABAS
// ========================================

function mudarAba(evento, idAba) {

    document
        .querySelectorAll(
            ".conteudo-aba"
        )
        .forEach(el => {

            el.style.display = "none";

            el.classList.remove(
                "ativo"
            );
        });

    document
        .querySelectorAll(
            ".aba-btn"
        )
        .forEach(el =>

            el.classList.remove(
                "ativa"
            )
        );

    const abaSelecionada =
        document.getElementById(idAba);

    abaSelecionada.style.display =
        "block";

    abaSelecionada.classList.add(
        "ativo"
    );

    evento.currentTarget.classList.add(
        "ativa"
    );

    calcularTotaisGlobais();
}

// ========================================
// START
// ========================================

if (
    typeof masterProdutosPascoa !==
    'undefined'
) {

    renderizarTabelas();
}

// ========================================
// FOOTER
// ========================================

function toggleFooter() {

    const footer =
        document.querySelector(
            '.footer-totais'
        );

    const botao =
        document.querySelector(
            '.footer-toggle'
        );

    footer.classList.toggle(
        'footer-hidden'
    );

    botao.classList.toggle(
        'hidden-mode'
    );

    if (
        footer.classList.contains(
            'footer-hidden'
        )
    ) {

        botao.innerHTML =
            'Mostrar ▲';
    }

    else {

        botao.innerHTML =
            'Esconder ✕';
    }
}


function exportarXLSX() {

    const abaAtiva =
        document.querySelector(
            '.conteudo-aba.ativo'
        );

    const tabelaOriginal =
        abaAtiva.querySelector('table');

    // ========================================
    // CLONA A TABELA
    // ========================================

    const tabelaClone =
        tabelaOriginal.cloneNode(true);

    // ========================================
    // SUBSTITUI INPUTS PELOS VALORES
    // ========================================

    const inputsOriginais =
        tabelaOriginal.querySelectorAll(
            'input'
        );

    const inputsClone =
        tabelaClone.querySelectorAll(
            'input'
        );

    inputsClone.forEach(
        (input, index) => {

            const valor =
                inputsOriginais[index].value;

            const td =
                input.parentElement;

            td.textContent = valor;
        }
    );

    // ========================================
    // WORKBOOK
    // ========================================

    const workbook =
        XLSX.utils.book_new();

    // ========================================
    // PLANILHA PRINCIPAL
    // ========================================

    const worksheet =
        XLSX.utils.table_to_sheet(
            tabelaClone
        );

    XLSX.utils.book_append_sheet(
        workbook,
        worksheet,
        'Planejador'
    );

    // ========================================
    // EXPORTA
    // ========================================

    XLSX.writeFile(
        workbook,
        'planejador_pascoa.xlsx'
    );
}