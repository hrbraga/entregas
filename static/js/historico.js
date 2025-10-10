document.addEventListener('DOMContentLoaded', () => {

    /**
     * Função assíncrona para buscar os dados de notas fiscais do servidor
     * e renderizar a tabela.
     */
    async function loadNotasAndRenderTable() {
        try {
            const response = await fetch('/get_notas');
            if (!response.ok) {
                throw new Error('Erro ao buscar os dados de notas fiscais.');
            }
            const notas = await response.json();
            
            renderNotasTable(notas);
            
        } catch (error) {
            console.error("Erro:", error);
            alert("Erro ao carregar os dados de notas fiscais. Por favor, tente novamente.");
        }
    }

    /**
     * Função para preencher a tabela de histórico com os dados das notas fiscais.
     * @param {Array} notas - Os dados das notas fiscais para a tabela.
     */
    function renderNotasTable(notas) {
        const tbody = document.getElementById('notas-fiscais-body');
        tbody.innerHTML = ''; // Limpa a tabela antes de preencher

        if (notas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">Nenhuma nota fiscal importada ainda.</td></tr>';
            return;
        }

        notas.forEach(nota => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${nota.numero_nota}</td>
                <td>R$ ${parseFloat(nota.valor_total).toFixed(2).replace('.', ',')}</td>
                <td>${nota.data_emissao}</td>
                <td>${nota.data_importacao}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Chama a função para carregar os dados quando a página é carregada
    loadNotasAndRenderTable();
});