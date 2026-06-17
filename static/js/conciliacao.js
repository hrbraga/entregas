let dadosConciliacao = { matches: [], ofx_pendentes: [], sistema_pendentes: [] };

function formatarMoeda(valor) { return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
function formatarData(dataString) {
    if (!dataString) return '';
    const partes = dataString.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function processarOFX() {
    const conta = document.getElementById('conta_selecionada').value;
    const fileInput = document.getElementById('arquivo_ofx');
    
    if(!conta) return alert("Por favor, selecione a conta bancária primeiro!");
    if(!fileInput.files.length) return alert("Por favor, selecione um ficheiro OFX válido!");
    
    document.getElementById('lista_ofx').innerHTML = "<div style='text-align:center; padding: 40px;'>⏳ Lendo arquivo...</div>";
    
    const fd = new FormData();
    fd.append('action', 'ler_ofx'); fd.append('id_conta', conta); fd.append('arquivo_ofx', fileInput.files[0]);

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.ignoradas > 0) {
                    alert(`✅ Arquivo processado!\n\n${data.ignoradas} transações foram ocultadas pois você já as tinha conciliado em importações anteriores.`);
                }
                renderizarListas(data);
            } else { alert("Erro: " + data.message); }
        });
}

function renderizarListas(dados) {
    dadosConciliacao = dados;
    const listaOfx = document.getElementById('lista_ofx');
    const listaSis = document.getElementById('lista_sistema');
    listaOfx.innerHTML = ''; listaSis.innerHTML = '';

    // Painel de Ações em Lote (Botão e Selecionar Todos)
    if (dados.ofx_pendentes.length > 0) {
        listaOfx.innerHTML += `
            <div style="background: #e9ecef; padding: 10px; border-radius: 6px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <label style="cursor: pointer; font-weight: bold; font-size: 13px;">
                    <input type="checkbox" onchange="selecionarTodosLote(this)"> Sel. Todos
                </label>
                <button onclick="abrirModalLote()" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: #007bff;">➕ Adicionar Lote ao Caixa</button>
            </div>
        `;
    }

    dados.matches.forEach((m, i) => listaOfx.innerHTML += criarCartaoOFX(m.ofx, 'match', i));
    dados.matches.forEach((m, i) => listaSis.innerHTML += criarCartaoSistema(m.sistema, 'match', i));
    dados.ofx_pendentes.forEach((ofx, i) => listaOfx.innerHTML += criarCartaoOFX(ofx, 'pendente', i));
    dados.sistema_pendentes.forEach((sis, i) => listaSis.innerHTML += criarCartaoSistema(sis, 'pendente', i));
}

function criarCartaoOFX(trx, status, index) {
    const cor = trx.tipo === 'Entrada' ? 'positivo' : 'negativo';
    const icone = trx.tipo === 'Entrada' ? '⬇️' : '⬆️';
    let botoes = status === 'match' ? 
        `<button class="btn-conciliar" onclick="confirmarMatch(${index})">✅ Confirmar Match</button>` : 
        `<div style="display:flex; gap:10px; margin-top:10px; align-items:center;">
            <button class="btn-conciliar" style="background:#004C99; flex:1;" onclick="adicionarNova(${index})">➕ Add Individual</button>
            <input type="checkbox" class="check-lote-ofx" value="${index}" style="width:20px; height:20px; cursor:pointer;" title="Selecionar para Lote">
         </div>`;

    return `<div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="ofx-${status}-${index}">
                <div class="t-row"><span class="t-data">📅 ${formatarData(trx.data)}</span><span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span></div>
                <div class="t-desc">${trx.descricao}</div>
                ${botoes}
            </div>`;
}

function criarCartaoSistema(trx, status, index) { /* Código original mantido */
    const cor = trx.tipo === 'Entrada' ? 'positivo' : 'negativo';
    const icone = trx.tipo === 'Entrada' ? '⬇️' : '⬆️';
    return `<div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="sis-${status}-${index}">
                <div class="t-row"><span class="t-data">📅 ${formatarData(trx.data_movimento)}</span><span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span></div>
                <div class="t-desc">${trx.descricao}</div>
            </div>`;
}

function confirmarMatch(index) {
    const matchDados = dadosConciliacao.matches[index];
    const fd = new FormData();
    fd.append('action', 'confirmar_match'); fd.append('id_caixa', matchDados.sistema.id); fd.append('fitid', matchDados.ofx.fitid);
    fetch('conciliacao_actions.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
        if(data.status === 'success') {
            document.getElementById(`ofx-match-${index}`).style.display = 'none';
            document.getElementById(`sis-match-${index}`).style.display = 'none';
        }
    });
}

// LÓGICA DE LOTE 
function selecionarTodosLote(checkbox) {
    document.querySelectorAll('.check-lote-ofx').forEach(cb => cb.checked = checkbox.checked);
}

function abrirModalLote() {
    const selecionados = Array.from(document.querySelectorAll('.check-lote-ofx:checked')).map(cb => cb.value);
    if(selecionados.length === 0) return alert("Selecione pelo menos 1 transação nas caixinhas!");
    
    // Usamos o mesmo modal, mas sinalizamos que é lote
    document.getElementById('modal_tx_index').value = 'LOTE:' + selecionados.join(',');
    document.getElementById('modal_tx_desc').innerText = `LOTE: ${selecionados.length} transações selecionadas para inserção.`;
    document.getElementById('modalCategoria').style.display = 'flex';
}

function adicionarNova(index) {
    document.getElementById('modal_tx_index').value = index;
    document.getElementById('modal_tx_desc').innerText = dadosConciliacao.ofx_pendentes[index].descricao;
    document.getElementById('modalCategoria').style.display = 'flex';
}

function fecharModalCategoria() { document.getElementById('modalCategoria').style.display = 'none'; }

function salvarNovaTransacao(e) {
    e.preventDefault();
    const indexRaw = document.getElementById('modal_tx_index').value;
    const idCategoria = document.getElementById('modal_id_categoria').value;
    const idConta = document.getElementById('conta_selecionada').value;
    const fd = new FormData();

    if(indexRaw.startsWith('LOTE:')) {
        // MODO LOTE
        const indices = indexRaw.replace('LOTE:', '').split(',');
        const transacoesParaLote = indices.map(i => dadosConciliacao.ofx_pendentes[i]);
        
        fd.append('action', 'adicionar_ofx_lote');
        fd.append('id_conta', idConta);
        fd.append('id_categoria', idCategoria);
        fd.append('transacoes', JSON.stringify(transacoesParaLote));

        fetch('conciliacao_actions.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                indices.forEach(i => document.getElementById(`ofx-pendente-${i}`).style.display = 'none');
                fecharModalCategoria();
            }
        });
    } else {
        // MODO INDIVIDUAL
        const ofxDados = dadosConciliacao.ofx_pendentes[indexRaw];
        fd.append('action', 'adicionar_ofx'); fd.append('id_conta', idConta); fd.append('id_categoria', idCategoria);
        fd.append('fitid', ofxDados.fitid); fd.append('data', ofxDados.data); fd.append('valor', ofxDados.valor);
        fd.append('tipo', ofxDados.tipo); fd.append('descricao', ofxDados.descricao);
        fetch('conciliacao_actions.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                document.getElementById(`ofx-pendente-${indexRaw}`).style.display = 'none';
                fecharModalCategoria();
            }
        });
    }
}