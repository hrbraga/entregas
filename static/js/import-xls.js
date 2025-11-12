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
    const linhasDaTabela = document.querySelectorAll('.tableizer-table tbody tr');

    linhasDeDados.forEach(linha => {
        const codigo = String(linha[0] || '').trim();
        const caixasImportadas = linha[1] || 0;
        const unidadesImportadas = linha[2] || 0;

        let linhaEncontrada = false;

        for (const linhaTabela of linhasDaTabela) {
            const codigoDaLinha = String(linhaTabela.querySelector('td:first-child').textContent).trim();
            if (codigoDaLinha === codigo) {
                const inputCaixas = linhaTabela.querySelector('input.caixas');
                const inputUnidades = linhaTabela.querySelector('input.unidades');
                
                // AQUI: Usa a nova variável global com o array de produtos ativos
                const itemDados = window.activeProducts.find(item => String(item.codigo) === codigo);
                const totalSpan = linhaTabela.querySelector('.total-item');

                if (inputCaixas) {
                    inputCaixas.value = caixasImportadas;
                }
                if (inputUnidades) {
                    inputUnidades.value = unidadesImportadas;
                }
                
                if (window.calcularTotalLinha && itemDados && totalSpan) {
                    window.calcularTotalLinha(itemDados, inputCaixas, inputUnidades, totalSpan);
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
        const mensagemErro = `Os seguintes códigos não foram importados porque não existem na planilha: \n\n${codigosNaoEncontrados.join('\n')}`;
        alert(mensagemErro);
    } else {
        alert("Arquivo importado com sucesso!");
    }

    if (typeof calcularTotalGeral === 'function') {
        calcularTotalGeral();
    }
}