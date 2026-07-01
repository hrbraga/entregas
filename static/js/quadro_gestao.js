// static/js/quadro_gestao.js

window.dadosGlobais = {};
let chartInstancia = null;

function formatarBRL(valor) {
    return valor ? valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'R$ 0,00';
}

function mascaraMoeda(e) {
    let v = e.target.value.replace(/\D/g, '');
    v = (v / 100).toFixed(2) + '';
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    e.target.value = v;
}

// Retorna o nome do mês por extenso
function obterNomeMesExtenso(datas) {
    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    
    if (datas && datas.length > 0 && datas[0].includes('/')) {
        const partes = datas[0].split('/');
        if (partes[1]) return meses[parseInt(partes[1], 10) - 1];
    }
 
    return "-";
}

// 1. CARREGAMENTO DO DASHBOARD
async function carregarDashboard() {
    try {
        const response = await fetch('../api/get_quadro_gestao.php?t=' + new Date().getTime());
        const dados = await response.json();
        
        if(dados.error) {
            console.error("Erro retornado do PHP:", dados.error);
            return;
        }
        
        window.dadosGlobais = dados;

        if (dados.dias_pendentes && dados.dias_pendentes.length > 0) {
            document.getElementById('modalPendencias').style.display = 'flex';
            document.getElementById('dashboardContent').classList.add('dashboard-blur');
            
            let inputsHTML = '';
            dados.dias_pendentes.forEach(dia => {
                let dataFormatada = dia.split('-').reverse().slice(0,2).join('/');
                inputsHTML += `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <label style="font-weight:600; font-size: 16px;">Dia ${dataFormatada}:</label>
                    <input type="text" name="venda[${dia}]" class="input-moeda" required style="width:140px; padding:8px; border:1px solid #ccc; border-radius:4px; font-size: 16px;" placeholder="0,00">
                </div>`;
            });
            document.getElementById('listaPendencias').innerHTML = inputsHTML;

            document.querySelectorAll('.input-moeda').forEach(input => {
                input.addEventListener('input', mascaraMoeda);
            });

        } else {
            document.getElementById('modalPendencias').style.display = 'none';
            document.getElementById('dashboardContent').classList.remove('dashboard-blur');
            
            renderizarCards(dados);
            renderizarGrafico(dados);
            calcularMetricasDias(dados); // Alimenta os novos containers
        }
    } catch(err) { 
        console.error("Erro crítico ao carregar dashboard:", err); 
    }
}

// 2. RENDERIZAÇÃO DOS CARDS (Com GAP Dinâmico)
function renderizarCards(d) {
    document.getElementById('c-meta-total').innerText = formatarBRL(d.meta_total);
    document.getElementById('c-meta-acumulada').innerText = formatarBRL(d.meta_acumulada);
    document.getElementById('c-venda-acumulada').innerText = formatarBRL(d.venda_acumulada);
    document.getElementById('c-atingimento').innerText = (d.atingimento || 0).toFixed(1) + '%';
    
    // --- LÓGICA DINÂMICA DO GAP ---
    const elemGap = document.getElementById('c-gap');
    const cardGapIcon = elemGap.parentElement.querySelector('.qg-card-icon');
    const cardGapTitle = elemGap.parentElement.querySelector('.qg-card-header span');

    if (d.gap > 0) {
        elemGap.innerText = formatarBRL(d.gap);
        elemGap.style.color = '#ef4444'; 
        if(cardGapIcon) {
            cardGapIcon.style.color = '#ef4444';
            cardGapIcon.style.background = '#fef2f2';
        }
        if(cardGapTitle) cardGapTitle.innerText = 'GAP da Meta';
    } else {
        const valorAcima = Math.abs(d.gap); 
        elemGap.innerText = '+ ' + formatarBRL(valorAcima);
        elemGap.style.color = '#10b981'; 
        if(cardGapIcon) {
            cardGapIcon.style.color = '#10b981';
            cardGapIcon.style.background = '#ecfdf5';
        }
        if(cardGapTitle) cardGapTitle.innerText = 'Acima da Meta';
    }

    document.getElementById('c-meta-ontem').innerText = formatarBRL(d.meta_ontem);
    document.getElementById('c-venda-ontem').innerText = formatarBRL(d.venda_ontem);
    document.getElementById('c-meta-hoje').innerText = formatarBRL(d.meta_hoje);
    document.getElementById('c-meta-ajustada').innerText = formatarBRL(d.meta_ajustada);
}

// 3. CALCULA OS DIAS ÚTEIS E DIAS RESTANTES
function calcularMetricasDias(dados) {
    document.getElementById('c-nome-mes').innerText = obterNomeMesExtenso(dados.grafico_datas);

    let diasUteis = 0;
    let diasRestantes = 0;
    const hojeDia = new Date().getDate();

    if (dados.grafico_metas && dados.grafico_datas) {
        let ultimaMetaAcumulada = 0;
        
        dados.grafico_metas.forEach((metaAcumulada, index) => {
            let metaDoDia = metaAcumulada - ultimaMetaAcumulada;
            ultimaMetaAcumulada = metaAcumulada;

            if (metaDoDia > 0) {
                diasUteis++; 
                
                let diaNum = parseInt(dados.grafico_datas[index].split('/')[0], 10);
                if (diaNum >= hojeDia) {
                    diasRestantes++; 
                }
            }
        });
    }

    document.getElementById('c-dias-uteis').innerText = diasUteis;
    document.getElementById('c-dias-frente').innerText = diasRestantes;
}

// 4. RENDERIZAÇÃO DO GRÁFICO (Eixo Duplo)
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
            responsive: true, 
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', display: true, position: 'left' },
                y1: { type: 'linear', display: true, position: 'right', min: 0, grid: { drawOnChartArea: false } }
            }
        }
    });
}

// 5. FUNÇÕES DE RESET
function abrirModalReset() {
    document.getElementById('modalResetMes').style.display = 'flex';
}

async function confirmarResetMes() {
    try {
        const response = await fetch('../api/resetar_mes.php', { method: 'POST' });
        const res = await response.json();
        
        if (res.success) {
            alert('Mês reiniciado! Todas as metas e vendas deste mês foram limpas.');
            document.getElementById('modalResetMes').style.display = 'none';
            carregarDashboard();
        } else {
            alert('Erro ao reiniciar: ' + res.error);
        }
    } catch (err) { alert('Erro de conexão.'); }
}

// 6. EVENTO DE ENVIO DO MODAL DE PENDÊNCIAS
document.getElementById('formPendencias').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const dataToSend = {};
    for (let [key, val] of formData.entries()) {
        const dia = key.replace('venda[', '').replace(']', '');
        dataToSend[dia] = parseFloat(val.replace(/\./g, '').replace(',', '.'));
    }
    try {
        const response = await fetch('../api/salvar_vendas_pendentes.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dataToSend) });
        const res = await response.json();
        if (res.success) { alert('Vendas salvas com sucesso!'); carregarDashboard(); } 
        else { alert('Erro ao salvar: ' + res.error); }
    } catch (err) { alert('Erro de conexão ao salvar.'); }
});

// 7. IMPORTAÇÃO DE PLANILHA CSV
document.getElementById('importar_metas').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    if(!confirm("Deseja importar as metas desta planilha?")) {
        e.target.value = ''; // Limpa a seleção se cancelar
        return;
    }
    
    const reader = new FileReader();
    reader.onload = async function(event) {
        const lines = event.target.result.split('\n').slice(1);
        const dataToImport = lines.filter(l => l.trim()).map(l => {
            const [data, meta] = l.split(';');
            return { data: data ? data.trim() : '', meta: meta ? meta.trim() : '' };
        }).filter(item => item.data);

        // --- VALIDAÇÃO INTELIGENTE DO MÊS ---
        if (dataToImport.length > 0) {
            // Pega a primeira data válida da planilha e separa dia, mês e ano
            const [diaCSV, mesCSV, anoCSV] = dataToImport[0].data.split('/');
            
            // Pega o mês e ano do dia de hoje (sistema operacional)
            const hoje = new Date();
            const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0'); // Garante formato "06"
            const anoAtual = String(hoje.getFullYear()); // Ex: "2026"

            // Se o mês ou ano do CSV forem diferentes do mês/ano que estamos vivendo
            if (mesCSV !== mesAtual || anoCSV !== anoAtual) {
                // Exibe o modal amarelo de aviso
                document.getElementById('modalErroMes').style.display = 'flex';
                e.target.value = ''; // Limpa o arquivo no input
                return; // 🛑 Trava o código aqui! Não manda pro PHP.
            }
        }
        
        try {
            const response = await fetch('../api/importar_metas_csv.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dataToImport) });
            const res = await response.json();
            
            if (res.success) { 
                alert('Planilha importada com sucesso!'); 
                carregarDashboard(); 
            } else { 
                alert('Erro ao importar: ' + res.error); 
            }
        } catch (err) { 
            alert('Erro crítico na conexão de importação. Verifique se o backend respondeu um erro.'); 
            console.error(err);
        } finally {
            e.target.value = ''; // Sempre limpa o input no final para permitir nova importação
        }
    };
    reader.readAsText(file);
});

// Abre o modal preenchendo o texto
function enviarWhatsApp() {
    const d = window.dadosGlobais;
    if(!d || !d.meta_total) return alert("Os dados ainda não foram carregados completamente.");
    
    const statusMetaTexto = d.gap > 0 ? `📉 *GAP para meta:* ${formatarBRL(d.gap)}` : `📈 *Acima da Meta:* ${formatarBRL(Math.abs(d.gap))}`;
    
    // Define o rótulo mostrando a data exata se ela existir
    const rotuloUltimoDia = d.data_ultimo_dia ? `Último Dia (${d.data_ultimo_dia})` : `Último Dia`;

    const texto = `📅 *Meta do Mês:* ${formatarBRL(d.meta_total)}
🗓️ *Meta acumulada:* ${formatarBRL(d.meta_acumulada)}
💰 *Venda acumulada:* ${formatarBRL(d.venda_acumulada)}
🎯 *Atingimento:* ${typeof d.atingimento === 'number' ? d.atingimento.toFixed(1) : 0}%

${statusMetaTexto}

⏪ *Meta - ${rotuloUltimoDia}:* ${formatarBRL(d.meta_ontem)}
✅ *Venda - ${rotuloUltimoDia}:* ${formatarBRL(d.venda_ontem)}

💪🏼 *Meta de Hoje:* ${formatarBRL(d.meta_hoje)}
🎯 *Meta Ajustada:* ${formatarBRL(d.meta_ajustada)}`;

    const modal = document.getElementById('modalCopiar');
    const textArea = document.getElementById('textoCopia');
    if (modal && textArea) { 
        textArea.value = texto; 
        modal.style.display = 'flex'; 
    } 
}

// Ação do Botão 1: Pega o texto do textarea e abre a URL do WhatsApp
function enviarDiretoWhatsAppModal() {
    const texto = document.getElementById("textoCopia").value;
    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(texto)}`;
    window.open(url, '_blank');
}

// Ação do Botão 2: Copia limpando espaços extras que bugam o WhatsApp Web
async function copiarTexto() {
    let texto = document.getElementById("textoCopia").value;
    // Normaliza as quebras de linha para o padrão mais simples e remove espaços inúteis
    texto = texto.replace(/\r?\n/g, '\n').trim(); 
    
    try {
        await navigator.clipboard.writeText(texto);
        alert("Mensagem copiada! Se colar no WhatsApp Web e o botão de enviar sumir, dê um ESPAÇO no final da mensagem.");
        document.getElementById('modalCopiar').style.display = 'none';
    } catch (err) {
        const copyText = document.getElementById("textoCopia");
        copyText.select();
        document.execCommand("copy");
        alert("Mensagem copiada!");
    }
}

carregarDashboard();