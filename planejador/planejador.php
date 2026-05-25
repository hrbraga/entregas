<?php
// O seu auth_check já embute o config.php que trata a sessão
require '../config.php';
require_once '../auth/auth_check.php';

$page_title = "Planejador de Páscoa";
$sessao_nome = "Planejador de Páscoa";

// Identificação da loja logada na sessão
$loja_logada = $_SESSION['usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 'loja_nao_identificada';

$db_path = __DIR__ . '/../db/pascoa.db';
$db_pascoa = new PDO('sqlite:' . $db_path);
$db_pascoa->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Garante a tabela estruturada para os inputs e importações
$db_pascoa->exec("CREATE TABLE IF NOT EXISTS pedidos_pascoa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    loja_id TEXT,
    codigo_sap TEXT,
    un_vend_ant_loja INTEGER DEFAULT 0,
    un_vend_ant_vd INTEGER DEFAULT 0,
    sugestao_loja INTEGER DEFAULT 0,
    sugestao_vd INTEGER DEFAULT 0,
    pedido_loja INTEGER DEFAULT 0,
    pedido_vd INTEGER DEFAULT 0,
    UNIQUE(loja_id, codigo_sap)
)");

// Processamento do salvamento automático (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_update'])) {
    $campo = $_POST['campo'];
    $valor = (int)$_POST['valor'];
    $sap = $_POST['codigo_sap'];

    $campos_permitidos = ['pedido_loja', 'pedido_vd', 'un_vend_ant_loja', 'un_vend_ant_vd'];
    if (in_array($campo, $campos_permitidos)) {
        $stmt = $db_pascoa->prepare("INSERT INTO pedidos_pascoa (loja_id, codigo_sap, $campo) VALUES (?, ?, ?) 
            ON CONFLICT(loja_id, codigo_sap) DO UPDATE SET $campo = excluded.$campo");
        $stmt->execute([$loja_logada, $sap, $valor]);
        echo "Sucesso";
    }
    exit;
}

$mensagem = '';

// Processamento do novo CSV padrão Cacau Show (Separador ';')
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tipo_importacao'])) {
    $tipo = $_POST['tipo_importacao'];

    if (isset($_FILES['planilha_csv']) && $_FILES['planilha_csv']['error'] == 0) {
        $arquivo = $_FILES['planilha_csv']['tmp_name'];

        if (($handle = fopen($arquivo, "r")) !== FALSE) {
            $col_sugestao = ($tipo === 'loja') ? 'sugestao_loja' : 'sugestao_vd';
            $col_vendas_ant = ($tipo === 'loja') ? 'un_vend_ant_loja' : 'un_vend_ant_vd';

            $stmt = $db_pascoa->prepare("INSERT INTO pedidos_pascoa (loja_id, codigo_sap, $col_vendas_ant, $col_sugestao) 
                VALUES (?, ?, ?, ?) ON CONFLICT(loja_id, codigo_sap) DO UPDATE SET 
                $col_vendas_ant = excluded.$col_vendas_ant, $col_sugestao = excluded.$col_sugestao");

            while (($linha = fgetcsv($handle, 2000, ";")) !== FALSE) {
                if (isset($linha[1]) && strtoupper(trim($linha[1])) == 'CODIGO') {
                    continue;
                }

                if (isset($linha[1]) && is_numeric(trim($linha[1]))) {
                    $sap = trim($linha[1]);
                    $vendas_ant = (int)($linha[5] ?? 0);
                    $sugestao = (int)($linha[10] ?? 0);

                    $stmt->execute([$loja_logada, $sap, $vendas_ant, $sugestao]);
                }
            }
            fclose($handle);
            $nome_aba = ($tipo === 'loja') ? 'LOJA' : 'VENDA DIRETA';
            $mensagem = "<div class='alert-success'>Planilha de $nome_aba importada com sucesso!</div>";
        }
    }
}

// Resgata o histórico gravado
$stmt = $db_pascoa->prepare("SELECT * FROM pedidos_pascoa WHERE loja_id = ?");
$stmt->execute([$loja_logada]);
$dados_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<link rel="stylesheet" href="../static/css/planejador.css">
<script src="../static/js/arrays_pascoa.js"></script>

<div class="container-dashboard">

    <div class="top-actions">

        <a href="dashboard_planejador.php"
            class="btn-dashboard">
            📊 Abrir Dashboard
        </a>

        <button
            class="btn-exportar"
            onclick="exportarXLSX()">
            ⬇ Exportar XLSX
        </button>

    </div>

    <div class="abas-container">
        <button class="aba-btn ativa" onclick="mudarAba(event, 'aba-loja')">Pedido LOJA</button>
        <button class="aba-btn" onclick="mudarAba(event, 'aba-vd')">Pedido VD</button>
        <button class="aba-btn" onclick="mudarAba(event, 'aba-total')">Resumo Consolidado</button>
    </div>

    <div id="aba-loja" class="conteudo-aba ativo">
        <div class="form-import">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tipo_importacao" value="loja">
                <label><strong>Importar Sugestão LOJA (CSV da Simulação):</strong></label>
                <input type="file" name="planilha_csv" accept=".csv" required>
                <button type="submit" class="btn-processar">Processar</button>
            </form>
        </div>

        <table class="tabela-pedidos">
            <thead>
                <tr>
                    <th class="text-left">Cód SAP</th>
                    <th class="text-left">Produto</th>
                    <th>Grupo</th>
                    <th>Preço Venda</th>
                    <th style="background:#fff3cd;">Vendido 26 (Cx)</th>
                    <th>Sugestão CS (Cx)</th>
                    <th style="background:#d4edda;">Seu Pedido Loja (Cx)</th>
                </tr>
            </thead>
            <tbody id="tbody-loja"></tbody>
        </table>
    </div>

    <div id="aba-vd" class="conteudo-aba">
        <div class="form-import">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tipo_importacao" value="vd">
                <label><strong>Importar Sugestão VD (CSV da Simulação):</strong></label>
                <input type="file" name="planilha_csv" accept=".csv" required>
                <button type="submit" class="btn-processar">Processar</button>
            </form>
        </div>

        <table class="tabela-pedidos">
            <thead>
                <tr>
                    <th class="text-left">Cód SAP</th>
                    <th class="text-left">Produto</th>
                    <th>Grupo</th>
                    <th>Preço Venda</th>
                    <th style="background:#fff3cd;">Vendido 26 (Cx)</th>
                    <th>Sugestão CS (Cx)</th>
                    <th style="background:#d4edda;">Seu Pedido VD (Cx)</th>
                </tr>
            </thead>
            <tbody id="tbody-vd"></tbody>
        </table>
    </div>

    <div id="aba-total" class="conteudo-aba">
        <h3>Visão Geral Combinada (Loja + Venda Direta)</h3>
        <table class="tabela-pedidos">
            <thead>
                <tr>
                    <th class="text-left">Cód SAP</th>
                    <th class="text-left">Produto</th>
                    <th>Grupo</th>
                    <th>Preço Venda</th>
                    <th>Total Vendido Anterior (Cx)</th>
                    <th>Total Sugerido (Cx)</th>
                    <th style="background:#d1ecf1;">Total Planejado (Cx)</th>
                    <th style="background:#e2e3e5;">Faturamento Previsto</th>
                </tr>
            </thead>
            <tbody id="tbody-total"></tbody>
        </table>
    </div>
</div>

<div class="footer-toggle" onclick="toggleFooter()">
    Esconder ✕
</div>

<div class="footer-totais">
    <div class="footer-totais-inner">
        <div class="total-box">
            <span class="total-label">Sell In Sugestão:</span>
            <span class="total-value" id="foot-vendido">R$ 0,00</span>
        </div>
        <div class="total-box">
            <span class="total-label">Sell In Pedido:</span>
            <span class="total-value" id="foot-sugestao">R$ 0,00</span>
        </div>
        <div class="total-box destaque">
            <span class="total-label">Seu Pedido Total:</span>
            <span class="total-value" id="foot-pedido">R$ 0,00</span>
        </div>
    </div>
    <div class="footer-cluster">
        <div id="cluster-info"></div>
    </div>
</div>

<script>
    const dadosBanco = <?= json_encode($dados_banco) ?>;
</script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="../static/js/planejador.js"></script>

<?php include '../includes/footer.php'; ?>