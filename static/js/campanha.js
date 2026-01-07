const usuariosAutorizados = ["2631","4402","1799","4195","4183","1065","7959","1370","3730","6446","2392","9991","1370","4028","4048","8251","3809","5835", "6890", "8820", "7552","7662","7731", "4012","9621", "5901", "1962"];

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