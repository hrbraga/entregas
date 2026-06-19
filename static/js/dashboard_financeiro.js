document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Gráfico de Barras: Receitas vs Despesas
    const ctxBarras = document.getElementById('graficoEvolucao');
    if (ctxBarras && typeof dadosEvolucao !== 'undefined') {
        new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: dadosEvolucao.meses,
                datasets: [
                    {
                        label: 'Receitas (R$)',
                        data: dadosEvolucao.receitas,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Despesas (R$)',
                        data: dadosEvolucao.despesas,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } }
            }
        });
    }

    // 2. Gráfico de Rosca: Para Onde Vai o Dinheiro? (Agora com 12 cores)
    const ctxRosca = document.getElementById('graficoDespesas');
    if (ctxRosca && typeof dadosRosca !== 'undefined') {
        new Chart(ctxRosca, {
            type: 'doughnut',
            data: {
                labels: dadosRosca.categorias,
                datasets: [{
                    data: dadosRosca.valores,
                    backgroundColor: [
                        '#dc3545', '#ffc107', '#17a2b8', '#6f42c1', 
                        '#fd7e14', '#6c757d', '#e83e8c', '#20c997', 
                        '#007bff', '#28a745', '#6610f2', '#343a40'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    // 3. Gráfico de Linha: Radar de Fluxo de Caixa (30 dias, plotado a cada 2)
    const ctxRadar = document.getElementById('graficoRadar');
    if (ctxRadar && typeof dadosRadar !== 'undefined') {
        new Chart(ctxRadar, {
            type: 'line',
            data: {
                labels: dadosRadar.dias,
                datasets: [{
                    label: 'Saldo Previsto (R$)',
                    data: dadosRadar.saldos,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: dadosRadar.saldos.map(s => s < 0 ? '#dc3545' : '#007bff'),
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
});

// Função para fechar o Modal do Robô
function fecharModalRobo() {
    const modal = document.getElementById('modalRobo');
    if (modal) {
        modal.style.display = 'none';
    }
}