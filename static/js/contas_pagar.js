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
            alert(d.message);
            if (d.status === 'success') location.reload();
        });
}

// --- FUNÇÕES DA TABELA E DO MODAL DE INCLUIR ---
function abrirModalConta() {
    document.getElementById('modalConta').style.display = 'flex';

    // Garante que o box de parcelamento apareça ao incluir uma nova conta
    const boxParcelamento = document.getElementById('box_parcelamento');
    if (boxParcelamento) boxParcelamento.style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalConta').style.display = 'none';
    document.getElementById('formConta').reset();
    document.getElementById('conta_id').value = "";
    document.getElementById('valor').value = "0,00"; // Reseta com máscara
    document.getElementById('tituloModal').innerText = "Incluir Conta a Pagar";

    // Resetar campos de parcelamento
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

    // Converte o valor do banco (1000.50) para a máscara visual (1.000,50)
    let valFormatado = parseFloat(c.valor).toFixed(2).replace('.', ',');
    valFormatado = valFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    document.getElementById('valor').value = valFormatado;

    document.getElementById('descricao').value = c.descricao;
    document.getElementById('id_categoria').value = c.id_categoria;
    document.getElementById('tituloModal').innerText = "Editar Conta";

    // Esconde o box de parcelamento ao editar uma conta existente
    const boxParcelamento = document.getElementById('box_parcelamento');
    if (boxParcelamento) boxParcelamento.style.display = 'none';

    document.getElementById('modalConta').style.display = 'flex';
}

function excluirConta(id) {
    if (!confirm('Tem a certeza que deseja eliminar esta conta?')) return;
    const fd = new FormData(); fd.append('action', 'excluir'); fd.append('id', id);
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => { alert(d.message); location.reload(); });
}

function selecionarCategoriaPorNome(nome) {
    const select = document.getElementById('id_categoria');
    for (let i = 0; i < select.options.length; i++) {
        // Ignora os optgroups e foca nas options
        if (select.options[i].text.toLowerCase().includes(nome.toLowerCase())) {
            select.selectedIndex = i; break;
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

                // Converte o valor do XML para a máscara visual
                let valXml = parseFloat(d.dados.valor_total).toFixed(2).replace('.', ',');
                valXml = valXml.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                document.getElementById('valor').value = valXml;

                document.getElementById('descricao').value = "Compra Mercadoria | NF " + d.dados.numero_nota;

                // Automação: Se for Cacau Show ou IBAC, seleciona Mercadoria para Revenda
                const fornecedorStr = d.dados.fornecedor.toLowerCase();
                if (fornecedorStr.includes('cacau') || fornecedorStr.includes('ibac')) {
                    selecionarCategoriaPorNome('Mercadoria para Revenda');
                } else {
                    document.getElementById('id_categoria').value = "";
                }

                alert("Dados do XML carregados com sucesso!");
            } else {
                alert(d.message);
            }
        });
}

function salvarConta(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const fornecedor = document.getElementById('fornecedor').value.toLowerCase();

    // Automação para Royalties, só ativa se o checkbox de parcelamento NÃO estiver marcado
    const isParcelado = document.getElementById('is_parcelado');
    if (document.getElementById('conta_id').value === "" && (fornecedor.includes('cacau') || fornecedor.includes('ibac'))) {
        if (!isParcelado || !isParcelado.checked) {
            if (confirm("🍫 Detectamos que é Cacau Show. Deseja gerar os Royalties automáticos?")) {
                fd.append('gerar_royalties', '1');
                const fXml = document.getElementById('import_xml_input');
                if (fXml.files.length > 0) fd.append('arquivo_xml', fXml.files[0]);
            }
        }
    }

    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(d => {
            alert(d.message);
            if (d.status === 'success') location.reload();
        });
}

// ==========================================
// LÓGICA DE PARCELAMENTO MANUAL
// ==========================================
function toggleParcelamento() {
    const isChecked = document.getElementById('is_parcelado').checked;
    document.getElementById('config_parcelas').style.display = isChecked ? 'block' : 'none';
    if (!isChecked) {
        document.getElementById('lista_parcelas').innerHTML = ''; // Limpa se desmarcar
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

    // Calcula o valor base da parcela
    const valorParcela = (valorTotal / qtd).toFixed(2);
    let html = '<div style="max-height: 250px; overflow-y: auto; padding-right: 5px; border-top: 1px solid #ccc; padding-top: 15px;">';

    // Tratamento de data para evitar bug de fuso horário
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

        // Adiciona dias para a próxima parcela
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

    if (checks.length > 0) {

        footer.style.display = 'flex';

        document.getElementById('qtdSelecionados').innerText =
            `${checks.length} título(s) selecionado(s)`;

        document.getElementById('valorSelecionado').innerText =
            total.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

    } else {

        footer.style.display = 'none';

    }
}

document.addEventListener('change', function (e) {

    // selecionar todos
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

    document.getElementById('selecionar_todos').checked = false;

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

            variosIds.forEach(id => {
                ids.push(id);
            });

        }
        else if (c.dataset.id) {

            ids.push(c.dataset.id);

        }
    });

    abrirModalBaixa(
        ids.join(','),
        '',
        total,
        'Diversos',
        'Baixa em lote'
    );
}

// ==========================================
// SIDEBAR COLUNAS
// ==========================================

function toggleColumnSidebar() {

    document
        .getElementById('columnSidebar')
        .classList
        .toggle('open');
}

// ==========================================
// TOGGLE COLUNAS
// ==========================================

document.querySelectorAll('.toggle-column').forEach(input => {

    input.addEventListener('change', () => {

        const coluna = input.dataset.column;

        const mostrar = input.checked;

        document
            .querySelectorAll(`.col-${coluna}`)
            .forEach(el => {

                el.style.display = mostrar
                    ? ''
                    : 'none';
            });
    });
});