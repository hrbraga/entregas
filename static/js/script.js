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
        
        tbodyToReceive.innerHTML = '';
        tbodyReceived.innerHTML = '';

        data.forEach(item => {
            // No PHP, os valores podem vir como strings, então garantimos que são números
            const recebido = parseInt(item.recebido, 10) || 0;
            const total_caixa = parseInt(item.total_caixa, 10) || 0;

            const isFullyReceived = recebido >= total_caixa;
            const targetTbody = isFullyReceived ? tbodyReceived : tbodyToReceive;
            
            const row = document.createElement('tr');
            
            if (recebido === total_caixa) {
                row.classList.add('fully-received');
            } else if (recebido > total_caixa) {
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
                    
                    // MODIFICAÇÃO: Adicionamos o ID ao JSON enviado
                    const updatedData = { id: parseInt(id, 10) };
                    let hasChanged = false;

                    row.querySelectorAll('input').forEach(input => {
                        const cell = input.closest('td');
                        const field = cell.dataset.field;
                        const oldValue = cell.dataset.oldValue;
                        const newValue = input.value;
                        
                        updatedData[field] = parseInt(newValue, 10);
                        if (oldValue !== newValue) {
                            hasChanged = true;
                        }
                    });

                    if (hasChanged) {
                        try {
                            // MUDANÇA AQUI: Aponta para a API PHP
                            const response = await fetch(`api/update_item.php`, {
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
                        loadDataAndRenderTables(); // Recarrega mesmo se não houver mudança (para restaurar a linha)
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
                        // MUDANÇA AQUI: Aponta para a API PHP (passando ID pela URL)
                        const response = await fetch(`api/delete_item.php?id=${id}`, { method: 'DELETE' });
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
            // Garante que os valores são numéricos
            summary[item.grupo].pedido += parseInt(item.total_caixa, 10) || 0;
            summary[item.grupo].a_receber += parseInt(item.a_receber, 10) || 0;
            summary[item.grupo].entregue += parseInt(item.recebido, 10) || 0;
        });
        return summary;
    }

    /**
     * Esta função preenche a tabela de resumo por grupo.
     */
    function renderGroupSummary(data) {
        const tbody = document.getElementById('group-summary-body');
        if (!tbody) return; // Se a tabela de resumo não existir, não faz nada
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
        const tableSections = document.querySelector('.delivery-tables-container');
        const summarySection = document.querySelector('.group-summary-section');
        
        if (tableSections) tableSections.style.display = 'grid';
        if (summarySection) summarySection.style.display = 'block';
        
        if (filteredResultsContainer) filteredResultsContainer.style.display = 'none';
        if (clearSearchButton) clearSearchButton.style.display = 'none';
        if (searchInput) searchInput.value = '';

        try {
            // MUDANÇA AQUI: Força a atualização com um timestamp (cache-busting)
            const response = await fetch('api/get_data.php?t=' + new Date().getTime());
            
            if (response.status === 401) { // 401 = Não Autorizado
                // MUDANÇA AQUI: Redireciona para login.php
                window.location.href = 'login.php'; 
                return;
            }
            if (!response.ok) {
                 throw new Error('Falha ao carregar dados do servidor.');
            }

            const data = await response.json();
            renderTable(data);
            const groupSummary = calculateGroupSummary(data);
            renderGroupSummary(groupSummary);
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
            showFeedback("Não foi possível carregar os dados. Verifique a consola (F12).", "error");
        }
    }

    // Funções para gerenciar o feedback na tela
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

    function showLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'block';
    }

    function hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'none';
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
                filteredResultsContainer.style.display = 'none';
                clearSearchButton.style.display = 'none';
                return;
            }

            clearSearchButton.style.display = 'inline-block';

            searchTimeout = setTimeout(async () => {
                try {
                    // MUDANÇA AQUI: Adiciona cache-busting
                    const response = await fetch(`api/search_items.php?q=${query}&t=` + new Date().getTime());
                    const results = await response.json();
                    
                    renderFilteredResults(results);
                } catch (error) {
                    console.error("Erro na busca:", error);
                    filteredResultsContainer.style.display = 'none';
                }
            }, 300);
        });
        
        if (clearSearchButton) {
            clearSearchButton.addEventListener('click', () => {
                searchInput.value = '';
                filteredResultsContainer.style.display = 'none';
                clearSearchButton.style.display = 'none';
                searchInput.focus();
            });
        }
    }

    /**
     * Renderiza os resultados da busca na mini-tabela.
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
            row.className = 'search-result-row';
            row.dataset.id = item.id;
            
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.pedido_total}</td>
                <td style="color: green;">${item.recebido}</td>
                <td style="color: red;">${item.a_receber}</td>
            `;
            
            row.addEventListener('click', () => {
                const targetRow = document.querySelector(`.delivery-tables-container [data-id="${item.id}"]`);
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

    if (importBtn) {
        importBtn.addEventListener('click', () => fileInput.click());
    }

if (fileInput) {
        fileInput.addEventListener('change', async (event) => {
            const files = event.target.files; 
            if (files.length > 0) {
                
                const formData = new FormData();
                let hasInvalidFile = false;

                for (const file of files) {
                    if (file.type !== 'text/xml' && !file.name.endsWith('.xml')) {
                        showFeedback(`Arquivo inválido ignorado: ${file.name}. Por favor, envie apenas arquivos XML.`, 'error');
                        hasInvalidFile = true;
                        continue; 
                    }
                    formData.append('file[]', file); // MUDANÇA AQUI: O PHP prefere 'file[]' para múltiplos uploads
                }

                if (!formData.has('file[]') && hasInvalidFile) {
                    return; 
                }

                showLoading();
                try {
                    // MUDANÇA AQUI: Aponta para a API PHP
                    const response = await fetch('api/upload_xml.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    if (result.success) {
                        showFeedback(result.message, 'success');
                        
                        // **** ESTA É A NOVA MUDANÇA ****
                        // Adiciona uma pequena pausa antes de recarregar os dados
                        // para dar tempo ao banco de dados de confirmar a transação.
                        setTimeout(() => {
                            loadDataAndRenderTables();
                        }, 300); // 300ms de atraso
                        
                    } else {
                        showFeedback('Erro: ' + result.message, 'error');
                    }
                } catch (error) {
                    showFeedback('Ocorreu um erro ao enviar os arquivos.', 'error');
                } finally {
                    hideLoading();
                    fileInput.value = ''; 
                }
            }
        });
    }
    
    // **** NOVO: LÓGICA PARA IMPORTAR PEDIDOS (CSV) ****
    const importCsvBtn = document.getElementById('import-csv-btn');
    const csvFileInput = document.getElementById('csv-file-input');

    if (importCsvBtn) {
        importCsvBtn.addEventListener('click', () => {
            csvFileInput.click(); 
        });
    }

    if (csvFileInput) {
        csvFileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.name.endsWith('.csv')) {
                showFeedback('Por favor, selecione um arquivo no formato CSV.', 'error');
                csvFileInput.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            showLoading();

            try {
                // MUDANÇA AQUI: Aponta para a API PHP
                const response = await fetch('api/import_csv.php', {
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
                showFeedback('Ocorreu um erro ao importar o CSV.', 'error');
            } finally {
                hideLoading();
                csvFileInput.value = ''; 
            }
        });
    }

    // Lógica para o botão de imprimir listagem
    const printListBtn = document.getElementById('print-list-btn');
    if (printListBtn) {
        printListBtn.addEventListener('click', () => {
            const tablesContainer = document.querySelector('.delivery-tables-container');
            if (tablesContainer) {
                showLoading();
                html2canvas(tablesContainer, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgProps= pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth() - 20;
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                    pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
                    pdf.save('listagem-entregas.pdf');
                    hideLoading();
                });
            }
        });
    }
});