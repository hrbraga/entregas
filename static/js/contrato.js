document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('contractForm');
    const btnLimpar = document.getElementById('limparContrato');
    
    // --- Elementos do Formulário ---
    const contratanteDocEl = document.getElementById('contratanteDoc');
    const contratadoDocEl = document.getElementById('contratadoDoc');
    const dataEntregaEl = document.getElementById('dataEntrega');
    const valorTotalEl = document.getElementById('valorTotal');
    const valorEntradaEl = document.getElementById('valorEntrada');
    const qntInputs = form.querySelectorAll('.sabor-grid input[type="number"]');

    // --- Elementos de Erro ---
    const errorContratanteDoc = document.getElementById('error-contratanteDoc');
    const errorContratadoDoc = document.getElementById('error-contratadoDoc');
    const errorDataEntrega = document.getElementById('error-dataEntrega');
    const errorValores = document.getElementById('error-valores');
    const errorQuantidades = document.getElementById('error-quantidades');

    // ==================================================
    // === FUNÇÕES HELPER PARA MOSTRAR/LIMPAR ERROS
    // ==================================================

    /**
     * Mostra uma mensagem de erro abaixo de um campo e aplica a classe 'invalid'.
     * @param {HTMLElement} inputEl - O elemento input (opcional).
     * @param {HTMLElement} errorEl - O elemento span de erro.
     * @param {string} message - A mensagem a ser exibida.
     */
    function showError(inputEl, errorEl, message) {
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
        if (inputEl) {
            inputEl.classList.add('invalid');
        }
    }

    /**
     * Limpa a mensagem de erro e remove a classe 'invalid'.
     * @param {HTMLElement} inputEl - O elemento input (opcional).
     * @param {HTMLElement} errorEl - O elemento span de erro.
     */
    function clearError(inputEl, errorEl) {
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }
        if (inputEl) {
            inputEl.classList.remove('invalid');
        }
    }

    // ==================================================
    // === FUNÇÕES DE VALIDAÇÃO
    // ==================================================

    function validarCPF(cpf) {
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;
        return true;
    }

    function validarCNPJ(cnpj) {
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
        let tamanho = cnpj.length - 2, numeros = cnpj.substring(0, tamanho), digitos = cnpj.substring(tamanho), soma = 0, pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0)) return false;
        tamanho += 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(1)) return false;
        return true;
    }
    
    function validarDocumento(doc) {
        const cleanDoc = doc.replace(/[^\d]+/g, ''); 
        if (cleanDoc.length === 11) {
            return validarCPF(cleanDoc);
        } else if (cleanDoc.length === 14) {
            return validarCNPJ(cleanDoc);
        }
        return false;
    }

    function validarDataEntrega(dataString) {
        if (!dataString) return false;
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0); 
        const partes = dataString.split('-');
        const dataEntrega = new Date(partes[0], partes[1] - 1, partes[2]); 
        return dataEntrega >= hoje;
    }

    // --- Funções "Wrapper" para validação em tempo real ---

    function validarDocInput(inputEl, errorEl) {
        if (!inputEl.value) { // Não mostra erro se estiver vazio, a não ser que seja submetido
             clearError(inputEl, errorEl);
             return false; // Retorna false para o submit saber que está vazio
        }
        if (validarDocumento(inputEl.value)) {
            clearError(inputEl, errorEl);
            return true;
        } else {
            showError(inputEl, errorEl, "CPF/CNPJ inválido.");
            return false;
        }
    }

    function validarDataInput(inputEl, errorEl) {
        if (!inputEl.value) {
            clearError(inputEl, errorEl);
            return false;
        }
        if (validarDataEntrega(inputEl.value)) {
            clearError(inputEl, errorEl);
            return true;
        } else {
            showError(inputEl, errorEl, "A data da entrega não pode ser anterior a hoje.");
            return false;
        }
    }

    function validarNumeroNegativo(inputEl, errorEl, nomeCampo) {
        const valor = parseFloat(inputEl.value);
        if (isNaN(valor) || valor >= 0) {
            clearError(inputEl, errorEl);
            return true;
        } else {
            showError(inputEl, errorEl, `O ${nomeCampo} não pode ser negativo.`);
            return false;
        }
    }

    function validarQuantidadesNegativas(inputs, errorEl) {
        let allValid = true;
        inputs.forEach(input => {
            const valor = parseFloat(input.value);
            if (!isNaN(valor) && valor < 0) {
                input.classList.add('invalid');
                allValid = false;
            } else {
                input.classList.remove('invalid');
            }
        });

        if (allValid) {
            clearError(null, errorEl); // Limpa o erro geral
            return true;
        } else {
            showError(null, errorEl, "As quantidades não podem ser negativas.");
            return false;
        }
    }


    // ==================================================
    // === ANEXAR EVENT LISTENERS
    // ==================================================

    // Validar documento ao sair do campo
    if (contratanteDocEl) contratanteDocEl.addEventListener('blur', () => validarDocInput(contratanteDocEl, errorContratanteDoc));
    if (contratadoDocEl) contratadoDocEl.addEventListener('blur', () => validarDocInput(contratadoDocEl, errorContratadoDoc));

    // Validar data ao mudar
    if (dataEntregaEl) dataEntregaEl.addEventListener('change', () => validarDataInput(dataEntregaEl, errorDataEntrega));

    // Validar valores ao digitar
    if (valorTotalEl) valorTotalEl.addEventListener('input', () => validarNumeroNegativo(valorTotalEl, errorValores, "Valor Total"));
    if (valorEntradaEl) valorEntradaEl.addEventListener('input', () => validarNumeroNegativo(valorEntradaEl, errorValores, "Valor de Entrada"));
    
    // Validar quantidades ao digitar
    qntInputs.forEach(input => {
        input.addEventListener('input', () => validarQuantidadesNegativas(qntInputs, errorQuantidades));
    });


    // ==================================================
    // === LÓGICA DO BOTÃO SUBMIT (GERAR)
    // ==================================================
    if (form) {
        form.addEventListener('submit', (event) => {
            
            // Roda todas as validações UMA ÚLTIMA VEZ
            const isContratanteDocValid = validarDocInput(contratanteDocEl, errorContratanteDoc);
            const isContratadoDocValid = validarDocInput(contratadoDocEl, errorContratadoDoc);
            const isDataValid = validarDataInput(dataEntregaEl, errorDataEntrega);
            const isValorTotalValid = validarNumeroNegativo(valorTotalEl, errorValores, "Valor Total");
            // Se o valor total for válido, checa a entrada. Se não, o erro já está lá.
            const isValorEntradaValid = isValorTotalValid ? validarNumeroNegativo(valorEntradaEl, errorValores, "Valor de Entrada") : false;
            const areQuantidadesValid = validarQuantidadesNegativas(qntInputs, errorQuantidades);

            // Verifica se algum campo obrigatório está vazio (o 'required' do HTML cuida disso, mas é uma segurança extra)
            if (!contratanteDocEl.value || !contratadoDocEl.value || !dataEntregaEl.value) {
                 // O navegador vai mostrar o pop-up de "preencha este campo"
                 return;
            }
            
            // Se QUALQUER uma for inválida, previne o envio
            if (!isContratanteDocValid || !isContratadoDocValid || !isDataValid || !isValorTotalValid || !isValorEntradaValid || !areQuantidadesValid) {
                event.preventDefault(); 
                alert("Por favor, corrija os campos destacados em vermelho antes de gerar o contrato.");
                return;
            }

            // --- CONFIRMAÇÃO ---
            const textoAviso = "O uso desse contrato exime o desenvolvedor de qualquer responsabilidade jurídica. Leia atentamente, peça recomendação profissional antes de enviar ao seu cliente.\n\nSe você concorda com o termo acima clique em 'OK'.";
            const usuarioConcordou = confirm(textoAviso);

            if (!usuarioConcordou) {
                event.preventDefault(); 
            }
            // Se concordou, o formulário é enviado (POST).
        });
    }

    // ==================================================
    // === LÓGICA DO BOTÃO LIMPAR (Item 3)
    // ==================================================
    if (btnLimpar) {
        btnLimpar.addEventListener('click', () => {
            if (confirm("Tem certeza que deseja limpar todos os campos do formulário?")) {
                
                const inputs = form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]');
                
                inputs.forEach(input => {
                    input.value = '';
                });

                // Limpa também todas as mensagens de erro
                clearError(contratanteDocEl, errorContratanteDoc);
                clearError(contratadoDocEl, errorContratadoDoc);
                clearError(dataEntregaEl, errorDataEntrega);
                clearError(valorTotalEl, errorValores); // Limpa o span de valores
                clearError(valorEntradaEl, null); // Só remove a classe 'invalid'
                qntInputs.forEach(input => input.classList.remove('invalid'));
                clearError(null, errorQuantidades); // Limpa o span de quantidades
            }
        });
    }

});