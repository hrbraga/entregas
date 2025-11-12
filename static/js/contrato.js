document.addEventListener('DOMContentLoaded', () => {
    // Pega o formulário
    const form = document.getElementById('contractForm');
    
    if (form) {
        // "Ouve" a tentativa de *envio* (submit)
        form.addEventListener('submit', (event) => {
            
            // 1. O texto do seu aviso. O \n quebra a linha no pop-up.
            const textoAviso = "O uso desse contrato exime o desenvolvedor de qualquer responsabilidade jurídica. Leia atentamente, peça recomendação profissional antes de enviar ao seu cliente.\n\nSe você concorda com o termo acima clique em 'OK'.";

            // 2. Chama o pop-up de confirmação
            const usuarioConcordou = confirm(textoAviso);

            // 3. Verifica a resposta
            if (!usuarioConcordou) {
                // Se clicou "Cancelar", IMPEDE o envio do formulário
                console.log("Envio cancelado pelo usuário.");
                event.preventDefault(); // A mágica está aqui.
            }
            // Se clicou "OK", o script não faz nada e deixa o formulário ser enviado.
        });
    }
});