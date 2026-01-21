// static/js/gerenciar_etiquetas.js

// --- Filtro da tabela (Busca Instantânea) ---
document.getElementById('buscaLocal').addEventListener('keyup', function() {
    const termo = this.value.toLowerCase();
    const linhas = document.querySelectorAll('#tabelaProdutos tbody tr');
    linhas.forEach(tr => {
        const texto = tr.getAttribute('data-search');
        tr.style.display = texto.includes(termo) ? '' : 'none';
    });
});

// --- Modal de Produtos ---
function abrirModalProduto() {
    document.getElementById('formProdutos').reset();
    document.getElementById('prodId').value = ''; 
    document.getElementById('modalTitle').innerText = 'Adicionar Novo Produto';
    document.getElementById('modalProduto').style.display = 'flex';
}

function fecharModalProduto() {
    document.getElementById('modalProduto').style.display = 'none';
}

function editarProduto(p) {
    abrirModalProduto();
    document.getElementById('modalTitle').innerText = 'Editar: ' + p.nome_produto;
    
    // Preencher campos
    document.getElementById('prodId').value = p.id;
    document.getElementById('nome_produto').value = p.nome_produto;
    document.getElementById('codigo_barras').value = p.codigo_barras || '';
    document.getElementById('codigo_interno').value = p.codigo_interno || '';
    document.getElementById('preco1').value = p.preco1;
    document.getElementById('preco2').value = p.preco2;
}

// --- Ações de Banco de Dados ---

async function salvarProduto(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formProdutos'));
    
    try {
        const res = await fetch('produtos_actions.php', { method: 'POST', body: formData });
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
    if(!confirm('Tem certeza que deseja excluir este produto?')) return;
    
    const formData = new FormData();
    formData.append('action', 'excluir');
    formData.append('id', id);
    
    try {
        const res = await fetch('produtos_actions.php', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') location.reload();
        else alert('Erro ao excluir: ' + json.message);
    } catch (err) {
        alert('Erro ao processar exclusão.');
    }
}

// --- Importação (Excel/CSV) ---

function abrirModalImportacao() {
    document.getElementById('modalImport').style.display = 'flex';
}

function processarImportacao() {
    const fileInput = document.getElementById('arquivoImportacao');
    if (!fileInput.files.length) {
        alert("Selecione um arquivo!");
        return;
    }

    const file = fileInput.files[0];
    const reader = new FileReader();
    
    document.getElementById('loading').style.display = 'block';

    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        
        // Converte para JSON
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        // Mapeamento das colunas (tenta adivinhar nomes comuns)
        const produtosFormatados = jsonData.map(row => {
            return {
                codigo_barras:  row['Barras'] || row['Codigo Barras'] || row['EAN'] || row['GTIN'] || '',
                codigo_interno: row['Interno'] || row['Codigo Interno'] || row['SKU'] || '',
                nome_produto:   row['Nome'] || row['Descricao'] || row['Produto'] || '',
                preco1:         row['Preco1'] || row['Preco Cheio'] || row['Preco'] || 0,
                preco2:         row['Preco2'] || row['Preco Lovers'] || row['Preco CL'] || 0
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
        const res = await fetch('produtos_actions.php', { method: 'POST', body: formData });
        const json = await res.json();
        document.getElementById('loading').style.display = 'none';
        
        if (json.status === 'success') {
            alert(json.message);
            location.reload();
        } else {
            alert('Erro na importação: ' + json.message);
        }
    } catch (err) {
        document.getElementById('loading').style.display = 'none';
        alert('Erro ao enviar dados.');
    }
}