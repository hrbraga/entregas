<?php 
    require '../config.php';       // 1. Inclui a configuração e sessão
    require '../auth/auth_check.php'; // 2. Protege a página
    require '../includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<!DOCTYPE html>
<html lang="pr-BR"></html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://img.icons8.com/dusk/64/cafe.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/planilhas.css">
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/style.css">
    <title>Histório Notas Fiscais</title>
</head>

<body></body>

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