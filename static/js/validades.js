/* static/js/validades.js */

document.addEventListener('DOMContentLoaded', () => {
    configurarDataMinima(); // Impede datas retroativas no cadastro
    carregarAbas();
    carregarItens();
});

// === 0. CONFIGURAÇÕES GERAIS ===
function configurarDataMinima() {
    const hoje = new Date();
    // Formata para YYYY-MM-DD
    const dataFormatada = hoje.toISOString().split('T')[0];
    
    // Define o mínimo no input de data
    const inputDate = document.getElementById('prod-date');
    if(inputDate) {
        inputDate.setAttribute('min', dataFormatada);
    }
}

// === 1. CONFIGURAÇÃO DAS ABAS ===
function carregarAbas() {
    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const hoje = new Date();
    
    const header = document.getElementById('tabs-header');
    const content = document.getElementById('tabs-content');

    header.innerHTML = '';
    content.innerHTML = '';

    for (let i = 0; i < 4; i++) {
        let d = new Date(hoje.getFullYear(), hoje.getMonth() + i, 1);
        let mesIndex = d.getMonth();
        let ano = d.getFullYear();
        let mesNome = meses[mesIndex];
        
        let tabId = `tab-${mesIndex}-${ano}`;

        // Botão
        let btn = document.createElement('button');
        btn.className = `tab-btn ${i === 0 ? 'active' : ''}`;
        btn.innerText = i === 0 ? `Mês Atual (${mesNome})` : `${mesNome}/${ano}`;
        btn.onclick = () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        };
        header.appendChild(btn);

        // Painel
        let pane = document.createElement('div');
        pane.className = `tab-pane ${i === 0 ? 'active' : ''}`;
        pane.id = tabId;
        pane.innerHTML = `
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="15%">Cód.</th>
                            <th width="30%">Produto</th>
                            <th width="15%">Validade</th>
                            <th width="15%">Estoque</th>
                            <th width="15%">Meta Diária</th>
                            <th width="10%">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-${tabId}"></tbody>
                </table>
                <div id="empty-${tabId}" style="display:none; text-align:center; padding: 20px; color: #999;">
                    Nenhum produto vencendo neste mês.
                </div>
            </div>
        `;
        content.appendChild(pane);
    }
}

// === 2. BUSCA INTELIGENTE ===
const searchInput = document.getElementById('search-input');
const resultsBox = document.getElementById('search-results');

searchInput.addEventListener('input', async (e) => {
    let term = e.target.value;
    if (term.length < 3) {
        resultsBox.style.display = 'none';
        return;
    }
    try {
        let res = await fetch(`../api/validades_search.php?q=${term}`);
        let data = await res.json();
        resultsBox.innerHTML = '';
        if (data.length > 0) {
            resultsBox.style.display = 'block';
            data.forEach(prod => {
                let div = document.createElement('div');
                div.className = 'dropdown-item';
                let codigo = prod.codigo_barras || prod.codigo_interno;
                div.innerHTML = `<strong>${codigo}</strong> - ${prod.nome_produto}`;
                div.onclick = () => {
                    document.getElementById('prod-name').value = prod.nome_produto;
                    document.getElementById('prod-code').value = codigo;
                    searchInput.value = codigo;
                    resultsBox.style.display = 'none';
                };
                resultsBox.appendChild(div);
            });
        } else { resultsBox.style.display = 'none'; }
    } catch (err) { console.error(err); }
});

document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
        resultsBox.style.display = 'none';
    }
});

// === 3. SALVAR NOVO ITEM ===
document.getElementById('form-add').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validação extra JS para garantir data
    let inputDate = document.getElementById('prod-date').value;
    let hoje = new Date().toISOString().split('T')[0];
    if (inputDate < hoje) {
        alert("A data de validade não pode ser anterior a hoje.");
        return;
    }

    if(!document.getElementById('prod-code').value) {
        alert("Selecione um produto da lista.");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('codigo', document.getElementById('prod-code').value);
    formData.append('nome', document.getElementById('prod-name').value);
    formData.append('validade', inputDate);
    formData.append('qtd', document.getElementById('prod-qtd').value);

    try {
        let res = await fetch('../api/validades_actions.php', { method: 'POST', body: formData });
        let json = await res.json();
        if (json.success) {
            document.getElementById('form-add').reset();
            document.getElementById('prod-code').value = '';
            configurarDataMinima(); // Reseta o min da data
            alert('Produto adicionado!');
            carregarItens();
        }
    } catch (err) { alert('Erro ao salvar.'); }
});

// === 4. CARREGAR ITENS ===
async function carregarItens() {
    try {
        let res = await fetch('../api/validades_actions.php?action=list');
        let itens = await res.json();

        // 1. Atualiza a Data Geral do Sistema
        atualizarDataCabecalho(itens);

        // 2. Limpa e Prepara Tabelas
        document.querySelectorAll('tbody').forEach(tb => tb.innerHTML = '');
        document.querySelectorAll('[id^="empty-"]').forEach(el => el.style.display = 'block');

        // 3. Distribui os itens
        itens.forEach(item => {
            let validade = new Date(item.data_validade + "T00:00:00");
            let targetTabId = `tab-${validade.getMonth()}-${validade.getFullYear()}`;
            let tbody = document.getElementById(`tbody-${targetTabId}`);
            let emptyMsg = document.getElementById(`empty-${targetTabId}`);

            if (tbody) {
                if(emptyMsg) emptyMsg.style.display = 'none';

                let meta = calcularMeta(item.quantidade, validade);
                let hoje = new Date(); hoje.setHours(0,0,0,0);
                let diffDays = Math.ceil((validade - hoje) / (1000 * 60 * 60 * 24));
                
                let classAlerta = (diffDays <= 7) ? 'alert-vencendo' : '';
                let icone = (diffDays <= 7) ? '⚠️' : '';

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.codigo_produto}</td>
                    <td><div class="${classAlerta}">${icone} ${item.nome_produto}</div></td>
                    <td>${validade.toLocaleDateString('pt-BR')}</td>
                    <td>
                        <input type="number" value="${item.quantidade}" 
                               class="estoque-input" id="qtd-${item.id}" disabled>
                    </td>
                    <td><span class="meta-valor" id="meta-${item.id}">${meta.toFixed(1)}</span> un/dia</td>
                    <td>
                        <button class="btn-update" id="btn-${item.id}" onclick="atualizar(${item.id})" title="Editar Estoque">✏️</button>
                        <button class="btn-delete" onclick="excluir(${item.id})" title="Excluir">✕</button>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        });
    } catch (err) { console.error(err); }
}

// === FUNÇÃO NOVA: Encontra a data mais recente ===
function atualizarDataCabecalho(itens) {
    const display = document.getElementById('system-last-update');
    
    if (!itens || itens.length === 0) {
        display.innerText = "Nenhuma informação registrada.";
        return;
    }

    // Procura a maior data_atualizacao no array
    // O banco retorna string "YYYY-MM-DD HH:MM:SS" (UTC ou Local)
    // Vamos converter para Date e ordenar
    
    let datas = itens
        .map(i => i.data_atualizacao ? new Date(i.data_atualizacao.replace(" ", "T")) : new Date(0)) // O replace ajuda no Safari/Mobile se vier SQL padrão
        .sort((a, b) => b - a); // Ordena decrescente

    if (datas.length > 0 && datas[0].getTime() > 0) {
        let ultima = datas[0];
        
        // Ajuste de Fuso Horário se necessário (O SQLite salva UTC por padrão)
        // Se o seu servidor PHP estiver salvando UTC, subtraímos 3h (ou usamos toLocaleString)
        // Geralmente toLocaleString resolve se o navegador estiver no BR
        
        let formatada = ultima.toLocaleDateString('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        
        display.innerText = formatada;
    } else {
        display.innerText = "Nenhuma atualização recente.";
    }
}

function calcularMeta(qtd, dataValidade) {
    let hoje = new Date(); hoje.setHours(0,0,0,0);
    if (dataValidade <= hoje) return parseFloat(qtd);
    let diasUteis = 0;
    let tempDate = new Date(hoje);
    while (tempDate <= dataValidade) {
        if (tempDate.getDay() !== 0) diasUteis++;
        tempDate.setDate(tempDate.getDate() + 1);
    }
    return diasUteis > 0 ? (qtd / diasUteis) : parseFloat(qtd);
}

// === 5. AÇÕES (ATUALIZAR COM TRAVA) ===
window.atualizar = async function(id) {
    let input = document.getElementById(`qtd-${id}`);
    let btn = document.getElementById(`btn-${id}`);

    // ESTADO 1: Se está bloqueado, destrava para edição
    if (input.disabled) {
        input.disabled = false;  // Habilita
        input.focus();           // Foca no campo
        btn.innerHTML = "💾";    // Muda ícone para Salvar (Disk)
        btn.title = "Salvar Alteração";
        btn.classList.add('btn-saving'); // Muda cor (definido no CSS)
        return; // Para por aqui, espera usuário digitar e clicar de novo
    }

    // ESTADO 2: Se já estava habilitado, o clique significa SALVAR
    let novaQtd = input.value;
    if(novaQtd === "") return;

    const formData = new FormData();
    formData.append('action', 'update_qtd');
    formData.append('id', id);
    formData.append('qtd', novaQtd);

    try {
        let res = await fetch('../api/validades_actions.php?action=list&t=' + Date.now());
        let itens = await res.json();

        if (json.success) {
            if (json.deleted) {
                carregarItens(); // Se zerou, recarrega tudo
            } else {
                // Sucesso visual
                input.disabled = true;       // Trava de novo
                btn.innerHTML = "✏️";        // Volta ícone editar
                btn.title = "Editar Estoque";
                btn.classList.remove('btn-saving');
                
                input.style.borderColor = 'green';
                setTimeout(() => input.style.borderColor = '#ddd', 1000);
                
                // Recalcula meta na tela sem recarregar tudo (opcional, mas mais rápido)
                carregarItens(); 
            }
        }
    } catch (err) { alert('Erro ao atualizar'); }
};

window.excluir = async function(id) {
    if(!confirm("Tem certeza que deseja excluir?")) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    await fetch('../api/validades_actions.php', { method: 'POST', body: formData });
    carregarItens();
};

/* ... (Todo o código anterior) ... */

// =========================================
// 7. LÓGICA DO RELATÓRIO (MODAL)
// =========================================

function abrirModalRelatorio() {
    const modal = document.getElementById('modal-relatorio');
    const selectAno = document.getElementById('rel-ano');
    const selectMes = document.getElementById('rel-mes');
    
    // Preenche o Select de Ano dinamicamente (Ano atual - 1 até Ano atual + 5)
    if (selectAno.options.length === 0) {
        const anoAtual = new Date().getFullYear();
        for (let i = anoAtual - 1; i <= anoAtual + 5; i++) {
            let option = document.createElement('option');
            option.value = i;
            option.innerText = i;
            if (i === anoAtual) option.selected = true;
            selectAno.appendChild(option);
        }
    }

    // Seleciona o mês atual automaticamente
    selectMes.value = new Date().getMonth() + 1;

    // Mostra o modal (Flex para centralizar)
    modal.style.display = 'flex';
}

function fecharModalRelatorio() {
    document.getElementById('modal-relatorio').style.display = 'none';
}

function gerarRelatorio() {
    const mes = document.getElementById('rel-mes').value;
    const ano = document.getElementById('rel-ano').value;

    // Abre a página de impressão em nova aba
    const url = `relatorio_print.php?mes=${mes}&ano=${ano}`;
    window.open(url, '_blank');
    
    // Opcional: fechar o modal após clicar
    fecharModalRelatorio();
}

// Fechar modal se clicar fora da caixa (no fundo escuro)
document.getElementById('modal-relatorio').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalRelatorio();
    }
});