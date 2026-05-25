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

// ========================================
// DASHBOARD
// ========================================

function calcularDashboard() {

    let selling = 0;
    let sellout = 0;
    let taxasAdd = 0;
    let midia = 0;
    let riv = 0;
    let sugestaoTotal = 0;

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

        selling += sellInProduto;

        sellout += sellOutProduto;

        sugestaoTotal += sellInSugestao;

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
    // GRÁFICO CANAL
    // ========================================

    new Chart(
        document.getElementById(
            'graficoCanal'
        ),
        {

            type: 'doughnut',

            data: {

                labels:
                    Object.keys(
                        canais
                    ),

                datasets: [{

                    data:
                        Object.values(
                            canais
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