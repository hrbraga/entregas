document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('contractForm');
    const btnLimpar = document.getElementById('limparContrato');
    
    // ==================================================
    // === LÓGICA DO BOTÃO SUBMIT (GERAR)
    // ==================================================
    if (form) {
        form.addEventListener('submit', (event) => {
            
            // --- VALIDAÇÃO (Itens 2, 3, 4, 5, 6) ---
            const contratanteDoc = document.getElementById('contratanteDoc').value;
            const contratadoDoc = document.getElementById('contratadoDoc').value;
            const dataEntrega = document.getElementById('dataEntrega').value;
            const valorTotal = parseFloat(document.getElementById('valorTotal').value);
            const valorEntrada = parseFloat(document.getElementById('valorEntrada').value);

            // 1. Validar Documentos (Bug 4)
            if (!validarDocumento(contratanteDoc)) {
                event.preventDefault(); 
                alert("Erro: O CPF/CNPJ do CONTRATANTE é inválido.");
                return;
            }
            if (!validarDocumento(contratadoDoc)) {
                event.preventDefault(); 
                alert("Erro: O CPF/CNPJ do CONTRATADO é inválido.");
                return;
            }
            
            // 2. Validar Data (Item 6)
            if (!validarDataEntrega(dataEntrega)) {
                event.preventDefault(); 
                alert("Erro: A data da entrega não pode ser anterior à data de hoje.");
                return;
            }

            // 3. Validar Valores Negativos (Bug 2)
            if (valorTotal < 0 || valorEntrada < 0) {
                event.preventDefault();
                alert("Erro: Os valores (Total e Entrada) não podem ser negativos.");
                return;
            }

            // 4. Validar Quantidades Negativas (Bug 3)
            const quantidades = form.querySelectorAll('.sabor-grid input[type="number"]');
            let hasNegativeQty = false;
            quantidades.forEach(input => {
                if (parseFloat(input.value) < 0) {
                    hasNegativeQty = true;
                }
            });
            
            if (hasNegativeQty) {
                event.preventDefault();
                alert("Erro: A quantidade de bombons não pode ser negativa.");
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
                    // Limpa o valor
                    input.value = '';
                });
            }
        });
    }

    // ==================================================
    // === FUNÇÕES DE VALIDAÇÃO (Itens 4, 5, 6)
    // ==================================================

    function validarDocumento(doc) {
        const cleanDoc = doc.replace(/[^\d]+/g, ''); 

        if (cleanDoc.length === 11) {
            return validarCPF(cleanDoc);
        } else if (cleanDoc.length === 14) {
            return validarCNPJ(cleanDoc);
        } else {
            return false;
        }
    }

    function validarDataEntrega(dataString) {
        if (!dataString) return false;
        
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0); 

        const partes = dataString.split('-');
        const dataEntrega = new Date(partes[0], partes[1] - 1, partes[2]); 

        return dataEntrega >= hoje;
    }

    // --- Algoritmos Padrão de Validação (Bug 4) ---

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
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
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

});