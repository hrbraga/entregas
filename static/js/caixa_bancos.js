let movimentacoesGlobais = []; 

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatarData(dataIso) {
    const partes = dataIso.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function toggleMenuAcoes(event, idMovimento) {
    event.stopPropagation();
    let menus = document.getElementsByClassName("dropdown-content");
    for (let i = 0; i < menus.length; i++) {
        if (menus[i].id !== `menu-${idMovimento}`) {
            menus[i].classList.remove('dropdown-show');
        }
    }
    document.getElementById(`menu-${idMovimento}`).classList.toggle("dropdown-show");
}

window.onclick = function(event) {
    let modalLancamento = document.getElementById('modalLancamento');
    let modalImportacao = document.getElementById('modalImportacao');
    let modalTransferencia = document.getElementById('modalTransferencia');
    
    if (event.target == modalLancamento || event.target == modalImportacao || event.target == modalTransferencia) {
        fecharModais();
    }

    if (!event.target.matches('.btn-dots')) {
        let dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('dropdown-show')) {
                dropdowns[i].classList.remove('dropdown-show');
            }
        }
    }
}

async function carregarMovimentacoes() {
    const conta = document.getElementById('filtroConta').value;
    const dataInicio = document.getElementById('filtroDataInicio').value;
    const dataFim = document.getElementById('filtroDataFim').value;
    const tipo = document.getElementById('filtroTipo').value;
    const corpoTabela = document.getElementById('corpoTabelaCaixa');

    corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Carregando dados...</td></tr>';

    try {
        const url = `caixa_bancos_actions.php?acao=listar&conta=${conta}&dataInicio=${dataInicio}&dataFim=${dataFim}&tipo=${tipo}`;
        const resposta = await fetch(url);
        const dados = await resposta.json();

        if (dados.erro) { alert(dados.erro); return; }

        document.getElementById('cardSaldoInicial').textContent = formatarMoeda(dados.saldo_inicial);
        document.getElementById('cardEntradas').textContent = formatarMoeda(dados.entradas);
        document.getElementById('cardSaidas').textContent = formatarMoeda(dados.saidas);
        document.getElementById('cardSaldoFinal').textContent = formatarMoeda(dados.saldo_final);

        movimentacoesGlobais = dados.movimentacoes; 

        if (dados.movimentacoes.length === 0) {
            corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada neste período.</td></tr>';
            return;
        }

        let htmlTabela = '';
        let saldoAtualLinha = parseFloat(dados.saldo_inicial); 

        dados.movimentacoes.forEach(mov => {
            const valorFloat = parseFloat(mov.valor);
            
            if (mov.tipo === 'Entrada') { saldoAtualLinha += valorFloat; } else { saldoAtualLinha -= valorFloat; }

            const classeValor = mov.tipo === 'Entrada' ? 'valor-entrada' : 'valor-saida';
            const corSaldo = saldoAtualLinha >= 0 ? '#1565c0' : '#c62828';
            const sinal = mov.tipo === 'Entrada' ? '+' : '-';
            const nomeCategoria = mov.categoria_nome || '<span style="color:#999">Sem Categoria</span>';
            const nomeBanco = mov.banco_nome ? `<br><small style="color: #6c757d; font-weight: 500;">🏦 ${mov.banco_nome}</small>` : '';

            // Cor de crachá especial para transferências
            let badgeClass = 'bg-info';
            if(mov.origem === 'Manual') badgeClass = 'bg-secondary';
            if(mov.origem === 'Transferencia') badgeClass = 'bg-primary';

            htmlTabela += `
                <tr>
                    <td>${formatarData(mov.data_movimento)}</td>
                    <td>${mov.descricao} ${nomeBanco}</td>
                    <td>${nomeCategoria}</td>
                    <td><span class="badge ${badgeClass}" style="background-color: ${mov.origem === 'Transferencia' ? '#17a2b8' : ''}">${mov.origem}</span></td>
                    <td>${mov.tipo}</td>
                    <td class="${classeValor}">${sinal} ${formatarMoeda(valorFloat)}</td>
                    <td style="font-weight: bold; color: ${corSaldo}">${formatarMoeda(saldoAtualLinha)}</td>
                    <td style="text-align: center;">
                        <div class="dropdown-acoes">
                            <button onclick="toggleMenuAcoes(event, ${mov.id})" class="btn-dots">⋮</button>
                            <div id="menu-${mov.id}" class="dropdown-content">
                                <a href="#" onclick="editarLancamento(${mov.id}); return false;">✏️ Editar / Classificar</a>
                                <a href="#" onclick="excluirLancamento(${mov.id}); return false;" class="text-danger">🗑️ Excluir</a>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });
        corpoTabela.innerHTML = htmlTabela;
    } catch (erro) { corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Erro ao carregar os dados.</td></tr>'; }
}

function abrirModalLancamento() {
    const filtroConta = document.getElementById('filtroConta').value;
    if (filtroConta === 'todas') {
        alert("⚠️ Você está na Visão Geral. Selecione uma Conta específica (ex: Itaú, Caixa Loja) no filtro para poder fazer um novo lançamento.");
        return;
    }
    document.getElementById('formLancamento').reset();
    document.getElementById('idMovimentoEdicao').value = ""; 
    document.getElementById('contaSelecionadaOculta').value = filtroConta; 
    document.getElementById('novaData').valueAsDate = new Date();
    
    document.getElementById('novaData').readOnly = false;
    document.getElementById('novoValor').readOnly = false;
    document.getElementById('novaDescricao').readOnly = false;
    document.getElementById('novoTipo').disabled = false;
    document.getElementById('novaData').style.backgroundColor = "";
    document.getElementById('novoValor').style.backgroundColor = "";
    document.getElementById('novaDescricao').style.backgroundColor = "";
    document.getElementById('novoTipo').style.backgroundColor = "";
    document.getElementById('tituloModalLancamento').innerText = "Novo Lançamento Manual";
    
    document.getElementById('modalLancamento').style.display = 'block';
}

function abrirModalImportacao() {
    const filtroConta = document.getElementById('filtroConta').value;
    if (filtroConta === 'todas') {
        alert("⚠️ Encontra-se na Visão Geral. Selecione uma Conta bancária específica no filtro lá em cima para importar o extrato.");
        return;
    }
    document.getElementById('formImportacao').reset();
    document.getElementById('modalImportacao').style.display = 'block';
}

// NOVA FUNÇÃO DO MODAL DE TRANSFERÊNCIA
function abrirModalTransferencia() {
    document.getElementById('formTransferencia').reset();
    document.getElementById('transfData').valueAsDate = new Date();
    
    // Se o usuário estiver com uma conta selecionada, já preenche a Origem automaticamente
    const filtroConta = document.getElementById('filtroConta').value;
    if(filtroConta !== 'todas') {
        document.getElementById('transfOrigem').value = filtroConta;
    }
    
    document.getElementById('modalTransferencia').style.display = 'block';
}

function fecharModais() {
    document.getElementById('modalLancamento').style.display = 'none';
    document.getElementById('modalImportacao').style.display = 'none';
    document.getElementById('modalTransferencia').style.display = 'none';
}

async function salvarLancamento(event) {
    event.preventDefault(); 
    const formData = new FormData();
    formData.append('acao', 'salvar_lancamento');
    formData.append('id_movimento', document.getElementById('idMovimentoEdicao').value); 
    formData.append('id_conta', document.getElementById('contaSelecionadaOculta').value);
    formData.append('data', document.getElementById('novaData').value);
    formData.append('tipo', document.getElementById('novoTipo').value);
    formData.append('valor', document.getElementById('novoValor').value);
    formData.append('descricao', document.getElementById('novaDescricao').value);
    formData.append('categoria', document.getElementById('novaCategoria').value);

    try {
        const resposta = await fetch('caixa_bancos_actions.php', { method: 'POST', body: formData });
        const dados = await resposta.json();
        if (dados.erro) { alert(dados.erro); } else {
            fecharModais();
            carregarMovimentacoes();
        }
    } catch (erro) { alert("Erro ao tentar guardar o lançamento."); }
}

// NOVA FUNÇÃO: SALVAR TRANSFERÊNCIA
async function salvarTransferencia(event) {
    event.preventDefault();
    
    const origem = document.getElementById('transfOrigem').value;
    const destino = document.getElementById('transfDestino').value;
    const valor = document.getElementById('transfValor').value;
    
    if(origem === destino) {
        alert("⚠️ A conta de origem não pode ser a mesma de destino!");
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'transferencia');
    formData.append('id_origem', origem);
    formData.append('id_destino', destino);
    formData.append('data', document.getElementById('transfData').value);
    formData.append('valor', valor);
    formData.append('obs', document.getElementById('transfObs').value);

    try {
        const resposta = await fetch('caixa_bancos_actions.php', { method: 'POST', body: formData });
        const dados = await resposta.json();
        
        if (dados.erro) { 
            alert(dados.erro); 
        } else {
            alert(`✅ ${dados.mensagem}`);
            fecharModais();
            carregarMovimentacoes();
        }
    } catch (erro) { alert("Erro ao tentar transferir."); }
}

function editarLancamento(id) {
    document.getElementById(`menu-${id}`).classList.remove('dropdown-show');
    const mov = movimentacoesGlobais.find(m => m.id == id);
    
    if (mov.origem !== 'Manual' && mov.origem !== 'Importacao') {
        alert(`❌ Acesso Negado: Este lançamento veio de "${mov.origem}". Para não quebrar os históricos, não é permitido editar uma transferência por aqui.`);
        return;
    }

    document.getElementById('idMovimentoEdicao').value = mov.id;
    document.getElementById('contaSelecionadaOculta').value = mov.id_conta; 
    document.getElementById('novaData').value = mov.data_movimento;
    document.getElementById('novoTipo').value = mov.tipo;
    document.getElementById('novoValor').value = mov.valor;
    document.getElementById('novaDescricao').value = mov.descricao;
    document.getElementById('novaCategoria').value = mov.id_categoria || "";
    
    const isImportado = (mov.origem === 'Importacao');
    
    document.getElementById('novaData').readOnly = isImportado;
    document.getElementById('novoValor').readOnly = isImportado;
    document.getElementById('novaDescricao').readOnly = isImportado;
    document.getElementById('novoTipo').disabled = isImportado; 
    
    const corBloqueado = isImportado ? "#e9ecef" : "";
    document.getElementById('novaData').style.backgroundColor = corBloqueado;
    document.getElementById('novoValor').style.backgroundColor = corBloqueado;
    document.getElementById('novaDescricao').style.backgroundColor = corBloqueado;
    document.getElementById('novoTipo').style.backgroundColor = corBloqueado;

    document.getElementById('tituloModalLancamento').innerText = isImportado ? "Classificar Importação" : "Editar Lançamento";
    document.getElementById('modalLancamento').style.display = 'block';
}

async function excluirLancamento(id) {
    document.getElementById(`menu-${id}`).classList.remove('dropdown-show');
    const mov = movimentacoesGlobais.find(m => m.id == id);
    
    if (mov.origem !== 'Manual' && mov.origem !== 'Importacao') {
        alert(`❌ Acesso Negado: Este lançamento veio de "${mov.origem}". Para reverter uma transferência, por favor exclua a movimentação, ou faça uma transferência inversa.`);
        return;
    }

    if(confirm(`Tem certeza que deseja apagar o lançamento "${mov.descricao}" no valor de R$ ${mov.valor}?`)) {
        const formData = new FormData();
        formData.append('acao', 'excluir');
        formData.append('id', id);

        try {
            const resposta = await fetch('caixa_bancos_actions.php', { method: 'POST', body: formData });
            const dados = await resposta.json();
            if (dados.erro) { alert(dados.erro); } else { carregarMovimentacoes(); }
        } catch (erro) { alert("Erro ao tentar excluir."); }
    }
}

async function processarImportacao(event) {
    event.preventDefault();
    const conta = document.getElementById('filtroConta').value;
    const arquivo = document.getElementById('arquivoExtrato').files[0];

    if (conta === 'todas') { alert("⚠️ Selecione uma Conta bancária específica."); return; }
    if (!arquivo) { alert("⚠️ Selecione um arquivo para importar."); return; }

    const formData = new FormData();
    formData.append('acao', 'importar_ofx');
    formData.append('id_conta', conta);
    formData.append('arquivo', arquivo);

    try {
        const resposta = await fetch('caixa_bancos_actions.php', { method: 'POST', body: formData });
        const dados = await resposta.json();
        
        if (dados.erro) { alert(dados.erro); } else {
            alert(`✅ ${dados.mensagem}`);
            fecharModais();
            carregarMovimentacoes(); 
        }
    } catch (erro) { alert("Erro de comunicação."); }
}

window.addEventListener('DOMContentLoaded', () => { carregarMovimentacoes(); });