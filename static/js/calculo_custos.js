// static/js/calculo_custos.js

document.addEventListener('DOMContentLoaded', () => {
    if (typeof produtos_db !== 'undefined') {
        renderizarTabela(produtos_db);
    }
    
    // Ativa o botão limpar se ele existir na página
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) {
        btnLimpar.addEventListener('click', limparTudo);
    }
});

function renderizarTabela(dados) {
    window.activeProducts = dados;
    const tbody = document.getElementById('corpo-tabela');
    tbody.innerHTML = '';

    dados.forEach((p, index) => {
        // Formatação segura de valores
        const preco = p.preco ? parseFloat(p.preco).toFixed(2) : '0.00';
        const mbLiq = p.mbLiquida ? parseFloat(p.mbLiquida).toFixed(2) : '0.00';
        const mbBru = p.mbBruta ? parseFloat(p.mbBruta).toFixed(2) : '0.00';
        
        // Custos bases (garantindo número)
        const custoCx = parseFloat(p.custoCaixa) || 0;
        const custoUn = parseFloat(p.custoUn) || 0;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="col-campanha">${p.campanha}</td>
            <td>${p.codigo}</td>
            <td>${p.descricao}</td>
            <td style="font-weight:bold;">${preco}</td> <td>${p.qtCaixa}</td>
            
            <td style="background-color: #fff5f2; padding: 2px;">
                <input type="number" min="0" step="1" 
                       class="input-calc qtd-caixas" 
                       data-index="${index}"
                       data-custo-cx="${custoCx}"
                       placeholder="0">
            </td>
            <td style="background-color: #fff5f2; padding: 2px;">
                <input type="number" min="0" step="1" 
                       class="input-calc qtd-unidades" 
                       data-index="${index}"
                       data-custo-un="${custoUn}"
                       placeholder="0">
            </td>

            <td>${parseFloat(p.valorUn).toFixed(2)}</td>
            <td>${parseFloat(p.royalties).toFixed(2)}</td>
            <td>${parseFloat(p.st).toFixed(2)}</td>
            <td>${parseFloat(p.ipi).toFixed(2)}</td>
            <td>${parseFloat(p.txsAdicionais).toFixed(2)}</td>
            <td>${parseFloat(p.txMidia).toFixed(2)}</td>
            <td>${custoCx.toFixed(2)}</td>
            <td>${custoUn.toFixed(2)}</td>
            
            <td>${mbLiq}%</td>
            <td>${mbBru}%</td>
            
            <td class="col-total" id="total-linha-${index}">0.00</td>
        `;
        tbody.appendChild(tr);
    });

    preencherFiltroCampanha(dados);
    adicionarEventosCalculo();
}

function adicionarEventosCalculo() {
    const inputs = document.querySelectorAll('.input-calc');
    
    inputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let valor = e.target.value;
            if (valor < 0) e.target.value = 0;
            if (valor.includes('.') || valor.includes(',')) {
                e.target.value = Math.floor(parseFloat(valor) || 0);
            }
            
            calcularLinha(e.target);
            calcularTotalGeral();
        });
    });
}

function calcularLinha(elementoInput) {
    const row = elementoInput.closest('tr');
    const index = elementoInput.dataset.index;
    
    const inputCaixas = row.querySelector('.qtd-caixas');
    const inputUnidades = row.querySelector('.qtd-unidades');
    
    const qtdCaixas = parseInt(inputCaixas.value) || 0;
    const qtdUnidades = parseInt(inputUnidades.value) || 0;
    
    const custoCx = parseFloat(inputCaixas.dataset.custoCx);
    const custoUn = parseFloat(inputUnidades.dataset.custoUn);
    
    const total = (qtdCaixas * custoCx) + (qtdUnidades * custoUn);
    
    // Atualiza visual SEM R$
    const celulaTotal = document.getElementById(`total-linha-${index}`);
    celulaTotal.innerText = total.toFixed(2);
    celulaTotal.dataset.valorRaw = total; 
}

function calcularTotalGeral() {
    const totaisLinhas = document.querySelectorAll('.col-total');
    let totalGeral = 0;
    
    totaisLinhas.forEach(col => {
        const valor = parseFloat(col.dataset.valorRaw) || 0;
        totalGeral += valor;
    });
    
    const spanTotal = document.getElementById('vlr-transferencia');
    if(spanTotal) {
        // Mantém R$ apenas no Total Geral lá no topo da página
        spanTotal.innerText = totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function preencherFiltroCampanha(dados) {
    const select = document.getElementById('filterCampanha');
    if (select.options.length > 1) return;
    
    const campanhas = [...new Set(dados.map(item => item.campanha))];
    campanhas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        select.appendChild(opt);
    });
}

function filtrarTabela() {
    const filtroCampanha = document.getElementById('filterCampanha').value;
    const filtroCodigo = document.getElementById('filterCodigo').value.toUpperCase();
    const filtroDescricao = document.getElementById('filterDescricao').value.toUpperCase();
    
    const linhas = document.querySelectorAll('#corpo-tabela tr');

    linhas.forEach(linha => {
        const tdCampanha = linha.children[0].textContent;
        const tdCodigo = linha.children[1].textContent;
        const tdDescricao = linha.children[2].textContent;
        
        const matchCampanha = filtroCampanha === "" || tdCampanha === filtroCampanha;
        const matchCodigo = tdCodigo.toUpperCase().includes(filtroCodigo);
        const matchDescricao = tdDescricao.toUpperCase().includes(filtroDescricao);
        
        if (matchCampanha && matchCodigo && matchDescricao) {
            linha.style.display = "";
        } else {
            linha.style.display = "none";
        }
    });
}

// --- FUNÇÃO LIMPAR TUDO ---
function limparTudo() {
    if (!confirm("Tem certeza que deseja limpar todos os valores inseridos?")) {
        return;
    }

    // Limpa os inputs de caixas e unidades
    const inputs = document.querySelectorAll('.input-calc');
    inputs.forEach(input => {
        input.value = ""; 
    });

    // Zera visualmente as colunas de total por linha
    const totaisLinha = document.querySelectorAll('.col-total');
    totaisLinha.forEach(td => {
        td.innerText = "0.00";
        td.dataset.valorRaw = "0"; 
    });

    // Zera o Total Geral da transferência
    calcularTotalGeral();
}

// --- FUNÇÕES DE EXPORTAÇÃO CORRIGIDAS ---

function exportToPDF() {
  const lojaRemetente = document.querySelector("#remetente").value || "";
  const lojaDestino = document.querySelector("#destino").value || "";
  const dataTransferenciaInput = document.querySelector("#date").value || "";
  const totalTransferenciaElement = document.querySelector("#vlr-transferencia");
  
  // Tratamento seguro para converter o total para float
  const totalTransferencia = parseFloat(totalTransferenciaElement.textContent.trim().replace(/\./g, '').replace(',', '.')) || 0;

  if (lojaRemetente === "" || lojaDestino === "" || dataTransferenciaInput === "") {
    alert("Por favor, preencha todos os campos do formulário (Loja Remetente, Loja Destino e Data da Transferência).");
    return;
  }

  if (totalTransferencia === 0) {
    alert("O valor total da transferência é zero. Adicione caixas ou unidades para exportar.");
    return;
  }

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  const dataArray = dataTransferenciaInput.split('-');
  const dataCorreta = new Date(dataArray[0], dataArray[1] - 1, dataArray[2]);
  const dataTransferencia = dataCorreta.toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).replace(/\//g, "-");

  doc.text("Informações da Transferência", 10, 10);
  doc.text(`Loja Remetente: ${lojaRemetente}`, 10, 20);
  doc.text(`Loja Destino: ${lojaDestino}`, 10, 30);
  doc.text(`Data da Transferência: ${dataTransferencia}`, 10, 40);
  doc.text(`Valor Total da Transferência: ${totalTransferenciaElement.textContent.trim()}`, 10, 50);

  const tableRows = [];
  const table = document.querySelector(".tableizer-table tbody");

  table.querySelectorAll("tr").forEach(row => {
    // CORREÇÃO: Índices e classes corretos para a tabela custos_produtos
    const codigo = row.children[1].textContent.trim(); 
    const descricao = row.children[2].textContent.trim();
    
    const caixasInput = row.querySelector("input.qtd-caixas");
    const unidadesInput = row.querySelector("input.qtd-unidades");
    
    const totalElement = row.querySelector(".col-total");
    const total = totalElement ? totalElement.textContent.trim() : "0,00";

    const caixas = caixasInput ? caixasInput.value.trim() || "0" : "0";
    const unidades = unidadesInput ? unidadesInput.value.trim() || "0" : "0";

    if (caixas !== "0" || unidades !== "0") {
      tableRows.push([codigo, descricao, caixas, unidades, total]);
    }
  });

  if (tableRows.length > 0) {
    const headers = ["Código", "Descrição do Material", "Caixas", "Unidades", "Total"];

    doc.autoTable({
      head: [headers],
      body: tableRows,
      startY: 60,
      theme: 'striped',
      styles: { fontSize: 10 },
      headStyles: { fillColor: [0, 102, 204] },
    });
  }

  const defaultFileName = `Trasf para ${lojaDestino} - ${dataTransferencia}`;
  const fileName = prompt("Digite o nome do arquivo para exportação:", defaultFileName) || defaultFileName;
  doc.save(`${fileName}.pdf`);
}

function exportToXLS() {
  const lojaRemetente = document.querySelector("#remetente").value || "";
  const lojaDestino = document.querySelector("#destino").value || "";
  const dataTransferenciaInput = document.querySelector("#date").value || "";
  const totalTransferenciaElement = document.querySelector("#vlr-transferencia");
  const totalTransferencia = parseFloat(totalTransferenciaElement.textContent.trim().replace(/\./g, '').replace(',', '.')) || 0;

  if (lojaRemetente === "" || lojaDestino === "" || dataTransferenciaInput === "") {
    alert("Por favor, preencha todos os campos do formulário (Loja Remetente, Loja Destino e Data da Transferência).");
    return;
  }

  if (totalTransferencia === 0) {
    alert("O valor total da transferência é zero. Adicione caixas ou unidades para exportar.");
    return;
  }

  const wb = XLSX.utils.book_new();
  const tituloPagina = "Custos de Produtos";

  const dataArray = dataTransferenciaInput.split('-');
  const dataCorreta = new Date(dataArray[0], dataArray[1] - 1, dataArray[2]);
  const dataTransferencia = dataCorreta.toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).replace(/\//g, "-");

  const lojaInfo = [
    ["Informações da Transferência"],
    ["Loja Remetente", lojaRemetente],
    ["Loja Destino", lojaDestino],
    ["Data da Transferência", dataTransferencia],
  ];

  const tableRows = [];
  const table = document.querySelector(".tableizer-table tbody");

  table.querySelectorAll("tr").forEach(row => {
    // CORREÇÃO: Índices e classes corretos
    const codigo = row.children[1].textContent.trim();
    const descricao = row.children[2].textContent.trim();
    
    const caixasInput = row.querySelector("input.qtd-caixas");
    const unidadesInput = row.querySelector("input.qtd-unidades");
    
    const totalElement = row.querySelector(".col-total");
    const total = totalElement ? totalElement.textContent.trim() : "0,00";

    const caixas = caixasInput ? caixasInput.value.trim() || "0" : "0";
    const unidades = unidadesInput ? unidadesInput.value.trim() || "0" : "0";

    if (caixas !== "0" || unidades !== "0") {
      tableRows.push([codigo, descricao, caixas, unidades, total]);
    }
  });

  if (tableRows.length > 0) {
    const headers = ["Código", "Descrição do Material", "Caixas", "Unidades", "Total"];
    lojaInfo.push([], headers, ...tableRows);
  }

  const sheet = XLSX.utils.aoa_to_sheet(lojaInfo);
  XLSX.utils.book_append_sheet(wb, sheet, tituloPagina);

  const defaultFileName = `Trasf para ${lojaDestino} - ${dataTransferencia}`;
  const fileName = prompt("Digite o nome do arquivo para exportação:", defaultFileName) || defaultFileName;
  XLSX.writeFile(wb, `${fileName}.xlsx`);
}