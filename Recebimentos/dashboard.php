<?php
require '../config.php';       // 1. Inclui a configuração e sessão
require '../auth/auth_check.php'; // 2. Protege a página
require '../includes/header.php';  // 3. Inclui o cabeçalho HTML
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
    <link rel="stylesheet" href="../static/css/dashboard.css">
    <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <title>Dashboard Entregas</title>
</head>

<body>

    <div class="dashboard-container">
        <div class="card totalizer-card">
            <h2>Totalizador de Entregas</h2>

            <div class="totalizer-items-container">
                <div class="totalizer-item">
                    <p class="label">TOTAL PEDIDO</p>
                    <span class="value" id="total-pedido-val">0</span>
                </div>
                <div class="totalizer-item">
                    <p class="label">RECEBIDO</p>
                    <span class="value received" id="recebido-val">0</span>
                    <small class="percentual-a-receber" id="recebido-pct" style="display: block; font-size: 0.8rem; margin-top: 5px;">0%</small>
                </div>
                <div class="totalizer-item">
                    <p class="label">A RECEBER</p>
                    <span class="value to-receive" id="a-receber-val">0</span>
                    <small class="percentual-a-receber" id="a-receber-pct" style="display: block; font-size: 0.8rem; margin-top: 5px;">0%</small>
                </div>
            </div>

        </div>

        <div class="card">
            <h2>Progresso Geral de Entregas</h2>
            <canvas id="progress-chart"></canvas>
        </div>

        <div class="card">
            <h2>Status de Entrega por SKU</h2>
            <canvas id="sku-status-chart"></canvas>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <h2>Status de Entrega por Grupo</h2>
            <canvas id="group-status-chart"></canvas>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../static/js/dashboard.js"></script>
<script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<?php
require '../includes/footer.php'; // 4. Inclui o rodapé
?>