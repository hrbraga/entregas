<?php 
    // --- Configuração da Página ---
    $page_title = "Dashboard de Entregas";
    
    // CSS específico desta página (do {% block head %})
    $additional_head_tags = '
        <link rel="stylesheet" href="../static/css/dashboard.css">
    ';

    // Scripts específicos desta página (do {% block scripts %})
    $additional_scripts = '
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../static/js/dashboard.js"></script>
        <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    ';
    // --- Fim da Configuração ---

    require '../config.php';       // 1. Inclui a configuração e sessão
    require '../auth/auth_check.php'; // 2. Protege a página
    require '../includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

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
            </div>
            <div class="totalizer-item">
                <p class="label">A RECEBER</p>
                <span class="value to-receive" id="a-receber-val">0</span>
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
<?php 
    require '../includes/footer.php'; // 4. Inclui o rodapé
?>