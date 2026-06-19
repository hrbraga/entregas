<?php
// Descobre qual é o nome do ficheiro atual (ex: dashboard.php, dre.php)
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Função rápida para injetar o estilo se o botão corresponder à página atual
function ativo($nome_pagina, $pagina_atual) {
    return ($nome_pagina === $pagina_atual) ? 'style="font-weight: bold; background: #f8f9fa;"' : '';
}
?>

<div class="financeiro-nav" style="margin-bottom: 30px;">
    <a href="dashboard.php" <?= ativo('dashboard.php', $pagina_atual) ?>>🏠 Dashboard</a>
    
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Cadastros ▾</button>
        <div class="nav-dropdown-content">
            <a href="gerenciar_contas.php" <?= ativo('gerenciar_contas.php', $pagina_atual) ?>>Contas Correntes</a>
            <a href="#">Fornecedores</a>
            <a href="#">Clientes</a>
        </div>
    </div>
    
    <a href="caixa_bancos.php" <?= ativo('caixa_bancos.php', $pagina_atual) ?>>Caixa e Bancos</a>
    <a href="contas_pagar.php" <?= ativo('contas_pagar.php', $pagina_atual) ?>>Contas a Pagar</a>
    <a href="contas_receber.php" <?= ativo('contas_receber.php', $pagina_atual) ?>>Contas a Receber</a>
    
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Relatórios ▾</button>
        <div class="nav-dropdown-content">
            <a href="relatorio_contas.php" <?= ativo('relatorio_contas.php', $pagina_atual) ?>>Pagamentos</a>
            <a href="dre.php" <?= ativo('dre.php', $pagina_atual) ?>>📊 DRE</a>
            <a href="fluxo_caixa.php" <?= ativo('fluxo_caixa.php', $pagina_atual) ?>>📈 Fluxo de Caixa</a>
        </div>
    </div>
</div>