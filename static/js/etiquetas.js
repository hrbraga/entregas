document.addEventListener('DOMContentLoaded', () => {
    
    const inputCodigo = document.getElementById('codigo_produto');
    const inputQuantidade = document.getElementById('quantidade');
    const btnAdicionar = document.getElementById('adicionar_produto');
    const tabelaCorpo = document.querySelector("#lista_produtos tbody");
    const btnGerar = document.getElementById('gerar_etiquetas');
    const containerImpressao = document.getElementById('container_impressao');

    // --- Ação: Adicionar Produto ---
    const adicionarProdutoNaLista = async () => {
        const codigo = inputCodigo.value;
        const quantidade = inputQuantidade.value;

        if (!codigo || !quantidade || quantidade < 1) {
            alert('Por favor, insira um código válido e uma quantidade.');
            return;
        }

        try {
            const response = await fetch(`../api/buscar_produto.php?codigo=${codigo}`);
            
            if (!response.ok) {
                const erro = await response.json();
                throw new Error(erro.erro || 'Produto não encontrado');
            }

            const produto = await response.json();

            const newRow = tabelaCorpo.insertRow();
            newRow.innerHTML = `
                <td data-field="nome">${produto.nome_produto}</td>
                <td data-field="preco1">${parseFloat(produto.preco1).toFixed(2)}</td>
                <td data-field="preco2">${parseFloat(produto.preco2).toFixed(2)}</td>
                <td data-field="qtd">${quantidade}</td>
                <td><button class="remover">Remover</button></td>
            `;
            
            inputCodigo.value = '';
            inputQuantidade.value = '1';
            inputCodigo.focus();

        } catch (error) {
            alert(`Erro: ${error.message}`);
            inputCodigo.select();
        }
    };

    btnAdicionar.addEventListener('click', adicionarProdutoNaLista);
    
    inputCodigo.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            adicionarProdutoNaLista();
        }
    });

    tabelaCorpo.addEventListener('click', (e) => {
        if (e.target.classList.contains('remover')) {
            e.target.closest('tr').remove();
        }
    });

    // --- Ação: Gerar Etiquetas (Somente Impressão) ---
    btnGerar.addEventListener('click', () => {
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

        // 1. Constrói o HTML
        prepararHTMLParaImpressao(produtosParaImprimir);

        // 2. Chama a impressão do navegador
        window.print();
    });

    function prepararHTMLParaImpressao(produtos) {
        containerImpressao.innerHTML = ''; 
        
        let paginaAtual = document.createElement('div');
        paginaAtual.className = 'pagina_a4';
        containerImpressao.appendChild(paginaAtual);

        produtos.forEach(produto => {
            for (let i = 0; i < produto.qtd; i++) {
                
                const [int1, dec1] = produto.preco1.split('.');
                const [int2, dec2] = produto.preco2.split('.');
                
                const etiqueta = document.createElement('div');
                etiqueta.className = 'etiqueta';
                
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
                
                paginaAtual.appendChild(etiqueta);
            }
        });
    }
});