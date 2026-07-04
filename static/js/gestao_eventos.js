    function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
    function fecharModal(id) { document.getElementById(id).style.display = 'none'; }

    async function salvarEvento() {
        const nome = document.getElementById('ev_nome').value.trim();
        const data = document.getElementById('ev_data').value;
        const estoque = document.getElementById('ev_estoque').value;

        if (!nome || !data) {
            alert('Por favor, preencha o nome e a data do evento.');
            return;
        }

        try {
            const res = await fetch('../api/pdv_criar_evento.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ nome_evento: nome, data_evento: data, controla_estoque: estoque })
            });
            const json = await res.json();

            if (json.success) {
                alert('Evento criado com sucesso!');
                window.location.reload(); // Recarrega a página para atualizar a tabela
            } else {
                alert('Erro: ' + json.error);
            }
        } catch (e) {
            alert('Erro ao conectar com o servidor.');
        }
    }

    function abrirEstoque(id) {
    document.getElementById('estoque_evento_id').value = id;
    abrirModal('modalEstoque');
}

async function importarEstoque() {
    try {
        const form = document.getElementById('formEstoque');
        const fileInput = form.querySelector('input[type="file"]');
        
        if (!fileInput.value) {
            alert("Por favor, selecione um arquivo CSV antes de importar.");
            return;
        }

        const formData = new FormData(form);
        const res = await fetch('../api/pdv_importar_estoque.php', { method: 'POST', body: formData });
        
        // Pega o texto puro antes de converter para JSON para ver se o PHP cuspiu erro
        const text = await res.text(); 
        
        try {
            const json = JSON.parse(text);
            if(json.success) {
                alert('Estoque importado com sucesso!');
                window.location.reload();
            } else {
                alert('Erro na importação: ' + json.error);
            }
        } catch(err) {
            alert('Erro do Servidor PHP (Veja o aviso):\n' + text);
        }
    } catch(e) {
        alert('Erro de comunicação com a internet.');
    }
}

async function mudarStatusEvento(id, statusAtual) {
    // Inverte o status: se está ativo vira inativo, e vice-versa
    const novoStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
    const acao = novoStatus === 'inativo' ? 'ENCERRAR' : 'REATIVAR';

    // Pede confirmação para evitar cliques acidentais
    if(!confirm(`Deseja realmente ${acao} este evento?\n\nEventos encerrados não aparecerão mais no PDV para abertura de caixa.`)) {
        return;
    }

    try {
        const res = await fetch('../api/pdv_alterar_status_evento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id, status: novoStatus })
        });
        const json = await res.json();
        
        if(json.success) {
            window.location.reload();
        } else {
            alert('Erro: ' + json.error);
        }
    } catch(e) {
        alert('Erro de conexão ao tentar alterar o status.');
    }
}

/* ==========================================
   ESTOQUE MANUAL & GERENCIAMENTO
========================================== */
let carrinhoManual = [];
let debounceBuscaManual;

function abrirOpcoesEstoque(id) {
    document.getElementById('estoque_evento_id').value = id;
    abrirModal('modalOpcoesEstoque');
}

function abrirEstoqueManual() {
    carrinhoManual = []; 
    limparCamposBuscaManual();
    renderizarListaManual();
    abrirModal('modalEstoqueManual');
    setTimeout(() => document.getElementById('busca_produto_manual').focus(), 100);
}

function limparCamposBuscaManual() {
    document.getElementById('produto_selecionado_id_manual').value = '';
    document.getElementById('produto_selecionado_nome_manual').value = '';
    document.getElementById('busca_produto_manual').value = '';
    document.getElementById('qtd_produto_manual').value = 1;
}

// BUSCA: INSERÇÃO MANUAL
document.getElementById('busca_produto_manual').addEventListener('input', (e) => {
    clearTimeout(debounceBuscaManual);
    const termo = e.target.value.trim();
    const dropdown = document.getElementById('dropdown_busca_manual');
    
    if (termo.length < 2) { dropdown.style.display = 'none'; return; }

    debounceBuscaManual = setTimeout(async () => {
        try {
            let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
            let json = await res.json();
            dropdown.innerHTML = '';
            
            if (json.success && json.produtos.length > 0) {
                json.produtos.forEach(p => {
                    let div = document.createElement('div');
                    div.style.cssText = "padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;";
                    div.innerHTML = `<strong>${p.nome}</strong> <small style="color:#666;">(${p.codigo_interno})</small>`;
                    // Clica para selecionar, MAS NÃO INSERE na lista ainda
                    div.onclick = () => {
                        document.getElementById('produto_selecionado_id_manual').value = p.id;
                        document.getElementById('produto_selecionado_nome_manual').value = p.nome;
                        document.getElementById('busca_produto_manual').value = p.nome;
                        dropdown.style.display = 'none';
                        document.getElementById('qtd_produto_manual').focus(); // Joga pro campo qtd
                    };
                    dropdown.appendChild(div);
                });
                dropdown.style.display = 'block';
            }
        } catch(err) { console.error(err); }
    }, 300);
});

// AÇÃO: INSERIR NA LISTA MANUAL
function adicionarItemManualLista() {
    const id = document.getElementById('produto_selecionado_id_manual').value;
    const nome = document.getElementById('produto_selecionado_nome_manual').value;
    const qtd = parseFloat(document.getElementById('qtd_produto_manual').value);

    if(!id) return alert("Por favor, pesquise e selecione um produto primeiro.");
    if(isNaN(qtd) || qtd <= 0) return alert("Quantidade inválida!");

    const existente = carrinhoManual.find(i => i.id === id);
    if(existente) {
        existente.qtd += qtd;
    } else {
        carrinhoManual.push({ id, nome, qtd });
    }

    limparCamposBuscaManual();
    renderizarListaManual();
    document.getElementById('busca_produto_manual').focus();
}

// HABILITA A TECLA ENTER (Manual)
document.getElementById('qtd_produto_manual').addEventListener('keypress', (e) => {
    if(e.key === 'Enter') { e.preventDefault(); adicionarItemManualLista(); }
});
document.getElementById('busca_produto_manual').addEventListener('keypress', (e) => {
    if(e.key === 'Enter') { e.preventDefault(); document.getElementById('qtd_produto_manual').focus(); }
});


function removerItemManual(index) {
    carrinhoManual.splice(index, 1);
    renderizarListaManual();
}

function renderizarListaManual() {
    const tbody = document.getElementById('lista_manual_tbody');
    tbody.innerHTML = '';
    carrinhoManual.forEach((item, index) => {
        tbody.innerHTML += `
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;">${item.nome}</td>
                <td style="padding: 10px; text-align: center;">${item.qtd}</td>
                <td style="padding: 10px; text-align: center;">
                    <button onclick="removerItemManual(${index})" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">❌</button>
                </td>
            </tr>`;
    });
}

async function salvarEstoqueManual() {
    if(carrinhoManual.length === 0) return alert('Adicione produtos na lista primeiro.');
    const eventoId = document.getElementById('estoque_evento_id').value;

    try {
        const res = await fetch('../api/pdv_inserir_estoque_manual.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ evento_id: eventoId, itens: carrinhoManual })
        });
        const json = await res.json();
        if(json.success) {
            alert('Estoque manual inserido com sucesso!');
            window.location.reload();
        } else {
            alert('Erro: ' + json.error);
        }
    } catch(e) { alert('Erro de comunicação.'); }
}

/* ==========================================
   2. GERENCIAR ESTOQUE (COM NOVA BUSCA)
========================================== */
let eventoGerenciamentoAtual = 0;
let debounceBuscaGerenciar;

async function abrirGerenciarEstoque(id) {
    eventoGerenciamentoAtual = id;
    document.getElementById('produto_selecionado_id_gerenciar').value = '';
    document.getElementById('busca_produto_gerenciar').value = '';
    document.getElementById('qtd_produto_gerenciar').value = 1;
    abrirModal('modalGerenciarEstoque');
    carregarEstoqueGerenciamento();
}

// BUSCA: DENTRO DO GERENCIAR ESTOQUE
document.getElementById('busca_produto_gerenciar').addEventListener('input', (e) => {
    clearTimeout(debounceBuscaGerenciar);
    const termo = e.target.value.trim();
    const dropdown = document.getElementById('dropdown_busca_gerenciar');
    
    if (termo.length < 2) { dropdown.style.display = 'none'; return; }

    debounceBuscaGerenciar = setTimeout(async () => {
        try {
            let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
            let json = await res.json();
            dropdown.innerHTML = '';
            
            if (json.success && json.produtos.length > 0) {
                json.produtos.forEach(p => {
                    let div = document.createElement('div');
                    div.style.cssText = "padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;";
                    div.innerHTML = `<strong>${p.nome}</strong> <small style="color:#666;">(${p.codigo_interno})</small>`;
                    div.onclick = () => {
                        document.getElementById('produto_selecionado_id_gerenciar').value = p.id;
                        document.getElementById('busca_produto_gerenciar').value = p.nome;
                        dropdown.style.display = 'none';
                        document.getElementById('qtd_produto_gerenciar').focus();
                    };
                    dropdown.appendChild(div);
                });
                dropdown.style.display = 'block';
            }
        } catch(err) { console.error(err); }
    }, 300);
});

// AÇÃO: INSERE DIRETO NO BANCO PELO GERENCIAR ESTOQUE
async function adicionarNovoItemGerenciamento() {
    const id = document.getElementById('produto_selecionado_id_gerenciar').value;
    const qtd = parseFloat(document.getElementById('qtd_produto_gerenciar').value);

    if(!id) return alert("Selecione um produto na busca primeiro.");
    if(isNaN(qtd) || qtd <= 0) return alert("Quantidade inválida!");

    // Reutiliza a API de inserção manual, mandando só 1 item e atualizando a lista na hora
    try {
        const res = await fetch('../api/pdv_inserir_estoque_manual.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ evento_id: eventoGerenciamentoAtual, itens: [{ id: id, qtd: qtd }] })
        });
        const json = await res.json();
        if(json.success) {
            document.getElementById('produto_selecionado_id_gerenciar').value = '';
            document.getElementById('busca_produto_gerenciar').value = '';
            document.getElementById('qtd_produto_gerenciar').value = 1;
            carregarEstoqueGerenciamento(); // Recarrega a tabela de gerenciamento!
            document.getElementById('busca_produto_gerenciar').focus();
        } else {
            alert('Erro ao adicionar item: ' + json.error);
        }
    } catch(e) { alert('Erro de comunicação.'); }
}

// HABILITA A TECLA ENTER (Gerenciar)
document.getElementById('qtd_produto_gerenciar').addEventListener('keypress', (e) => {
    if(e.key === 'Enter') { e.preventDefault(); adicionarNovoItemGerenciamento(); }
});
document.getElementById('busca_produto_gerenciar').addEventListener('keypress', (e) => {
    if(e.key === 'Enter') { e.preventDefault(); document.getElementById('qtd_produto_gerenciar').focus(); }
});

// FUNÇÕES DE CARREGAR, ATUALIZAR E EXCLUIR QUE JÁ ESTAVAM FUNCIONANDO
async function carregarEstoqueGerenciamento() {
    const tbody = document.getElementById('lista_gerenciar_tbody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    try {
        const res = await fetch(`../api/pdv_listar_estoque.php?evento_id=${eventoGerenciamentoAtual}`);
        const json = await res.json();
        
        if (json.success) {
            tbody.innerHTML = '';
            if(json.estoque.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;">Estoque vazio para este evento.</td></tr>';
                return;
            }
            
            json.estoque.forEach(item => {
                tbody.innerHTML += `
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; color: #666;">${item.codigo_interno}</td>
                        <td style="padding: 10px;"><strong>${item.nome_produto}</strong></td>
                        <td style="padding: 10px; text-align: center;">
                            <input type="number" id="qtd_${item.id}" value="${item.quantidade_atual}" style="width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 3px;">
                        </td>
                        <td style="padding: 10px; text-align: center; display: flex; gap: 5px; justify-content: center;">
                            <button onclick="acaoEstoque(${item.id}, 'atualizar')" title="Salvar Nova Quantidade" style="background: #0d6efd; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">💾</button>
                            <button onclick="acaoEstoque(${item.id}, 'excluir')" title="Remover do Evento" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">🗑️</button>
                        </td>
                    </tr>`;
            });
        }
    } catch(e) { tbody.innerHTML = '<tr><td colspan="4" style="color:red; text-align:center;">Erro ao carregar.</td></tr>'; }
}

async function acaoEstoque(id_registro, acao) {
    if(acao === 'excluir' && !confirm('Tem certeza que deseja remover este produto do evento?')) return;
    
    const payload = { id_registro: id_registro, acao: acao };
    if(acao === 'atualizar') {
        payload.quantidade = document.getElementById(`qtd_${id_registro}`).value;
    }

    try {
        const res = await fetch('../api/pdv_atualizar_item_estoque.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        
        if(json.success) {
            carregarEstoqueGerenciamento(); 
        } else {
            alert('Erro: ' + json.error);
        }
    } catch(e) { alert('Erro de comunicação.'); }
}

/* ==========================================
   HUB DE ESTOQUE
========================================== */
function abrirHubEstoque(id) {
    document.getElementById('hub_evento_id').value = id;
    abrirModal('modalHubEstoque');
}

function irParaAdicionarEstoque() {
    const id = document.getElementById('hub_evento_id').value;
    fecharModal('modalHubEstoque');
    // Preenche o campo escondido do modal antigo do CSV
    document.getElementById('estoque_evento_id').value = id; 
    abrirModal('modalOpcoesEstoque');
}

function irParaGerenciarEstoque() {
    const id = document.getElementById('hub_evento_id').value;
    fecharModal('modalHubEstoque');
    abrirGerenciarEstoque(id);
}