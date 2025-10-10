document.addEventListener('DOMContentLoaded', () => {

    /**
     * Esta função preenche a tabela principal com os dados dos itens.
     * @param {Array} data - Os dados para a tabela.
     */
    function renderTable(data) {
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = ''; // Limpa a tabela antes de preencher

        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.codigo_sap}</td>
                <td>${item.item}</td>
                <td>${item.grupo}</td>
                <td>${item.pedido_loja}</td>
                <td>${item.pedido_vd}</td>
                <td>${item.total_caixa}</td>
                <td>${item.a_receber}</td>
                <td>${item.recebido}</td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Esta função calcula o resumo de grupos a partir dos dados da tabela principal.
     * @param {Array} data - Os dados completos dos itens.
     * @returns {Object} Um objeto com o resumo de cada grupo.
     */
    function calculateGroupSummary(data) {
        const summary = {};

        data.forEach(item => {
            if (!summary[item.grupo]) {
                summary[item.grupo] = { pedido: 0, a_receber: 0, entregue: 0 };
            }
            summary[item.grupo].pedido += item.total_caixa;
            summary[item.grupo].a_receber += item.a_receber;
            summary[item.grupo].entregue += item.recebido;
        });

        return summary;
    }


    /**
     * Esta função preenche a tabela de resumo por grupo.
     * @param {Object} data - Os dados de resumo por grupo.
     */
    function renderGroupSummary(data) {
        const tbody = document.getElementById('group-summary-body');
        tbody.innerHTML = '';

        for (const grupo in data) {
            const item = data[grupo];
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${grupo}</td>
                <td>${item.pedido}</td>
                <td>${item.a_receber}</td>
                <td>${item.entregue}</td>
            `;
            tbody.appendChild(row);
        }
    }


    /**
     * Função assíncrona para buscar os dados do servidor e renderizar as tabelas.
     */
    async function loadDataAndRenderTables() {
        try {
            const response = await fetch('/get_data');
            if (!response.ok) {
                throw new Error('Erro ao buscar os dados do servidor.');
            }
            const data = await response.json();
            
            // Renderiza a tabela principal com os dados do banco de dados
            renderTable(data);
            
            // Calcula e renderiza o resumo por grupo
            const groupSummary = calculateGroupSummary(data);
            renderGroupSummary(groupSummary);

        } catch (error) {
            console.error("Erro:", error);
            alert("Erro ao carregar os dados. Por favor, tente novamente.");
        }
    }

    // Chama a função para carregar os dados quando a página é carregada
    loadDataAndRenderTables();


    // Lógica para o botão de upload do XML
    const importBtn = document.getElementById('import-xml-btn');
    const fileInput = document.getElementById('xml-file-input');

    // Ao clicar no botão, "clica" no input de arquivo escondido
    importBtn.addEventListener('click', () => {
        fileInput.click();
    });

    // Quando o usuário selecionar um arquivo, esta função será executada
    fileInput.addEventListener('change', async (event) => {
        const file = event.target.files[0];
        if (file) {
            // VERIFICAÇÃO DE TIPO DE ARQUIVO
            if (file.type !== 'text/xml' && !file.name.endsWith('.xml')) {
                alert('Por favor, selecione um arquivo no formato XML.');
                fileInput.value = '';
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/upload_xml', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    // AQUI, a função para recarregar as tabelas é chamada
                    loadDataAndRenderTables();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro ao enviar o arquivo:', error);
                alert('Ocorreu um erro ao enviar o arquivo.');
            }

            // Limpa o input após o envio
            fileInput.value = '';
        }
    });
});