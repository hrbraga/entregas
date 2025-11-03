const linhas = document.querySelectorAll("tbody tr");
const vlrTransferencia = document.getElementById("vlr-transferencia");

function calcularCustoTotal(linha) {
  const caixasInput = linha.querySelector(".caixas");
  const unidadesInput = linha.querySelector(".unidades");
  const custoCaixaCell = linha.querySelector("td:nth-child(12)");
  const custoUnCell = linha.querySelector("td:nth-child(13)");
  const totalCell = linha.querySelector("td:last-child");
  const totalValorSpan = totalCell.querySelector("span#total-valor");

  const quantidadeCaixas = parseFloat(caixasInput.value) || 0;
  const quantidadeUnidades = parseFloat(unidadesInput.value) || 0;
  const custoPorCaixa = parseFloat(custoCaixaCell.textContent.replace(",", ".")) || 0;
  const custoPorUnidade = parseFloat(custoUnCell.textContent.replace(",", ".")) || 0;

  const custoTotalCaixas = quantidadeCaixas * custoPorCaixa;
  const custoTotalUnidades = quantidadeUnidades * custoPorUnidade;

  const custoTotalGeral = custoTotalCaixas + custoTotalUnidades;

  totalValorSpan.textContent = custoTotalGeral.toFixed(2).replace(".", ",");
}

function calcularTotalGeral() {
  let totalGeral = 0;

  linhas.forEach((linha) => {
    const totalCell = linha.querySelector("td:last-child span#total-valor");
    const valorTotal = parseFloat(totalCell.textContent.replace(",", ".")) || 0;
    totalGeral += valorTotal;
  });

  vlrTransferencia.textContent = totalGeral.toFixed(2).replace(".", ",");
}


linhas.forEach((linha) => {
  const caixasInput = linha.querySelector(".caixas");
  const unidadesInput = linha.querySelector(".unidades");

  caixasInput.addEventListener("input", () => {
    calcularCustoTotal(linha);
    calcularTotalGeral();
  });

  unidadesInput.addEventListener("input", () => {
    calcularCustoTotal(linha);
    calcularTotalGeral();
  });
});

calcularTotalGeral();