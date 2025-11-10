document.addEventListener('DOMContentLoaded', () => {

    // Função para mostrar feedback na tela (similar à de script.js)
    function showFeedback(message, type) {
        const feedbackDiv = document.getElementById('feedback-message');
        if (!feedbackDiv) return;
        feedbackDiv.textContent = message;
        feedbackDiv.className = `feedback-message ${type}`;
        feedbackDiv.style.display = 'block';
        setTimeout(() => {
            feedbackDiv.style.display = 'none';
        }, 5000);
    }

    /**
     * Função assíncrona para buscar os dados de notas fiscais do servidor
     * e renderizar a tabela.
     */
    async function loadNotasAndRenderTable() {
        try {
            // MUDANÇA AQUI: Adiciona cache-busting
            const response = await fetch('api/get_notas.php?t=' + new Date().getTime());
            
            if (response.status === 401) { // 401 = Não Autorizado
                window.location.href = 'login.php'; // Redireciona para o login
                return;
            }
            if (!response.ok) {
                throw new Error('Erro ao buscar os dados de notas fiscais.');
            }
            const notas = await response.json();
            
            renderNotasTable(notas);
            attachDeleteListeners();
            
        } catch (error) {
            console.error("Erro:", error);
            showFeedback("Erro ao carregar os dados de notas fiscais. Por favor, tente novamente.", "error");
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
            tbody.innerHTML = '<tr><td colspan="5">Nenhuma nota fiscal importada ainda.</td></tr>';
            return;
        }

        notas.forEach(nota => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${nota.numero_nota}</td>
                <td>R$ ${parseFloat(nota.valor_total).toFixed(2).replace('.', ',')}</td>
                <td>${nota.data_emissao}</td>
                <td>${nota.data_importacao}</td>
                <td><button class="delete-nota-btn action-btn" data-numero-nota="${nota.numero_nota}" title="Excluir Nota Fiscal">🗑️</button></td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Função para anexar os event listeners aos botões de exclusão de notas fiscais.
     */
    function attachDeleteListeners() {
        document.querySelectorAll('.delete-nota-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const numeroNota = e.target.dataset.numeroNota;
                
                const confirmMessage = `Tem certeza que deseja excluir a Nota Fiscal ${numeroNota} do histórico? \n\nOs itens de entrega (Recebido/A Receber) serão ajustados automaticamente.`;
                
                if (confirm(confirmMessage)) {
                    try {
                        // MUDANÇA AQUI: Aponta para a API PHP (passando o número da nota pela URL)
                        const response = await fetch(`api/delete_nota.php?numero_nota=${numeroNota}`, { method: 'DELETE' });
                        const result = await response.json();
                        
                        if (result.success) {
                            showFeedback(result.message, "success");
                            loadNotasAndRenderTable(); // Recarrega a tabela
                        } else {
                            showFeedback('Erro: ' + result.message, "error");
                        }
                    } catch (error) {
                        showFeedback('Ocorreu um erro ao excluir a nota fiscal.', "error");
                    }
                }
            });
        });
    }

    // Chama a função para carregar os dados quando a página é carregada
    loadNotasAndRenderTable();
});