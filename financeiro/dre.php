<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "DRE - Demonstração de Resultados";
$sessao_nome = "DRE (Regime de Caixa)";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// Filtros Iniciais (Padrão: Mês Atual)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

try {
    // Busca todas as Movimentações do Caixa + Taxas Ocultas da Cielo em apenas uma query
    $sql_dre = "
        SELECT 'Caixa' as origem, mc.tipo as fluxo, mc.valor, COALESCE(cat.nome, 'Sem Categoria') as categoria, COALESCE(cat.grupo, 'Outros') as grupo
        FROM movimentacoes_caixa mc
        LEFT JOIN categorias_financeiras cat ON mc.id_categoria = cat.id
        WHERE mc.id_usuario = ? AND mc.data_movimento BETWEEN ? AND ?

        UNION ALL
        
        SELECT 'Cielo' as origem, 'Saida' as fluxo, cr.taxa_importacao as valor, 'Taxas de Cartão' as categoria, 'Deduções e Impostos' as grupo
        FROM contas_receber cr
        WHERE cr.id_usuario = ? AND cr.status = 'Recebido' AND COALESCE(cr.taxa_importacao, 0) > 0 AND cr.data_pagamento BETWEEN ? AND ?
    ";

    $params = [$id_usuario, $data_inicio, $data_fim, $id_usuario, $data_inicio, $data_fim];
    
    $stmt = $db_financeiro->prepare($sql_dre);
    $stmt->execute($params);
    $movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Arrays para organizar a cascata do DRE
    $receitas_brutas = [];
    $deducoes = [];
    $custos_variaveis = [];
    $despesas_operacionais = [];

    // Totais
    $tot_receita_bruta = 0;
    $tot_deducoes = 0;
    $tot_custos = 0;
    $tot_despesas = 0;

    // Motor de Classificação Automática
    foreach ($movimentos as $m) {
        $val = (float) $m['valor'];
        $cat = $m['categoria'];
        $grupo = mb_strtolower($m['grupo'], 'UTF-8');
        $cat_low = mb_strtolower($cat, 'UTF-8');

        if ($m['fluxo'] === 'Entrada') {
            // É Receita!
            $receitas_brutas[$cat] = ($receitas_brutas[$cat] ?? 0) + $val;
            $tot_receita_bruta += $val;
        } else {
            // É Saída (Precisamos descobrir onde se encaixa no DRE)
            if (strpos($grupo, 'deduç') !== false || strpos($grupo, 'imposto') !== false || strpos($cat_low, 'taxa') !== false || strpos($cat_low, 'desconto') !== false || strpos($cat_low, 'devoluç') !== false) {
                // 1. Deduções (Taxas Cielo, Descontos dados, Impostos de venda)
                $deducoes[$cat] = ($deducoes[$cat] ?? 0) + $val;
                $tot_deducoes += $val;
            } elseif (strpos($grupo, 'custo') !== false || strpos($grupo, 'fornecedor') !== false || strpos($grupo, 'variável') !== false || strpos($cat_low, 'mercadoria') !== false) {
                // 2. Custos Variáveis (O que gasta para poder vender - CMV)
                $custos_variaveis[$cat] = ($custos_variaveis[$cat] ?? 0) + $val;
                $tot_custos += $val;
            } else {
                // 3. Despesas Operacionais / Fixas (Aluguer, Salários, Energia, Juros pagos...)
                $despesas_operacionais[$cat] = ($despesas_operacionais[$cat] ?? 0) + $val;
                $tot_despesas += $val;
            }
        }
    }

    // Cálculos da Cascata
    $receita_liquida = $tot_receita_bruta - $tot_deducoes;
    $margem_contribuicao = $receita_liquida - $tot_custos;
    $resultado_liquido = $margem_contribuicao - $tot_despesas;

    // Percentagens (Análise Vertical)
    $perc_margem = $receita_liquida > 0 ? ($margem_contribuicao / $receita_liquida) * 100 : 0;
    $perc_lucro = $receita_liquida > 0 ? ($resultado_liquido / $receita_liquida) * 100 : 0;

} catch (Exception $e) {
    die("Erro ao calcular o DRE: " . $e->getMessage());
}

// Função auxiliar para desenhar as linhas das tabelas
function desenharLinhaDRE($nome, $valor, $is_subtracao = false) {
    if ($valor == 0) return '';
    $cor = $is_subtracao ? '#dc3545' : '#333';
    $sinal = $is_subtracao ? '- R$ ' : 'R$ ';
    echo "<tr style='border-bottom: 1px dashed #eee;'>
            <td style='padding: 8px 20px; font-size: 13px; color: #555; padding-left: 40px;'>↳ " . htmlspecialchars($nome) . "</td>
            <td style='padding: 8px 20px; font-size: 13px; text-align: right; color: {$cor}; font-weight: 500;'>" . $sinal . number_format($valor, 2, ',', '.') . "</td>
          </tr>";
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<div class="financeiro-nav">
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Cadastros ▾</button>
        <div class="nav-dropdown-content">
            <a href="gerenciar_contas.php">Contas Correntes</a>
            <a href="#">Fornecedores</a>
            <a href="#">Clientes</a>
        </div>
    </div>
    <a href="caixa_bancos.php">Caixa e Bancos</a>
    <a href="contas_pagar.php">Contas a Pagar</a>
    <a href="contas_receber.php">Contas a Receber</a>
    <div class="nav-dropdown">
        <button class="nav-dropbtn">Relatórios ▾</button>
        <div class="nav-dropdown-content">
            <a href="relatorio_contas.php">Pagamentos</a>
            <a href="#">Recebimentos</a>
            <a href="dre.php" style="font-weight: bold; background: #f8f9fa;">📊 DRE</a>
        </div>
    </div>
</div>

<div class="financeiro-wrapper" style="max-width: 900px; margin: 0 auto;">
    
    <form class="composicao-box" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; padding: 20px; margin-bottom: 25px;">
        <div class="form-group">
            <label>Data Início</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Data Fim</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px; background: #343a40;">GERAR DRE</button>
    </form>

    <div class="composicao-box" style="padding: 0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <div style="background: #343a40; color: white; padding: 15px 20px; text-align: center;">
            <h2 style="margin: 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;">Demonstração do Resultado do Exercício</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #adb5bd;">Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr style="background: #e9ecef;">
                    <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">1. RECEITA BRUTA DE VENDAS</td>
                    <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #28a745;">R$ <?= number_format($tot_receita_bruta, 2, ',', '.') ?></td>
                </tr>
                <?php foreach($receitas_brutas as $nome => $valor) desenharLinhaDRE($nome, $valor); ?>

                <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                    <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">2. (-) DEDUÇÕES DA RECEITA (Impostos e Taxas)</td>
                    <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_deducoes, 2, ',', '.') ?></td>
                </tr>
                <?php foreach($deducoes as $nome => $valor) desenharLinhaDRE($nome, $valor, true); ?>

                <tr style="background: #d4edda; border-top: 2px solid #c3e6cb;">
                    <td style="padding: 15px 20px; font-weight: bold; font-size: 16px; color: #155724;">3. (=) RECEITA LÍQUIDA (1 - 2)</td>
                    <td style="padding: 15px 20px; text-align: right; font-weight: bold; font-size: 16px; color: #155724;">R$ <?= number_format($receita_liquida, 2, ',', '.') ?></td>
                </tr>

                <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                    <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">4. (-) CUSTOS VARIÁVEIS / FORNECEDORES (CMV)</td>
                    <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_custos, 2, ',', '.') ?></td>
                </tr>
                <?php foreach($custos_variaveis as $nome => $valor) desenharLinhaDRE($nome, $valor, true); ?>

                <tr style="background: #cce5ff; border-top: 2px solid #b8daff;">
                    <td style="padding: 15px 20px; font-weight: bold; font-size: 16px; color: #004085;">
                        5. (=) MARGEM DE CONTRIBUIÇÃO (3 - 4)
                        <span style="font-size: 11px; background: #0056b3; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 10px;"><?= number_format($perc_margem, 1, ',', '.') ?>%</span>
                    </td>
                    <td style="padding: 15px 20px; text-align: right; font-weight: bold; font-size: 16px; color: #004085;">R$ <?= number_format($margem_contribuicao, 2, ',', '.') ?></td>
                </tr>

                <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                    <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">6. (-) DESPESAS OPERACIONAIS E FIXAS</td>
                    <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_despesas, 2, ',', '.') ?></td>
                </tr>
                <?php foreach($despesas_operacionais as $nome => $valor) desenharLinhaDRE($nome, $valor, true); ?>

                <?php 
                    $bg_resultado = $resultado_liquido >= 0 ? '#28a745' : '#dc3545';
                    $texto_resultado = $resultado_liquido >= 0 ? 'LUCRO LÍQUIDO' : 'PREJUÍZO LÍQUIDO';
                ?>
                <tr style="background: <?= $bg_resultado ?>; color: white; border-top: 2px solid #fff;">
                    <td style="padding: 20px; font-weight: bold; font-size: 18px;">
                        7. (=) RESULTADO DO EXERCÍCIO (<?= $texto_resultado ?>)
                        <span style="font-size: 12px; background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 4px; margin-left: 10px;"><?= number_format($perc_lucro, 1, ',', '.') ?>% de Margem Líquida</span>
                    </td>
                    <td style="padding: 20px; text-align: right; font-weight: bold; font-size: 20px;">R$ <?= number_format($resultado_liquido, 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 5px solid #ffc107; border-radius: 4px; font-size: 13px; color: #856404;">
        <strong>💡 Como ler o seu DRE:</strong><br>
        - A <b>Margem de Contribuição</b> indica o quanto sobra das suas vendas após pagar os fornecedores para cobrir as despesas da loja.<br>
        - As <b>Taxas Ocultas da Cielo</b> foram importadas automaticamente do Contas a Receber e já estão embutidas nas "Deduções", garantindo o cálculo perfeito do seu lucro sem distorcer o seu Caixa bancário!
    </div>
</div>

<?php require '../includes/footer.php'; ?>