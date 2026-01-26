// import-xls.js

document.addEventListener('DOMContentLoaded', () => {
    const importInput = document.getElementById('importXLS');
    const btnImportar = document.getElementById('btn-importar');

    btnImportar.addEventListener('click', () => {
        importInput.click();
    });

    importInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });

            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

            preencherTabela(jsonData);
        };

        reader.readAsArrayBuffer(file);
    });
});

function preencherTabela(dadosImportados) {
    if (!dadosImportados || dadosImportados.length <= 1) {
        alert("O arquivo importado não contém dados válidos.");
        return;
    }

    const linhasDeDados = dadosImportados.slice(1);
    const codigosNaoEncontrados = [];
    const linhasDaTabela = document.querySelectorAll('#corpo-tabela tr'); // Melhor usar #corpo-tabela tr

    linhasDeDados.forEach(linha => {
        const codigo = String(linha[0] || '').trim();
        const caixasImportadas = linha[1] || 0;
        const unidadesImportadas = linha[2] || 0;

        let linhaEncontrada = false;

        for (const linhaTabela of linhasDaTabela) {
            // AJUSTE: O código está na 2ª coluna (índice 1 no DOM, mas nth-child(2) no CSS)
            const codigoDaLinha = String(linhaTabela.querySelector('td:nth-child(2)').textContent).trim();
            
            if (codigoDaLinha === codigo) {
                // AJUSTE: Classes corretas conforme calculo_custos.js
                const inputCaixas = linhaTabela.querySelector('input.qtd-caixas');
                const inputUnidades = linhaTabela.querySelector('input.qtd-unidades');
                
                // Agora activeProducts existe
                const itemDados = window.activeProducts ? window.activeProducts.find(item => String(item.codigo) === codigo) : null;

                if (inputCaixas) {
                    inputCaixas.value = caixasImportadas;
                }
                if (inputUnidades) {
                    inputUnidades.value = unidadesImportadas;
                }
                
                // AJUSTE: Chama a função de cálculo correta do calculo_custos.js
                if (typeof calcularLinha === 'function' && inputCaixas) {
                    calcularLinha(inputCaixas); // Recalcula a linha
                }

                linhaEncontrada = true;
                break;
            }
        }

        if (!linhaEncontrada && codigo) {
            codigosNaoEncontrados.push(codigo);
        }
    });

    if (codigosNaoEncontrados.length > 0) {
        alert(`Os seguintes códigos não foram importados:\n\n${codigosNaoEncontrados.join('\n')}`);
    } else {
        alert("Arquivo importado com sucesso!");
    }

    if (typeof calcularTotalGeral === 'function') {
        calcularTotalGeral();
    }
}