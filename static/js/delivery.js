document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('form-delivery');
    const btnLimpar = document.getElementById('btn-limpar');
    const foneInput = document.getElementById('fone');

    // --- 1. MÁSCARA DE TELEFONE (Formata enquanto digita) ---
    foneInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número
        
        // Limita a 11 dígitos
        if (value.length > 11) value = value.slice(0, 11);

        // Aplica a formatação (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (value.length > 10) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else if (value.length > 5) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
        } else if (value.length > 0) {
            value = value.replace(/^(\d*)/, '($1');
        }
        
        e.target.value = value;
    });

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
            // Restaura a data do pedido para hoje
            document.getElementById('data_pedido').valueAsDate = new Date();
        }
    });

    // --- BOTÃO IMPRIMIR (SUBMIT) COM VALIDAÇÕES ---
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // --- VALIDAÇÃO 1: TELEFONE ---
        const foneValor = foneInput.value;
        // Regex aceita: (11) 91234-5678 ou (11) 1234-5678
        const foneRegex = /^\(\d{2}\) \d{4,5}-\d{4}$/;

        if (foneValor && !foneRegex.test(foneValor)) {
            alert("Por favor, insira um telefone válido no formato (DDD) 00000-0000.");
            foneInput.focus();
            return; // Para a execução
        }

        // --- VALIDAÇÃO 2: DATA DE AGENDAMENTO ---
        const dataEntregaInput = document.getElementById('data_entrega');
        const dataEntregaValor = dataEntregaInput.value;

        if (dataEntregaValor) {
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0); // Zera a hora para comparar apenas a data
            
            // Cria a data selecionada considerando o fuso local (adicionando T00:00:00)
            // Isso evita o bug onde a data volta um dia devido ao UTC
            const dataSelecionada = new Date(dataEntregaValor + 'T00:00:00');

            if (dataSelecionada < hoje) {
                alert("A data de agendamento não pode ser anterior a hoje.");
                dataEntregaInput.focus();
                return; // Para a execução
            }
        }

        // --- SE PASSOU NAS VALIDAÇÕES, PREENCHE E IMPRIME ---

        const dataPedido = document.getElementById('data_pedido').value;
        const de = document.getElementById('de_quem').value || '---';
        const para = document.getElementById('para_quem').value || '---';
        const fone = document.getElementById('fone').value || '---';
        const endereco = document.getElementById('endereco').value || 'Retirada no Balcão';
        const horaEntrega = document.getElementById('hora_entrega').value;
        const obs = document.getElementById('obs').value;

        // Preenche a área de impressão
        document.getElementById('print-data-pedido').textContent = formatDate(dataPedido);
        document.getElementById('print-de').textContent = de;
        document.getElementById('print-para').textContent = para;
        document.getElementById('print-fone').textContent = fone;
        
        // Converte quebras de linha do textarea para HTML
        document.getElementById('print-endereco').innerHTML = endereco.replace(/\n/g, '<br>');
        
        if (dataEntregaValor) {
            document.getElementById('print-data-entrega').textContent = formatDate(dataEntregaValor);
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

        // Aciona a impressão
        window.print();
    });
});