document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('form-delivery');
    const btnLimpar = document.getElementById('btn-limpar');

    // Formata data para o padrão brasileiro (dd/mm/aaaa)
    function formatDate(dateString) {
        if (!dateString) return '';
        const partes = dateString.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    // Botão Limpar
    btnLimpar.addEventListener('click', () => {
        if(confirm('Deseja limpar todos os campos?')) {
            form.reset();
            // Restaura a data de hoje
            document.getElementById('data_pedido').valueAsDate = new Date();
        }
    });

    // Botão Imprimir (Submit do form)
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // 1. Coleta os valores
        const dataPedido = document.getElementById('data_pedido').value;
        const de = document.getElementById('de_quem').value || '---';
        const para = document.getElementById('para_quem').value || '---';
        const fone = document.getElementById('fone').value || '---';
        const endereco = document.getElementById('endereco').value || 'Retirada no Balcão';
        const dataEntrega = document.getElementById('data_entrega').value;
        const horaEntrega = document.getElementById('hora_entrega').value;
        const obs = document.getElementById('obs').value;

        // 2. Preenche a área de impressão
        document.getElementById('print-data-pedido').textContent = formatDate(dataPedido);
        document.getElementById('print-de').textContent = de;
        document.getElementById('print-para').textContent = para;
        document.getElementById('print-fone').textContent = fone;
        
        // Converte quebras de linha do textarea para HTML na impressão
        document.getElementById('print-endereco').innerHTML = endereco.replace(/\n/g, '<br>');
        
        if (dataEntrega) {
            document.getElementById('print-data-entrega').textContent = formatDate(dataEntrega);
        } else {
            document.getElementById('print-data-entrega').textContent = "Imediata";
        }

        document.getElementById('print-hora-entrega').textContent = horaEntrega || "--:--";

        const boxObs = document.getElementById('box-obs');
        if (obs.trim() !== "") {
            boxObs.style.display = 'block';
            document.getElementById('print-obs').textContent = obs;
        } else {
            boxObs.style.display = 'none';
        }

        // 3. Aciona a impressão
        window.print();
    });
});