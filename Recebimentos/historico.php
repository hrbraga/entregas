<?php 
    require '../config.php';       // 1. Inclui a configuração e sessão
    require '../auth/auth_check.php'; // 2. Protege a página
?>

<!DOCTYPE html>
<html lang="pr-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://img.icons8.com/dusk/64/cafe.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/planilhas.css">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/style.css">
    <title>Histório Notas Fiscais</title>
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

<div class="container">
    <div id="feedback-message" class="feedback-message" style="display: none;"></div>
    
    <h2>Histórico de Notas Fiscais</h2>
    <table class="historical-table">
        <thead>
            <tr>
                <th>Número da Nota Fiscal</th>
                <th>Valor Total</th>
                <th>Data de Emissão</th>
                <th>Data de Importação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="notas-fiscais-body">
            </tbody>
    </table>
</div>
</body>
<script src="../static/js/historico.js"></script>
<?php 
    require '../includes/footer.php'; // 4. Inclui o rodapé
?>