function exportToPDF() {
  // ---- Lógica de Validação ----
  const lojaRemetente = document.querySelector("#remetente").value || "";
  const lojaDestino = document.querySelector("#destino").value || "";
  const dataTransferenciaInput = document.querySelector("#date").value || "";
  const totalTransferenciaElement = document.querySelector("#vlr-transferencia");
  const totalTransferencia = parseFloat(totalTransferenciaElement.textContent.trim().replace('.', '').replace(',', '.')) || 0;

  if (lojaRemetente === "" || lojaDestino === "" || dataTransferenciaInput === "") {
    alert("Por favor, preencha todos os campos do formulário (Loja Remetente, Loja Destino e Data da Transferência).");
    return;
  }

  if (totalTransferencia === 0) {
    alert("O valor total da transferência é zero. Adicione caixas ou unidades para exportar.");
    return;
  }
  // ---- Fim da Lógica de Validação ----

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const tituloPagina = document.querySelector("h1").textContent.trim();

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
    const codigo = row.children[0].textContent.trim();
    const descricao = row.children[1].textContent.trim();
    const caixasInput = row.querySelector("input.caixas");
    const unidadesInput = row.querySelector("input.unidades");
    const total = row.querySelector(".total-item").textContent.trim();

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

  // CRIA UM NOME DE ARQUIVO PADRÃO E O USA NO PROMPT
  const defaultFileName = `Trasf para ${lojaDestino} - ${dataTransferencia}`;
  const fileName = prompt("Digite o nome do arquivo para exportação:", defaultFileName) || defaultFileName;
  doc.save(`${fileName}.pdf`);
}

function exportToXLS() {
  // ---- Lógica de Validação ----
  const lojaRemetente = document.querySelector("#remetente").value || "";
  const lojaDestino = document.querySelector("#destino").value || "";
  const dataTransferenciaInput = document.querySelector("#date").value || "";
  const totalTransferenciaElement = document.querySelector("#vlr-transferencia");
  const totalTransferencia = parseFloat(totalTransferenciaElement.textContent.trim().replace('.', '').replace(',', '.')) || 0;

  if (lojaRemetente === "" || lojaDestino === "" || dataTransferenciaInput === "") {
    alert("Por favor, preencha todos os campos do formulário (Loja Remetente, Loja Destino e Data da Transferência).");
    return;
  }

  if (totalTransferencia === 0) {
    alert("O valor total da transferência é zero. Adicione caixas ou unidades para exportar.");
    return;
  }
  // ---- Fim da Lógica de Validação ----

  const wb = XLSX.utils.book_new();
  const tituloPagina = document.querySelector("h1").textContent.trim();

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
    const codigo = row.children[0].textContent.trim();
    const descricao = row.children[1].textContent.trim();
    const caixasInput = row.querySelector("input.caixas");
    const unidadesInput = row.querySelector("input.unidades");
    const total = row.querySelector(".total-item").textContent.trim();

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

  // CRIA UM NOME DE ARQUIVO PADRÃO E O USA NO PROMPT
  const defaultFileName = `Trasf para ${lojaDestino} - ${dataTransferencia}`;
  const fileName = prompt("Digite o nome do arquivo para exportação:", defaultFileName) || defaultFileName;
  XLSX.writeFile(wb, `${fileName}.xlsx`);
}