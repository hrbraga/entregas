document.addEventListener('DOMContentLoaded', () => {

    // Cache para armazenar o último timer de pesquisa (usado para o filtro dinâmico)
    let searchTimeout = null;
    
    // Elementos de pesquisa
    const searchInput = document.getElementById('product-search');
    const filteredResultsContainer = document.getElementById('filtered-results-container');
    const filteredResultsBody = document.getElementById('filtered-results-body');
    const clearSearchButton = document.getElementById('clear-search-btn');

    /**
     * Esta função preenche as DUAS tabelas principais (A Receber e Recebidos).
     * @param {Array} data - Os dados completos de ItemEntrega.
     */
    function renderTable(data) {
        const tbodyToReceive = document.getElementById('table-body-to-receive');
        const tbodyReceived = document.getElementById('table-body-received');
        
        // Limpa ambas as tabelas
        tbodyToReceive.innerHTML = '';
        tbodyReceived.innerHTML = '';

        data.forEach(item => {
            const isFullyReceived = item.recebido >= item.total_caixa;
            const targetTbody = isFullyReceived ? tbodyReceived : tbodyToReceive;
            
            const row = document.createElement('tr');
            
            // Lógica de cores
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
            
            targetTbody.appendChild(row);
        });

        attachTableListeners();
    }

    /**
     * Função para anexar os event listeners (edição e exclusão).
     */
    function attachTableListeners() {
        
        // 1. Lógica de Edição
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const editableCells = row.querySelectorAll('.editable-cell');
                const editBtn = e.target;
                
                editBtn.style.display = 'none';
                row.querySelector('.delete-btn').style.display = 'none';
                
                const saveBtn = document.createElement('button');
                const cancelBtn = document.createElement('button');
                
                saveBtn.innerHTML = '✔️';
                saveBtn.className = 'action-btn save-btn';
                saveBtn.title = 'Confirmar';

                cancelBtn.innerHTML = '❌';
                cancelBtn.className = 'action-btn cancel-btn';
                cancelBtn.title = 'Cancelar';
                
                editBtn.parentNode.appendChild(saveBtn);
                editBtn.parentNode.appendChild(cancelBtn);

                editableCells.forEach(cell => {
                    const oldValue = cell.textContent;
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
                        loadDataAndRenderTables();
                    }
                });

                // Lógica do botão 'Cancelar'
                cancelBtn.addEventListener('click', () => {
                    loadDataAndRenderTables();
                });
            });
        });

        // 2. Lógica de Exclusão
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
        // Assegura que as tabelas principais e o resumo estejam visíveis
        const tableSections = document.querySelector('.delivery-tables-container');
        const summarySection = document.querySelector('.group-summary-section');
        
        if (tableSections) tableSections.style.display = 'grid';
        if (summarySection) summarySection.style.display = 'block';
        
        // Esconde os resultados da busca e o botão limpar
        if (filteredResultsContainer) filteredResultsContainer.style.display = 'none';
        if (clearSearchButton) clearSearchButton.style.display = 'none';
        if (searchInput) searchInput.value = ''; // Limpa o campo de busca

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

    // ===============================================
    // LÓGICA DE BUSCA INSTANTÂNEA (FILTRO DINÂMICO)
    // ===============================================
    
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim();

            if (query.length < 3) {
                // Se a busca for esvaziada ou curta, esconde tudo e o botão limpar
                filteredResultsContainer.style.display = 'none';
                clearSearchButton.style.display = 'none';
                return;
            }

            // Se a busca for válida, mostra o botão limpar
            clearSearchButton.style.display = 'inline-block';

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/search_items?q=${query}`);
                    const results = await response.json();
                    
                    renderFilteredResults(results);
                } catch (error) {
                    console.error("Erro na busca:", error);
                    filteredResultsContainer.style.display = 'none';
                }
            }, 300); // Atraso de 300ms
        });
        
        // Listener para o botão de limpar pesquisa
        if (clearSearchButton) {
            clearSearchButton.addEventListener('click', () => {
                searchInput.value = ''; // Limpa o input
                filteredResultsContainer.style.display = 'none'; // Esconde a tabela de resultados
                clearSearchButton.style.display = 'none'; // Esconde o próprio botão
                searchInput.focus(); // Coloca o foco de volta no input
            });
        }
    }

    /**
     * Renderiza os resultados da busca na mini-tabela.
     * @param {Array} results - Lista de itens encontrados.
     */
    function renderFilteredResults(results) {
        filteredResultsBody.innerHTML = '';
        
        if (results.length === 0) {
            filteredResultsBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum item encontrado.</td></tr>';
            filteredResultsContainer.style.display = 'block';
            return;
        }

        results.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'search-result-row'; // Adiciona uma classe para destaque
            row.dataset.id = item.id;
            
            // Adiciona a formatação pedida
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.pedido_total}</td>
                <td style="color: green;">${item.recebido}</td>
                <td style="color: red;">${item.a_receber}</td>
            `;
            
            // Adiciona a funcionalidade de clique para rolar e destacar
            row.addEventListener('click', () => {
                const targetRow = document.querySelector(`tr[data-id="${item.id}"]`);
                if (targetRow) {
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetRow.classList.add('highlight-row');
                    setTimeout(() => targetRow.classList.remove('highlight-row'), 3000);
                }
            });

            filteredResultsBody.appendChild(row);
        });

        filteredResultsContainer.style.display = 'block';
    }
    
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
    
    // Lógica para o botão de imprimir listagem
    const printListBtn = document.getElementById('print-list-btn');
    if (printListBtn) {
        printListBtn.addEventListener('click', () => {
            const table = document.querySelector('.delivery-tables-container table');
            if (table) {
                html2canvas(table).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgProps= pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save('listagem-entregas.pdf');
                });
            }
        });
    }
});