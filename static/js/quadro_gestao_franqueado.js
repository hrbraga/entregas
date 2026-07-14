let choicesInstance;

document.addEventListener('DOMContentLoaded', function() {
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
    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    if (datas && datas.length > 0 && datas[0].includes('/')) {
        const partes = datas[0].split('/');
        if (partes[1]) return meses[parseInt(partes[1], 10) - 1];
    }
    return "-";
}

async function carregarDashboard() {
    try {
        // Pega os IDs selecionados pelo Choices.js
        const ids = choicesInstance.getValue(true); 
        
        // Se ids estiver vazio (vazio = todas), define como 'todas'
        const lojaIds = (ids.length === 0) ? 'todas' : ids.join(',');

        const response = await fetch(`../api/get_quadro_gestao_franqueado.php?lojas=${lojaIds}&t=` + new Date().getTime());
        const dados = await response.json();
        
        if(dados.error) {
            console.error("Erro BD:", dados.error);
            return;
        }
        
        window.dadosGlobais = dados;
        renderizarCards(dados);
        renderizarGrafico(dados);
        calcularMetricasDias(dados);
    } catch(err) { 
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
        if(cardGapTitle) cardGapTitle.innerText = 'GAP da Meta';
    } else {
        elemGap.innerText = '+ ' + formatarBRL(Math.abs(d.gap));
        elemGap.style.color = '#10b981'; 
        if(cardGapTitle) cardGapTitle.innerText = 'Acima da Meta';
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
                let diaNum = parseInt(dados.grafico_datas[index].split('/')[0], 10);
                if (diaNum >= hojeDia) diasRestantes++; 
            }
        });
    }
    document.getElementById('c-dias-uteis').innerText = diasUteis;
    document.getElementById('c-dias-frente').innerText = diasRestantes;
}

function renderizarGrafico(dados) {
    const ctx = document.getElementById('graficoEvolucao').getContext('2d');
    if(chartInstancia) chartInstancia.destroy();

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
    
    if(!d || !d.meta_total) return alert("Os dados ainda não foram carregados completamente.");
    
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
    async function salvarCorrecaoVenda(dataVenda, inputId) {
        const inputElement = document.getElementById(inputId);
        const novoValorString = inputElement.value;
        
        // Converte a string "1.250,00" para decimal "1250.00"
        const novoValorDecimal = parseFloat(novoValorString.replace(/\./g, '').replace(',', '.'));

        if (isNaN(novoValorDecimal)) {
            alert("Por favor, insira um valor válido.");
            return;
        }

        if(confirm(`Tem certeza que deseja corrigir a venda do dia ${dataVenda} para R$ ${novoValorString}?`)) {
            try {
                // Aqui chamaremos a API do backend
                const res = await fetch('../api/admin_salvar_correcao_venda.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ data: dataVenda, valor: novoValorDecimal })
                });
                
                const json = await res.json();
                
                if(json.success) {
                    alert('✅ Venda corrigida com sucesso!');
                    inputElement.style.backgroundColor = '#d1e7dd'; // Pisca verde pra confirmar
                    setTimeout(() => inputElement.style.backgroundColor = '', 1500);
                } else {
                    alert('Erro ao salvar: ' + json.error);
                }
            } catch (e) {
                alert('Erro de comunicação com o servidor.');
            }
        }
    }


// Carrega os dados da opção "Todas as lojas" assim que a página abrir
window.onload = carregarDashboard;