document.addEventListener('DOMContentLoaded', () => {

    /**
     * Esta função preenche a tabela principal com os dados dos itens e botões de ação.
     * @param {Array} data - Os dados para a tabela.
     */
    function renderTable(data) {
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = '';

        data.forEach(item => {
            const row = document.createElement('tr');
            
            if (item.recebido === item.total_caixa) {
                row.classList.add('fully-received');
            } else if (item.recebido > item.total_caixa) {
                row.classList.add('over-received');
            }

            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.grupo}</td>
                <td class="editable-cell" data-id="${item.id}" data-field="pedido_loja" data-old-value="${item.pedido_loja}">${item.pedido_loja}</td>
                <td class="editable-cell" data-id="${item.id}" data-field="pedido_vd" data-old-value="${item.pedido_vd}">${item.pedido_vd}</td>
                <td>${item.total_caixa}</td>
                <td>${item.a_receber}</td>
                <td>${item.recebido}</td>
                <td>
                    <button class="action-btn edit-btn" data-id="${item.id}" title="Editar">✏️</button>
                    <button class="action-btn delete-btn" data-id="${item.id}" title="Excluir">🗑️</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Event listeners para os botões de edição
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const editableCells = row.querySelectorAll('.editable-cell');
                const editBtn = e.target;
                
                editBtn.style.display = 'none';
                row.querySelector('.delete-btn').style.display = 'none';
                
                const saveBtn = document.createElement('button');
                const cancelBtn = document.createElement('button');
                
                // Novo código para os botões de Salvar e Cancelar
                saveBtn.innerHTML = '✔️';
                saveBtn.className = 'action-btn save-btn';
                saveBtn.title = 'Confirmar';

                cancelBtn.innerHTML = '❌';
                cancelBtn.className = 'action-btn cancel-btn';
                cancelBtn.title = 'Cancelar';
                
                editBtn.parentNode.appendChild(saveBtn);
                editBtn.parentNode.appendChild(cancelBtn);

                editableCells.forEach(cell => {
                    const oldValue = cell.dataset.oldValue;
                    cell.innerHTML = `<input type="number" value="${oldValue}" min="0">`;
                });
                
                // Lógica do botão 'Salvar'
                saveBtn.addEventListener('click', async () => {
                    const id = row.querySelector('.editable-cell').dataset.id;
                    const updatedData = {};
                    let hasChanged = false;

                    row.querySelectorAll('input').forEach(input => {
                        const cell = input.closest('td');
                        const field = cell.dataset.field;
                        const oldValue = cell.dataset.oldValue;
                        const newValue = input.value;
                        
                        updatedData[field] = parseInt(newValue);
                        if (oldValue !== newValue) {
                            hasChanged = true;
                        }
                    });

                    if (hasChanged) {
                        try {
                            const response = await fetch(`/update_item/${id}`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(updatedData)
                            });
                            const result = await response.json();
                            if (result.success) {
                                showFeedback(result.message, 'success');
                                loadDataAndRenderTables();
                            } else {
                                showFeedback('Erro: ' + result.message, 'error');
                            }
                        } catch (error) {
                            showFeedback('Ocorreu um erro ao editar o item.', 'error');
                        }
                    } else {
                        loadDataAndRenderTables(); // Recarrega se não houver mudanças
                    }
                });

                // Lógica do botão 'Cancelar'
                cancelBtn.addEventListener('click', () => {
                    loadDataAndRenderTables(); // Recarrega a tabela para descartar alterações
                });
            });
        });

        // Event listeners para os botões de exclusão
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const id = e.target.dataset.id;
                const confirmDelete = confirm("Tem certeza que deseja excluir este item?");

                if (confirmDelete) {
                    try {
                        const response = await fetch(`/delete_item/${id}`, { method: 'DELETE' });
                        const result = await response.json();
                        if (result.success) {
                            showFeedback(result.message, 'success');
                            loadDataAndRenderTables();
                        } else {
                            showFeedback('Erro: ' + result.message, 'error');
                        }
                    } catch (error) {
                        showFeedback('Ocorreu um erro ao excluir o item.', 'error');
                    }
                }
            });
        });
    }

    /**
     * Esta função calcula o resumo de grupos a partir dos dados da tabela principal.
     * @param {Array} data - Os dados completos dos itens.
     * @returns {Object} Um objeto com o resumo de cada grupo.
     */
    function calculateGroupSummary(data) {
        const summary = {};
        data.forEach(item => {
            if (!summary[item.grupo]) {
                summary[item.grupo] = { pedido: 0, a_receber: 0, entregue: 0 };
            }
            summary[item.grupo].pedido += item.total_caixa;
            summary[item.grupo].a_receber += item.a_receber;
            summary[item.grupo].entregue += item.recebido;
        });
        return summary;
    }

    /**
     * Esta função preenche a tabela de resumo por grupo.
     * @param {Object} data - Os dados de resumo por grupo.
     */
    function renderGroupSummary(data) {
        const tbody = document.getElementById('group-summary-body');
        tbody.innerHTML = '';
        for (const grupo in data) {
            const item = data[grupo];
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${grupo}</td>
                <td>${item.pedido}</td>
                <td>${item.a_receber}</td>
                <td>${item.entregue}</td>
            `;
            tbody.appendChild(row);
        }
    }

    /**
     * Função assíncrona para buscar os dados do servidor e renderizar as tabelas.
     */
    async function loadDataAndRenderTables() {
        try {
            const response = await fetch('/get_data');
            const data = await response.json();
            renderTable(data);
            const groupSummary = calculateGroupSummary(data);
            renderGroupSummary(groupSummary);
        } catch (error) {
            showFeedback("Erro ao carregar os dados. Por favor, tente novamente.", "error");
        }
    }

    // Funções para gerenciar o feedback na tela
    function showFeedback(message, type) {
        const feedbackDiv = document.getElementById('feedback-message');
        feedbackDiv.textContent = message;
        feedbackDiv.className = `feedback-message ${type}`;
        feedbackDiv.style.display = 'block';
        setTimeout(() => {
            feedbackDiv.style.display = 'none';
        }, 5000);
    }

    function showLoading() {
        document.getElementById('loading-spinner').style.display = 'block';
    }

    function hideLoading() {
        document.getElementById('loading-spinner').style.display = 'none';
    }

    loadDataAndRenderTables();

    // Lógica para o botão de upload do XML
    const importBtn = document.getElementById('import-xml-btn');
    const fileInput = document.getElementById('xml-file-input');

    importBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async (event) => {
        const file = event.target.files[0];
        if (file) {
            if (file.type !== 'text/xml' && !file.name.endsWith('.xml')) {
                showFeedback('Por favor, selecione um arquivo no formato XML.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            showLoading();

            try {
                const response = await fetch('/upload_xml', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showFeedback(result.message, 'success');
                    loadDataAndRenderTables();
                } else {
                    showFeedback('Erro: ' + result.message, 'error');
                }
            } catch (error) {
                showFeedback('Ocorreu um erro ao enviar o arquivo.', 'error');
            } finally {
                hideLoading();
            }
        }
    });
});