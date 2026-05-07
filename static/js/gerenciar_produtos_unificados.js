const tabela = document.getElementById('tabela-produtos');
const modal = document.getElementById('modalProduto');
const form = document.getElementById('formProduto');

// Função para desenhar a tabela com os dados do banco
function renderizarTabela() {
    // 1. Criamos uma variável de texto vazia para guardar as linhas
    let htmlLinhas = ''; 
    
    produtos.forEach(p => {
        const precoVenda = parseFloat(p.preco_venda || 0).toFixed(2);
        const custoUn = parseFloat(p.custoUn || 0).toFixed(2);
        const mbLiquida = parseFloat(p.mbLiquida || 0).toFixed(2);

        // 2. Adicionamos o HTML de cada linha à nossa variável (sem tocar no ecrã)
        htmlLinhas += `
            <tr>
                <td>${p.codigo_interno || '-'}</td>
                <td>${p.codigo_barras || '-'}</td>
                <td>${p.nome_produto}</td>
                <td>${p.campanha || '-'}</td>
                <td>R$ ${precoVenda}</td>
                <td>R$ ${custoUn}</td>
                <td>${mbLiquida}%</td>
                <td>
                    <button class="btn btn-edit" onclick='editarProduto(${JSON.stringify(p).replace(/'/g, "\\'")})'>Editar</button>
                    <button class="btn btn-danger" style="padding: 6px 10px;" onclick="excluirProduto(${p.id})">Eliminar</button>
                </td>
            </tr>
        `;
    });
    
    // 3. Injetamos tudo na tabela de uma só vez!
    tabela.innerHTML = htmlLinhas; 
}

function abrirModal() {
    form.reset();
    document.getElementById('produto_id').value = '';
    document.getElementById('modalTitle').innerText = 'Novo Produto';
    modal.style.display = 'block';
}

function fecharModal() {
    modal.style.display = 'none';
}

// Preenche o formulário com os dados do produto clicado
function editarProduto(p) {
    document.getElementById('modalTitle').innerText = 'Editar Produto';
    document.getElementById('produto_id').value = p.id;
    document.getElementById('nome_produto').value = p.nome_produto;
    document.getElementById('codigo_interno').value = p.codigo_interno;
    document.getElementById('codigo_barras').value = p.codigo_barras;
    document.getElementById('preco_venda').value = p.preco_venda;
    document.getElementById('preco2').value = p.preco2;
    
    document.getElementById('campanha').value = p.campanha;
    document.getElementById('qtCaixa').value = p.qtCaixa;
    document.getElementById('valorUn').value = p.valorUn;
    document.getElementById('st').value = p.st;
    document.getElementById('ipi').value = p.ipi;
    document.getElementById('txsAdicionais').value = p.txsAdicionais;
    document.getElementById('txMidia').value = p.txMidia;
    
    modal.style.display = 'block';
}

// Envia os dados para o ficheiro PHP que criámos na mensagem anterior
function salvarProduto(event) {
    event.preventDefault(); // Impede o ecrã de recarregar imediatamente
    const formData = new FormData(form);

    // Ajusta este caminho se o teu ficheiro PHP de actions estiver noutra pasta
    fetch('api/produtos_unificados_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            location.reload(); // Recarrega a página para atualizar a tabela visualmente
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao comunicar com o servidor.');
    });
}

// Envia o pedido de eliminação
function excluirProduto(id) {
    if (confirm('Tem a certeza que deseja eliminar este produto? Esta ação não pode ser revertida.')) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        fetch('api/produtos_unificados_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
}

// Arranca a função assim que a página é lida
renderizarTabela();