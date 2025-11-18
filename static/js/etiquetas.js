document.addEventListener('DOMContentLoaded', () => {
    
    // Elementos da interface
    const inputCodigo = document.getElementById('codigo_produto');
    const inputQuantidade = document.getElementById('quantidade');
    const btnAdicionar = document.getElementById('adicionar_produto');
    const tabelaCorpo = document.querySelector("#lista_produtos tbody");
    const btnGerar = document.getElementById('gerar_etiquetas');
    const containerImpressao = document.getElementById('container_impressao');

    // --- Ação: Adicionar Produto na Tabela ---
    const adicionarProdutoNaLista = async () => {
        const codigo = inputCodigo.value;
        const quantidade = inputQuantidade.value;

        if (!codigo || !quantidade || quantidade < 1) {
            alert('Por favor, insira um código válido e uma quantidade.');
            return;
        }

        try {
            // CAMINHO AJUSTADO PARA A API:
            // Sobe de 'js' para 'static' (../)
            // Sobe de 'static' para 'entradas' (../)
            // Entra em 'api' (api/)
            const response = await fetch(`../api/buscar_produto.php?codigo=${codigo}`);
            
            if (!response.ok) {
                // Se a API retornou 404 (Not Found) ou outro erro
                const erro = await response.json();
                throw new Error(erro.erro || 'Produto não encontrado');
            }

            const produto = await response.json();

            // Adiciona o produto na tabela da interface
            const newRow = tabelaCorpo.insertRow();
            // Usamos 'data-field' para facilitar a leitura na hora de gerar
            newRow.innerHTML = `
                <td data-field="nome">${produto.nome_produto}</td>
                <td data-field="preco1">${parseFloat(produto.preco1).toFixed(2)}</td>
                <td data-field="preco2">${parseFloat(produto.preco2).toFixed(2)}</td>
                <td data-field="qtd">${quantidade}</td>
                <td><button class="remover">Remover</button></td>
            `;
            
            // Limpa os campos de entrada e foca no código
            inputCodigo.value = '';
            inputQuantidade.value = '1';
            inputCodigo.focus();

        } catch (error) {
            alert(`Erro: ${error.message}`);
            inputCodigo.select(); // Seleciona o código errado para facilitar a correção
        }
    };

    // Adiciona ao clicar no botão
    btnAdicionar.addEventListener('click', adicionarProdutoNaLista);
    
    // Adiciona ao pressionar "Enter" no campo de código
    inputCodigo.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            adicionarProdutoNaLista();
        }
    });

    // --- Ação: Remover Item da Tabela ---
    tabelaCorpo.addEventListener('click', (e) => {
        if (e.target.classList.contains('remover')) {
            e.target.closest('tr').remove();
        }
    });

    // --- Ação: Gerar as Etiquetas (PDF ou Impressão) ---
    btnGerar.addEventListener('click', () => {
        // 1. Pega todos os produtos da tabela
        const produtosParaImprimir = [];
        tabelaCorpo.querySelectorAll('tr').forEach(row => {
            produtosParaImprimir.push({
                nome: row.querySelector('[data-field="nome"]').textContent,
                preco1: row.querySelector('[data-field="preco1"]').textContent,
                preco2: row.querySelector('[data-field="preco2"]').textContent,
                qtd: parseInt(row.querySelector('[data-field="qtd"]').textContent, 10)
            });
        });

        if (produtosParaImprimir.length === 0) {
            alert('Adicione pelo menos um produto à lista.');
            return;
        }

        // 2. Pergunta ao usuário (O "alert" que você pediu)
        // confirm() retorna 'true' para OK e 'false' para Cancelar
        const escolha = confirm("Como quer gerar?\n\nClique 'OK' para PDF\nClique 'Cancelar' para Impressão Direta");
        
        // 3. Constrói o HTML das etiquetas no container invisível
        prepararHTMLParaImpressao(produtosParaImprimir);

        // 4. Executa a ação escolhida
        if (escolha) {
            // Gerar PDF
            gerarPDF();
        } else {
            // Imprimir
            window.print();
        }

        // 5. Limpa a área de preparação depois de gerar
        // (Colocado dentro de um timeout para dar tempo da impressão/pdf processar)
        setTimeout(() => {
            containerImpressao.innerHTML = '';
        }, 1000);
    });

    // --- Função Auxiliar: Constrói o HTML ---
    function prepararHTMLParaImpressao(produtos) {
        containerImpressao.innerHTML = ''; // Limpa antes de começar
        
        let paginaAtual = document.createElement('div');
        paginaAtual.className = 'pagina_a4';
        containerImpressao.appendChild(paginaAtual);

        produtos.forEach(produto => {
            for (let i = 0; i < produto.qtd; i++) {
                
                // Separa os centavos dos preços
                const [int1, dec1] = produto.preco1.split('.');
                const [int2, dec2] = produto.preco2.split('.');
                
                // Cria o HTML de UMA etiqueta
                const etiqueta = document.createElement('div');
                etiqueta.className = 'etiqueta';
                
                // **IMPORTANTE**: Este HTML interno deve ser ajustado
                // junto com o CSS para o posicionamento exato
                etiqueta.innerHTML = `
                    <div class="nome_produto">${produto.nome}</div>
                    
                    <div class="preco1 preco-bloco">
                        <span class="rs">R$</span>
                        <span class="int">${int1}</span>
                        <span class="dec">,${dec1 ? dec1.padEnd(2, '0') : '00'}</span>
                    </div>
                    
                    <div class="preco2 preco-bloco">
                        <span class="rs">R$</span>
                        <span class="int">${int2}</span>
                        <span class="dec">,${dec2 ? dec2.padEnd(2, '0') : '00'}</span>
                    </div>
                `;
                
                // Adiciona a etiqueta na página A4
                paginaAtual.appendChild(etiqueta);
                
                // (Opcional: lógica para quebrar a página se encher)
                // ...
            }
        });
    }

    // --- Função Auxiliar: Gerar PDF ---
    function gerarPDF() {
        const elemento = document.getElementById('container_impressao');
        const opt = {
            margin:       0, // Sem margens
            filename:     'etiquetas.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        // Usa a biblioteca html2pdf
        html2pdf().from(elemento).set(opt).save();
    }
});