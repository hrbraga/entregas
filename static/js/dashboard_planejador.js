const dbMap = {};

dadosBanco.forEach(row => {
    dbMap[row.codigo_sap] = row;
});

// ========================================
// FORMATADOR
// ========================================

function formatR$(valor) {

    return 'R$ ' + valor
        .toFixed(2)
        .replace('.', ',')
        .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
}

// ========================================
// CLUSTER
// ========================================

function calcularClusterCampanha(percentual, vip = false) {

    if (vip) {

        if (percentual >= 102) {
            return {
                nome: 'OURO',
                meta: '>= 102%'
            };
        }

        if (percentual >= 100) {
            return {
                nome: 'PRATA',
                meta: '100% até 101,99%'
            };
        }

        if (percentual >= 95) {
            return {
                nome: 'BRONZE',
                meta: '95% até 99,99%'
            };
        }

        return {
            nome: 'COBRE',
            meta: '< 95%'
        };
    }

    // STANDARD

    if (percentual >= 105) {
        return {
            nome: 'OURO',
            meta: '>= 105%'
        };
    }

    if (percentual >= 100) {
        return {
            nome: 'PRATA',
            meta: '100% até 104,99%'
        };
    }

    if (percentual >= 95) {
        return {
            nome: 'BRONZE',
            meta: '95% até 99,99%'
        };
    }

    return {
        nome: 'COBRE',
        meta: '< 95%'
    };
}

// ========================================
// CONFIGURAÇÕES
// ========================================

function obterCondicoes(cluster, vip = false) {

    const standard = {

        OURO: {
            entrada: 70,
            segunda: 30,

            vencEntrada: '30/03/2027',
            vencSegunda: '28/04/2027',

            roy: '15/04/2027',
            produto: '19/04/2027'
        },

        PRATA: {
            entrada: 80,
            segunda: 20,

            vencEntrada: '30/03/2027',
            vencSegunda: '21/04/2027',

            roy: '12/04/2027',
            produto: '19/04/2027'
        },

        BRONZE: {
            entrada: 90,
            segunda: 10,

            vencEntrada: '30/03/2027',
            vencSegunda: '14/04/2027',

            roy: '08/04/2027',
            produto: '19/04/2027'
        },

        COBRE: {
            entrada: 100,
            segunda: 0,

            vencEntrada: '30/03/2027',

            roy: '05/04/2027',
            produto: '19/04/2027'
        }
    };

    const vipConfig = {

        OURO: {
            entrada: 70,
            segunda: 30,

            vencEntrada: '30/03/2027',
            vencSegunda: '05/05/2027',

            roy: '22/04/2027',
            produto: '19/04/2027'
        },

        PRATA: {
            entrada: 80,
            segunda: 20,

            vencEntrada: '30/03/2027',
            vencSegunda: '28/04/2027',

            roy: '19/04/2027',
            produto: '19/04/2027'
        },

        BRONZE: {
            entrada: 90,
            segunda: 10,

            vencEntrada: '30/03/2027',
            vencSegunda: '23/04/2027',

            roy: '15/04/2027',
            produto: '19/04/2027'
        },

        COBRE: {
            entrada: 100,
            segunda: 0,

            vencEntrada: '30/03/2027',

            roy: '05/04/2027',
            produto: '19/04/2027'
        }
    };

    return vip
        ? vipConfig[cluster]
        : standard[cluster];
}

// ========================================
// CARD COMERCIAL
// ========================================

function renderizarCondicaoComercial(
    selling,
    sugestao,
    taxasAdd,
    vip = false
) {

    const percentual = sugestao > 0
        ? (selling / sugestao) * 100
        : 0;

    let faltaTexto = '';

    let metaOuro = vip ? 102 : 105;

    if (percentual < metaOuro) {

        const valorNecessario =
            sugestao * (metaOuro / 100);

        const faltaValor =
            valorNecessario - selling;

        faltaTexto = `
        <div
            style="
                margin-top:12px;
                font-size:14px;
                color:#856404;
                background:#fff3cd;
                border:1px solid #ffeeba;
                padding:10px;
                border-radius:8px;
            "
        >
            Faltam
            <strong>
                ${formatR$(faltaValor)}
            </strong>
            para atingir o
            <strong>
                OURO
            </strong>
        </div>
    `;
    }

    const clusterInfo =
        calcularClusterCampanha(
            percentual,
            vip
        );

    const cluster = clusterInfo.nome;

    const config =
        obterCondicoes(
            cluster,
            vip
        );

    const primeiraValor =
        selling * (config.entrada / 100);

    const segundaValor =
        selling * (config.segunda / 100);

    const royalties =
        selling * 0.07;

    const cores = {

        OURO: '#d4af37',
        PRATA: '#9e9e9e',
        BRONZE: '#cd7f32',
        COBRE: '#c96f28'
    };

    const html = `

        <div
            class="condicao-box"
            style="
                border-left: 8px solid ${cores[cluster]};
                width: 100%;
            "
        >
<div
    style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:25px;
        gap:20px;
        width:100%;
    "
>

                <div>

                    <div
                        class="cluster-tag"
                        style="
                            background:${cores[cluster]};
                            color:white;
                        "
                    >
                        ${cluster}
                    </div>

                    <h2
                        style="
                            margin:10px 0 5px;
                        "
                    >
                        Franqueado
                        ${vip ? 'VIP' : 'STANDARD'}
                    </h2>

                    <div
                        style="
                            color:#666;
                            font-size:14px;
                        "
                    >
                        Meta:
                        ${clusterInfo.meta}
                    </div>

                </div>

                <div
    style="
        text-align:right;
        min-width:220px;
        background:#f8f9fa;
        padding:18px;
        border-radius:12px;
        border:1px solid #e9ecef;
    "
>

                    <div
                        style="
                            font-size:13px;
                            color:#777;
                        "
                    >
                        Performance Atual
                    </div>

                    <div
                        style="
                            font-size:38px;
                            font-weight:bold;
                            color:#343a40;
                        "
                    >
                        ${percentual.toFixed(2)}%
                        ${faltaTexto}
                    </div>

                </div>

            </div>

<div class="condicao-linha">



<div class="condicao-item">

    <strong>
        1ª Parcela - ${config.entrada}%
    </strong>

    <span>
        ${formatR$(primeiraValor)}
    </span>

    <small>
        ${config.vencEntrada}
    </small>

</div>

${config.segunda > 0 ? `

<div class="condicao-item">

    <strong>
        2ª Parcela - ${config.segunda}%
    </strong>

    <span>
        ${formatR$(segundaValor)}
    </span>

    <small>
        ${config.vencSegunda}
    </small>

</div>

` : ''}

<div class="condicao-item">

    <strong>
        Royalties
    </strong>

    <span>
        ${formatR$(royalties)}
    </span>

    <small>
        ${config.roy}
    </small>

</div>

<div class="condicao-item">

    <strong>
        Tx Produto
    </strong>

    <span>
        ${formatR$(taxasAdd)}
    </span>

    <small>
        ${config.produto}
    </small>

</div>

</div> 

    `;

    document.getElementById(
        'card-condicao'
    ).innerHTML = html;
}

function gerarLinhaComparativo(
    titulo,
    sugestao,
    pedido
) {

    const diferenca =
        pedido - sugestao;

    const percentual =
        sugestao > 0
            ? (diferenca / sugestao) * 100
            : 0;

    const classe =
        diferenca >= 0
            ? 'valor-positivo'
            : 'valor-negativo';

    return `
        <tr>

            <td>${titulo}</td>

            <td>
                ${formatR$(sugestao)}
            </td>

            <td>
                ${formatR$(pedido)}
            </td>

            <td class="${classe}">
                ${diferenca >= 0 ? '+' : '-'}
                ${formatR$(Math.abs(diferenca))}
            </td>

            <td class="
                percentual
                ${classe}
            ">
                ${percentual >= 0 ? '+' : ''}
                ${percentual.toFixed(2)}%
            </td>

        </tr>
    `;
}

// ========================================
// DASHBOARD
// ========================================

function calcularDashboard() {
    const unidadesPorGrupo = {};
    let selling = 0;
    let sellout = 0;
    let taxasAdd = 0;
    let midia = 0;
    let riv = 0;
    let sugestaoTotal = 0;
    let sellInSugestaoTotal = 0;
    let sellOutSugestaoTotal = 0;
    let sellInLoja = 0;
    let sellOutLoja = 0;
    let sellInVD = 0;
    let sellOutVD = 0;
    let sugestaoSellInLoja = 0;
    let sugestaoSellOutLoja = 0;
    let sugestaoSellInVD = 0;
    let sugestaoSellOutVD = 0;
    let totalPedidoVD = 0;

    const categorias = {};

    const canais = {
        Loja: 0,
        VD: 0
    };

    const topProdutos = [];

    const acimaSugestao = [];

    const abaixoSugestao = [];

    masterProdutosPascoa.forEach(prod => {

        const dbInfo =
            dbMap[prod.sap] || {};

        const pedidoLoja =
            parseInt(dbInfo.pedido_loja || 0);

        const pedidoVD =
            parseInt(dbInfo.pedido_vd || 0);

        const vendidoLoja =
            parseInt(dbInfo.un_vend_ant_loja || 0);

        const vendidoVD =
            parseInt(dbInfo.un_vend_ant_vd || 0);

        const sugestaoLoja =
            parseInt(dbInfo.sugestao_loja || 0);

        const sugestaoVD =
            parseInt(dbInfo.sugestao_vd || 0);

        const totalPedido =
            pedidoLoja + pedidoVD;

        const totalVendido =
            vendidoLoja + vendidoVD;

        const totalSugestao =
            sugestaoLoja + sugestaoVD;

        const qtdTotal =
            totalPedido * prod.qtdCx;

        const qtdSugestao =
            totalSugestao * prod.qtdCx;

        // Garante que o grupo exista no objeto
        if (!unidadesPorGrupo[prod.grupo]) {
            unidadesPorGrupo[prod.grupo] = {
                pedido: 0,
                vendido26: 0,
                sugestao: 0
            };
        }

        // Soma as unidades dentro do grupo específico
        unidadesPorGrupo[prod.grupo].pedido += qtdTotal;
        unidadesPorGrupo[prod.grupo].sugestao += qtdSugestao;
        unidadesPorGrupo[prod.grupo].vendido26 += (totalVendido * prod.qtdCx);

        const diferenca =
            qtdTotal - qtdSugestao;

        const sellInProduto =
            totalPedido *
            prod.custoCx;

        const sellOutProduto =
            (totalPedido * prod.qtdCx) *
            prod.precoVenda;

        const taxaAddProduto =
            (totalPedido * prod.qtdCx) *
            (prod.txAdd || 0);

        const midiaProduto =
            (totalPedido * prod.qtdCx) *
            (prod.txMidia || 0);

        const rivProduto =
            totalPedido *
            (prod.rivUnitario || 0);

        const sellOutHistorico =
            (totalVendido * prod.qtdCx) *
            prod.precoVenda;

        const sellInSugestao =
            totalSugestao *
            prod.custoCx;

        const sellOutSugestao =
            (totalSugestao * prod.qtdCx) *
            prod.precoVenda;

        // ========================================
        // LOJA
        // ========================================

        sellInLoja +=
            pedidoLoja * prod.custoCx;

        sellOutLoja +=
            (pedidoLoja * prod.qtdCx) *
            prod.precoVenda;

        sugestaoSellInLoja +=
            sugestaoLoja * prod.custoCx;

        sugestaoSellOutLoja +=
            (sugestaoLoja * prod.qtdCx) *
            prod.precoVenda;

        // ========================================
        // VD
        // ========================================

        sellInVD +=
            pedidoVD * prod.custoCx;

        sellOutVD +=
            (pedidoVD * prod.qtdCx) *
            prod.precoVenda;

        sugestaoSellInVD +=
            sugestaoVD * prod.custoCx;

        sugestaoSellOutVD +=
            (sugestaoVD * prod.qtdCx) *
            prod.precoVenda;

        totalPedidoVD += pedidoVD;

        selling += sellInProduto;

        sellout += sellOutProduto;

        sugestaoTotal += sellInSugestao;

        sellInSugestaoTotal += sellInSugestao;

        sellOutSugestaoTotal += sellOutSugestao;

        taxasAdd += taxaAddProduto;

        midia += midiaProduto;

        riv += rivProduto;

        // CATEGORIAS

        if (!categorias[prod.grupo]) {
            categorias[prod.grupo] = 0;
        }

        categorias[prod.grupo] += qtdTotal;

        // CANAIS

        canais.Loja +=
            pedidoLoja * prod.qtdCx;

        canais.VD +=
            pedidoVD * prod.qtdCx;

        // TOP 10

        topProdutos.push({

            nome: prod.descricao,

            quantidade: qtdTotal

        });
        if (diferenca > 0) {

            acimaSugestao.push({

                nome: prod.descricao,

                quantidade: diferenca

            });
        }

        if (diferenca < 0) {

            abaixoSugestao.push({

                nome: prod.descricao,

                quantidade: Math.abs(diferenca)

            });
        }
    });

    // ========================================
    // COMPARATIVO
    // ========================================

    const mostrarVD =
        totalPedidoVD > 0;

    let htmlComparativo = `

<div class="comparativo-wrapper">

    <div class="comparativo-titulo">
        SUGESTÃO X PEDIDO
    </div>

    <div class="comparativo-grid">

        <!-- LOJA -->

        <div class="comparativo-bloco">

            <div class="comparativo-header">
                LOJA
            </div>

            <div class="comparativo-tabela">

                <table>

                    <thead>

                        <tr>

                            <th></th>
                            <th>SUGESTÃO</th>
                            <th>PEDIDO</th>
                            <th>R$</th>
                            <th>%</th>

                        </tr>

                    </thead>

                    <tbody>

                        ${gerarLinhaComparativo(
        'SELL IN',
        sugestaoSellInLoja,
        sellInLoja
    )}

                        ${gerarLinhaComparativo(
        'SELL OUT',
        sugestaoSellOutLoja,
        sellOutLoja
    )}

                    </tbody>

                </table>

            </div>

        </div>
`;

    if (mostrarVD) {

        htmlComparativo += `

        <!-- VD -->

        <div class="comparativo-bloco">

            <div class="comparativo-header">
                VD
            </div>

            <div class="comparativo-tabela">

                <table>

                    <thead>

                        <tr>

                            <th></th>
                            <th>SUGESTÃO</th>
                            <th>PEDIDO</th>
                            <th>R$</th>
                            <th>%</th>

                        </tr>

                    </thead>

                    <tbody>

                        ${gerarLinhaComparativo(
            'SELL IN',
            sugestaoSellInVD,
            sellInVD
        )}

                        ${gerarLinhaComparativo(
            'SELL OUT',
            sugestaoSellOutVD,
            sellOutVD
        )}

                    </tbody>

                </table>

            </div>

        </div>
    `;
    }

    htmlComparativo += `

        <!-- TOTAL -->

        <div class="comparativo-bloco">

            <div class="comparativo-header">
                TOTAL
            </div>

            <div class="comparativo-tabela">

                <table>

                    <thead>

                        <tr>

                            <th></th>
                            <th>SUGESTÃO</th>
                            <th>PEDIDO</th>
                            <th>R$</th>
                            <th>%</th>

                        </tr>

                    </thead>

                    <tbody>

                        ${gerarLinhaComparativo(
        'SELL IN',
        sellInSugestaoTotal,
        selling
    )}

                        ${gerarLinhaComparativo(
        'SELL OUT',
        sellOutSugestaoTotal,
        sellout
    )}

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
`;

    document.getElementById(
        'comparativo-container'
    ).innerHTML = htmlComparativo;

    // ========================================
    // KPI
    // ========================================

    document.getElementById(
        'kpi-selling'
    ).textContent =
        formatR$(selling);

    document.getElementById(
        'kpi-sellout'
    ).textContent =
        formatR$(sellout);

    document.getElementById(
        'kpi-add'
    ).textContent =
        formatR$(taxasAdd);

    document.getElementById(
        'kpi-midia'
    ).textContent =
        formatR$(midia);

    document.getElementById(
        'kpi-riv'
    ).textContent =
        formatR$(riv);

    // ========================================
    // VIP
    // ========================================

    const toggleVip =
        document.getElementById(
            'toggleVip'
        );

    const isVip =
        toggleVip
            ? toggleVip.checked
            : false;

    // ========================================
    // CARD COMERCIAL
    // ========================================

    renderizarCondicaoComercial(
        selling,
        sugestaoTotal,
        taxasAdd,
        isVip
    );

    // ========================================
    // DESTROI GRÁFICOS
    // ========================================

    Chart.getChart(
        'graficoCategoria'
    )?.destroy();

    Chart.getChart(
        'graficoComparativoUnidades'
    )?.destroy();

    Chart.getChart(
        'graficoCanal'
    )?.destroy();

    Chart.getChart(
        'graficoTop10'
    )?.destroy();

    Chart.getChart(
        'graficoAcimaSugestao'
    )?.destroy();

    Chart.getChart(
        'graficoAbaixoSugestao'
    )?.destroy();

    // ========================================
    // GRÁFICO CATEGORIA
    // ========================================

    new Chart(
        document.getElementById(
            'graficoCategoria'
        ),
        {

            type: 'pie',

            data: {

                labels:
                    Object.keys(
                        categorias
                    ),

                datasets: [{

                    data:
                        Object.values(
                            categorias
                        )

                }]
            },

            options: {

                plugins: {

                    legend: {
                        position: 'bottom'
                    }

                }

            }

        }
    );

    // ========================================
    // GRÁFICO CANAL (Com Porcentagens)
    // ========================================

    // Calcula o total para podermos extrair as porcentagens
    const valoresCanais = Object.values(canais);
    const totalCanais = valoresCanais.reduce((a, b) => a + b, 0);

    new Chart(
        document.getElementById('graficoCanal'),
        {
            type: 'doughnut',
            data: {
                labels: Object.keys(canais),
                datasets: [{
                    data: valoresCanais
                }]
            },
            // Ativa o plugin de labels para este gráfico específico
            plugins: [ChartDataLabels],
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    // Configuração do Hover (Mouse por cima)
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                let valor = context.raw;
                                let percentual = totalCanais > 0 ? ((valor / totalCanais) * 100).toFixed(1) + '%' : '0%';
                                return label + valor + ' cx (' + percentual + ')';
                            }
                        }
                    },
                    // Configuração do texto fixo visível no gráfico
                    datalabels: {
                        color: '#ffffff', // Cor da fonte (branco fica bom em gráficos de rosca)
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: (value, context) => {
                            // Se o valor for 0, não mostra o label para não poluir
                            if (value === 0) return null;

                            let percentual = totalCanais > 0 ? ((value / totalCanais) * 100).toFixed(1) + '%' : '0%';
                            return percentual;
                        }
                    }
                }
            }
        }
    );

    // ========================================
    // TOP 10
    // ========================================

    topProdutos.sort(
        (a, b) =>
            b.quantidade -
            a.quantidade
    );

    const top10 =
        topProdutos.slice(0, 10);

    new Chart(
        document.getElementById(
            'graficoTop10'
        ),
        {

            type: 'bar',

            data: {

                labels:
                    top10.map(
                        p =>
                            p.nome.substring(0, 35)
                    ),

                datasets: [{

                    label:
                        'Quantidade Comprada',

                    data:
                        top10.map(
                            p => p.quantidade
                        )

                }]
            },

            options: {

                indexAxis: 'y',

                plugins: {

                    legend: {
                        position: 'bottom'
                    }

                }

            }

        }
    );
    acimaSugestao.sort(
        (a, b) =>
            b.quantidade -
            a.quantidade
    );

    const topAcima =
        acimaSugestao.slice(0, 10);

    new Chart(
        document.getElementById(
            'graficoAcimaSugestao'
        ),
        {

            type: 'bar',

            data: {

                labels:
                    topAcima.map(
                        p =>
                            p.nome.substring(0, 35)
                    ),

                datasets: [{

                    label:
                        'Acima da Sugestão',

                    data:
                        topAcima.map(
                            p => p.quantidade
                        )
                }]
            },

            options: {

                indexAxis: 'y',

                plugins: {

                    legend: {
                        position: 'bottom'
                    }

                }

            }

        }
    );

    abaixoSugestao.sort(
        (a, b) =>
            b.quantidade -
            a.quantidade
    );

    const topAbaixo =
        abaixoSugestao.slice(0, 10);

    new Chart(
        document.getElementById(
            'graficoAbaixoSugestao'
        ),
        {

            type: 'bar',

            data: {

                labels:
                    topAbaixo.map(
                        p =>
                            p.nome.substring(0, 35)
                    ),

                datasets: [{

                    label:
                        'Abaixo da Sugestão',

                    data:
                        topAbaixo.map(
                            p => p.quantidade
                        )
                }]
            },

            options: {

                indexAxis: 'y',

                plugins: {

                    legend: {
                        position: 'bottom'
                    }

                }

            }

        }
    );

    // ========================================
    // GRÁFICO COMPARATIVO UNIDADES
    // ========================================

    // ========================================
    // GRÁFICO COMPARATIVO UNIDADES POR GRUPO
    // ========================================

    const labelsGrupos = Object.keys(unidadesPorGrupo);

    // Mapeia os dados para cada coluna
    const dataVendido = labelsGrupos.map(g => unidadesPorGrupo[g].vendido26);
    const dataSugestao = labelsGrupos.map(g => unidadesPorGrupo[g].sugestao);
    const dataPedido = labelsGrupos.map(g => unidadesPorGrupo[g].pedido);

    new Chart(
        document.getElementById('graficoComparativoUnidades'),
        {
            type: 'bar',
            data: {
                labels: labelsGrupos, // O eixo X agora são os grupos (ex: Ovos, Tabletes)
                datasets: [
                    {
                        label: 'Vendido 26',
                        data: dataVendido,
                        backgroundColor: '#9e9e9e',
                        borderWidth: 0
                    },
                    {
                        label: 'Sugestão',
                        data: dataSugestao,
                        backgroundColor: '#17a2b8',
                        borderWidth: 0
                    },
                    {
                        label: 'Pedido',
                        data: dataPedido,
                        backgroundColor: '#d4af37',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom' // Mostra a legenda para identificar as cores
                    }
                },
                scales: {
                    x: {
                        // Isso garante que as barras fiquem agrupadas (lado a lado) por categoria
                        stacked: false
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );

}

// ========================================
// START
// ========================================

calcularDashboard();

// ========================================
// EVENTO VIP
// ========================================

document
    .getElementById('toggleVip')
    .addEventListener(
        'change',
        () => {

            calcularDashboard();

        }
    );