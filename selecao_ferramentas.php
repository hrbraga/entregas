<?php
$page_title = "Custo Produtos";

// CSS específico para esta página
$additional_head_tags = '
        <link rel="stylesheet" href="static/Custos/css/global.css">
        <link rel="stylesheet" href="static/Custos/css/selecao.css">
    ';

require 'config.php';       // 1. Inclui a configuração e sessão
require 'auth_check.php'; // 2. Protege a página
require 'includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<hr>
<h2>Ferramentas </h2>
<hr>
<section class="custos">
    <div class="campanhas">
        <div class="campanha-2 campanha">
            <a href="custos_selecao.php">
                <img src="static/Custos/src/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                <p>Custo Produtos</p>
            </a>
        </div>
        <div class="controleEntradas campanhas">
            <div class="campanha-2 campanha">
                <a href="login.php">
                    <img src="static/Custos/src/img/caminhoes.jfif" alt="Caminhões de Entrega">
                    <p>Entregas de Natal 2025</p>
                </a>
            </div>
        </div>
</section>

<script src="static/Custos/js/campanha.js"></script>

<?php
require 'includes/footer.php'; // 4. Inclui o rodapé
?>