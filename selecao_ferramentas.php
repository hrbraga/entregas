<?php
require 'config.php';
require 'auth/custos_auth_check.php'; // Protege esta página (Nível 1)
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/global.css">
    <link rel="stylesheet" href="static/css/selecao.css">
    <link rel="shortcut icon" href="static/img/chocolatinho.png" type="image/x-icon">
    <title>Caixa de Ferramentas</title>
</head>

<body>
    <header>
        <h1>Caixa de Ferramentas</h1>
        <div style="position: absolute; top: 10px; right: 20px; font-size: 1.6rem; color: white; z-index: 100;">
            Acesso: <?php echo htmlspecialchars($_SESSION['rcky_code']); ?> | <a href="auth/custos_logout.php"
                style="color: white; text-decoration: underline;">Sair</a>
        </div>
    </header>

    <main>

        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha"><a href="auth/area_franqueado.php"><img src="static/img/franqueado.jfif"
                            alt="Loja">
                        <p>Área do Franqueado</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="etiquetas/etiquetas.php">
                        <img src="static/img/megaloja.webp" alt="Mega Loja">
                        <p>Etiquetas Prateleira</p>
                    </a>
                </div>
                  <div class="campanha-2 campanha">
                    <a href="gestao/quadro_gestao.php">
                        <img src="static/img/gestao.jfif" alt="Quadro de Gestão">
                        <p>Quadro de Gestão</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="validades/validades.php">
                        <img src="static/img/trufas.webp" alt="Controle de Validades">
                        <p>Controle de Validades</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="delivery/gerador_delivery.php">
                        <img src="static/img/delivery.jfif" alt="Delivery">
                        <p>Etiqueta Delivery</p>
                    </a>
                </div>
                 <div class="campanha-2 campanha">
                    <a href="contrato/contratoPetit.php">
                        <img src="static/img/petitDeli.jpg" alt="Bombons Petit Deli">
                        <p>Contrato Encomenda Petit Deli</p>
                    </a>
                </div>
        </section>
    </main>

<div id="modalAvisoAcesso" style="display: flex; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <h2 style="color: #333; margin-top: 0;">Comunicado Importante</h2>
        <p style="color: #555; font-size: 16px; line-height: 1.5; margin: 20px 0;">
            Prezados, realizamos mudanças importantes na estrutura de acessos do sistema.
            <br><br>
            Caso você não consiga acessar a área do franqueado, <strong style="font-size: 16px;">entre em contato com o Hugo</strong> para que ele regularize o seu nível de acesso.
        </p>
        <button onclick="document.getElementById('modalAvisoAcesso').style.display='none'" 
                style="font-size: 16px; padding: 10px 25px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            Entendido
        </button>
    </div>
</div>

<script>
// Se na URL tiver o erro 'acesso_negado', abre o modal automaticamente
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('erro') && urlParams.get('erro') === 'acesso_negado') {
        document.getElementById('modalAvisoAcesso').style.display = 'flex';
    }
};
</script>

    <script src="static/js/campanha.js"></script>
</body>

</html>