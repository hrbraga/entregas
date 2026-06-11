// --- FUNÇÃO GLOBAL DE MÁSCARA FINANCEIRA ---
function mascararMoeda(obj) {
    let v = obj.value.replace(/\D/g, '');
    if (v === '') v = '0';
    v = (parseInt(v) / 100).toFixed(2) + '';
    v = v.replace('.', ',');
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    obj.value = v;
}

function parseMoedaBR(str) {
    if (!str) return 0;
    str = str.toString().replace(/\./g, '').replace(',', '.');
    return parseFloat(str) || 0;
}

// --- FUNÇÕES DO MODAL DE RECEBIMENTO ---
function abrirModalBaixa(id, vencimento, valorTotal, cliente, descricao) {
    document.getElementById('modalBaixa').style.display = 'flex';
    document.getElementById('id_baixa').value = id || '';
    
    // Zera os campos adicionais
    if(document.getElementById('juros_baixa')) document.getElementById('juros_baixa').value = "0,00";
    if(document.getElementById('desconto_baixa')) document.getElementById('desconto_baixa').value = "0,00";
    if(document.getElementById('taxa_cartao')) document.getElementById('taxa_cartao').value = "0,00";

    document.getElementById('valor_base_baixa').value = parseFloat(valorTotal).toFixed(2);
    document.getElementById('data_pagamento').value = new Date().toISOString().split('T')[0];

    calcularValorRecebido();
}

function calcularValorRecebido() {
    let base = parseFloat(document.getElementById('valor_base_baixa').value) || 0;
    let juros = Math.abs(parseMoedaBR(document.getElementById('juros_baixa').value));
    let desconto = Math.abs(parseMoedaBR(document.getElementById('desconto_baixa').value));
    let taxa = Math.abs(parseMoedaBR(document.getElementById('taxa_cartao').value));

    // Lógica do Recebimento: Base + Juros - Descontos - Taxa Cartão
    let total = base + juros - desconto - taxa;
    if (total < 0) total = 0;

    let totalExibicao = total.toFixed(2).replace('.', ',');
    totalExibicao = totalExibicao.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

    document.getElementById('valor_pago_display').value = "R$ " + totalExibicao;
}

function fecharModalBaixa() {
    document.getElementById('modalBaixa').style.display = 'none';
    document.getElementById('formBaixa').reset();
}

function salvarBaixa(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('contas_receber_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') {
                setTimeout(() => { location.reload(); }, 2200);
            }
        });
}

// --- FUNÇÕES DA TABELA E DO MODAL DE INCLUIR ---
function abrirModalConta() {
    document.getElementById('modalConta').style.display = 'flex';
    const boxParcelamento = document.getElementById('box_parcelamento');
    if (boxParcelamento) boxParcelamento.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalConta').style.display = 'none';
    document.getElementById('formConta').reset();
    document.getElementById('conta_id').value = "";
    document.getElementById('valor').value = "0,00"; 
    document.getElementById('tituloModal').innerText = "Incluir Conta a Receber";

    const listParcelas = document.getElementById('lista_parcelas');
    if (listParcelas) listParcelas.innerHTML = '';
    const configParcelas = document.getElementById('config_parcelas');
    if (configParcelas) configParcelas.style.display = 'none';
}

function editarConta(c) {
    document.getElementById('conta_id').value = c.id;
    document.getElementById('cliente').value = c.cliente;
    document.getElementById('emissao').value = c.emissao;
    document.getElementById('vencimento').value = c.vencimento;
    document.getElementById('nota_fiscal').value = c.nota_fiscal;

    let valFormatado = parseFloat(c.valor).toFixed(2).replace('.', ',');
    valFormatado = valFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('valor').value = valFormatado;

    document.getElementById('descricao').value = c.descricao;
    
    const selectCat = document.getElementById('id_categoria');
    selectCat.value = c.id_categoria;
    if(selectCat.tomselect) selectCat.tomselect.setValue(c.id_categoria);

    document.getElementById('tituloModal').innerText = "Editar Recebimento";

    const boxParcelamento = document.getElementById('box_parcelamento');
    if (boxParcelamento) boxParcelamento.style.display = 'none';

    document.getElementById('modalConta').style.display = 'flex';
}

function excluirConta(id) {
    if (!confirm('Tem a certeza que deseja eliminar este registo?')) return;
    const fd = new FormData(); fd.append('action', 'excluir'); fd.append('id', id);
    fetch('contas_receber_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') {
                setTimeout(() => { location.reload(); }, 2200);
            }
        });
}

function salvarConta(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    fetch('contas_receber_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') {
                fecharModal();
                setTimeout(() => { location.reload(); }, 2200);
            }
        });
}

// ==========================================
// MODAL DE IMPORTAÇÃO (Em Desenvolvimento)
// ==========================================
function abrirModalImportar() {
    document.getElementById('modalImportar').style.display = 'flex';
}
function fecharModalImportar() {
    document.getElementById('modalImportar').style.display = 'none';
}
function processarImportacao() {
    alert('Funcionalidade de processamento de ficheiro em desenvolvimento!');
    fecharModalImportar();
}

// ==========================================
// LÓGICA DE PARCELAMENTO MANUAL
// ==========================================
function toggleParcelamento() {
    const isChecked = document.getElementById('is_parcelado').checked;
    document.getElementById('config_parcelas').style.display = isChecked ? 'block' : 'none';
    if (!isChecked) {
        document.getElementById('lista_parcelas').innerHTML = ''; 
    }
}

function gerarPreviewParcelas() {
    const qtd = parseInt(document.getElementById('qtd_parcelas').value);
    const intervalo = parseInt(document.getElementById('intervalo_parcelas').value);
    const valorTotalStr = document.getElementById('valor').value;
    const valorTotal = parseMoedaBR(valorTotalStr);
    const dataInicialStr = document.getElementById('vencimento').value;

    if (!dataInicialStr || valorTotal <= 0) {
        alert("Atenção: Preencha o 'Vencimento' e o 'Valor' acima antes de gerar as parcelas.");
        return;
    }

    const valorParcela = (valorTotal / qtd).toFixed(2);
    let html = '<div style="max-height: 250px; overflow-y: auto; padding-right: 5px; border-top: 1px solid #ccc; padding-top: 15px;">';
    let dataAtual = new Date(dataInicialStr + 'T12:00:00');

    for (let i = 1; i <= qtd; i++) {
        let dataInput = dataAtual.toISOString().split('T')[0];
        let valExibicao = parseFloat(valorParcela).toFixed(2).replace('.', ',');
        valExibicao = valExibicao.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');

        html += `
            <div style="display:grid; grid-template-columns: 50px 1fr 1fr; gap:10px; margin-bottom:10px; align-items:center; background:#fff; padding:10px; border-radius:4px; border:1px solid #eee;">
                <div style="font-weight:bold; text-align:center; font-size: 14px; color:#666;">${i}/${qtd}</div>
                <div>
                    <label style="font-size:11px; margin-bottom:2px;">Vencimento</label>
                    <input type="date" name="parcela_vencimento[]" value="${dataInput}" class="form-control" required>
                </div>
                <div>
                    <label style="font-size:11px; margin-bottom:2px;">Valor (R$)</label>
                    <input type="text" name="parcela_valor[]" value="${valExibicao}" class="form-control text-right" required onkeyup="mascararMoeda(this)">
                </div>
            </div>
        `;
        if (intervalo === 30) {
            dataAtual.setMonth(dataAtual.getMonth() + 1);
        } else {
            dataAtual.setDate(dataAtual.getDate() + intervalo);
        }
    }
    html += '</div>';
    html += '<span style="color:#666; display:block; margin-top:10px; font-size: 10px">Pode ajustar manualmente as datas ou os valores antes de guardar.</span>';

    document.getElementById('lista_parcelas').innerHTML = html;
}

// ==========================================
// RECEBIMENTO EM LOTE
// ==========================================
function atualizarFooterLote() {
    const checks = document.querySelectorAll('.check-titulo:checked');
    let total = 0;
    checks.forEach(check => { total += parseFloat(check.dataset.valor || 0); });
    const footer = document.getElementById('footerLote');

    if (checks.length > 0) {
        footer.style.display = 'flex';
        document.getElementById('qtdSelecionados').innerText = `${checks.length} título(s) selecionado(s)`;
        document.getElementById('valorSelecionado').innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    } else {
        footer.style.display = 'none';
    }
}

document.addEventListener('change', function (e) {
    if (e.target.id === 'selecionar_todos') {
        const marcado = e.target.checked;
        document.querySelectorAll('.check-titulo').forEach(c => { c.checked = marcado; });
    }
    atualizarFooterLote();
});

function limparSelecaoLote() {
    document.querySelectorAll('.check-titulo').forEach(c => { c.checked = false; });
    document.getElementById('selecionar_todos').checked = false;
    atualizarFooterLote();
}

function abrirBaixaLote() {
    const checks = document.querySelectorAll('.check-titulo:checked');
    if (checks.length === 0) {
        alert('Selecione pelo menos um título.');
        return;
    }
    let total = 0;
    let ids = [];
    checks.forEach(c => {
        total += parseFloat(c.dataset.valor || 0);
        ids.push(c.dataset.id);
    });
    abrirModalBaixa(ids.join(','), '', total, 'Diversos', 'Recebimento em lote');
}

// ==========================================
// SIDEBAR COLUNAS
// ==========================================
function toggleColumnSidebar() {
    document.getElementById('columnSidebar').classList.toggle('open');
}

document.querySelectorAll('.toggle-column').forEach(input => {
    input.addEventListener('change', () => {
        const coluna = input.dataset.column;
        const mostrar = input.checked;
        document.querySelectorAll(`.col-${coluna}`).forEach(el => {
            el.style.display = mostrar ? '' : 'none';
        });
    });
});

// ==========================================
// AUTOCOMPLETE CLIENTE
// ==========================================
const clienteInput = document.getElementById('cliente');
const clienteLista = document.getElementById('cliente_sugestoes');

if (clienteInput) {
    clienteInput.addEventListener('input', async function () {
        const termo = this.value.trim();
        if (termo.length < 2) {
            clienteLista.innerHTML = '';
            clienteLista.style.display = 'none';
            return;
        }

        const fd = new FormData();
        fd.append('action', 'buscar_clientes');
        fd.append('termo', termo);

        const res = await fetch('contas_receber_actions.php', { method: 'POST', body: fd });
        const data = await res.json();

        clienteLista.innerHTML = '';

        if (data.status === 'success' && data.dados.length > 0) {
            data.dados.forEach(nome => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerText = nome;
                item.onclick = () => {
                    clienteInput.value = nome;
                    clienteLista.innerHTML = '';
                    clienteLista.style.display = 'none';
                };
                clienteLista.appendChild(item);
            });
            clienteLista.style.display = 'block';
        } else {
            clienteLista.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.autocomplete-wrapper')) {
            if(clienteLista) clienteLista.style.display = 'none';
        }
    });
}

// ==========================================
// TOAST NOTIFICATION
// ==========================================
function mostrarToast(mensagem) {
    const toast = document.getElementById('toast-notification');
    if (!toast) return;
    toast.innerText = mensagem;
    toast.classList.remove('show');
    void toast.offsetWidth;
    toast.classList.add('show');
    setTimeout(() => { toast.classList.remove('show'); }, 2200);
}

// ==========================================
// TOM SELECT - CATEGORIA
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('id_categoria');
    if (el) {
        new TomSelect(el, {
            create: false,
            placeholder: 'Selecione a Categoria...',
            maxOptions: 500
        });
    }
});

// ==========================================
// FILTRO ESTILO EXCEL
// ==========================================
const filtrosAtivos = {
    cliente: [],
    categoria: []
};

function toggleFiltro(tipo) {
    const dropdown = document.getElementById(`filtro-${tipo}`);
    const estaAberto = dropdown.style.display === 'flex';

    document.querySelectorAll('.filtro-dropdown').forEach(el => { el.style.display = 'none'; });

    if (estaAberto) return;

    preencherFiltro(tipo);
    dropdown.style.display = 'flex';
    dropdown.style.flexDirection = 'column';
}

function preencherFiltro(tipo) {
    const dropdown = document.getElementById(`filtro-${tipo}`);
    const classe = tipo === 'cliente' ? '.col-cliente' : '.col-categoria';
    const valores = new Set();

    document.querySelectorAll(`tbody ${classe}`).forEach(el => {
        const texto = el.innerText.trim();
        if (texto) valores.add(texto);
    });

    const ordenado = [...valores].sort();

    dropdown.innerHTML = `
        <input type="text" class="filtro-pesquisa" placeholder="Pesquisar..." onkeyup="filtrarOpcoesFiltro(this)">
        <div class="filtro-opcoes"></div>
    `;

    const container = dropdown.querySelector('.filtro-opcoes');

    ordenado.forEach(valor => {
        const checked = filtrosAtivos[tipo].includes(valor) ? 'checked' : '';
        container.innerHTML += `
            <label class="filtro-item">
                <input type="checkbox" value="${valor}" ${checked} onchange="alterarFiltro('${tipo}', this)">
                ${valor}
            </label>
        `;
    });
}

function alterarFiltro(tipo, checkbox) {
    const valor = checkbox.value;
    if (checkbox.checked) {
        filtrosAtivos[tipo].push(valor);
    } else {
        filtrosAtivos[tipo] = filtrosAtivos[tipo].filter(v => v !== valor);
    }
    aplicarFiltrosExcel();
}

function aplicarFiltrosExcel() {
    const linhas = document.querySelectorAll('.table-financeiro tbody tr');

    linhas.forEach(linha => {
        if (linha.classList.contains('child-row') || linha.querySelector('.empty-state')) return;

        const cliente = linha.querySelector('.col-cliente')?.innerText.trim();
        const categoria = linha.querySelector('.col-categoria')?.innerText.trim();

        const filtroCliente = filtrosAtivos.cliente.length === 0 || filtrosAtivos.cliente.includes(cliente);
        const filtroCategoria = filtrosAtivos.categoria.length === 0 || filtrosAtivos.categoria.includes(categoria);

        linha.style.display = (filtroCliente && filtroCategoria) ? '' : 'none';
    });
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.filtro-dropdown') && !e.target.closest('.filtro-icon')) {
        document.querySelectorAll('.filtro-dropdown').forEach(el => el.style.display = 'none');
    }
});

function filtrarOpcoesFiltro(input) {
    const termo = input.value.toLowerCase();
    const itens = input.parentElement.querySelectorAll('.filtro-item');
    itens.forEach(item => {
        const texto = item.innerText.toLowerCase();
        item.style.display = texto.includes(termo) ? 'flex' : 'none';
    });
}