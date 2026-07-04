/* =========================================
   MÁSCARA UNIVERSAL DE MOEDA (0,00)
========================================= */
function mascaraMoeda(event) {
    let input = event.target;
    let valor = input.value.replace(/\D/g, ''); // Remove tudo que não for número

    if (valor === '') {
        input.value = '';
        return;
    }

    // Divide por 100 para criar os decimais e formata
    valor = (parseInt(valor) / 100).toFixed(2) + '';
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    input.value = valor;
}

// Converte a string (ex: 1.500,00) de volta para decimal do banco (ex: 1500.00)
function converterParaDecimal(valorString) {
    if (!valorString) return 0;
    return parseFloat(valorString.replace(/\./g, '').replace(',', '.'));
}

/* =========================================
   ESTADO DO PDV
========================================= */
let carrinho = [];
let totalVenda = 0;
let valorTotalComDesconto = 0;
let totalFechamentoGaveta = 0;

/* =========================================
   FUNÇÕES DO CARRINHO (CUPOM)
========================================= */
function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function adicionarItem(id, nome, preco, estoqueMaximo = 'ilimitado') {
    let itemExistente = carrinho.find(item => item.id === id);
    let qtdAtual = itemExistente ? itemExistente.quantidade : 0;

    // TRAVA DE SEGURANÇA DO ESTOQUE
    if (estoqueMaximo !== 'ilimitado' && (qtdAtual + 1) > estoqueMaximo) {
        mostrarAlerta('Estoque Indisponível', `Você tem apenas ${estoqueMaximo} unidade(s) de "${nome}" no estoque deste evento.`);
        return; // Interrompe a função, não deixa adicionar no carrinho!
    }

    if (itemExistente) {
        itemExistente.quantidade += 1;
        itemExistente.subtotal = itemExistente.quantidade * itemExistente.preco;
    } else {
        // Salvamos o estoque limite dentro do carrinho para conferir depois
        carrinho.push({ id: id, nome: nome, preco: preco, quantidade: 1, subtotal: preco, estoque: estoqueMaximo });
    }
    atualizarTelaCupom();
}

function atualizarTelaCupom() {
    const tbody = document.querySelector('.cart-table tbody');
    tbody.innerHTML = ''; 
    totalVenda = 0; 

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

    document.querySelector('.total-value').innerText = formatarMoeda(totalVenda);
}

function alterarQuantidade(index, valor) {
    let item = carrinho[index];

    // TRAVA DE SEGURANÇA DO BOTÃO +
    if (valor > 0 && item.estoque !== 'ilimitado' && (item.quantidade + valor) > item.estoque) {
        mostrarAlerta('Estoque Indisponível', `O estoque limite deste produto é de ${item.estoque} unidade(s).`);
        return; 
    }

    item.quantidade += valor;

    if (item.quantidade <= 0) {
        carrinho.splice(index, 1);
    } else {
        item.subtotal = item.quantidade * item.preco;
    }
    atualizarTelaCupom();
}

/* =========================================
   CONTROLE DE MODAIS E ALERTAS
========================================= */
function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
function fecharModal(id) { document.getElementById(id).style.display = 'none'; }

function mostrarAlerta(titulo, mensagem) {
    document.getElementById('alertaTitulo').innerText = titulo;
    document.getElementById('alertaMensagem').innerText = mensagem;
    abrirModal('modalAlerta');
}

/* =========================================
   CANCELAMENTOS
========================================= */
function cancelarVenda() {
    if (carrinho.length === 0) {
        mostrarAlerta("Aviso", "O carrinho já está vazio.");
        return;
    }
    abrirModal('modalCancelar');
}

function confirmarCancelamento() {
    carrinho = [];
    atualizarTelaCupom();
    fecharModal('modalCancelar');
    mostrarAlerta("Cancelado", "A venda foi cancelada com sucesso.");
}

function abrirCancelarItem() {
    if (carrinho.length === 0) {
        mostrarAlerta('Aviso', 'Não há produtos no carrinho para cancelar.');
        return;
    }
    
    const listaDiv = document.getElementById('lista-cancelar-item');
    listaDiv.innerHTML = ''; 
    
    carrinho.forEach((item, index) => {
        let divItem = document.createElement('div');
        divItem.style.cssText = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;";
        divItem.innerHTML = `
            <span style="font-size: 1.1rem; color: #333;">${item.quantidade}x ${item.nome}</span>
            <button onclick="removerItemCarrinho(${index})" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;">Remover</button>
        `;
        listaDiv.appendChild(divItem);
    });
    
    abrirModal('modalCancelarItem');
}

function removerItemCarrinho(index) {
    const itemRemovido = carrinho[index].nome;
    carrinho.splice(index, 1); 
    atualizarTelaCupom();
    
    if (carrinho.length === 0) {
        fecharModal('modalCancelarItem');
    } else {
        abrirCancelarItem(); // Recarrega a lista
    }
    mostrarAlerta('Sucesso', `Item "${itemRemovido}" foi removido do cupom.`);
}

/* =========================================
   PAGAMENTO E FINALIZAÇÃO DA VENDA
========================================= */
function abrirPagamento() {
    if (carrinho.length === 0) {
        mostrarAlerta("Aviso", "Adicione produtos antes de pagar.");
        return;
    }
    // Zera descontos antigos
    document.getElementById('pag-desconto').value = '';
    document.getElementById('pag-acrescimo').value = '';
    recalcularTotalPagamento();
    abrirModal('modalPagamento');
}

function recalcularTotalPagamento() {
    let desconto = converterParaDecimal(document.getElementById('pag-desconto').value);
    let acrescimo = converterParaDecimal(document.getElementById('pag-acrescimo').value);
    
    valorTotalComDesconto = totalVenda - desconto + acrescimo;
    if(valorTotalComDesconto < 0) valorTotalComDesconto = 0;

    document.getElementById('pag-subtotal').innerText = formatarMoeda(totalVenda);
    document.getElementById('pag-total').innerText = formatarMoeda(valorTotalComDesconto);
    
    let elTotalTroco = document.getElementById('troco-total-venda');
    if(elTotalTroco) elTotalTroco.innerText = formatarMoeda(valorTotalComDesconto);
}

function processarPagamento(metodo) {
    if (metodo === 'Dinheiro') {
        fecharModal('modalPagamento');
        abrirModal('modalTroco');
        
        document.getElementById('troco-total-venda').innerText = formatarMoeda(valorTotalComDesconto);
        document.getElementById('valor-recebido').value = '';
        document.getElementById('valor-troco').innerText = 'R$ 0,00';
        setTimeout(() => document.getElementById('valor-recebido').focus(), 100);
        return; 
    }
    processarPagamentoFinal(metodo, valorTotalComDesconto, 0);
}

function calcularTroco() {
    let recebido = converterParaDecimal(document.getElementById('valor-recebido').value);
    let troco = recebido - valorTotalComDesconto;
    if (troco < 0) troco = 0; 
    document.getElementById('valor-troco').innerText = formatarMoeda(troco);
}

function finalizarDinheiro() {
    let recebido = converterParaDecimal(document.getElementById('valor-recebido').value);
    if (recebido < valorTotalComDesconto) {
        mostrarAlerta('Aviso', 'O valor recebido é menor que o total da venda!');
        return;
    }
    fecharModal('modalTroco');
    processarPagamentoFinal('Dinheiro', recebido, recebido - valorTotalComDesconto);
}

// A MÁGICA FINAL: SALVAR NO BANCO
async function processarPagamentoFinal(metodo, valorRecebido, troco) {
    const desconto = converterParaDecimal(document.getElementById('pag-desconto').value);
    const acrescimo = converterParaDecimal(document.getElementById('pag-acrescimo').value);

    const payloadVenda = {
        metodo: metodo,
        subtotal: totalVenda,
        desconto: desconto,
        acrescimo: acrescimo,
        total_final: valorTotalComDesconto,
        itens: carrinho
    };

    try {
        const res = await fetch('../api/pdv_salvar_venda.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payloadVenda)
        });
        const json = await res.json();
        
        if(json.success) {
            // MONTA O RECIBO PARA IMPRIMIR
            let reciboHTML = `<div style="text-align: center;"><strong>RECIBO</strong><br>Data: ${new Date().toLocaleDateString('pt-BR')}</div><br>`;
            carrinho.forEach(item => {
                reciboHTML += `<div>${item.quantidade}x ${item.nome} = ${formatarMoeda(item.subtotal)}</div>`;
            });
            reciboHTML += `<hr>Subtotal: ${formatarMoeda(totalVenda)}<br>`;
            if (desconto > 0) reciboHTML += `Desconto: -${formatarMoeda(desconto)}<br>`;
            if (acrescimo > 0) reciboHTML += `Acréscimo: +${formatarMoeda(acrescimo)}<br>`;
            reciboHTML += `<strong>TOTAL FINAL: ${formatarMoeda(valorTotalComDesconto)}</strong><br>PGTO: ${metodo}`;
            
            if (metodo === 'Dinheiro') {
                reciboHTML += `<br>Recebido: ${formatarMoeda(valorRecebido)}<br>Troco: ${formatarMoeda(troco)}`;
            }

            document.getElementById('recibo-print').innerHTML = reciboHTML;

            // Limpa tudo e avisa
            fecharModal('modalPagamento');
            carrinho = [];
            atualizarTelaCupom();
            
            window.print();
            mostrarAlerta("Sucesso!", `Venda salva e finalizada com sucesso!`);
        } else {
            mostrarAlerta('Erro', 'Falha ao salvar a venda: ' + json.error);
        }
    } catch (e) {
        mostrarAlerta('Erro', 'Erro ao conectar com o banco de dados.');
    }
}

/* =========================================
   ABERTURA E FECHAMENTO DE CAIXA
========================================= */
async function abrirCaixa() {
    const nomeOperador = document.getElementById('nome_operador_input') ? document.getElementById('nome_operador_input').value.trim() : 'Operador';
    const eventoId = document.getElementById('evento_id_input') ? document.getElementById('evento_id_input').value : 0;
    const inputFundo = document.getElementById('fundo_caixa_input');
    const fundo = converterParaDecimal(inputFundo.value);
    
    // Trava de segurança:
    if (document.getElementById('evento_id_input') && eventoId === "0") {
        mostrarAlerta('Atenção', 'Por favor, selecione o Evento ou PDV de trabalho.');
        return;
    }

    if (isNaN(fundo) || fundo < 0) {
        mostrarAlerta('Erro', 'Por favor, informe um valor válido para o fundo de caixa.');
        return;
    }

    const payload = {
        nome_operador: nomeOperador,
        evento_id: eventoId,
        fundo_caixa: fundo
    };

    try {
        const res = await fetch('../api/pdv_abrir_caixa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            document.getElementById('modalAberturaCaixa').style.display = 'none';
            mostrarAlerta('Sucesso', 'Caixa aberto! Um excelente dia de vendas.');
            // Recarrega a página para o sistema assumir o evento 100%
            setTimeout(() => window.location.reload(), 1500); 
        } else {
            mostrarAlerta('Erro', 'Falha ao abrir caixa: ' + json.error);
        }
    } catch (e) {
        mostrarAlerta('Erro', 'Falha na comunicação com o servidor.');
    }
}

async function abrirFechamento() {
    document.querySelectorAll('.fechamento-input').forEach(input => input.value = '');
    calcularTotalFechamento();
    
    // Busca o total vendido no banco e atualiza na tela de fechamento
    try {
        let res = await fetch('../api/pdv_resumo_turno.php');
        let json = await res.json();
        if(json.success) {
            document.getElementById('resumo-vendido-hoje').innerText = formatarMoeda(parseFloat(json.total));
        } else {
            document.getElementById('resumo-vendido-hoje').innerText = "R$ 0,00";
        }
    } catch(e) {
        document.getElementById('resumo-vendido-hoje').innerText = "Erro ao carregar";
    }

    abrirModal('modalFechamento');
}

function calcularTotalFechamento() {
    const inputs = document.querySelectorAll('.fechamento-input');
    let soma = 0;
    inputs.forEach(input => {
        soma += converterParaDecimal(input.value);
    });
    totalFechamentoGaveta = soma;
    document.getElementById('total-fechamento-calc').innerText = formatarMoeda(soma);
}

async function confirmarFechamento() {
    if (totalFechamentoGaveta < 0) {
        mostrarAlerta('Aviso', 'O valor total não pode ser negativo.');
        return;
    }

    const payload = {
        valor_gaveta: totalFechamentoGaveta,
        f_dinheiro: converterParaDecimal(document.getElementById('f_dinheiro').value),
        f_debito: converterParaDecimal(document.getElementById('f_debito').value),
        f_credito: converterParaDecimal(document.getElementById('f_credito').value),
        f_pix: converterParaDecimal(document.getElementById('f_pix').value),
        f_alimentacao: converterParaDecimal(document.getElementById('f_alimentacao').value),
        f_outros: converterParaDecimal(document.getElementById('f_outros').value)
    };

    try {
        const res = await fetch('../api/pdv_fechar_caixa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            fecharModal('modalFechamento');
            window.location.href = 'relatorio_fechamento.php?turno=' + json.turno_id;
        } else {
            mostrarAlerta('Erro', 'Falha ao fechar o caixa: ' + json.error);
        }
    } catch (e) {
        mostrarAlerta('Erro', 'Erro de comunicação com o servidor.');
    }
}

/* =========================================
   BUSCA INTELIGENTE (AUTOCOMPLETE & LEITOR)
========================================= */
const inputBusca = document.getElementById('input-busca');
const dropdown = document.getElementById('search-dropdown');
let debounceTimer;

if(inputBusca) {
    // Quando o operador vai digitando (mostra a lista)
    inputBusca.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        let termo = e.target.value.trim();
        
        if (termo.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
                let json = await res.json();
                
                dropdown.innerHTML = ''; 
                
                if (json.success && json.produtos.length > 0) {
                    json.produtos.forEach(p => {
                        let itemDiv = document.createElement('div');
                        itemDiv.style.cssText = "padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; justify-content: space-between; font-size: 1.1rem; transition: background 0.2s;";
                        itemDiv.onmouseover = () => itemDiv.style.background = '#f8f9fa';
                        itemDiv.onmouseout = () => itemDiv.style.background = 'white';
                        
                        itemDiv.innerHTML = `<span style="color: #333;">${p.nome}</span><strong style="color: #0d6efd;">${formatarMoeda(parseFloat(p.preco))}</strong>`;
                        
                        // Clica na sugestão e joga no cupom
                        itemDiv.addEventListener('click', () => {
                            adicionarItem(p.id, p.nome, parseFloat(p.preco), p.estoque);
                            inputBusca.value = '';
                            dropdown.style.display = 'none';
                            inputBusca.focus();
                        });
                        
                        dropdown.appendChild(itemDiv);
                    });
                    dropdown.style.display = 'block';
                } else {
                    dropdown.style.display = 'none';
                }
            } catch (err) {
                console.error("Erro na busca de produtos", err);
            }
        }, 300);
    });

    // Quando aperta Enter (ideal para leitor de código de barras)
    inputBusca.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            let termo = e.target.value.trim();
            if (!termo) return;
            
            try {
                let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
                let json = await res.json();
                
                if (json.success && json.produtos.length > 0) {
                    let p = json.produtos[0];
                    adicionarItem(p.id, p.nome, parseFloat(p.preco), p.estoque);
                    e.target.value = ''; 
                    dropdown.style.display = 'none';
                } else {
                    mostrarAlerta('Aviso', 'Produto não encontrado.');
                    e.target.value = ''; 
                    dropdown.style.display = 'none';
                }
            } catch (err) {
                mostrarAlerta('Erro', 'Falha na comunicação com o banco.');
            }
        }
    });

    // Oculta a lista se clicar fora dela
    document.addEventListener('click', (e) => {
        if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

/* =========================================
   INICIALIZAÇÃO E ATALHOS DE TECLADO
========================================= */
document.addEventListener('DOMContentLoaded', () => {
    atualizarTelaCupom(); // Garante que a tela comece vazia
});

document.addEventListener('keydown', (event) => {
    // F12 = Pagar
    if (event.key === 'F12') {
        event.preventDefault(); 
        abrirPagamento();
    }
    // F2 = Focar na Busca
    if (event.key === 'F2') {
        event.preventDefault();
        if(document.getElementById('input-busca')) document.getElementById('input-busca').focus();
    }
    // Esc = Fechar modais abertos
    if (event.key === 'Escape') {
        fecharModal('modalCancelar');
        fecharModal('modalPagamento');
        fecharModal('modalAlerta');
        fecharModal('modalCancelarItem');
        fecharModal('modalFechamento');
        fecharModal('modalTroco');
    }
});