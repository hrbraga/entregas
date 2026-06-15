// Variável global para armazenar os dados lidos do OFX
let dadosConciliacao = { matches: [], ofx_pendentes: [], sistema_pendentes: [] };

// Função para formatar moeda no padrão brasileiro
function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Função para formatar data (YYYY-MM-DD para DD/MM/YYYY)
function formatarData(dataString) {
    if (!dataString) return '';
    const partes = dataString.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

// 1. LER O FICHEIRO E ENVIAR PARA O PHP
function processarOFX() {
    const conta = document.getElementById('conta_selecionada').value;
    const fileInput = document.getElementById('arquivo_ofx');
    
    if(!conta) {
        alert("Por favor, selecione a conta bancária primeiro!");
        return;
    }
    if(!fileInput.files.length) {
        alert("Por favor, selecione um ficheiro OFX válido!");
        return;
    }
    
    // Indicadores de Loading
    document.getElementById('lista_ofx').innerHTML = "<div style='text-align:center; padding: 40px; font-weight: bold; color: #17a2b8;'>A processar ficheiro OFX... ⏳</div>";
    document.getElementById('lista_sistema').innerHTML = "<div style='text-align:center; padding: 40px; font-weight: bold; color: #28a745;'>A cruzar dados com o sistema... 🔍</div>";
    
    const fd = new FormData();
    fd.append('action', 'ler_ofx');
    fd.append('id_conta', conta);
    fd.append('arquivo_ofx', fileInput.files[0]);

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                renderizarListas(data);
            } else {
                alert("Erro ao ler ficheiro: " + data.message);
                document.getElementById('lista_ofx').innerHTML = "";
                document.getElementById('lista_sistema').innerHTML = "";
            }
        }).catch(err => {
            alert("Ocorreu um erro de comunicação.");
            console.error(err);
        });
}

// 2. DESENHAR OS CARTÕES NA TELA
function renderizarListas(dados) {
    dadosConciliacao = dados;

    const listaOfx = document.getElementById('lista_ofx');
    const listaSis = document.getElementById('lista_sistema');

    listaOfx.innerHTML = '';
    listaSis.innerHTML = '';

    // A. Renderizar Matches (Aparecem no topo destacados em verde)
    dados.matches.forEach((match, index) => {
        listaOfx.innerHTML += criarCartaoOFX(match.ofx, 'match', index);
        listaSis.innerHTML += criarCartaoSistema(match.sistema, 'match', index);
    });

    // B. Renderizar Pendentes OFX (O que sobrou do banco)
    dados.ofx_pendentes.forEach((ofx, index) => {
        listaOfx.innerHTML += criarCartaoOFX(ofx, 'pendente', index);
    });

    // C. Renderizar Pendentes Sistema (O que está no sistema mas não veio no banco)
    dados.sistema_pendentes.forEach((sis, index) => {
        listaSis.innerHTML += criarCartaoSistema(sis, 'pendente', index);
    });

    // Mensagens de vazio
    if (dados.matches.length === 0 && dados.ofx_pendentes.length === 0) {
        listaOfx.innerHTML = '<div style="text-align:center; padding:20px; color:#777;">Nenhuma transação encontrada no ficheiro.</div>';
    }
    if (dados.matches.length === 0 && dados.sistema_pendentes.length === 0) {
        listaSis.innerHTML = '<div style="text-align:center; padding:20px; color:#777;">Tudo limpo! Nenhuma transação pendente no sistema.</div>';
    }
}

// 3. CONSTRUTORES DE HTML (CARTÕES)
function criarCartaoOFX(trx, status, index) {
    const cor = trx.tipo === 'Entrada' ? 'positivo' : 'negativo';
    const icone = trx.tipo === 'Entrada' ? '⬇️' : '⬆️';
    let botoes = '';

    if (status === 'match') {
        botoes = `<button class="btn-conciliar" onclick="confirmarMatch(${index})">✅ Confirmar Match</button>`;
    } else {
        botoes = `<button class="btn-conciliar" style="background:#007bff;" onclick="adicionarNova(${index})">➕ Adicionar ao Caixa</button>`;
    }

    return `
        <div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="ofx-${status}-${index}">
            <div class="t-row">
                <span class="t-data">📅 ${formatarData(trx.data)}</span>
                <span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span>
            </div>
            <div class="t-desc">${trx.descricao}</div>
            <div style="font-size:10px; color:#999; margin-bottom: 8px;">ID: ${trx.fitid}</div>
            ${botoes}
        </div>
    `;
}

function criarCartaoSistema(trx, status, index) {
    const cor = trx.tipo === 'Entrada' ? 'positivo' : 'negativo';
    const icone = trx.tipo === 'Entrada' ? '⬇️' : '⬆️';
    const flag = status === 'match' ? '<span style="background:#28a745; color:white; padding:3px 6px; border-radius:3px; font-size:11px; float:right;">Correspondência Encontrada</span>' : '<span style="background:#ffc107; color:#333; padding:3px 6px; border-radius:3px; font-size:11px; float:right;">Aguardando Banco</span>';

    return `
        <div class="t-card ${trx.tipo.toLowerCase()} ${status}" id="sis-${status}-${index}">
            <div class="t-row">
                <span class="t-data">📅 ${formatarData(trx.data_movimento)}</span>
                <span class="t-valor ${cor}">${icone} ${formatarMoeda(trx.valor)}</span>
            </div>
            <div class="t-desc">${trx.descricao}</div>
            <div style="margin-top: 10px;">${flag}</div>
        </div>
    `;
}

// 4. AÇÕES DE CONCILIAÇÃO (A Ligar ao Backend)
function confirmarMatch(index) {
    const matchDados = dadosConciliacao.matches[index];
    const fd = new FormData();
    fd.append('action', 'confirmar_match');
    fd.append('id_caixa', matchDados.sistema.id);
    fd.append('fitid', matchDados.ofx.fitid);

    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // Esconde os dois cartões após o sucesso com uma animação suave
                document.getElementById(`ofx-match-${index}`).style.display = 'none';
                document.getElementById(`sis-match-${index}`).style.display = 'none';
            } else {
                alert(data.message);
            }
        });
}

// Abre o Modal com os dados da transação selecionada
function adicionarNova(index) {
    const ofxDados = dadosConciliacao.ofx_pendentes[index];
    
    // Preenche os dados visuais no Modal
    document.getElementById('modal_tx_index').value = index;
    document.getElementById('modal_tx_data').innerText = `📅 ${formatarData(ofxDados.data)}`;
    document.getElementById('modal_tx_desc').innerText = ofxDados.descricao;
    
    const cor = ofxDados.tipo === 'Entrada' ? '#28a745' : '#dc3545';
    const icone = ofxDados.tipo === 'Entrada' ? '⬇️' : '⬆️';
    document.getElementById('modal_tx_valor').innerHTML = `<span style="color: ${cor};">${icone} ${formatarMoeda(ofxDados.valor)}</span>`;
    
    // Abre o Modal
    document.getElementById('modalCategoria').style.display = 'flex';
}

function fecharModalCategoria() {
    document.getElementById('modalCategoria').style.display = 'none';
    
    // Limpa a seleção do Tom Select
    const select = document.getElementById('modal_id_categoria');
    if(select.tomselect) {
        select.tomselect.clear();
    }
}

// Disparado ao submeter o formulário do Modal
function salvarNovaTransacao(e) {
    e.preventDefault(); // Impede o ecrã de recarregar
    
    const index = document.getElementById('modal_tx_index').value;
    const idCategoria = document.getElementById('modal_id_categoria').value;
    const idConta = document.getElementById('conta_selecionada').value;
    const ofxDados = dadosConciliacao.ofx_pendentes[index];
    
    if(!idCategoria) {
        alert("Por favor, selecione uma categoria.");
        return;
    }

    // Prepara os dados para o PHP
    const fd = new FormData();
    fd.append('action', 'adicionar_ofx');
    fd.append('id_conta', idConta);
    fd.append('fitid', ofxDados.fitid);
    fd.append('data', ofxDados.data);
    fd.append('valor', ofxDados.valor);
    fd.append('tipo', ofxDados.tipo);
    fd.append('descricao', ofxDados.descricao);
    fd.append('id_categoria', idCategoria);

    // Envia o pedido e processa a resposta
    fetch('conciliacao_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // Esconde o cartão da lista e fecha o modal
                document.getElementById(`ofx-pendente-${index}`).style.display = 'none';
                fecharModalCategoria();
            } else {
                alert("Erro ao gravar: " + data.message);
            }
        });
}