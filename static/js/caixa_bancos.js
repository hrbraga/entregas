let movimentacoesGlobais = []; 
let paginaAtual = 1;
let limitePorPagina = 25;
let tomSelectCategoria = null;
let backupCategoriasHTML = '';

// 1. FORMATAÇÃO DA MOEDA EM TEMPO REAL
function formatarValorMonetario(input) {
    let valor = input.value.replace(/\D/g, ''); 
    if (valor === '') valor = '0';
    input.value = (parseInt(valor, 10) / 100).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// 2. FILTRO DE CATEGORIAS + TOMSELECT (RESOLVENDO O CONFLITO DE NOMES DO BANCO)
function filtrarCategorias() {
    const tipoSelecionado = document.getElementById('novoTipo').value; 
    const select = document.getElementById('novaCategoria'); 

    if (!select) return; 

    // Se é a primeira vez, salva todo o HTML original das categorias
    if (!backupCategoriasHTML) {
        backupCategoriasHTML = select.innerHTML;
    }

    // Destrói o TomSelect se já existir para podermos manipular o HTML nativo
    if (tomSelectCategoria) {
        tomSelectCategoria.destroy();
        tomSelectCategoria = null;
    }

    // Restaura o HTML completo com todos os optgroups
    select.innerHTML = backupCategoriasHTML;

    // Se um tipo foi selecionado no modal, filtramos com base nas equivalências do Banco de Dados
    if (tipoSelecionado) {
        const grupos = select.querySelectorAll('optgroup');
        grupos.forEach(grupo => {
            // Pega o nome do tipo que veio do banco (ex: "Receita", "Despesa") e converte para minúsculas
            const tipoBanco = (grupo.getAttribute('data-tipo') || '').toLowerCase().trim();
            
            let manter = false;

            // Regra de equivalência: Se escolheu "Entrada", aceita "entrada" ou "receita"
            if (tipoSelecionado === 'Entrada' && (tipoBanco === 'entrada' || tipoBanco === 'receita')) {
                manter = true;
            }
            
            // Regra de equivalência: Se escolheu "Saida", aceita "saida", "saída" ou "despesa"
            if (tipoSelecionado === 'Saida' && (tipoBanco === 'saida' || tipoBanco === 'saída' || tipoBanco === 'despesa')) {
                manter = true;
            }

            // Se não bater com a regra, remove o grupo visualmente
            if (!manter) {
                grupo.remove(); 
            }
        });
    }

    select.value = ''; // Limpa a escolha anterior para não bugar o envio

    // Inicializa o TomSelect limpo com as opções filtradas
    tomSelectCategoria = new TomSelect('#novaCategoria', {
        create: false,
        placeholder: 'Pesquisar ou selecionar categoria...',
        maxOptions: 500
    });
}
function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatarData(dataIso) {
    if (!dataIso) return '';
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

        if (dados.movimentacoes.length === 0) {
            movimentacoesGlobais = [];
            corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada neste período.</td></tr>';
            atualizarControlesPaginacao();
            return;
        }

        // PRÉ-CÁLCULO DOS SALDOS: Calculamos tudo antes de fatiar as páginas
        let saldoAtualLinha = parseFloat(dados.saldo_inicial); 
        dados.movimentacoes.forEach(mov => {
            const valorFloat = parseFloat(mov.valor);
            if (mov.tipo === 'Entrada') { saldoAtualLinha += valorFloat; } else { saldoAtualLinha -= valorFloat; }
            mov.saldoCalculadoDaLinha = saldoAtualLinha; // Guarda o saldo no objeto
        });

        movimentacoesGlobais = dados.movimentacoes; 
        paginaAtual = 1; // Ao buscar novos dados, sempre volta para a página 1
        
        renderizarTabela(); // Renderiza a página atual

    } catch (erro) { 
        corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; color: red;">Erro ao carregar os dados.</td></tr>'; 
    }
}

// NOVA FUNÇÃO: Renderiza apenas a quantidade estabelecida pela paginação
function renderizarTabela() {
    const corpoTabela = document.getElementById('corpoTabelaCaixa');
    let htmlTabela = '';

    const totalPaginas = Math.ceil(movimentacoesGlobais.length / limitePorPagina);
    if (paginaAtual > totalPaginas) paginaAtual = totalPaginas;
    if (paginaAtual < 1) paginaAtual = 1;

    // Fatiando os dados
    const inicio = (paginaAtual - 1) * limitePorPagina;
    const fim = inicio + limitePorPagina;
    const movsPagina = movimentacoesGlobais.slice(inicio, fim);

    if (movsPagina.length === 0) {
        corpoTabela.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada.</td></tr>';
    } else {
        movsPagina.forEach(mov => {
            const valorFloat = parseFloat(mov.valor);
            const classeValor = mov.tipo === 'Entrada' ? 'valor-entrada' : 'valor-saida';
            const corSaldo = mov.saldoCalculadoDaLinha >= 0 ? '#1565c0' : '#c62828';
            const sinal = mov.tipo === 'Entrada' ? '+' : '-';
            const nomeCategoria = mov.categoria_nome || '<span style="color:#999">Sem Categoria</span>';
            const nomeBanco = mov.banco_nome ? `<br><small style="color: #6c757d; font-weight: 500;">🏦 ${mov.banco_nome}</small>` : '';

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
                    <td style="font-weight: bold; color: ${corSaldo}">${formatarMoeda(mov.saldoCalculadoDaLinha)}</td>
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
    }

    atualizarControlesPaginacao(totalPaginas);
}

// NOVA FUNÇÃO: Atualiza os botões do rodapé (Habilita/Desabilita)
function atualizarControlesPaginacao(totalPaginas = 1) {
    const texto = document.getElementById('textoPaginacao');
    const btnPrim = document.getElementById('btnPagPrimeira');
    const btnAnt = document.getElementById('btnPagAnterior');
    const btnProx = document.getElementById('btnPagProxima');
    const btnUlt = document.getElementById('btnPagUltima');

    if (!texto) return;

    if (movimentacoesGlobais.length === 0) {
        texto.innerText = `Página 0 de 0`;
        btnPrim.disabled = true; btnAnt.disabled = true; btnProx.disabled = true; btnUlt.disabled = true;
        return;
    }

    texto.innerText = `Página ${paginaAtual} de ${totalPaginas}`;
    
    // Desabilita botões se tiver na primeira ou última página
    btnPrim.disabled = (paginaAtual === 1);
    btnAnt.disabled = (paginaAtual === 1);
    btnProx.disabled = (paginaAtual === totalPaginas);
    btnUlt.disabled = (paginaAtual === totalPaginas);
}

// NOVA FUNÇÃO: Responde aos cliques dos botões de Próxima/Anterior
function mudarPagina(acao) {
    const totalPaginas = Math.ceil(movimentacoesGlobais.length / limitePorPagina);
    
    if (acao === 'primeira') paginaAtual = 1;
    else if (acao === 'anterior' && paginaAtual > 1) paginaAtual--;
    else if (acao === 'proxima' && paginaAtual < totalPaginas) paginaAtual++;
    else if (acao === 'ultima') paginaAtual = totalPaginas;

    renderizarTabela();
}

// NOVA FUNÇÃO: Responde quando o usuário escolhe 25, 50 ou 100 itens no select
function mudarLimite(novoLimite) {
    limitePorPagina = parseInt(novoLimite);
    paginaAtual = 1; // Volta pra 1ª página pra não bugar caso o cálculo reduza o total de páginas
    renderizarTabela();
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
    
    // Corrigido para novoTipo
    const selectTipo = document.getElementById('novoTipo');
    if (selectTipo) {
        selectTipo.disabled = false;
        selectTipo.style.backgroundColor = "";
        selectTipo.value = ""; 
    }
    
    document.getElementById('novaData').style.backgroundColor = "";
    document.getElementById('novoValor').style.backgroundColor = "";
    document.getElementById('novaDescricao').style.backgroundColor = "";
    document.getElementById('tituloModalLancamento').innerText = "Novo Lançamento Manual";
    
    // Reseta e inicia o filtro de categorias
    filtrarCategorias();
    
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

function abrirModalTransferencia() {
    document.getElementById('formTransferencia').reset();
    document.getElementById('transfData').valueAsDate = new Date();
    
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
    
    // Corrigido para novoTipo
    const selectTipo = document.getElementById('novoTipo');
    if (selectTipo) {
        selectTipo.value = mov.tipo;
    }
    
    // Formata o valor corretamente para o padrão 0.000,00 na edição
    let valorEdit = parseFloat(mov.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('novoValor').value = valorEdit;
    
    document.getElementById('novaDescricao').value = mov.descricao;
    
    // Filtra as categorias de acordo com o tipo (Entrada/Saída) da movimentação
    filtrarCategorias();
    
    // Define a categoria correta usando a API do TomSelect no ID correto
    if (tomSelectCategoria && mov.id_categoria) {
        tomSelectCategoria.setValue(mov.id_categoria);
    }
    
    const isImportado = (mov.origem === 'Importacao');
    
    document.getElementById('novaData').readOnly = isImportado;
    document.getElementById('novoValor').readOnly = isImportado;
    document.getElementById('novaDescricao').readOnly = isImportado;
    
    if (selectTipo) {
        selectTipo.disabled = isImportado;
        selectTipo.style.backgroundColor = isImportado ? "#e9ecef" : "";
    }
    
    const corBloqueado = isImportado ? "#e9ecef" : "";
    document.getElementById('novaData').style.backgroundColor = corBloqueado;
    document.getElementById('novoValor').style.backgroundColor = corBloqueado;
    document.getElementById('novaDescricao').style.backgroundColor = corBloqueado;

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