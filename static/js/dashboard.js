document.addEventListener('DOMContentLoaded', async () => {

    /**
     * Função para obter dados do back-end para os gráficos.
     * Adiciona timestamp para evitar cache.
     */
    async function getDashboardData() {
        try {
            const response = await fetch('../api/get_dashboard_data.php?t=' + new Date().getTime());
            
            if (response.status === 401) { 
                window.location.href = '../auth/login.php'; 
                return null;
            }
            if (!response.ok) {
                const errorText = await response.text(); 
                console.error("Erro do servidor (get_dashboard_data.php):", errorText);
                throw new Error('Erro ao buscar dados do dashboard.');
            }
            return await response.json();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
            return null;
        }
    }

    /**
     * Renderiza os cartões de totais (Pedido, Recebido, A Receber)
     */
    function renderTotalizers(data) {
        const totalPedido = parseInt(data.total_pedido, 10) || 0;
        const totalRecebido = parseInt(data.total_recebido, 10) || 0;
        const totalAReceber = totalPedido - totalRecebido;

        // Atualiza valores numéricos
        document.getElementById('total-pedido-val').textContent = totalPedido.toFixed(0);
        document.getElementById('recebido-val').textContent = totalRecebido.toFixed(0);
        document.getElementById('a-receber-val').textContent = totalAReceber.toFixed(0);

        // Calcula porcentagens
        let pctRecebido = 0;
        let pctAReceber = 0;

        if (totalPedido > 0) {
            pctRecebido = (totalRecebido / totalPedido) * 100;
            pctAReceber = (totalAReceber / totalPedido) * 100;
        }

        // Atualiza textos de porcentagem
        const elRecebidoPct = document.getElementById('recebido-pct');
        const elAReceberPct = document.getElementById('a-receber-pct');

        if (elRecebidoPct) {
            elRecebidoPct.textContent = pctRecebido.toFixed(1).replace('.', ',') + '%';
        }
        
        if (elAReceberPct) {
            elAReceberPct.textContent = pctAReceber.toFixed(1).replace('.', ',') + '%';
        }
    }

    /**
     * Gráfico Rosca: Progresso Geral
     */
    function renderProgressChart(data) {
        const ctx = document.getElementById('progress-chart').getContext('2d');
        const percentage = parseFloat(data.progresso_geral) || 0;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Entregue', 'A Entregar'],
                datasets: [{
                    data: [percentage, 100 - percentage],
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderColor: ['#28a745', '#e9ecef'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { enabled: false }
                }
            }
        });
    }

    /**
     * Gráfico Pizza: Status por SKU
     */
    function renderSkuStatusChart(data) {
        const ctx = document.getElementById('sku-status-chart').getContext('2d');
        const skuStatus = data.sku_status;

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Não Entregues', 'Parcialmente Entregues', 'Totalmente Entregues'],
                datasets: [{
                    data: [skuStatus.nao_entregues, skuStatus.parcialmente_entregues, skuStatus.totalmente_entregues],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } }
            }
        });
    }

    /**
     * Gráfico Barras: Status por Grupo
     */
    function renderGroupStatusChart(data) {
        const ctx = document.getElementById('group-status-chart').getContext('2d');
        const grupos = data.grupos;

        const labels = Object.keys(grupos);
        const naoEntregues = labels.map(label => grupos[label].nao_entregues);
        const parcialmenteEntregues = labels.map(label => grupos[label].parcialmente_entregues);
        const totalmenteEntregues = labels.map(label => grupos[label].totalmente_entregues);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Não Entregues', data: naoEntregues, backgroundColor: '#dc3545' },
                    { label: 'Parcialmente Entregues', data: parcialmenteEntregues, backgroundColor: '#ffc107' },
                    { label: 'Totalmente Entregues', data: totalmenteEntregues, backgroundColor: '#28a745' }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true }
                }
            }
        });
    }

    /**
     * Tabela: Top 5 Pendências (Layout Fixo e Travado)
     */
    function renderTopPendencias(data) {
        const tbody = document.querySelector('#top-pendencias-table tbody');
        
        // AJUSTE CRUCIAL: Define a largura das colunas via JS para garantir o layout fixo
        // Isso impede que a tabela cresça além do container
        const thead = document.querySelector('#top-pendencias-table thead tr');
        if (thead && thead.cells.length === 2) {
            thead.cells[0].style.width = '75%'; // Coluna Item (ocupa a maior parte)
            thead.cells[1].style.width = '25%'; // Coluna Falta (ocupa o final)
        }

        if (!tbody) return;
        
        tbody.innerHTML = '';
        const lista = data.top_pendencias || [];

        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:10px;">Nenhuma pendência crítica! 🎉</td></tr>';
            return;
        }

        lista.forEach(item => {
            const tr = document.createElement('tr');
            
            // O atributo 'title' permite ler o nome completo ao passar o mouse, caso seja cortado
            tr.innerHTML = `
                <td title="${item.codigo_sap} - ${item.item}" style="text-align: left; padding: 8px; font-size: 0.9rem; border-bottom: 1px solid #eee;">
                    <strong>${item.codigo_sap}</strong> - ${item.item}
                </td>
                <td style="color: #dc3545; font-weight: bold; padding: 8px; text-align: center; border-bottom: 1px solid #eee;">
                    ${item.falta}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // ===============================================
    // INICIALIZAÇÃO
    // ===============================================
    const dashboardData = await getDashboardData();
    if (dashboardData) {
        renderTotalizers(dashboardData);
        renderProgressChart(dashboardData);
        renderSkuStatusChart(dashboardData);
        renderTopPendencias(dashboardData); // Chama a nova função
        renderGroupStatusChart(dashboardData);
    }
});