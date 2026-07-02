// pdv.js

// Estado do nosso PDV
let carrinho = [];
let totalVenda = 0;

/**
 * Função para adicionar um produto ao carrinho
 */
function adicionarItem(id, nome, preco) {
    // Verifica se o item já está no carrinho
    let itemExistente = carrinho.find(item => item.id === id);

    if (itemExistente) {
        itemExistente.quantidade += 1;
        itemExistente.subtotal = itemExistente.quantidade * itemExistente.preco;
    } else {
        carrinho.push({
            id: id,
            nome: nome,
            preco: preco,
            quantidade: 1,
            subtotal: preco
        });
    }

    atualizarTelaCupom();
}

/**
 * Função para renderizar o carrinho na tela
 */
function atualizarTelaCupom() {
    const tbody = document.querySelector('.cart-table tbody');
    tbody.innerHTML = ''; // Limpa a tabela atual
    totalVenda = 0; // Zera o total para recalcular

    carrinho.forEach((item, index) => {
        totalVenda += item.subtotal;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nome}</td>
            <td>
                <button onclick="alterarQuantidade(${index}, -1)" style="padding: 2px 5px; cursor: pointer;">-</button>
                <span style="margin: 0 5px;">${item.quantidade}</span>
                <button onclick="alterarQuantidade(${index}, 1)" style="padding: 2px 5px; cursor: pointer;">+</button>
            </td>
            <td>${formatarMoeda(item.preco)}</td>
            <td><strong>${formatarMoeda(item.subtotal)}</strong></td>
        `;
        tbody.appendChild(tr);
    });

    // Atualiza o valor total gigante na tela
    document.querySelector('.total-value').innerText = formatarMoeda(totalVenda);
}

/**
 * Função para aumentar ou diminuir a quantidade de um item no cupom
 */
function alterarQuantidade(index, valor) {
    let item = carrinho[index];
    item.quantidade += valor;

    if (item.quantidade <= 0) {
        // Se a quantidade chegar a zero, remove do carrinho
        carrinho.splice(index, 1);
    } else {
        item.subtotal = item.quantidade * item.preco;
    }

    atualizarTelaCupom();
}

/**
 * Função utilitária para formatar o valor para Real (R$)
 */
function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// ... (mantenha a parte de cima do carrinho igual) ...

/* =========================================
   CONTROLE DE MODAIS
========================================= */
function abrirModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
}

function mostrarAlerta(titulo, mensagem) {
    document.getElementById('alertaTitulo').innerText = titulo;
    document.getElementById('alertaMensagem').innerText = mensagem;
    abrirModal('modalAlerta');
}

/* =========================================
   CANCELAMENTO
========================================= */
// Substituímos o window.confirm por esta função que abre o modal
function cancelarVenda() {
    if (carrinho.length === 0) {
        mostrarAlerta("Aviso", "O carrinho já está vazio.");
        return;
    }
    abrirModal('modalCancelar');
}

// Essa função é chamada quando o operador clica em "Sim, Cancelar"
function confirmarCancelamento() {
    carrinho = [];
    atualizarTelaCupom();
    fecharModal('modalCancelar');
    mostrarAlerta("Cancelado", "A venda foi cancelada com sucesso.");
}

/* =========================================
   PAGAMENTO E FINALIZAÇÃO
========================================= */
function abrirPagamento() {
    if (carrinho.length === 0) {
        mostrarAlerta("Aviso", "Adicione produtos antes de pagar.");
        return;
    }
    // Atualiza o valor gigantão dentro do modal
    document.getElementById('pag-total').innerText = formatarMoeda(totalVenda);
    abrirModal('modalPagamento');
}

function processarPagamento(metodo) {
    const valorCobrado = totalVenda;

    // 1. MONTA O HTML DO RECIBO
    let reciboHTML = `
        <div style="text-align: center; margin-bottom: 10px;">
            <strong>CACAU SHOW</strong><br>
            RECIBO DE VENDA (Não Fiscal)<br>
            Data: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}
            <br>------------------------------
        </div>
    `;
    
    carrinho.forEach(item => {
        reciboHTML += `<div>${item.quantidade}x ${item.nome}<br><div style="text-align: right;">${formatarMoeda(item.subtotal)}</div></div>`;
    });
    
    reciboHTML += `
        <div style="margin-top: 10px; border-top: 1px dashed #000; padding-top: 10px;">
            <strong style="font-size: 14px;">TOTAL: ${formatarMoeda(valorCobrado)}</strong><br>
            PAGAMENTO: ${metodo}
        </div>
        <div style="text-align: center; margin-top: 20px;">
            Obrigado pela preferencia!
        </div>
    `;

    // 2. Joga o HTML na div invisível
    document.getElementById('recibo-print').innerHTML = reciboHTML;

    // 3. Limpa o sistema e exibe a mensagem (antes de abrir a tela de print)
    fecharModal('modalPagamento');
    let carrinhoCopia = [...carrinho]; // Opcional: guardar para salvar no banco depois
    carrinho = [];
    atualizarTelaCupom();
    
    // 4. Chama a impressora!
    window.print();
    
    // (O alerta nativo entra depois da impressão)
    mostrarAlerta("Sucesso!", `Venda de ${formatarMoeda(valorCobrado)} finalizada em ${metodo}.`);
}

/* =========================================
   ATALHOS DE TECLADO
========================================= */
document.addEventListener('keydown', (event) => {
    // F12 = Pagar
    if (event.key === 'F12') {
        event.preventDefault(); // Evita abrir o inspecionar elemento do navegador
        abrirPagamento();
    }
    // Esc = Fechar qualquer modal
    if (event.key === 'Escape') {
        fecharModal('modalCancelar');
        fecharModal('modalPagamento');
        fecharModal('modalAlerta');
    }
});

// Inicializa a tela vazia ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    atualizarTelaCupom();
});

// Adicione isso no final do seu pdv.js
const inputBusca = document.querySelector('.search-bar input');

inputBusca.addEventListener('keypress', async function(e) {
    if (e.key === 'Enter') {
        let termo = this.value.trim();
        if (!termo) return;
        
        let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
        let json = await res.json();
        
        if (json.success && json.produto) {
            let p = json.produto;
            // Garante que o preço venha como número decimal
            adicionarItem(p.id, p.nome, parseFloat(p.preco));
            this.value = ''; // Limpa a barra para o próximo bip
        } else {
            mostrarAlerta('Aviso', 'Produto não encontrado.');
            this.value = ''; // Limpa a barra mesmo se errar
        }
    }
});