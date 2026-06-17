let dadosConciliacao = { matches: [], ofx_pendentes: [], sistema_pendentes: [] };
let valorSelecionadoOFX = 0;
let fitidsSelecionados = [];

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatarData(dataString) {
    if (!dataString) return '';
    const partes = dataString.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function processarOFX() {
    const conta = document.getElementById('conta_selecionada').value;
    const fileInput = document.getElementById('arquivo_ofx');

    if (!conta) return alert("Por favor, selecione a conta bancária primeiro!");
    if (!fileInput.files.length) return alert("Por favor, selecione um ficheiro OFX válido!");

    document.getElementById('lista_ofx').innerHTML = "<div style='text-align:center; padding: 40px;'>⏳ Lendo arquivo...</div>";

    const fd = new FormData();
    fd.append('action', 'ler_ofx');
    fd.append('id_conta', conta);
    fd.append('arquivo_ofx', fileInput.files[0]);

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.ignoradas > 0) {
                    alert(`✅ Arquivo processado!\n\n${data.ignoradas} transações foram ocultadas pois você já as tinha conciliado em importações anteriores.`);
                }
                renderizarListas(data);
                // Limpa variáveis globais a cada novo processamento
                valorSelecionadoOFX = 0;
                fitidsSelecionados = [];
            } else { alert("Erro: " + data.message); }
        });
}

function renderizarListas(dados) {
    dadosConciliacao = dados;
    const listaOfx = document.getElementById('lista_ofx');
    const listaSis = document.getElementById('lista_sistema');
    listaOfx.innerHTML = ''; listaSis.innerHTML = '';

    // Painel de Ações em Lote (Botão, Selecionar Todos e Soma)
    if (dados.ofx_pendentes.length > 0) {
        listaOfx.innerHTML += `
            <div style="background: #e9ecef; padding: 10px; border-radius: 6px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <label style="cursor: pointer; font-weight: bold; font-size: 13px;">
                    <input type="checkbox" onchange="selecionarTodosLote(this)"> Sel. Todos
                </label>
                <span id="soma-selecionada" style="font-size: 13px; color: #004C99;"></span>
                <button onclick="abrirModalLote()" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: #007bff;">➕ Add Lote ao Caixa</button>
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

    // Adicionado o evento onchange="calcularSomaOFX()" ao checkbox
    let botoes = status === 'match' ?
        `<button class="btn-conciliar" onclick="confirmarMatch(${index})">✅ Confirmar Match</button>` :
        `<div style="display:flex; gap:10px; margin-top:10px; align-items:center;">
            <button class="btn-conciliar" style="background:#004C99; flex:1;" onclick="adicionarNova(${index})">➕ Add Individual</button>
            <input type="checkbox" class="check-lote-ofx" value="${index}" onchange="calcularSomaOFX()" style="width:20px; height:20px; cursor:pointer;" title="Selecionar para Lote ou Conciliação">
         </div>`;

    return `<div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="ofx-${status}-${index}">
                <div class="t-row"><span class="t-data">📅 ${formatarData(trx.data)}</span><span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span></div>
                <div class="t-desc">${trx.descricao}</div>
                ${botoes}
            </div>`;
}

function criarCartaoSistema(trx, status, index) {
    const cor = trx.tipo === 'Entrada' ? 'positivo' : 'negativo';
    const icone = trx.tipo === 'Entrada' ? '⬇️' : '⬆️';
    let botoes = '';

    if (status === 'pendente') {
        // Trocamos o index pelo 'this' para passar o próprio elemento do botão
        botoes = `<div style="margin-top:10px;">
                    <button class="btn-conciliar" style="background:#28a745; width:100%;" onclick="fazerMatchMultiplo(${trx.id}, ${trx.valor}, this)">🔗 Conciliar OFX Selecionados</button>
                  </div>`;
    }

    return `<div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="sis-${status}-${index}">
                <div class="t-row"><span class="t-data">📅 ${formatarData(trx.data_movimento)}</span><span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span></div>
                <div class="t-desc">${trx.descricao}</div>
                ${botoes}
            </div>`;
}

// LÓGICA DE SOMA E MATCH MÚLTIPLO (N-para-1)
function calcularSomaOFX() {
    valorSelecionadoOFX = 0;
    fitidsSelecionados = [];

    const checkboxes = document.querySelectorAll('.check-lote-ofx:checked');

    checkboxes.forEach(cb => {
        const index = cb.value;
        const ofx = dadosConciliacao.ofx_pendentes[index];
        valorSelecionadoOFX += parseFloat(ofx.valor);
        fitidsSelecionados.push(ofx.fitid);
    });

    const divSoma = document.getElementById('soma-selecionada');
    if (divSoma) {
        divSoma.innerHTML = checkboxes.length > 0 ? `Soma: <strong>${formatarMoeda(valorSelecionadoOFX)}</strong>` : '';
    }
}

function fazerMatchMultiplo(id_caixa, valor_sistema, botaoClicado) {
    if (fitidsSelecionados.length === 0) {
        return alert("Por favor, selecione os lançamentos do banco (OFX) primeiro.");
    }

    const somaVal = Math.abs(valorSelecionadoOFX).toFixed(2);
    const sisVal = Math.abs(parseFloat(valor_sistema)).toFixed(2);

    if (somaVal !== sisVal) {
        return alert(`A soma do banco (${formatarMoeda(valorSelecionadoOFX)}) não bate com o valor do sistema (${formatarMoeda(valor_sistema)}).`);
    }

    const fd = new FormData();
    fd.append('action', 'confirmar_match_multiplo');
    fd.append('id_caixa', id_caixa);
    fd.append('fitids', JSON.stringify(fitidsSelecionados));

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {

                // 1. Oculta o lançamento único do Sistema de forma garantida (usando o botão)
                if (botaoClicado) {
                    const cartaoSistema = botaoClicado.closest('.t-card');
                    if (cartaoSistema) cartaoSistema.style.display = 'none';
                }

                // 2. Oculta todos os lançamentos do OFX que estavam marcados e DESMARCA o checkbox
                const checkboxes = document.querySelectorAll('.check-lote-ofx:checked');
                checkboxes.forEach(cb => {
                    const indexOfx = cb.value;
                    const cardOfx = document.getElementById(`ofx-pendente-${indexOfx}`);
                    if (cardOfx) cardOfx.style.display = 'none';

                    cb.checked = false; // <-- ESTA LINHA CORRIGE O PROBLEMA
                });
                // 3. Limpa a seleção e zera a soma visual
                valorSelecionadoOFX = 0;
                fitidsSelecionados = [];
                const divSoma = document.getElementById('soma-selecionada');
                if (divSoma) divSoma.innerHTML = '';

                // 4. Desmarca a caixinha de "Selecionar Todos" se estiver ativa
                const chkTodos = document.querySelector('input[onchange="selecionarTodosLote(this)"]');
                if (chkTodos) chkTodos.checked = false;

            } else {
                alert('Erro: ' + data.message);
            }
        });
}

// LÓGICA ORIGINAL DE MATCH 1-PARA-1
function confirmarMatch(index) {
    const matchDados = dadosConciliacao.matches[index];
    const fd = new FormData();
    fd.append('action', 'confirmar_match');
    fd.append('id_caixa', matchDados.sistema.id);
    fd.append('fitid', matchDados.ofx.fitid);

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById(`ofx-match-${index}`).style.display = 'none';
                document.getElementById(`sis-match-${index}`).style.display = 'none';
            }
        });
}

// LÓGICA DE LOTE E ADIÇÃO 
function selecionarTodosLote(checkbox) {
    document.querySelectorAll('.check-lote-ofx').forEach(cb => cb.checked = checkbox.checked);
    calcularSomaOFX(); // Recalcula a soma ao selecionar/desmarcar todos
}

function abrirModalLote() {
    const selecionados = Array.from(document.querySelectorAll('.check-lote-ofx:checked')).map(cb => cb.value);
    if (selecionados.length === 0) return alert("Selecione pelo menos 1 transação nas caixinhas!");

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

    if (indexRaw.startsWith('LOTE:')) {
        // MODO LOTE
        const indices = indexRaw.replace('LOTE:', '').split(',');
        const transacoesParaLote = indices.map(i => dadosConciliacao.ofx_pendentes[i]);

        fd.append('action', 'adicionar_ofx_lote');
        fd.append('id_conta', idConta);
        fd.append('id_categoria', idCategoria);
        fd.append('transacoes', JSON.stringify(transacoesParaLote));

        fetch('conciliacao_actions.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                indices.forEach(i => {
                    const card = document.getElementById(`ofx-pendente-${i}`);
                    if (card) card.style.display = 'none';

                    // Encontra e desmarca o checkbox oculto
                    const chk = document.querySelector(`.check-lote-ofx[value="${i}"]`);
                    if (chk) chk.checked = false;
                });

                fecharModalCategoria();
                calcularSomaOFX(); // Agora vai somar zero corretamente
            }
        });
    } else {
        // MODO INDIVIDUAL
        const ofxDados = dadosConciliacao.ofx_pendentes[indexRaw];
        fd.append('action', 'adicionar_ofx');
        fd.append('id_conta', idConta);
        fd.append('id_categoria', idCategoria);
        fd.append('fitid', ofxDados.fitid);
        fd.append('data', ofxDados.data);
        fd.append('valor', ofxDados.valor);
        fd.append('tipo', ofxDados.tipo);
        fd.append('descricao', ofxDados.descricao);

        fetch('conciliacao_actions.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                document.getElementById(`ofx-pendente-${indexRaw}`).style.display = 'none';
                fecharModalCategoria();
            }
        });
    }
}