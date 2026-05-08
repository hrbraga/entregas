// --- FUNÇÃO GLOBAL DE MÁSCARA FINANCEIRA ---
function mascararMoeda(obj) {
    let v = obj.value.replace(/\D/g, ''); 
    if(v === '') v = '0';
    v = (parseInt(v) / 100).toFixed(2) + ''; 
    v = v.replace('.', ','); 
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'); 
    obj.value = v;
}

function parseMoedaBR(str) {
    if(!str) return 0;
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
        if(d.status === 'success') location.reload(); 
    });
}

// --- FUNÇÕES DA TABELA E DO MODAL DE INCLUIR ---
function abrirModalConta() { document.getElementById('modalConta').style.display = 'flex'; }
function fecharModal() { 
    document.getElementById('modalConta').style.display = 'none'; 
    document.getElementById('formConta').reset(); 
    document.getElementById('conta_id').value = ""; 
    document.getElementById('valor').value = "0,00"; // Reseta com máscara
    document.getElementById('tituloModal').innerText = "Incluir Conta a Pagar";
}
function toggleGrupo(id) { document.querySelectorAll('.'+id).forEach(row => { row.style.display = row.style.display === 'none' ? 'table-row' : 'none'; }); }

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
    abrirModalConta();
}

function excluirConta(id) {
    if(!confirm('Tem a certeza que deseja eliminar esta conta?')) return;
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
    if(!file) return;
    const fd = new FormData(); fd.append('arquivo_xml', file); fd.append('action', 'parse_xml');
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
    .then(res => res.json()).then(d => {
        if(d.status === 'success') {
            document.getElementById('fornecedor').value = d.dados.fornecedor;
            document.getElementById('emissao').value = d.dados.emissao;
            document.getElementById('vencimento').value = d.dados.vencimento;
            document.getElementById('nota_fiscal').value = d.dados.numero_nota;
            
            // Converte o valor do XML para a máscara visual
            let valXml = parseFloat(d.dados.valor_total).toFixed(2).replace('.', ',');
            valXml = valXml.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            document.getElementById('valor').value = valXml;
            
            document.getElementById('descricao').value = "Compra Mercadoria - NF " + d.dados.numero_nota;
            
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
    
    if(document.getElementById('conta_id').value === "" && (fornecedor.includes('cacau') || fornecedor.includes('ibac'))) {
        if(confirm("🍫 Detectamos que é Cacau Show. Deseja gerar os Royalties automáticos?")) {
            fd.append('gerar_royalties', '1');
            const fXml = document.getElementById('import_xml_input');
            if(fXml.files.length > 0) fd.append('arquivo_xml', fXml.files[0]);
        }
    }
    
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
    .then(res => res.json()).then(d => { 
        alert(d.message); 
        if(d.status === 'success') location.reload(); 
    });
}
