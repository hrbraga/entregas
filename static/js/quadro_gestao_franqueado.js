let choicesInstance;

document.addEventListener('DOMContentLoaded', function () {
    choicesInstance = new Choices('#seletorLojas', {
        removeItemButton: true,
        placeholderValue: 'Selecione as lojas...',
        searchPlaceholderValue: 'Pesquisar...',
        noResultsText: 'Nenhuma loja encontrada',
        noChoicesText: 'Não há mais lojas cadastradas',
        itemSelectText: 'Clique para selecionar',
        searchEnabled: true,
    });

    // Quando o usuário mudar as opções, atualiza o dashboard
    document.getElementById('seletorLojas').addEventListener('change', carregarDashboard);
});

window.dadosGlobais = {};
let chartInstancia = null;

function formatarBRL(valor) {
    return valor ? valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'R$ 0,00';
}

function obterNomeMesExtenso(datas) {

    const meses = [
        "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
    ];

    if (!datas || datas.length === 0) {
        return "-";
    }

    const partes = datas[0].split("-");

    if (partes.length >= 2) {
        return meses[parseInt(partes[1], 10) - 1];
    }

    return "-";
}

async function carregarDashboard() {
    try {
        const ids = choicesInstance.getValue(true);
        const lojaIds = (ids.length === 0) ? 'todas' : ids.join(',');

        const response = await fetch(`../api/get_quadro_gestao_franqueado.php?lojas=${lojaIds}&t=` + new Date().getTime());
        const dados = await response.json();

        if (dados.error) return;

        window.dadosGlobais = dados;
        renderizarCards(dados);
        renderizarGrafico(dados);
        calcularMetricasDias(dados);
        renderizarTabelaAuditoria(dados); // <--- Adicione esta linha aqui
    } catch (err) {
        console.error("Erro:", err);
    }
}

function renderizarCards(d) {
    document.getElementById('c-meta-total').innerText = formatarBRL(d.meta_total);
    document.getElementById('c-meta-acumulada').innerText = formatarBRL(d.meta_acumulada);
    document.getElementById('c-venda-acumulada').innerText = formatarBRL(d.venda_acumulada);

    let ating = (d.atingimento || 0);
    document.getElementById('c-atingimento').innerText = ating.toFixed(1) + '%';

    let circle = document.getElementById('c-atingimento-circle');
    if (circle) {
        let graus = (ating / 100) * 360;
        if (graus > 360) graus = 360;
        circle.style.setProperty('--progress', graus + 'deg');
        document.getElementById('c-atingimento-circle-text').innerText = Math.round(ating) + '%';
        circle.style.background = ating >= 100 ? `conic-gradient(#10b981 var(--progress), #e5e7eb 0deg)` : `conic-gradient(#3b82f6 var(--progress), #e5e7eb 0deg)`;
    }

    const elemGap = document.getElementById('c-gap');
    const cardGapTitle = document.getElementById('c-gap-title');

    if (d.gap > 0) {
        elemGap.innerText = formatarBRL(d.gap);
        elemGap.style.color = '#ef4444';
        if (cardGapTitle) cardGapTitle.innerText = 'GAP da Meta';
    } else {
        elemGap.innerText = '+ ' + formatarBRL(Math.abs(d.gap));
        elemGap.style.color = '#10b981';
        if (cardGapTitle) cardGapTitle.innerText = 'Acima da Meta';
    }

    document.getElementById('c-meta-ontem').innerText = formatarBRL(d.meta_ontem);
    document.getElementById('c-venda-ontem').innerText = formatarBRL(d.venda_ontem);
    document.getElementById('c-meta-hoje').innerText = formatarBRL(d.meta_hoje);
    document.getElementById('c-meta-ajustada').innerText = formatarBRL(d.meta_ajustada);
}

function calcularMetricasDias(dados) {
    document.getElementById('c-nome-mes').innerText = obterNomeMesExtenso(dados.grafico_datas);
    let diasUteis = 0; let diasRestantes = 0;
    const hojeDia = new Date().getDate();

    if (dados.grafico_metas && dados.grafico_datas) {
        let ultimaMetaAcumulada = 0;
        dados.grafico_metas.forEach((metaAcumulada, index) => {
            let metaDoDia = metaAcumulada - ultimaMetaAcumulada;
            ultimaMetaAcumulada = metaAcumulada;
            if (metaDoDia > 0) {
                diasUteis++;
                let diaNum = parseInt(dados.grafico_datas[index].split('-')[2], 10);
                if (diaNum >= hojeDia) diasRestantes++;
            }
        });
    }
    document.getElementById('c-dias-uteis').innerText = diasUteis;
    document.getElementById('c-dias-frente').innerText = diasRestantes;
}

function renderizarGrafico(dados) {
    const ctx = document.getElementById('graficoEvolucao').getContext('2d');
    if (chartInstancia) chartInstancia.destroy();

    const graficoAtingimentos = dados.grafico_metas.map((metaAcum, index) => {
        const vendaAcum = dados.grafico_vendas[index];
        if (vendaAcum === null || vendaAcum === undefined) return null;
        return metaAcum > 0 ? parseFloat(((vendaAcum / metaAcum) * 100).toFixed(1)) : 0;
    });

    chartInstancia = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dados.grafico_datas,
            datasets: [
                { label: 'Meta Acumulada (R$)', data: dados.grafico_metas, borderColor: '#9ca3af', borderDash: [5, 5], fill: false, tension: 0.3, yAxisID: 'y' },
                { label: 'Venda Acumulada (R$)', data: dados.grafico_vendas, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.3, yAxisID: 'y' },
                { label: 'Atingimento Diário (%)', data: graficoAtingimentos, borderColor: '#3b82f6', borderWidth: 3, fill: false, tension: 0.3, yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', display: true, position: 'left' },
                y1: { type: 'linear', display: true, position: 'right', min: 0, grid: { drawOnChartArea: false } }
            }
        }
    });
}

function enviarWhatsApp() {
    const d = window.dadosGlobais;

    // Pega todas as checkboxes que estão marcadas
    const checkboxes = document.querySelectorAll('.loja-checkbox:checked');
    // Coleta o texto das lojas selecionadas
    const nomesSelecionados = Array.from(checkboxes).map(cb => cb.parentElement.textContent.trim());

    // Define o nome da visão para o WhatsApp
    let nomeVisao = "";
    if (nomesSelecionados.length === 0) {
        nomeVisao = "Nenhuma loja selecionada";
    } else if (checkboxes.length === document.querySelectorAll('.loja-checkbox').length) {
        nomeVisao = "Consolidado de todas as Lojas";
    } else {
        nomeVisao = "Lojas: " + nomesSelecionados.join(', ');
    }

    if (!d || !d.meta_total) return alert("Os dados ainda não foram carregados completamente.");

    const statusMetaTexto = d.gap > 0 ? `📉 *GAP para meta:* ${formatarBRL(d.gap)}` : `📈 *Acima da Meta:* ${formatarBRL(Math.abs(d.gap))}`;
    const rotuloUltimoDia = d.data_ultimo_dia ? `Último Dia (${d.data_ultimo_dia})` : `Último Dia`;

    const texto = `*Visão:* ${nomeVisao}
📅 *Meta do Mês:* ${formatarBRL(d.meta_total)}
🗓️ *Meta acumulada:* ${formatarBRL(d.meta_acumulada)}
💰 *Venda acumulada:* ${formatarBRL(d.venda_acumulada)}
🎯 *Atingimento:* ${typeof d.atingimento === 'number' ? d.atingimento.toFixed(1) : 0}%

${statusMetaTexto}

⏪ *Meta - ${rotuloUltimoDia}:* ${formatarBRL(d.meta_ontem)}
✅ *Venda - ${rotuloUltimoDia}:* ${formatarBRL(d.venda_ontem)}

💪🏼 *Meta de Hoje:* ${formatarBRL(d.meta_hoje)}
🎯 *Meta Ajustada:* ${formatarBRL(d.meta_ajustada)}`;

    document.getElementById('textoCopia').value = texto;
    document.getElementById('modalCopiar').style.display = 'flex';
}

function enviarDiretoWhatsAppModal() {
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(document.getElementById("textoCopia").value)}`, '_blank');
}

async function copiarTexto() {
    let texto = document.getElementById("textoCopia").value.replace(/\r?\n/g, '\n').trim();
    try {
        await navigator.clipboard.writeText(texto);
        alert("Mensagem copiada!");
        document.getElementById('modalCopiar').style.display = 'none';
    } catch (err) {
        const copyText = document.getElementById("textoCopia");
        copyText.select();
        document.execCommand("copy");
        alert("Mensagem copiada!");
    }
}

// --- Controle de Abas ---
function mudarAba(abaId) {
    document.querySelectorAll('.aba').forEach(el => el.classList.remove('ativa'));
    document.querySelectorAll('.conteudo-aba').forEach(el => el.classList.remove('ativo'));
    event.currentTarget.classList.add('ativa');
    document.getElementById(abaId).classList.add('ativo');
}

// --- Máscara de Moeda para o Input ---
function mascaraMoeda(event) {
    let input = event.target;
    let valor = input.value.replace(/\D/g, '');
    if (valor === '') { input.value = ''; return; }
    valor = (parseInt(valor) / 100).toFixed(2) + '';
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = valor;
}

// --- Salvar a Edição ---
async function salvarCorrecaoVenda(lojaId, dataVenda, inputId) {

    const inputElement = document.getElementById(inputId);

    const novoValorString = inputElement.value;

    const novoValorDecimal = parseFloat(
        novoValorString
            .replace(/\./g, '')
            .replace(',', '.')
    );

    if (isNaN(novoValorDecimal)) {
        alert("Informe um valor válido.");
        return;
    }

    if (!confirm(`Deseja salvar a venda da loja para ${dataVenda}?`)) {
        return;
    }

    try {

        const resposta = await fetch('../api/admin_salvar_correcao_venda.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({

                loja_id: lojaId,
                data: dataVenda,
                valor: novoValorDecimal

            })

        });

        const json = await resposta.json();

        if (json.success) {

            inputElement.style.background = "#d1e7dd";

            setTimeout(() => {

                inputElement.style.background = "";

            }, 500);

            await carregarDashboard();



        } else {

            alert(json.error);

        }

    } catch (e) {

        console.error(e);

        alert("Erro ao comunicar com o servidor.");

    }

}

function renderizarTabelaAuditoria(dados) {

    const tabela = document.querySelector("#aba_auditoria table");
    const thead = tabela.querySelector("thead");
    const tbody = document.getElementById("tbody_auditoria_vendas");

    tbody.innerHTML = "";

    if (!dados.auditoria || dados.auditoria.length === 0) {
        thead.innerHTML = "";
        tbody.innerHTML = "<tr><td>Nenhum dado encontrado.</td></tr>";
        return;
    }

    // ======================================
    // MONTA A LISTA DE LOJAS
    // ======================================

    const lojas = dados.auditoria[0].lojas;

    // ======================================
    // CABEÇALHO
    // ======================================

    let htmlHeader = `
        <tr>
            <th rowspan="2"
                style="padding:10px;border:1px solid #ddd;background:#f8f9fa;">
                Data
            </th>
    `;

     const cores = [
            "#0d6efd",
            "#198754",
            "#fd7e14",
            "#6f42c1",
            "#dc3545",
            "#20c997",
            "#6610f2",
            "#795548",
            "#009688",
            "#ff9800"
        ];

    lojas.forEach((loja, index) => {
  

        htmlHeader += `
            <th colspan="3"
                style="
                    padding:10px;
                    border:1px solid #ddd;
                    background:${cores[index % cores.length]};
                    color:white;
                    font-size:16px;
                    font-weight:bold;
                    text-align:center;">
                ${loja.nome}
            </th>
        `;

    });

    htmlHeader += "</tr><tr>";

    lojas.forEach(() => {

        htmlHeader += `
            <th style="padding:8px;border:1px solid #ddd;">Meta</th>
            <th style="padding:8px;border:1px solid #ddd;">Venda</th>
            <th style="padding:8px;border:1px solid #ddd;">Salvar</th>
        `;

    });

    htmlHeader += "</tr>";

    thead.innerHTML = htmlHeader;

    // ======================================
    // LINHAS
    // ======================================

    dados.auditoria.forEach(linha => {

        const partes = linha.data.split("-");

        let html = `
            <tr>

                <td
                    style="
                        border:1px solid #ddd;
                        padding:10px;
                        font-weight:bold;">
                    ${partes[2]}/${partes[1]}/${partes[0]}
                </td>
        `;

        linha.lojas.forEach(loja => {

            const corLinha =
                loja.venda >= loja.meta
                    ? "#e8f7ec"
                    : "#fff2f2";

            const inputId = `venda_${linha.data}_${loja.id}`;

            html += `

                <td
                    style="
                    background:${corLinha};
                    border:1px solid #ddd;
                    text-align:right;
                    padding:8px;
                    color:#666;">
                    ${formatarBRL(loja.meta)}
                </td>

                <td
                style="
                    background:${corLinha};
                    border:1px solid #ddd;
                    padding:5px;">

                    <input
                        id="${inputId}"
                        type="text"
                        class="input-edicao-venda"
                        value="${loja.venda.toFixed(2).replace('.', ',')}"
                        onkeyup="mascaraMoeda(event)"
                        style="
                            width:110px;
                            text-align:right;">
                </td>

                <td
                    style="
                        border:1px solid #ddd;
                        text-align:center;">

                    <button

                        onclick="salvarCorrecaoVenda(
                            ${loja.id},
                            '${linha.data}',
                            '${inputId}'
                        )"

                        style="
                            background:#0d6efd;
                            color:white;
                            border:none;
                            padding:7px 12px;
                            border-radius:5px;
                            cursor:pointer;">

                        💾

                    </button>

                </td>

            `;

        });

        html += "</tr>";

        tbody.insertAdjacentHTML("beforeend", html);

    });

}


window.onload = carregarDashboard;