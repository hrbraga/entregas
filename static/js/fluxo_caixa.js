document.addEventListener('DOMContentLoaded', function () {
    // 1. Inicializa o seletor múltiplo (TomSelect)
    const elContas = document.getElementById('contas_selecionadas');
    if (elContas && !elContas.tomselect) {
        new TomSelect(elContas, {
            plugins: ['remove_button'],
            hideSelected: true,
            placeholder: "Selecione uma ou mais contas...",
        });
    }

    // 2. Renderiza o Gráfico de Barras Sintético
    const ctx = document.getElementById('graficoFluxo');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: typeof graficoLabels !== 'undefined' ? graficoLabels : [],
                datasets: [
                    {
                        label: 'Receitas',
                        data: typeof graficoReceitas !== 'undefined' ? graficoReceitas : [],
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: '#28a745',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Despesas',
                        data: typeof graficoDespesas !== 'undefined' ? graficoDespesas : [],
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor: '#dc3545',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }
});

// 3. Função Efeito Sanfona (Expansor de Categorias)
function toggleExpander(groupId) {
    const rows = document.querySelectorAll('.child-of-' + groupId);
    const icon = document.getElementById('icon_' + groupId);
    
    let isHidden = true;
    if (rows.length > 0) {
        isHidden = rows[0].style.display === 'none';
    }

    // Abre ou fecha as linhas filhas
    rows.forEach(row => {
        row.style.display = isHidden ? 'table-row' : 'none';
    });

    // Roda a setinha
    if (icon) {
        icon.innerHTML = isHidden ? '▼' : '▶';
        icon.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
    }
}