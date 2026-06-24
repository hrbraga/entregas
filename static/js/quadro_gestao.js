let dadosGlobais = {};

function formatarBRL(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

async function carregarDashboard() {
    try {
        // Ajuste o caminho se sua rota da api for diferente
        const response = await fetch('../api/get_quadro_gestao.php');
        const dados = await response.json();
        
        if(dados.error) {
            console.error(dados.error);
            return;
        }
        
        dadosGlobais = dados;

        // Verifica pendências de venda_dia
        if (dados.dias_pendentes && dados.dias_pendentes.length > 0) {
            document.getElementById('modalPendencias').style.display = 'flex';
            document.getElementById('dashboardContent').classList.add('dashboard-blur');
            
            let inputsHTML = '';
            dados.dias_pendentes.forEach(dia => {
                let dataFormatada = dia.split('-').reverse().slice(0,2).join('/');
                inputsHTML += `
                    <div style="display:flex; justify-content:space-between; width:100%; align-items: center;">
                        <label style="font-weight: 600;">Dia ${dataFormatada}:</label>
                        <input type="number" step="0.01" name="venda[${dia}]" required style="width:140px; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                    </div>`;
            });
            document.getElementById('listaPendencias').innerHTML = inputsHTML;
        } else {
            document.getElementById('modalPendencias').style.display = 'none';
            document.getElementById('dashboardContent').classList.remove('dashboard-blur');
            renderizarCards(dados);
            renderizarGrafico(dados);
        }
    } catch(err) {
        console.error("Erro ao carregar dashboard:", err);
    }
}

function renderizarCards(dados) {
    document.getElementById('c-meta-total').innerText = formatarBRL(dados.meta_total);
    document.getElementById('c-meta-acumulada').innerText = formatarBRL(dados.meta_acumulada);
    document.getElementById('c-venda-acumulada').innerText = formatarBRL(dados.venda_acumulada);
    document.getElementById('c-atingimento').innerText = (dados.atingimento || 0).toFixed(1) + '%';
    document.getElementById('c-gap').innerText = formatarBRL(dados.gap > 0 ? dados.gap : 0);
    document.getElementById('c-meta-ontem').innerText = formatarBRL(dados.meta_ontem);
    document.getElementById('c-venda-ontem').innerText = formatarBRL(dados.venda_ontem);
    document.getElementById('c-meta-hoje').innerText = formatarBRL(dados.meta_hoje);
    document.getElementById('c-meta-ajustada').innerText = formatarBRL(dados.meta_ajustada);
}

let chartInstancia = null;
function renderizarGrafico(dados) {
    const ctx = document.getElementById('graficoEvolucao').getContext('2d');
    
    // Destrói o gráfico anterior caso já exista
    if(chartInstancia) chartInstancia.destroy();
    
    chartInstancia = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dados.grafico_datas,
            datasets: [
                {
                    label: 'Meta Acumulada',
                    data: dados.grafico_metas,
                    borderColor: '#9ca3af',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3
                },
                {
                    label: 'Venda Acumulada',
                    data: dados.grafico_vendas,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

// ----------------------------------------------------
// A MÁGICA DA IMPORTAÇÃO ACONTECE AQUI
// ----------------------------------------------------
document.getElementById('importar_metas').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Confirmação para evitar clique acidental
    if(!confirm("Deseja importar os dados desta planilha? Isso atualizará as metas do mês.")) return;

    const reader = new FileReader();
    reader.onload = async function(event) {
        const text = event.target.result;
        const lines = text.split('\n');
        const dataToImport = [];

        // Começamos do 1 para pular o cabeçalho "Data;Meta Diaria"
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            const parts = line.split(';');
            if (parts.length >= 2) {
                dataToImport.push({
                    data: parts[0].trim(), // "01/06/2026"
                    meta: parts[1].trim()  // "2433,79"
                });
            }
        }

        if (dataToImport.length > 0) {
            try {
                // Envia os dados para o servidor
                const response = await fetch('../api/get_quadro_gestao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dataToImport)
                });
                
                const resData = await response.json();
                
                if (resData.success) {
                    alert('Planilha importada com sucesso!');
                    document.getElementById('importar_metas').value = ""; // Limpa o input
                    carregarDashboard(); // Recarrega os dados fresquinhos na tela!
                } else {
                    alert('Erro ao importar: ' + resData.error);
                }
            } catch (err) {
                alert('Ocorreu um erro de conexão com o servidor.');
            }
        } else {
            alert('A planilha parece estar vazia ou fora do formato esperado.');
        }
    };
    reader.readAsText(file);
});

function enviarWhatsApp() {
    const d = dadosGlobais;
    if(!d || !d.meta_total) return;
    
    const texto = `📅 Meta do Mês: ${formatarBRL(d.meta_total)}
🗓️ Meta de até ontem: ${formatarBRL(d.meta_acumulada)}
💰 Venda do mês: ${formatarBRL(d.venda_acumulada)}
🎯 Atingimento: ${d.atingimento.toFixed(1)}%

GAP para meta: ${formatarBRL(d.gap > 0 ? d.gap : 0)}

🗓️ Meta de Ontem: ${formatarBRL(d.meta_ontem)}
💹 Venda Ontem: ${formatarBRL(d.venda_ontem)}

💪🏼 Meta de Hoje: ${formatarBRL(d.meta_hoje)}
💪🏼 Meta de Hoje Ajustada: ${formatarBRL(d.meta_ajustada)}`;

    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(texto)}`;
    window.open(url, '_blank');
}

carregarDashboard();