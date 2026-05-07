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
    const linhasDaTabela = document.querySelectorAll('#corpo-tabela tr');

    linhasDeDados.forEach(linha => {
        // Recupera os valores originais lidos pela biblioteca
        let codigo = String(linha[0] || '').trim();
        let caixasImportadas = parseFloat(linha[1]) || 0;
        let unidadesImportadas = parseFloat(linha[2]) || 0;

        // CORREÇÃO DE SEGURANÇA: Se o arquivo for um CSV salvo no Brasil (separado por ; ou ,) 
        // e a biblioteca leu tudo junto na primeira coluna, nós forçamos a separação aqui:
        if (codigo.includes(';') || codigo.includes(',')) {
            const separador = codigo.includes(';') ? ';' : ',';
            const partes = codigo.split(separador);
            codigo = String(partes[0] || '').trim();
            caixasImportadas = parseFloat(partes[1]) || 0;
            unidadesImportadas = parseFloat(partes[2]) || 0;
        }

        // Ignora linhas totalmente vazias
        if (!codigo) return;

        let linhaEncontrada = false;

        for (const linhaTabela of linhasDaTabela) {
            // Pega o código da 1ª coluna da tabela gerada pelo gerador.js
            const codigoDaLinha = String(linhaTabela.querySelector('td:nth-child(1)').textContent).trim();
            
            if (codigoDaLinha === codigo) {
                const inputCaixas = linhaTabela.querySelector('input.caixas');
                const inputUnidades = linhaTabela.querySelector('input.unidades');
                const totalSpan = linhaTabela.querySelector('.total-item');

                if (inputCaixas) inputCaixas.value = caixasImportadas;
                if (inputUnidades) inputUnidades.value = unidadesImportadas;
                
                // Recalcula o total usando as lógicas ativas do gerador.js
                if (typeof calcularTotalLinha === 'function' && window.activeProducts) {
                    const itemDados = window.activeProducts.find(item => String(item.codigo) === codigo);
                    if (itemDados) {
                        calcularTotalLinha(itemDados, inputCaixas, inputUnidades, totalSpan);
                    }
                }

                linhaEncontrada = true;
                break;
            }
        }

        if (!linhaEncontrada) {
            codigosNaoEncontrados.push(codigo);
        }
    });

    if (codigosNaoEncontrados.length > 0) {
        alert(`Os seguintes códigos não foram encontrados na tabela atual:\n\n${codigosNaoEncontrados.join('\n')}`);
    } else {
        alert("Arquivo importado com sucesso!");
    }

    if (typeof calcularTotalGeral === 'function') {
        calcularTotalGeral();
    }
}