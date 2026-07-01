<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';
$page_title = "Conciliação Bancária";
$sessao_nome = "Conciliação Bancária";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

try {
    $stmt_bancos = $db_financeiro->prepare("SELECT id, nome_conta, banco FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_bancos->execute([$id_usuario]);
    $bancos = $stmt_bancos->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $bancos = [];
}

try {
    $stmt_cat = $db_financeiro->query("SELECT * FROM categorias_financeiras ORDER BY tipo ASC, grupo ASC, nome ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categorias = [];
}
// -----------------------------------------------------
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/financeiro.css">
<link rel="stylesheet" href="../static/css/conciliacao.css">

<?php require 'nav.php'; ?>

<div class="financeiro-container" style="max-width: 1400px; margin: 0 auto;">
    
    <div class="conciliacao-header">
        <div style="flex: 1; min-width: 250px;">
            <label style="font-weight: bold; display: block; margin-bottom: 8px; font-size: 14px;">1. Selecione a Conta Bancária:</label>
            <select id="conta_selecionada" class="form-control">
                <option value="">Selecione a conta para conciliar...</option>
                <?php foreach ($bancos as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome_conta']) ?> (<?= htmlspecialchars($b['banco']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 250px;">
            <label style="font-weight: bold; display: block; margin-bottom: 8px; font-size: 14px;">2. Carregue o ficheiro OFX do Banco:</label>
            <input type="file" id="arquivo_ofx" accept=".ofx" class="form-control" style="font-size: 12px;">
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="processarOFX()" style="padding: 10px 25px; font-size: 15px; height: 42px;">📥 Importar Extrato</button>
        </div>
    </div>

    <div class="conciliacao-grid">
        
        <div class="coluna-banco">
            <div class="header-banco">🏦 Extrato do Banco (OFX)</div>
            <div class="lista-transacoes" id="lista_ofx">
                <div style="text-align: center; color: #777; margin-top: 60px;">
                    <span style="font-size: 40px;">📄</span>
                    <h3 style="margin-top: 15px;">Nenhum ficheiro carregado</h3>
                    <p style="font-size: 14px;">Selecione a conta e faça o upload do ficheiro OFX acima.</p>
                </div>
            </div>
        </div>

        <div class="coluna-sistema">
            <div class="header-sistema">💻 Registos no Sistema (Caixa)</div>
            <div class="lista-transacoes" id="lista_sistema">
                <div style="text-align: center; color: #777; margin-top: 60px;">
                    <span style="font-size: 40px;">🔍</span>
                    <h3 style="margin-top: 15px;">Aguardando leitura...</h3>
                    <p style="font-size: 14px;">Os possíveis "matches" aparecerão aqui.</p>
                </div>
            </div>
        </div>
        
    </div>
</div>

<div id="modalCategoria" class="modal-financeiro dark-overlay" style="display: none;">
    <div class="modal-content modal-md">
        <div class="modal-header" style="background: #17a2b8; color: white;">
            <h2>Classificar Nova Transação</h2>
            <button onclick="fecharModalCategoria()" class="close-modal">&times;</button>
        </div>

        <form id="formCategoria" class="form-body" onsubmit="salvarNovaTransacao(event)">
            <input type="hidden" id="modal_tx_index">

            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #ddd;">
                <div style="font-size: 12px; color: #777; font-weight: bold; margin-bottom: 5px;" id="modal_tx_data">DATA</div>
                <div style="font-size: 16px; font-weight: bold; color: #333;" id="modal_tx_desc">DESCRIÇÃO</div>
                <div style="font-size: 22px; font-weight: bold; margin-top: 5px;" id="modal_tx_valor">VALOR</div>
            </div>

            <div class="form-group">
                <label style="font-weight: bold;">Categoria Financeira</label>
                <select name="id_categoria" id="modal_id_categoria" class="form-control" required>
                    <option value="">Selecione a Categoria...</option>
                    <?php
                    $tipo_atual = '';
                    foreach ($categorias as $cat) {
                        if ($cat['tipo'] !== $tipo_atual) {
                            if ($tipo_atual !== '') echo '</optgroup>';
                            $tipo_atual = $cat['tipo'];
                            // Divide visualmente Receitas de Despesas
                            echo '<optgroup label="--- ' . mb_strtoupper($tipo_atual, 'UTF-8') . 'S ---">';
                        }
                        
                        $grupo = !empty($cat['grupo']) ? $cat['grupo'] : 'Outros';
                        echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($grupo . ' ↳ ' . $cat['nome']) . '</option>';
                    }
                    if ($tipo_atual !== '') echo '</optgroup>';
                    ?>
                </select>
            </div>

            <div class="modal-footer" style="margin-top: 20px;">
                <button type="button" onclick="fecharModalCategoria()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-confirm" style="background: #17a2b8;">Confirmar Inserção</button>
            </div>
        </form>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new TomSelect('#modal_id_categoria', {
        create: false,
        placeholder: 'Pesquisar categoria...',
        maxOptions: 500
    });
});
</script>

<script src="../static/js/conciliacao.js"></script>

<?php require '../includes/footer.php'; ?>