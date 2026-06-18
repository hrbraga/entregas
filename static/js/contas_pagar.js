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

// --- FUNÇÕES DO MODAL DE BAIXA ---
function abrirModalBaixa(id, vencimento, valorTotal, fornecedor, descricao) {
    document.getElementById('modalBaixa').style.display = 'flex';
    document.getElementById('id_baixa').value = id || '';
    document.getElementById('vencimento_baixa').value = vencimento || '';
    document.getElementById('fornecedor_baixa').value = fornecedor || 'Diversos';
    document.getElementById('descricao_baixa').value = descricao || 'Baixa de Título';

    document.getElementById('juros_baixa').value = "0,00";
    document.getElementById('multa_baixa').value = "0,00";
    document.getElementById('desconto_baixa').value = "0,00";
    document.getElementById('creditos_cs_baixa').value = "0,00";

    document.getElementById('valor_base_baixa').value = parseFloat(valorTotal).toFixed(2);
    document.getElementById('data_pagamento').value = new Date().toISOString().split('T')[0];

    calcularValorPago();
}

function calcularValorPago() {
    let base = parseFloat(document.getElementById('valor_base_baixa').value) || 0;
    let juros = Math.abs(parseMoedaBR(document.getElementById('juros_baixa').value));
    let multa = Math.abs(parseMoedaBR(document.getElementById('multa_baixa').value));
    let desconto = Math.abs(parseMoedaBR(document.getElementById('desconto_baixa').value));
    let credito = Math.abs(parseMoedaBR(document.getElementById('creditos_cs_baixa').value));

    let total = base + juros + multa - desconto - credito;
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
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
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
    if (boxParcelamento) {
        boxParcelamento.style.display = 'block';
    }
}

function fecharModal() {
    document.getElementById('modalConta').style.display = 'none';
    document.getElementById('formConta').reset();
    document.getElementById('conta_id').value = "";
    document.getElementById('valor').value = "0,00";
    document.getElementById('tituloModal').innerText = "Incluir Conta a Pagar";

    const listParcelas = document.getElementById('lista_parcelas');
    if (listParcelas) listParcelas.innerHTML = '';
    const configParcelas = document.getElementById('config_parcelas');
    if (configParcelas) configParcelas.style.display = 'none';
}

function toggleGrupo(id) {
    document.querySelectorAll('.' + id).forEach(row => {
        row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    });
}

function editarConta(c) {
    document.getElementById('conta_id').value = c.id;
    document.getElementById('fornecedor').value = c.fornecedor;
    document.getElementById('emissao').value = c.emissao;
    document.getElementById('vencimento').value = c.vencimento;
    document.getElementById('nota_fiscal').value = c.nota_fiscal;

    let valFormatado = parseFloat(c.valor).toFixed(2).replace('.', ',');
    valFormatado = valFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('valor').value = valFormatado;

    document.getElementById('descricao').value = c.descricao;
    document.getElementById('id_categoria').value = c.id_categoria;
    
    const selectCat = document.getElementById('id_categoria');
    if (selectCat) {
        selectCat.value = c.id_categoria;
        if (selectCat.tomselect) selectCat.tomselect.setValue(c.id_categoria);
    }

    document.getElementById('tituloModal').innerText = "Editar Conta";

    const boxParcelamento = document.getElementById('box_parcelamento');
    if (boxParcelamento) boxParcelamento.style.display = 'none';

    document.getElementById('modalConta').style.display = 'flex';
}

function excluirConta(id) {
    if (!confirm('Tem a certeza que deseja eliminar esta conta?')) return;
    const fd = new FormData(); fd.append('action', 'excluir'); fd.append('id', id);
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') {
                setTimeout(() => { location.reload(); }, 2200);
            }
        });
}

// ==========================================
// ESTORNO
// ==========================================
function estornarConta(id) {
    if (!confirm('Atenção: O estorno irá voltar o título para Pendente e remover o lançamento do Caixa/DRE. Deseja continuar?')) return;
    const fd = new FormData(); 
    fd.append('action', 'estorno'); 
    fd.append('id', id);
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') setTimeout(() => { location.reload(); }, 2200);
        });
}

function selecionarCategoriaPorNome(nome) {
    const select = document.getElementById('id_categoria');
    if(!select) return;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].text.toLowerCase().includes(nome.toLowerCase())) {
            const valor = select.options[i].value;
            select.value = valor;
            if (select.tomselect) { select.tomselect.setValue(valor); }
            break;
        }
    }
}

function importarDadosXML() {
    const file = document.getElementById('import_xml_input').files[0];
    if (!file) return;
    const fd = new FormData(); fd.append('arquivo_xml', file); fd.append('action', 'parse_xml');
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            if (d.status === 'success') {
                document.getElementById('fornecedor').value = d.dados.fornecedor;
                document.getElementById('emissao').value = d.dados.emissao;
                document.getElementById('vencimento').value = d.dados.vencimento;
                document.getElementById('nota_fiscal').value = d.dados.numero_nota;

                let valXml = parseFloat(d.dados.valor_total).toFixed(2).replace('.', ',');
                valXml = valXml.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                document.getElementById('valor').value = valXml;

                document.getElementById('descricao').value = "Compra Mercadoria | NF " + d.dados.numero_nota;

                const fornecedorStr = d.dados.fornecedor.toLowerCase();
                if (fornecedorStr.includes('cacau') || fornecedorStr.includes('ibac')) {
                    selecionarCategoriaPorNome('Mercadoria para Revenda');
                } else {
                    const cat = document.getElementById('id_categoria');
                    if (cat) {
                        cat.value = "";
                        if (cat.tomselect) cat.tomselect.setValue("");
                    }
                }

                alert("Dados do XML carregados com sucesso!");
            } else {
                mostrarToast(d.message);
            }
        });
}

function salvarConta(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const fornecedor = document.getElementById('fornecedor').value.toLowerCase();

    const isParcelado = document.getElementById('is_parcelado');
    if (document.getElementById('conta_id').value === "" && (fornecedor.includes('cacau') || fornecedor.includes('ibac'))) {
        if (!isParcelado || !isParcelado.checked) {
            if (confirm("🍫 Detectamos que é Cacau Show. Deseja gerar os Royalties automáticos?")) {
                fd.append('gerar_royalties', '1');
                const fXml = document.getElementById('import_xml_input');
                if (fXml && fXml.files.length > 0) fd.append('arquivo_xml', fXml.files[0]);
            }
        }
    }

    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            mostrarToast(d.message);
            if (d.status === 'success') {
                fecharModal();
                setTimeout(() => { location.reload(); }, 2200);
            }
        });
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
        alert("Atenção: Preencha o 'Vencimento Inicial' e o 'Valor Total' acima antes de gerar as parcelas.");
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
    html += '<span style="color:#666; display:block; margin-top:10px; font-size: 10px">Você pode ajustar manualmente as datas ou os valores (ex: para arredondamentos) antes de salvar.</span>';

    document.getElementById('lista_parcelas').innerHTML = html;
}

// ==========================================
// BAIXA EM LOTE
// ==========================================
function atualizarFooterLote() {
    const checks = document.querySelectorAll('.check-titulo:checked');
    let total = 0;
    checks.forEach(check => {
        total += parseFloat(check.dataset.valor || 0);
    });

    const footer = document.getElementById('footerLote');
    if(footer) {
        if (checks.length > 0) {
            footer.style.display = 'flex';
            document.getElementById('qtdSelecionados').innerText = `${checks.length} título(s) selecionado(s)`;
            document.getElementById('valorSelecionado').innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        } else {
            footer.style.display = 'none';
        }
    }
}

document.addEventListener('change', function (e) {
    if (e.target.id === 'selecionar_todos') {
        const marcado = e.target.checked;
        document.querySelectorAll('.check-titulo').forEach(c => {
            c.checked = marcado;
        });
    }
    atualizarFooterLote();
});

function limparSelecaoLote() {
    document.querySelectorAll('.check-titulo').forEach(c => {
        c.checked = false;
    });
    const st = document.getElementById('selecionar_todos');
    if(st) st.checked = false;
    atualizarFooterLote();
}

function abrirBaixaLote() {
    const checks = document.querySelectorAll('.check-titulo:checked');
    if (checks.length === 0) {
        alert('Selecione ao menos um título.');
        return;
    }

    let total = 0;
    let ids = [];

    checks.forEach(c => {
        total += parseFloat(c.dataset.valor || 0);
        if (c.dataset.ids) {
            const variosIds = c.dataset.ids.split(',');
            variosIds.forEach(id => { ids.push(id); });
        }
        else if (c.dataset.id) {
            ids.push(c.dataset.id);
        }
    });

    abrirModalBaixa(ids.join(','), '', total, 'Diversos', 'Baixa em lote');
}

// ==========================================
// SIDEBAR COLUNAS
// ==========================================
function toggleColumnSidebar() {
    const sb = document.getElementById('columnSidebar');
    if(sb) sb.classList.toggle('open');
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
// AUTOCOMPLETE FORNECEDOR
// ==========================================
const fornecedorInput = document.getElementById('fornecedor');
const fornecedorLista = document.getElementById('fornecedor_sugestoes');

if (fornecedorInput) {
    fornecedorInput.addEventListener('input', async function () {
        const termo = this.value.trim();
        if (termo.length < 2) {
            fornecedorLista.innerHTML = '';
            fornecedorLista.style.display = 'none';
            return;
        }

        const fd = new FormData();
        fd.append('action', 'buscar_fornecedores');
        fd.append('termo', termo);

        const res = await fetch('contas_pagar_actions.php', { method: 'POST', body: fd });
        const data = await res.json();

        fornecedorLista.innerHTML = '';

        if (data.status === 'success' && data.dados.length > 0) {
            data.dados.forEach(nome => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerText = nome;
                item.onclick = () => {
                    fornecedorInput.value = nome;
                    fornecedorLista.innerHTML = '';
                    fornecedorLista.style.display = 'none';
                };
                fornecedorLista.appendChild(item);
            });
            fornecedorLista.style.display = 'block';
        } else {
            fornecedorLista.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.autocomplete-wrapper')) {
            if (fornecedorLista) fornecedorLista.style.display = 'none';
        }
    });
}

// ==========================================
// TOAST E TOMSELECT
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

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('id_categoria');
    // Trava de segurança para não inicializar 2 vezes
    if (el && !el.tomselect) {
        new TomSelect(el, {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: 'Digite para pesquisar...',
            maxOptions: 500,
            openOnFocus: true,
            closeAfterSelect: true
        });
    }
});

// ==========================================
// FILTRO ESTILO EXCEL
// ==========================================
const filtrosAtivos = { fornecedor: [], categoria: [] };

function toggleFiltro(tipo) {
    const dropdown = document.getElementById(`filtro-${tipo}`);
    const estaAberto = dropdown.style.display === 'flex';
    
    document.querySelectorAll('.filtro-dropdown').forEach(el => { 
        el.style.display = 'none'; 
    });
    
    if (estaAberto) return;
    
    preencherFiltro(tipo);
    dropdown.style.display = 'flex';
    dropdown.style.flexDirection = 'column';
}

function preencherFiltro(tipo) {
    const dropdown = document.getElementById(`filtro-${tipo}`);
    const classe = tipo === 'fornecedor' ? '.col-fornecedor' : '.col-categoria';
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
        if (linha.classList.contains('child-row')) return;
        
        const fornecedor = linha.querySelector('.col-fornecedor')?.innerText.trim();
        const categoria = linha.querySelector('.col-categoria')?.innerText.trim();
        
        const filtroFornecedor = filtrosAtivos.fornecedor.length === 0 || filtrosAtivos.fornecedor.includes(fornecedor);
        const filtroCategoria = filtrosAtivos.categoria.length === 0 || filtrosAtivos.categoria.includes(categoria);
        
        linha.style.display = (filtroFornecedor && filtroCategoria) ? '' : 'none';
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

// ==========================================
// MENU KEBAB (Sobreposição Perfeita)
// ==========================================
function toggleKebab(btn) {
    const dropdown = btn.nextElementSibling;
    const isShowing = dropdown.classList.contains('show');

    document.querySelectorAll('.kebab-dropdown').forEach(d => d.classList.remove('show'));

    if (!isShowing) {
        const rect = btn.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = rect.bottom + 'px';
        dropdown.style.left = (rect.right - 140) + 'px'; 
        dropdown.classList.add('show');
    }
}

window.addEventListener('scroll', () => {
    document.querySelectorAll('.kebab-dropdown').forEach(d => d.classList.remove('show'));
}, true);

document.addEventListener('click', e => {
    if (!e.target.matches('.btn-kebab')) {
        document.querySelectorAll('.kebab-dropdown').forEach(d => d.classList.remove('show'));
    }
});