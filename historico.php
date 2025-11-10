<?php 
    // --- Configuração da Página ---
    $page_title = "Histórico de Notas Fiscais";
    
    // CSS específico desta página (do {% block head %})
    $additional_head_tags = ''; // Não há CSS extra no original

    // Scripts específicos desta página (do {% block scripts %})
    $additional_scripts = '
        <script src="static/js/historico.js"></script>
    ';
    // --- Fim da Configuração ---

    require 'config.php';       // 1. Inclui a configuração e sessão
    require 'auth_check.php'; // 2. Protege a página
    require 'includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

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
<?php 
    require 'includes/footer.php'; // 4. Inclui o rodapé
?>