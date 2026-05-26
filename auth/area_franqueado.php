<?php
require '../config.php';
require './auth_check.php'; // Proteção básica de login
$page_title = "Painel do Franqueado"; // Define o nome aqui
$sessao_nome = "Área do Franqueado"; // Isso vai aparecer na barra azul
require '../includes/header.php';

// Proteção Extra: Se for um usuário de loja, não entra aqui
if (strpos($_SESSION['username'], 'loja-') === 0) {
    header("Location: ../selecao_ferramentas.php?erro=acesso_negado");
    exit;
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/selecao.css">

<div class="selecao-container">
     <div class="campanhas">
    <div class="campanha-2 campanha">
        <a href="../Custos/custos_selecao.php"><img src="../static/img/custos.jfif"
                alt="Loja">
            <p>Custo dos Produtos</p>
        </a>
    </div>
        <div class="campanha-2 campanha">
        <a href="../financeiro/contas_pagar.php"><img src="../static/img/fachada.webp"
                alt="Loja">
            <p>Contas a Pagar</p>
        </a>
    </div>
        <div class="campanha-2 campanha">
        <a href="../Recebimentos/recebimentos.php"><img src="../static/img/caminhoes.jfif"
                alt="caminhões">
            <p>Recebimentos</p>
        </a>
    </div>
      <div class="campanha-2 campanha">
        <a href="../planejador/planejador.php"><img src="../static/img/pascoa2027.JPG"
                alt="Loja">
            <p>Planejador Páscoa 2027</p>
        </a>
    </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>