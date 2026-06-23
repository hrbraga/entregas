const tabela = document.getElementById('tabela-produtos');
const modal = document.getElementById('modalProduto');
const form = document.getElementById('formProduto');

// Função para desenhar a tabela com os dados do banco
// Função para desenhar a tabela com os dados do banco
function renderizarTabela() {
    let htmlLinhas = ''; 
    
    produtos.forEach(p => {
        // AQUI ESTÁ A MUDANÇA: Puxamos o preco2 (Não Lovers) para exibir
        const precoNaoLovers = parseFloat(p.preco2 || 0).toFixed(2);
        const custoUn = parseFloat(p.custoUn || 0).toFixed(2);
        const mbLiquida = parseFloat(p.mbLiquida || 0).toFixed(2);

        htmlLinhas += `
            <tr>
                <td>${p.codigo_interno || '-'}</td>
                <td>${p.codigo_barras || '-'}</td>
                <td>${p.nome_produto}</td>
                <td>${p.campanha || '-'}</td>
                <td>R$ ${precoNaoLovers}</td> <td>R$ ${custoUn}</td>
                <td>${mbLiquida}%</td>
                <td>
                    <button class="btn btn-edit" onclick='editarProduto(${JSON.stringify(p).replace(/'/g, "\\'")})'>Editar</button>
                    <button class="btn btn-danger" style="padding: 6px 10px;" onclick="excluirProduto(${p.id})">Eliminar</button>
                </td>
            </tr>
        `;
    });
    
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

// --- IMPORTAÇÃO DE XML ---

function processarXML(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'importar_xml');
    formData.append('file', file);

    alert("Processando XML, aguarde...");

    fetch('api/produtos_unificados_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('xmlInput').value = ''; 

        if (data.status === 'success') {
            // Monta a mensagem base
            let mensagemAlerta = `XML Processado! ${data.atualizados} produtos atualizados.\n`;
            
            // Se houver produtos atualizados, adiciona os nomes na mensagem
            if (data.nomes_atualizados && data.nomes_atualizados.length > 0) {
                mensagemAlerta += `\nItens atualizados:\n- ` + data.nomes_atualizados.join(`\n- `);
            }
            
            // Exibe o alerta detalhado
            alert(mensagemAlerta);
            
            if (data.faltantes && data.faltantes.length > 0) {
                abrirModalFaltantes(data.faltantes);
            } else {
                location.reload(); 
            }
        } else {
            alert('Erro ao processar: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro na importação.');
    });
}

function abrirModalFaltantes(faltantes) {
    const tbody = document.getElementById('lista-faltantes');
    tbody.innerHTML = '';

    faltantes.forEach((item, index) => {
        // Guarda os dados no atributo value em JSON para facilitar o resgate
        const itemJSON = JSON.stringify(item).replace(/'/g, "&#39;");
        
        tbody.innerHTML += `
            <tr>
                <td><input type="checkbox" class="check-item-faltante" value='${itemJSON}' checked></td>
                <td>${item.codigo_interno}</td>
                <td>${item.nome_produto}</td>
                <td>R$ ${item.valorUn.toFixed(2)}</td>
                <td>R$ ${item.st.toFixed(2)}</td>
                <td>R$ ${item.ipi.toFixed(2)}</td>
            </tr>
        `;
    });

    document.getElementById('modalFaltantes').style.display = 'block';
}

function fecharModalFaltantes() {
    document.getElementById('modalFaltantes').style.display = 'none';
    location.reload(); // Recarrega a página para mostrar os atualizados
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.check-item-faltante');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function cadastrarFaltantes() {
    const checkboxes = document.querySelectorAll('.check-item-faltante:checked');
    if (checkboxes.length === 0) {
        alert("Nenhum item selecionado.");
        return;
    }

    const produtosParaCadastrar = [];
    checkboxes.forEach(cb => {
        produtosParaCadastrar.push(JSON.parse(cb.value));
    });

    const formData = new FormData();
    formData.append('action', 'cadastrar_lote');
    formData.append('produtos', JSON.stringify(produtosParaCadastrar));

    fetch('api/produtos_unificados_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Produtos cadastrados com sucesso!');
            location.reload();
        } else {
            alert('Erro ao cadastrar: ' + data.message);
        }
    });
}