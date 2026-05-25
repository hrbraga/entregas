<?php
// Tenta encontrar o config.php na raiz do projeto
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    die('Erro: config.php não encontrado.');
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Controle de Entregas'; ?></title>

    <link rel="stylesheet" href="../static/css/style.css">

    <?php echo isset($additional_head_tags) ? $additional_head_tags : ''; ?>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <div class="sua-classe-da-barra-azul">
                <span  class="titulo-paginas">
                    <?php echo isset($sessao_nome) ? mb_strtoupper($sessao_nome) : "CONTROLE"; ?>
                </span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="/selecao_ferramentas.php">Caixa de Ferramentas</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="../auth/logout.php">Sair (<?= htmlspecialchars($_SESSION['username']); ?>)</a></li>
            <?php else: ?>
                <li><a href="../auth/login.php">Login</a></li>
            <?php endif; ?>

        </ul>
    </nav>

    <main>