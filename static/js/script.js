document.addEventListener('DOMContentLoaded', () => {

    // ===============================================
    // 1. LÓGICA DAS ABAS (TABS) - CORRIGIDA
    // ===============================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // 1. Remove 'active' de todos os botões e conteúdos
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // 2. Adiciona 'active' no botão clicado
            btn.classList.add('active');

            // 3. Pega o ID do alvo (ex: 'tab-to-receive') e mostra
            const targetId = btn.getAttribute('data-target'); 
            // Fallback: se não tiver data-target, tenta usar onclick ou id manual
            if (targetId) {
                const targetContent = document.getElementById(targetId);
                if(targetContent) {
                    targetContent.classList.add('active');
                }
            } else {
                // Caso o HTML esteja usando o modelo antigo de onclick, chamamos a função global
                // (Mas o código acima deve resolver 99% dos casos)
                console.warn('Botão sem data-target:', btn);
            }
        });
    });

    // ===============================================
    // 2. INICIALIZAÇÃO E VARIÁVEIS GLOBAIS
    // ===============================================
    let searchTimeout = null;
    const searchInput = document.getElementById('product-search');
    const filteredResultsContainer = document.getElementById('filtered-results-container');
    const filteredResultsBody = document.getElementById('filtered-results-body');
    const clearSearchButton = document.getElementById('clear-search-btn');

    // Inicializa a tabela ao carregar
    loadDataAndRenderTables();


    // ===============================================
    // 3. FUNÇÃO PRINCIPAL DE RENDERIZAÇÃO
    // ===============================================
    function renderTable(data) {
        const tbodyToReceive = document.getElementById('table-body-to-receive'); // Aba "A Receber"
        const tbodyReceived = document.getElementById('table-body-received');     // Aba "Concluídos"
        
        // Limpa tabelas
        if(tbodyToReceive) tbodyToReceive.innerHTML = '';
        if(tbodyReceived) tbodyReceived.innerHTML = '';

        data.forEach(item => {
            const recebido = parseInt(item.recebido, 10) || 0;
            const total_caixa = parseInt(item.total_caixa, 10) || 0;
            const a_receber = Math.max(0, total_caixa - recebido);

            // Cálculo da porcentagem para a barra
            let percentage = 0;
            if (total_caixa > 0) {
                percentage = (recebido / total_caixa) * 100;
                if (percentage > 100) percentage = 100;
            }

            const isFullyReceived = recebido >= total_caixa && total_caixa > 0;
            const row = document.createElement('tr');

            // Verifica Excesso (Vermelho se recebeu a mais)
            if (recebido > total_caixa) {
                row.classList.add('over-received'); 
            }

            // --- CONTEÚDO DA LINHA ---
            
            // CASO 1: CONCLUÍDO (Vai para a aba de Concluídos)
            if (isFullyReceived) {
                row.classList.add('fully-received'); // Verde
                row.innerHTML = `
                    <td>${item.codigo_sap}</td>
                    <td>${item.item}</td>
                    <td>${item.grupo}</td>
                    <td>${item.total_caixa}</td>
                    <td class="editable-cell" data-id="${item.id}" data-field="recebido" data-old-value="${item.recebido}">${item.recebido}</td>
                    <td style="color: green; font-weight: bold;">CONCLUÍDO</td>
                    <td>
                        <button class="action-btn edit-btn" data-id="${item.id}" title="Editar">✏️</button>
                        <button class="action-btn delete-btn" data-id="${item.id}" title="Excluir">🗑️</button>
                    </td>
                `;
                if(tbodyReceived) tbodyReceived.appendChild(row);

            } else {
                // CASO 2: A RECEBER (Vai para a aba A Receber)
                row.innerHTML = `
                    <td>${item.codigo_sap}</td>
                    <td>${item.item}</td>
                    <td>${item.grupo}</td>
                    <td class="editable-cell" data-id="${item.id}" data-field="pedido_loja" data-old-value="${item.pedido_loja}">${item.pedido_loja}</td>
                    <td class="editable-cell" data-id="${item.id}" data-field="pedido_vd" data-old-value="${item.pedido_vd}">${item.pedido_vd}</td>
                    <td>${item.total_caixa}</td>
                    <td style="font-weight: bold; color: #d9534f;">${a_receber}</td>
                    
                    <td>
                        <div class="progress-container" title="Recebido: ${recebido} de ${total_caixa}">
                            <div class="progress-bar" style="width: ${percentage}%;"></div>
                            <div class="progress-text">${recebido} (${percentage.toFixed(0)}%)</div>
                        </div>
                    </td>

                    <td>
                        <button class="action-btn edit-btn" data-id="${item.id}" title="Editar">✏️</button>
                        <button class="action-btn delete-btn" data-id="${item.id}" title="Excluir">🗑️</button>
                    </td>
                `;
                if(tbodyToReceive) tbodyToReceive.appendChild(row);
            }
        });

        // Reaplica os eventos de clique nos botões recém-criados
        attachTableListeners();
    }

    // ===============================================
    // 4. EDIÇÃO E EXCLUSÃO (EVENT LISTENERS)
    // ===============================================
    function attachTableListeners() {
        
        // --- EDIÇÃO ---
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const editableCells = row.querySelectorAll('.editable-cell');
                const editBtn = e.target;
                
                editBtn.style.display = 'none';
                const delBtn = row.querySelector('.delete-btn');
                if(delBtn) delBtn.style.display = 'none';
                
                const saveBtn = document.createElement('button');
                const cancelBtn = document.createElement('button');
                
                saveBtn.innerHTML = '✔️';
                saveBtn.className = 'action-btn save-btn';
                saveBtn.title = 'Confirmar';
                saveBtn.style.marginRight = '5px';

                cancelBtn.innerHTML = '❌';
                cancelBtn.className = 'action-btn cancel-btn';
                cancelBtn.title = 'Cancelar';
                
                editBtn.parentNode.appendChild(saveBtn);
                editBtn.parentNode.appendChild(cancelBtn);

                editableCells.forEach(cell => {
                    const oldValue = cell.textContent;
                    cell.innerHTML = `<input type="number" value="${oldValue}" min="0" style="width: 70px;">`;
                });
                
                saveBtn.addEventListener('click', async () => {
                    const firstCell = row.querySelector('.editable-cell');
                    const id = firstCell.dataset.id;
                    
                    const updatedData = { id: parseInt(id, 10) };
                    let hasChanged = false;

                    row.querySelectorAll('input').forEach(input => {
                        const cell = input.closest('td');
                        const field = cell.dataset.field; 
                        const oldValue = cell.dataset.oldValue;
                        const newValue = input.value;
                        
                        updatedData[field] = parseInt(newValue, 10);
                        if (oldValue !== newValue) hasChanged = true;
                    });

                    if (hasChanged) {
                        try {
                            const response = await fetch(`../api/update_item.php`, {
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

                cancelBtn.addEventListener('click', () => {
                    loadDataAndRenderTables();
                });
            });
        });

        // --- EXCLUSÃO ---
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const id = e.target.dataset.id;
                if (confirm("Tem certeza que deseja excluir este item?")) {
                    try {
                        const response = await fetch(`../api/delete_item.php?id=${id}`, { method: 'DELETE' });
                        const result = await response.json();
                        if (result.success) {
                            showFeedback(result.message, 'success');
                            loadDataAndRenderTables();
                        } else {
                            showFeedback('Erro: ' + result.message, 'error');
                        }
                    } catch (error) {
                        showFeedback('Erro ao excluir o item.', 'error');
                    }
                }
            });
        });
    }

    // ===============================================
    // 5. RESUMO E LOAD DE DADOS
    // ===============================================
    
    function calculateGroupSummary(data) {
        const summary = {};
        data.forEach(item => {
            const grupo = item.grupo || 'Sem Grupo';
            if (!summary[grupo]) {
                summary[grupo] = { pedido: 0, a_receber: 0, entregue: 0 };
            }
            const recebido = parseInt(item.recebido, 10) || 0;
            const total = parseInt(item.total_caixa, 10) || 0;
            const a_receber = Math.max(0, total - recebido);

            summary[grupo].pedido += total;
            summary[grupo].a_receber += a_receber;
            summary[grupo].entregue += recebido;
        });
        return summary;
    }

    function renderGroupSummary(data) {
        const tbody = document.getElementById('group-summary-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        for (const grupo in data) {
            const item = data[grupo];
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${grupo}</td>
                <td>${item.pedido}</td>
                <td style="color: ${item.a_receber > 0 ? 'red' : 'green'}">${item.a_receber}</td>
                <td>${item.entregue}</td>
            `;
            tbody.appendChild(row);
        }
    }

    async function loadDataAndRenderTables() {
        if (filteredResultsContainer) filteredResultsContainer.style.display = 'none';
        if (clearSearchButton) clearSearchButton.style.display = 'none';
        if (searchInput) searchInput.value = '';

        try {
            const response = await fetch('../api/get_data.php?t=' + new Date().getTime());
            
            if (response.status === 401) {
                window.location.href = '../auth/login.php'; 
                return;
            }
            if (!response.ok) throw new Error('Falha ao carregar dados.');

            const data = await response.json();
            
            renderTable(data);
            const groupSummary = calculateGroupSummary(data);
            renderGroupSummary(groupSummary);
            
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
            showFeedback("Não foi possível carregar os dados.", "error");
        }
    }


    // ===============================================
    // 6. BUSCA INSTANTÂNEA
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
                    const response = await fetch(`../api/search_items.php?q=${query}&t=` + new Date().getTime());
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

    function renderFilteredResults(results) {
        if (!filteredResultsBody) return;
        filteredResultsBody.innerHTML = '';
        
        if (results.length === 0) {
            filteredResultsBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Nenhum item encontrado.</td></tr>';
            filteredResultsContainer.style.display = 'block';
            return;
        }

        results.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'search-result-row';
            row.style.cursor = 'pointer';
            
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.pedido_total}</td>
                <td style="color: green;">${item.recebido}</td>
                <td style="color: red;">${item.a_receber}</td>
            `;
            
            row.addEventListener('click', () => {
                alert(`Detalhes:\nProduto: ${item.item}\nCódigo: ${item.codigo_sap}\nRecebido: ${item.recebido}`);
            });

            filteredResultsBody.appendChild(row);
        });
        filteredResultsContainer.style.display = 'block';
    }


    // ===============================================
    // 7. IMPORTAÇÃO DE ARQUIVOS (XML / CSV)
    // ===============================================
    
    // --- XML ---
    const importXmlBtn = document.getElementById('import-xml-btn');
    const xmlFileInput = document.getElementById('xml-file-input');

    if (importXmlBtn) importXmlBtn.addEventListener('click', () => xmlFileInput.click());

    if (xmlFileInput) {
        xmlFileInput.addEventListener('change', async (event) => {
            const files = event.target.files; 
            if (files.length > 0) {
                const formData = new FormData();
                let hasInvalidFile = false;

                for (const file of files) {
                    if (file.type !== 'text/xml' && !file.name.endsWith('.xml')) {
                        showFeedback(`Arquivo inválido ignorado: ${file.name}.`, 'error');
                        hasInvalidFile = true;
                        continue; 
                    }
                    formData.append('file[]', file);
                }

                if (!formData.has('file[]') && hasInvalidFile) return;

                showLoading();
                try {
                    const response = await fetch('../api/upload_xml.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    if (result.success) {
                        showFeedback(result.message, 'success');
                        setTimeout(() => loadDataAndRenderTables(), 300);
                    } else {
                        showFeedback('Erro: ' + result.message, 'error');
                    }
                } catch (error) {
                    showFeedback('Erro ao enviar XML.', 'error');
                } finally {
                    hideLoading();
                    xmlFileInput.value = ''; 
                }
            }
        });
    }

    // --- CSV ---
    const importCsvBtn = document.getElementById('import-csv-btn');
    const csvFileInput = document.getElementById('csv-file-input');

    if (importCsvBtn) importCsvBtn.addEventListener('click', () => csvFileInput.click());

    if (csvFileInput) {
        csvFileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.name.endsWith('.csv')) {
                showFeedback('Selecione um arquivo CSV.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            showLoading();

            try {
                const response = await fetch('../api/import_csv.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    showFeedback(result.message, 'success');
                    loadDataAndRenderTables(); 
                } else {
                    showFeedback('Erro: ' + result.message, 'error');
                }
            } catch (error) {
                showFeedback('Erro ao importar CSV.', 'error');
            } finally {
                hideLoading();
                csvFileInput.value = ''; 
            }
        });
    }

    // ===============================================
    // 8. UTILITÁRIOS (Feedback, Loading)
    // ===============================================
    function showFeedback(message, type) {
        const feedbackDiv = document.getElementById('feedback-message');
        if (!feedbackDiv) return;
        feedbackDiv.textContent = message;
        feedbackDiv.className = `feedback-message ${type}`;
        feedbackDiv.style.display = 'block';
        setTimeout(() => { feedbackDiv.style.display = 'none'; }, 5000);
    }

    function showLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'block';
    }

    function hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'none';
    }
});