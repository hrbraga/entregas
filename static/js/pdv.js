/* =========================================
   MÁSCARA UNIVERSAL DE MOEDA (0,00)
========================================= */
function mascaraMoeda(event) {
    let input = event.target;
    let valor = input.value.replace(/\D/g, ''); 
    if (valor === '') { input.value = ''; return; }
    valor = (parseInt(valor) / 100).toFixed(2) + '';
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = valor;
}

function converterParaDecimal(valorString) {
    if (!valorString) return 0;
    return parseFloat(valorString.replace(/\./g, '').replace(',', '.'));
}

function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

/* =========================================
   ESTADO DO PDV
========================================= */
let carrinho = [];
let totalVenda = 0;
let valorTotalComDesconto = 0;
let totalFechamentoGaveta = 0;
let totalDescontoPromocoes = 0;

/* =========================================
   MOTOR DE PROMOÇÕES (BLINDADO)
========================================= */
function calcularMotorPromocoes() {
    let descontoCalculado = 0; // Variável local, muito mais segura!
    
    if (typeof PROMOCOES_ATIVAS === 'undefined' || PROMOCOES_ATIVAS.length === 0) {
        console.warn("🔍 Motor: PROMOCOES_ATIVAS não encontrada.");
        return 0;
    }

    PROMOCOES_ATIVAS.forEach(promo => {
        let produtosPromoIds = promo.produtos.map(id => parseInt(id)); 
        let itensElegiveis = carrinho.filter(item => produtosPromoIds.includes(parseInt(item.id)));
        
        if (itensElegiveis.length === 0) return;

        let qtdTotal = itensElegiveis.reduce((sum, item) => sum + parseInt(item.quantidade), 0);
        let gatilho = parseInt(promo.qtd_gatilho);
        
        if (qtdTotal < gatilho) return;

        let multiplicador = Math.floor(qtdTotal / gatilho);
        let mecanica = String(promo.tipo_mecanica).trim();
        let beneficio = parseFloat(promo.valor_beneficio);

        if (mecanica === 'leve_x_pague_y') {
            let itensOrdenados = [...itensElegiveis].sort((a, b) => parseFloat(a.preco) - parseFloat(b.preco));
            let qtdGratisRestante = multiplicador * beneficio;
            let desc = 0;
            for (let item of itensOrdenados) {
                if (qtdGratisRestante <= 0) break;
                let qtdDescontar = Math.min(parseInt(item.quantidade), qtdGratisRestante);
                desc += qtdDescontar * parseFloat(item.preco);
                qtdGratisRestante -= qtdDescontar;
            }
            descontoCalculado += desc;
        }
        else if (mecanica === 'preco_fixo_combo') {
            let itensOrdenados = [...itensElegiveis].sort((a, b) => parseFloat(b.preco) - parseFloat(a.preco));
            let qtdParaCombo = multiplicador * gatilho;
            let valorOriginalDosItensDoCombo = 0;
            for (let item of itensOrdenados) {
                if (qtdParaCombo <= 0) break;
                let qtdUsar = Math.min(parseInt(item.quantidade), qtdParaCombo);
                valorOriginalDosItensDoCombo += qtdUsar * parseFloat(item.preco);
                qtdParaCombo -= qtdUsar;
            }
            let valorPromocional = multiplicador * beneficio;
            if((valorOriginalDosItensDoCombo - valorPromocional) > 0) {
                descontoCalculado += (valorOriginalDosItensDoCombo - valorPromocional);
            }
        }
        else if (mecanica === 'desconto_valor') {
            descontoCalculado += (multiplicador * beneficio);
        }
    });
    
    return descontoCalculado; // A mágica acontece aqui!
}


/* =========================================
   FUNÇÕES DO CARRINHO (CUPOM)
========================================= */
function adicionarItem(id, nome, preco, estoqueMaximo = 'ilimitado') {
    let itemExistente = carrinho.find(item => item.id === id);
    let qtdAtual = itemExistente ? itemExistente.quantidade : 0;

    if (estoqueMaximo !== 'ilimitado' && (qtdAtual + 1) > estoqueMaximo) {
        mostrarAlerta('Estoque Indisponível', `Você tem apenas ${estoqueMaximo} unidade(s) de "${nome}".`);
        return; 
    }

    if (itemExistente) {
        itemExistente.quantidade += 1;
        itemExistente.subtotal = itemExistente.quantidade * itemExistente.preco;
    } else {
        carrinho.push({ id: id, nome: nome, preco: preco, quantidade: 1, subtotal: preco, estoque: estoqueMaximo });
    }
    atualizarTelaCupom();
}

function alterarQuantidade(index, valor) {
    let item = carrinho[index];

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

    // RECEBE O DESCONTO DIRETAMENTE DA FUNÇÃO
    let descontoFinal = calcularMotorPromocoes();
    
    // --- RASTREADOR ---
    console.log("🕵️ ATUALIZANDO TELA - Desconto calculado:", descontoFinal);
    
    let totalLiquido = totalVenda - descontoFinal;
    if (totalLiquido < 0) totalLiquido = 0;

    if (descontoFinal > 0) {
        console.log("✅ Desconto positivo! Criando linha verde...");
        let trPromo = document.createElement('tr');
        trPromo.style.backgroundColor = '#d1e7dd';
        trPromo.style.color = '#0f5132';
        trPromo.innerHTML = `
            <td colspan="3" style="text-align: right; font-weight: bold;">Promoções Ativas:</td>
            <td style="color: red; font-weight: bold;">- ${formatarMoeda(descontoFinal)}</td>
        `;
        tbody.appendChild(trPromo);
    } else {
        console.log("❌ Desconto é 0 ou menor, linha não criada.");
    }

    document.querySelector('.total-value').innerText = formatarMoeda(totalLiquido);
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
    if (carrinho.length === 0) return mostrarAlerta("Aviso", "O carrinho já está vazio.");
    abrirModal('modalCancelar');
}

function confirmarCancelamento() {
    carrinho = [];
    atualizarTelaCupom();
    fecharModal('modalCancelar');
    mostrarAlerta("Cancelado", "A venda foi cancelada com sucesso.");
}

function abrirCancelarItem() {
    if (carrinho.length === 0) return mostrarAlerta('Aviso', 'Não há produtos no carrinho.');
    const listaDiv = document.getElementById('lista-cancelar-item');
    listaDiv.innerHTML = ''; 
    carrinho.forEach((item, index) => {
        let divItem = document.createElement('div');
        divItem.style.cssText = "display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;";
        divItem.innerHTML = `
            <span style="font-size: 1.1rem; color: #333;">${item.quantidade}x ${item.nome}</span>
            <button onclick="removerItemCarrinho(${index})" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">Remover</button>
        `;
        listaDiv.appendChild(divItem);
    });
    abrirModal('modalCancelarItem');
}

function removerItemCarrinho(index) {
    const itemRemovido = carrinho[index].nome;
    carrinho.splice(index, 1); 
    atualizarTelaCupom();
    if (carrinho.length === 0) fecharModal('modalCancelarItem');
    else abrirCancelarItem();
    mostrarAlerta('Sucesso', `Item "${itemRemovido}" foi removido do cupom.`);
}

/* =========================================
   PAGAMENTO E FINALIZAÇÃO DA VENDA
========================================= */
function abrirPagamento() {
    if (carrinho.length === 0) return mostrarAlerta("Aviso", "Adicione produtos antes de pagar.");
    document.getElementById('pag-desconto').value = '';
    document.getElementById('pag-acrescimo').value = '';
    recalcularTotalPagamento();
    abrirModal('modalPagamento');
}

function recalcularTotalPagamento() {
    let descontoManual = converterParaDecimal(document.getElementById('pag-desconto').value);
    let acrescimo = converterParaDecimal(document.getElementById('pag-acrescimo').value);
    
    let base = totalVenda - totalDescontoPromocoes;
    if(base < 0) base = 0;

    valorTotalComDesconto = base - descontoManual + acrescimo;
    if(valorTotalComDesconto < 0) valorTotalComDesconto = 0;

    document.getElementById('pag-subtotal').innerText = formatarMoeda(base);
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
    if (recebido < valorTotalComDesconto) return mostrarAlerta('Aviso', 'Valor recebido é menor que o total!');
    fecharModal('modalTroco');
    processarPagamentoFinal('Dinheiro', recebido, recebido - valorTotalComDesconto);
}

async function processarPagamentoFinal(metodo, valorRecebido, troco) {
    const descontoManual = converterParaDecimal(document.getElementById('pag-desconto').value);
    const acrescimo = converterParaDecimal(document.getElementById('pag-acrescimo').value);

    // Soma o desconto manual com o automático para bater no relatório
    const descontoTotal = descontoManual + totalDescontoPromocoes;

    const payloadVenda = {
        metodo: metodo,
        subtotal: totalVenda,
        desconto: descontoTotal,
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
            let reciboHTML = `<div style="text-align: center;"><strong>RECIBO</strong><br>Data: ${new Date().toLocaleDateString('pt-BR')}</div><br>`;
            carrinho.forEach(item => {
                reciboHTML += `<div>${item.quantidade}x ${item.nome} = ${formatarMoeda(item.subtotal)}</div>`;
            });
            reciboHTML += `<hr>Subtotal Bruto: ${formatarMoeda(totalVenda)}<br>`;
            if (totalDescontoPromocoes > 0) reciboHTML += `Desconto Promoção: -${formatarMoeda(totalDescontoPromocoes)}<br>`;
            if (descontoManual > 0) reciboHTML += `Desconto Extra: -${formatarMoeda(descontoManual)}<br>`;
            if (acrescimo > 0) reciboHTML += `Acréscimo: +${formatarMoeda(acrescimo)}<br>`;
            reciboHTML += `<strong>TOTAL FINAL: ${formatarMoeda(valorTotalComDesconto)}</strong><br>PGTO: ${metodo}`;
            
            if (metodo === 'Dinheiro') {
                reciboHTML += `<br>Recebido: ${formatarMoeda(valorRecebido)}<br>Troco: ${formatarMoeda(troco)}`;
            }
            document.getElementById('recibo-print').innerHTML = reciboHTML;
            fecharModal('modalPagamento');
            carrinho = [];
            atualizarTelaCupom();
            window.print();
            mostrarAlerta("Sucesso!", `Venda finalizada com sucesso!`);
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
    const fundo = converterParaDecimal(document.getElementById('fundo_caixa_input').value);
    
    if (document.getElementById('evento_id_input') && eventoId === "0") return mostrarAlerta('Atenção', 'Selecione o Evento.');
    if (isNaN(fundo) || fundo < 0) return mostrarAlerta('Erro', 'Fundo de caixa inválido.');

    const payload = { nome_operador: nomeOperador, evento_id: eventoId, fundo_caixa: fundo };
    try {
        const res = await fetch('../api/pdv_abrir_caixa.php', { method: 'POST', body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) {
            document.getElementById('modalAberturaCaixa').style.display = 'none';
            mostrarAlerta('Sucesso', 'Caixa aberto!');
            setTimeout(() => window.location.reload(), 1500); 
        } else mostrarAlerta('Erro', 'Falha: ' + json.error);
    } catch (e) {}
}

async function abrirFechamento() {
    document.querySelectorAll('.fechamento-input').forEach(i => i.value = '');
    calcularTotalFechamento();
    try {
        let res = await fetch('../api/pdv_resumo_turno.php');
        let json = await res.json();
        document.getElementById('resumo-vendido-hoje').innerText = json.success ? formatarMoeda(parseFloat(json.total)) : "R$ 0,00";
    } catch(e) { document.getElementById('resumo-vendido-hoje').innerText = "Erro"; }
    abrirModal('modalFechamento');
}

function calcularTotalFechamento() {
    let soma = 0;
    document.querySelectorAll('.fechamento-input').forEach(i => soma += converterParaDecimal(i.value));
    totalFechamentoGaveta = soma;
    document.getElementById('total-fechamento-calc').innerText = formatarMoeda(soma);
}

async function confirmarFechamento() {
    if (totalFechamentoGaveta < 0) return mostrarAlerta('Aviso', 'Valor negativo não permitido.');
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
        const res = await fetch('../api/pdv_fechar_caixa.php', { method: 'POST', body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) {
            fecharModal('modalFechamento');
            window.location.href = 'relatorio_fechamento.php?turno=' + json.turno_id;
        } else mostrarAlerta('Erro', json.error);
    } catch (e) {}
}

/* =========================================
   BUSCA INTELIGENTE (COM NAVEGAÇÃO DE TECLADO)
========================================= */
const inputBusca = document.getElementById('input-busca');
const dropdownBusca = document.getElementById('search-dropdown');
let focoBuscaPDV = -1;
let debouncePDV;

if(inputBusca) {
    inputBusca.addEventListener('input', (e) => {
        clearTimeout(debouncePDV);
        const termo = e.target.value.trim();
        focoBuscaPDV = -1; 
        
        if (termo.length < 2) { dropdownBusca.style.display = 'none'; return; }

        debouncePDV = setTimeout(async () => {
            try {
                let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
                let json = await res.json();
                dropdownBusca.innerHTML = '';
                
                if (json.success && json.produtos.length > 0) {
                    json.produtos.forEach((p, index) => {
                        let div = document.createElement('div');
                        div.className = 'item-busca-pdv';
                        div.style.cssText = "padding: 12px 10px; border-bottom: 1px solid #eee; cursor: pointer; text-align: left;";
                        
                        let infoEstoque = p.estoque !== 'ilimitado' ? ` | Est: ${p.estoque}` : '';
                        div.innerHTML = `<strong>${p.nome}</strong> <small style="color:#666; display:block;">R$ ${parseFloat(p.preco).toFixed(2).replace('.', ',')} ${infoEstoque}</small>`;
                        
                        div.onclick = () => {
                            adicionarItem(p.id, p.nome, parseFloat(p.preco), p.estoque);
                            inputBusca.value = ''; 
                            dropdownBusca.style.display = 'none'; 
                            inputBusca.focus(); 
                        };

                        div.onmouseover = () => { focoBuscaPDV = index; atualizarFocoPDV(); };
                        dropdownBusca.appendChild(div);
                    });
                    dropdownBusca.style.display = 'block';
                }
            } catch(err) { console.error(err); }
        }, 250);
    });

inputBusca.addEventListener('keydown', async (e) => { // Adicionamos o "async" aqui
        const itens = dropdownBusca.getElementsByClassName('item-busca-pdv');
        
        // SE A LISTA ESTIVER ABERTA (Busca manual por nome/código)
        if (dropdownBusca.style.display === 'block' && itens.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                focoBuscaPDV++;
                if (focoBuscaPDV >= itens.length) focoBuscaPDV = 0;
                atualizarFocoPDV(itens);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                focoBuscaPDV--;
                if (focoBuscaPDV < 0) focoBuscaPDV = itens.length - 1;
                atualizarFocoPDV(itens);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focoBuscaPDV > -1) itens[focoBuscaPDV].click(); 
                else itens[0].click(); 
            } else if (e.key === 'Escape') {
                dropdownBusca.style.display = 'none';
            }
        } 
        // SE A LISTA NÃO ESTIVER ABERTA E DER ENTER (Leitor de Código de Barras!)
        else if (e.key === 'Enter') {
            e.preventDefault();
            const termo = e.target.value.trim();
            if (!termo) return;

            // 1. Cancela a abertura da lista suspensa
            clearTimeout(debouncePDV); 

            // 2. Faz uma busca relâmpago direta no banco
            try {
                let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
                let json = await res.json();
                
                if (json.success && json.produtos.length > 0) {
                    // Pega o primeiro item (que será a correspondência exata do código de barras)
                    let p = json.produtos[0];
                    adicionarItem(p.id, p.nome, parseFloat(p.preco), p.estoque);
                    
                    // Limpa o campo instantaneamente para o próximo bipe
                    inputBusca.value = ''; 
                    dropdownBusca.style.display = 'none'; 
                } else {
                    mostrarAlerta('Aviso', 'Produto não encontrado.');
                    inputBusca.value = '';
                }
            } catch(err) { 
                console.error(err); 
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (!inputBusca.contains(e.target) && !dropdownBusca.contains(e.target)) {
            dropdownBusca.style.display = 'none';
        }
    });
}

function atualizarFocoPDV(itens = null) {
    if(!itens) itens = dropdownBusca.getElementsByClassName('item-busca-pdv');
    for (let i = 0; i < itens.length; i++) itens[i].style.backgroundColor = "white";
    if (focoBuscaPDV >= 0 && focoBuscaPDV < itens.length) {
        itens[focoBuscaPDV].style.backgroundColor = "#e9ecef";
        itens[focoBuscaPDV].scrollIntoView({ block: "nearest" });
    }
}

/* =========================================
   INICIALIZAÇÃO E ATALHOS DE TECLADO
========================================= */
document.addEventListener('DOMContentLoaded', () => { atualizarTelaCupom(); });

document.addEventListener('keydown', (event) => {
    if (event.key === 'F12') { event.preventDefault(); abrirPagamento(); }
    if (event.key === 'F2') { event.preventDefault(); if(inputBusca) inputBusca.focus(); }
    if (event.key === 'Escape') {
        fecharModal('modalCancelar'); fecharModal('modalPagamento');
        fecharModal('modalAlerta'); fecharModal('modalCancelarItem');
        fecharModal('modalFechamento'); fecharModal('modalTroco');
    }
});