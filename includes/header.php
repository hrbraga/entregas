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
            <h1>Controle de Entregas</h1>
        </div>
        <ul class="nav-links">
            <li><a href="../Recebimentos/recebimentos.php">Entregas</a></li>
            <li><a href="../Recebimentos/historico.php">Histórico</a></li>
            <li><a href="../Recebimentos/dashboard.php">Dashboard</a></li>
            
            <li><a href="../selecao_ferramentas.php">Ferramentas</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="../auth/logout.php">Sair (<?= htmlspecialchars($_SESSION['username']); ?>)</a></li>
            <?php else: ?>
                <li><a href="../auth/login.php">Login</a></li>
            <?php endif; ?>
            
        </ul>
    </nav>
    
    <main>