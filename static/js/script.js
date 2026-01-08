document.addEventListener('DOMContentLoaded', () => {

    // ===============================================
    // 1. LÓGICA DAS ABAS (TABS)
    // ===============================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove 'active' de todos
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Adiciona 'active' no atual
            btn.classList.add('active');

            // Mostra conteúdo alvo
            const targetId = btn.getAttribute('data-target');
            if (targetId) {
                const targetContent = document.getElementById(targetId);
                if(targetContent) targetContent.classList.add('active');
            }
        });
    });

    // ===============================================
    // 2. VARIÁVEIS GLOBAIS
    // ===============================================
    let searchTimeout = null;
    const searchInput = document.getElementById('product-search');
    const filteredResultsContainer = document.getElementById('filtered-results-container');
    const filteredResultsBody = document.getElementById('filtered-results-body');
    const clearSearchButton = document.getElementById('clear-search-btn');

    // Inicializa
    loadDataAndRenderTables();


// ===============================================
// 3. FUNÇÃO PRINCIPAL DE RENDERIZAÇÃO
// ===============================================
function renderTable(data) {
    const tbodyToReceive = document.getElementById('table-body-to-receive');
    const tbodyReceived = document.getElementById('table-body-received');
    const tbodyVerify = document.getElementById('table-body-verify'); 
    const btnVerifyTab = document.getElementById('btn-verify-tab');

    // Limpa tabelas
    if(tbodyToReceive) tbodyToReceive.innerHTML = '';
    if(tbodyReceived) tbodyReceived.innerHTML = '';
    if(tbodyVerify) tbodyVerify.innerHTML = '';

    let hasVerifyItems = false; 

    data.forEach(item => {
        const recebido = parseInt(item.recebido, 10) || 0;
        const total_caixa = parseInt(item.total_caixa, 10) || 0;
        const a_receber = Math.max(0, total_caixa - recebido);
        const excesso = Math.max(0, recebido - total_caixa);

        // Barra de progresso (apenas visual)
        let percentage = 0;
        if (total_caixa > 0) {
            percentage = (recebido / total_caixa) * 100;
            if (percentage > 100) percentage = 100;
        }

        const row = document.createElement('tr');

        // --- LÓGICA DE DISTRIBUIÇÃO ---

        // 1. CASO: ITENS A VERIFICAR (Excesso)
        if (recebido > total_caixa) {
            hasVerifyItems = true;
            row.classList.add('over-received'); // Deixa vermelho claro
            
            // AQUI ESTÁ A CORREÇÃO:
            // Agora Pedido Loja e Pedido VD são 'editable-cell', igual na aba 'A Receber'
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.grupo}</td>
                
                <td class="editable-cell" data-id="${item.id}" data-field="pedido_loja" data-old-value="${item.pedido_loja}">${item.pedido_loja}</td>
                <td class="editable-cell" data-id="${item.id}" data-field="pedido_vd" data-old-value="${item.pedido_vd}">${item.pedido_vd}</td>
                
                <td>${item.total_caixa}</td>
                <td>${item.recebido}</td>
                <td style="color: red; font-weight: bold;">+${excesso} (Excesso)</td>
                
                <td>
                    <button class="action-btn edit-btn" data-id="${item.id}" title="Editar Pedido">✏️</button>
                    <button class="action-btn delete-btn" data-id="${item.id}" title="Excluir">🗑️</button>
                </td>
            `;
            if(tbodyVerify) tbodyVerify.appendChild(row);

        // 2. CASO: CONCLUÍDO (Exatamente igual)
        } else if (recebido === total_caixa && total_caixa > 0) {
            row.classList.add('fully-received');
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.grupo}</td>
                <td>${item.total_caixa}</td>
                <td>${item.recebido}</td>
                <td style="color: green; font-weight: bold;">CONCLUÍDO</td>
                <td>
                    <button class="action-btn delete-btn" data-id="${item.id}" title="Excluir">🗑️</button>
                </td>
            `;
            if(tbodyReceived) tbodyReceived.appendChild(row);

        // 3. CASO: A RECEBER (Pendente)
        } else {
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

    // Controla a visibilidade do botão da aba "Verificar"
    if (btnVerifyTab) {
        if (hasVerifyItems) {
            btnVerifyTab.style.display = 'block';
        } else {
            btnVerifyTab.style.display = 'none';
            // Se a aba ativa sumiu, volta para a primeira
            if (btnVerifyTab.classList.contains('active')) {
                // Simula clique na primeira aba disponível
                const firstTab = document.querySelector('.tab-btn');
                if(firstTab) firstTab.click();
            }
        }
    }

    // Reaplica os listeners para os novos botões criados
    attachTableListeners();
}
    // ===============================================
    // 4. EDIÇÃO E EXCLUSÃO
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
                saveBtn.style.marginRight = '5px';
                cancelBtn.innerHTML = '❌';
                cancelBtn.className = 'action-btn cancel-btn';
                
                editBtn.parentNode.appendChild(saveBtn);
                editBtn.parentNode.appendChild(cancelBtn);

                editableCells.forEach(cell => {
                    const oldValue = cell.textContent;
                    cell.innerHTML = `<input type="number" value="${oldValue}" min="0" style="width: 70px;">`;
                });
                
                // Salvar
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
                            showFeedback('Erro ao salvar edição.', 'error');
                        }
                    } else {
                        loadDataAndRenderTables();
                    }
                });

                // Cancelar
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
                        showFeedback('Erro ao excluir item.', 'error');
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
            
            renderTable(data); // Renderiza as 3 tabelas
            const groupSummary = calculateGroupSummary(data);
            renderGroupSummary(groupSummary);
            
        } catch (error) {
            console.error("Erro:", error);
            showFeedback("Erro ao carregar dados.", "error");
        }
    }

    // ===============================================
    // 6. BUSCA / IMPORTAÇÃO / UTILITÁRIOS (MANTIDOS)
    // ===============================================
    
    // (Apenas resumindo esta parte pois não muda, mas ela deve estar no seu arquivo final)
    // BUSCA
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
                } catch (error) {}
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
            filteredResultsBody.innerHTML = '<tr><td colspan="5" align="center">Nenhum item encontrado.</td></tr>';
            filteredResultsContainer.style.display = 'block';
            return;
        }
        results.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'search-result-row';
            row.style.cursor = 'pointer';
            row.innerHTML = `<td>${item.codigo_sap}</td><td>${item.item}</td><td>${item.pedido_total}</td><td style="color:green;">${item.recebido}</td><td style="color:red;">${item.a_receber}</td>`;
            row.addEventListener('click', () => alert(`Detalhes:\nProduto: ${item.item}\nCódigo: ${item.codigo_sap}\nRecebido: ${item.recebido}`));
            filteredResultsBody.appendChild(row);
        });
        filteredResultsContainer.style.display = 'block';
    }

    // XML/CSV Upload e Feedback (Mantidos iguais ao anterior)
    setupUploads();
    
    function setupUploads() {
        const importXmlBtn = document.getElementById('import-xml-btn');
        const xmlFileInput = document.getElementById('xml-file-input');
        if (importXmlBtn) importXmlBtn.addEventListener('click', () => xmlFileInput.click());
        if (xmlFileInput) xmlFileInput.addEventListener('change', async (e) => uploadFiles(e.target.files, '../api/upload_xml.php', 'file[]'));

        const importCsvBtn = document.getElementById('import-csv-btn');
        const csvFileInput = document.getElementById('csv-file-input');
        if (importCsvBtn) importCsvBtn.addEventListener('click', () => csvFileInput.click());
        if (csvFileInput) csvFileInput.addEventListener('change', async (e) => uploadFiles(e.target.files, '../api/import_csv.php', 'file'));
    }

    async function uploadFiles(files, url, fieldName) {
        if (!files.length) return;
        const formData = new FormData();
        for (const file of files) formData.append(fieldName, file);
        
        showLoading();
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) { showFeedback(result.message, 'success'); loadDataAndRenderTables(); }
            else { showFeedback(result.message, 'error'); }
        } catch (e) { showFeedback('Erro no envio.', 'error'); }
        finally { hideLoading(); }
    }

    function showFeedback(msg, type) {
        const el = document.getElementById('feedback-message');
        if(!el) return;
        el.textContent = msg;
        el.className = `feedback-message ${type}`;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 5000);
    }
    
    function showLoading() { const s = document.getElementById('loading-spinner'); if(s) s.style.display = 'block'; }
    function hideLoading() { const s = document.getElementById('loading-spinner'); if(s) s.style.display = 'none'; }
});