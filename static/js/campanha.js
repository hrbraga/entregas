const usuariosAutorizados = ["1871", "6890", "8820", "7731", "4012","9621"];

// Aguarda o carregamento do DOM
document.addEventListener("DOMContentLoaded", function () {
  // Seleciona o link contido na div com a classe 'campanha-3'
  const linkCampanha3 = document.querySelector('.campanha-3 a');

  if (linkCampanha3) {
    // Adiciona um listener para o evento de clique
    linkCampanha3.addEventListener('click', function (event) {
      // Solicita que o usuário informe seu código RCKY
      const rckyUsuario = prompt("Digite seu RCKY para acessar a campanha:");

      // Verifica se o input possui exatamente 4 dígitos numéricos
      if (!/^\d{4}$/.test(rckyUsuario)) {
        event.preventDefault();
        alert("Código inválido! Digite apenas 4 dígitos numéricos.");
        return;
      }

      // Se o código não estiver autorizado, impede o redirecionamento
      if (!usuariosAutorizados.includes(rckyUsuario)) {
        event.preventDefault();
        alert("Acesso negado! Você não tem permissão para acessar essa campanha. Valor R$ 30,00. Contate o Hugo, para solicitar o código pix e ser liberado.");
      }
      // Se o código estiver autorizado, o redirecionamento prossegue normalmente.
    });
  }
});