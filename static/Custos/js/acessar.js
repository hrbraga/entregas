function acessar() {
    const codigoDigitado = document.getElementById("codigo").value;
    const mensagemElemento = document.getElementById("mensagem");

    if (codigoDigitado.trim() === "") {
        mensagemElemento.textContent = "Digite o código de acesso.";
        mensagemElemento.style.color = "red";
        return;
    }

    if (!/^\d{4}$/.test(codigoDigitado)) {
        mensagemElemento.textContent = "Código inválido. Digite 4 números.";
        mensagemElemento.style.color = "red";
        return;
    }

    const codigosValidos = ["4012", "4195", "3730", "6995", "3705", "2932", "5100", "6446", "4028", "1370", "9998", "6850", "2392", "9621", "9438", "7552", "7662", "6346", "4048", "9194", "9603", "8372", "1871", "1692", "1926", "5901", "6890", "8820", "2631", "4183", "7731", "1300", "1065", "3809", "5835", "3540", "8135", "8844"];

    if (codigosValidos.includes(codigoDigitado)) {
        alert("Acesso Autorizado!");
        setTimeout(function () {
            window.location.href = '/custos/html/selecao.html';
        }, 50);
    } else {
        mensagemElemento.textContent = "Código de acesso inválido.";
        mensagemElemento.style.color = "red";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const formulario = document.getElementById("formularioAcesso");
    formulario.addEventListener("submit", function (event) {
        event.preventDefault();
        acessar();
    });
});