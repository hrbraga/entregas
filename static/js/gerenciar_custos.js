// static/js/gerenciar_custos.js

// --- 1. LÓGICA DE CÁLCULO ORIGINAL (Adaptada) ---
const els = {
    qt: document.getElementById('qtCaixa'),
    valor: document.getElementById('valorUn'),
    preco: document.getElementById('preco'),
    st: document.getElementById('st'),
    ipi: document.getElementById('ipi'),
    txs: document.getElementById('txsAdicionais'),
    midia: document.getElementById('txMidia'),
    
    // Outputs
    roy: document.getElementById('royalties'),
    custoCx: document.getElementById('custoCaixa'),
    custoUn: document.getElementById('custoUn'),
    mbLiq: document.getElementById('mbLiquida'),
    mbBru: document.getElementById('mbBruta')
};

function calcular() {
    // Valores numéricos (padrão 0 se vazio)
    const qt = parseFloat(els.qt.value) || 0;
    const valor = parseFloat(els.valor.value) || 0;
    const preco = parseFloat(els.preco.value) || 0;
    const st = parseFloat(els.st.value) || 0;
    const ipi = parseFloat(els.ipi.value) || 0;
    const txs = parseFloat(els.txs.value) || 0;
    const midia = parseFloat(els.midia.value) || 0;

    // 1. Royalties: (valor * 50%)
    const royalties = valor * 0.50;
    els.roy.value = royalties.toFixed(2);

    // 2. Custo Caixa: Soma de tudo
    const custoCaixa = valor + royalties + st + ipi + txs + midia;
    els.custoCx.value = custoCaixa.toFixed(2);

    // 3. Custo Unidade: Custo Caixa / Qtd
    let custoUn = 0;
    if (qt > 0) {
        custoUn = custoCaixa / qt;
        els.custoUn.value = custoUn.toFixed(2);
    } else {
        els.custoUn.value = "0.00";
    }

    // 4. MB Bruta: 1 - (custo Un / preço)
    let mbBruta = 0;
    if (preco > 0) {
        mbBruta = (1 - (custoUn / preco)) * 100;
        els.mbBru.value = mbBruta.toFixed(2);
    } else {
        els.mbBru.value = "0.00";
    }

    // 5. MB Líquida: 1 - ((valor un + royalties) / qt caixa / preço)
    let mbLiquida = 0;
    if (preco > 0 && qt > 0) {
        const numerador = valor + royalties;
        const custoBaseUn = numerador / qt;
        mbLiquida = (1 - (custoBaseUn / preco)) * 100;
        els.mbLiq.value = mbLiquida.toFixed(2);
    } else {
        els.mbLiq.value = "0.00";
    }
}

// Adicionar Listeners nos inputs
const inputs = [els.qt, els.valor, els.preco, els.st, els.ipi, els.txs, els.midia];
inputs.forEach(input => {
    if(input) input.addEventListener('input', calcular);
});


// --- 2. NOVAS FUNCIONALIDADES (MODAIS E AÇÕES) ---

// Filtro da tabela
document.getElementById('buscaLocal').addEventListener('keyup', function() {
    const termo = this.value.toLowerCase();
    const linhas = document.querySelectorAll('#tabelaProdutos tbody tr');
    linhas.forEach(tr => {
        const texto = tr.getAttribute('data-search');
        tr.style.display = texto.includes(termo) ? '' : 'none';
    });
});

function abrirModalProduto() {
    document.getElementById('formCustos').reset();
    document.getElementById('prodId').value = ''; // ID vazio = Inserir
    document.getElementById('modalTitle').innerText = 'Adicionar Novo Produto';
    document.getElementById('modalProduto').style.display = 'flex';
    // Limpa outputs calculados
    calcular(); 
}

function fecharModalProduto() {
    document.getElementById('modalProduto').style.display = 'none';
}

function editarProduto(produto) {
    abrirModalProduto();
    document.getElementById('modalTitle').innerText = 'Editar Produto: ' + produto.codigo;
    
    // Preencher campos com dados do banco
    document.getElementById('prodId').value = produto.id;
    document.getElementById('campanha').value = produto.campanha;
    document.getElementById('codigo').value = produto.codigo;
    document.getElementById('descricao').value = produto.descricao;
    
    document.getElementById('qtCaixa').value = produto.qtCaixa;
    document.getElementById('valorUn').value = produto.valorUn;
    document.getElementById('preco').value = produto.preco;
    
    document.getElementById('st').value = produto.st;
    document.getElementById('ipi').value = produto.ipi;
    document.getElementById('txsAdicionais').value = produto.txsAdicionais;
    document.getElementById('txMidia').value = produto.txMidia;
    
    // Força o recálculo para preencher os campos readonly corretamente
    calcular();
}

function abrirModalImportacao() {
    document.getElementById('modalImport').style.display = 'flex';
}

async function salvarProduto(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formCustos'));
    
    try {
        const res = await fetch('custos_actions.php', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') {
            alert(json.message);
            location.reload();
        } else {
            alert('Erro: ' + json.message);
        }
    } catch (err) {
        console.error(err);
        alert('Erro de comunicação.');
    }
}

async function excluirProduto(id) {
    if(!confirm('Deseja realmente excluir este produto?')) return;
    
    const formData = new FormData();
    formData.append('action', 'excluir');
    formData.append('id', id);
    
    try {
        const res = await fetch('custos_actions.php', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') location.reload();
        else alert('Erro ao excluir.');
    } catch (err) {
        alert('Erro ao excluir.');
    }
}

// Importação Excel
function processarImportacao() {
    const fileInput = document.getElementById('arquivoImportacao');
    if (!fileInput.files.length) { alert("Selecione um arquivo!"); return; }

    const file = fileInput.files[0];
    const reader = new FileReader();
    document.getElementById('loading').style.display = 'block';

    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        const produtosFormatados = jsonData.map(row => {
            return {
                campanha: row['Campanha'] || 'Importado',
                codigo: row['Codigo'] || row['Código'] || '',
                descricao: row['Descricao'] || row['Descrição'] || '',
                qtCaixa: row['QtCaixa'] || row['Qtd'] || 0,
                valorUn: row['ValorCX'] || row['Valor'] || 0,
                preco: row['Preco'] || row['Preço'] || 0,
                // Opcionais
                st: row['ST'] || 0,
                ipi: row['IPI'] || 0,
                txsAdicionais: row['Taxas'] || 0,
                txMidia: row['Midia'] || 0
            };
        });

        enviarImportacaoPHP(produtosFormatados);
    };
    reader.readAsArrayBuffer(file);
}

async function enviarImportacaoPHP(dados) {
    const formData = new FormData();
    formData.append('action', 'importar_massa');
    formData.append('dados_json', JSON.stringify(dados));

    try {
        const res = await fetch('custos_actions.php', { method: 'POST', body: formData });
        const json = await res.json();
        document.getElementById('loading').style.display = 'none';
        
        if (json.status === 'success') {
            alert(json.message);
            location.reload();
        } else {
            alert('Erro: ' + json.message);
        }
    } catch (err) {
        document.getElementById('loading').style.display = 'none';
        alert('Erro ao enviar.');
    }
}