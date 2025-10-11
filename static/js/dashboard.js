document.addEventListener('DOMContentLoaded', async () => {

    /**
     * Função para obter dados do back-end para os gráficos.
     */
    async function getDashboardData() {
        try {
            const response = await fetch('/get_dashboard_data');
            if (!response.ok) {
                throw new Error('Erro ao buscar dados do dashboard.');
            }
            return await response.json();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
            return null;
        }
    }

    /**
     * Função para renderizar os totalizadores.
     */
    function renderTotalizers(data) {
        const totalPedido = data.total_pedido;
        const totalRecebido = data.total_recebido;
        const totalAReceber = totalPedido - totalRecebido;

        document.getElementById('total-pedido-val').textContent = totalPedido.toFixed(0);
        document.getElementById('recebido-val').textContent = totalRecebido.toFixed(0);
        document.getElementById('a-receber-val').textContent = totalAReceber.toFixed(0);
    }

    /**
     * Função para renderizar o gráfico de progresso geral.
     */
    function renderProgressChart(data) {
        const ctx = document.getElementById('progress-chart').getContext('2d');
        const percentage = data.progresso_geral;

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
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        enabled: false,
                    }
                }
            }
        });
    }

    /**
     * Função para renderizar o gráfico de pizza de status de SKU.
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
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Função para renderizar o gráfico de barras de status por grupo.
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
                    {
                        label: 'Não Entregues',
                        data: naoEntregues,
                        backgroundColor: '#dc3545',
                    },
                    {
                        label: 'Parcialmente Entregues',
                        data: parcialmenteEntregues,
                        backgroundColor: '#ffc107',
                    },
                    {
                        label: 'Totalmente Entregues',
                        data: totalmenteEntregues,
                        backgroundColor: '#28a745',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                    }
                }
            }
        });
    }

    // Carrega e renderiza os gráficos e totalizadores quando a página é carregada
    const dashboardData = await getDashboardData();
    if (dashboardData) {
        renderTotalizers(dashboardData); // Nova função para preencher os totalizadores
        renderProgressChart(dashboardData);
        renderSkuStatusChart(dashboardData);
        renderGroupStatusChart(dashboardData);
    }
});

// Incluir as bibliotecas para PDF
const script = document.createElement('script');
script.src = "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js";
document.head.appendChild(script);

document.getElementById('print-dashboard-btn').addEventListener('click', () => {
      const printDashboardBtn = document.getElementById('print-dashboard-btn');
    if (printDashboardBtn) {
        printDashboardBtn.addEventListener('click', () => {
            // Adiciona um pequeno atraso para garantir que os gráficos sejam renderizados
            setTimeout(() => {
                const dashboard = document.querySelector('.dashboard-container');
                if (dashboard) {
                    html2canvas(dashboard, { scale: 2 }).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF('l', 'mm', 'a4'); // 'l' para paisagem
                        const imgProps= pdf.getImageProperties(imgData);
                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                        pdf.save('dashboard-entregas.pdf');
                    });
                }
            }, 500); // Atraso de 500ms (meio segundo)
        });
    }
});