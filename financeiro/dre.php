<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "DRE - Demonstração de Resultados";
require '../includes/header.php';

$id_usuario = $_SESSION['user_id'];

// 1. Segurança: Garante que a coluna existe
try {
    $db_financeiro->exec("ALTER TABLE contas_receber ADD COLUMN taxa_importacao REAL DEFAULT 0");
} catch (Throwable $e) {}

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

try {
    // Adicionada a exclusão de Retiradas Proprietário direto no banco de dados
    $sql_dre = "
        SELECT 'Caixa' as origem, mc.tipo as fluxo, mc.valor, COALESCE(cat.nome, 'Sem Categoria') as categoria, COALESCE(cat.grupo, 'Outros') as grupo
        FROM movimentacoes_caixa mc
        LEFT JOIN categorias_financeiras cat ON mc.id_categoria = cat.id
        WHERE mc.id_usuario = ? 
          AND mc.data_movimento BETWEEN ? AND ?
          AND cat.nome != 'Transferência'
          AND mc.origem != 'Transferência'
          AND cat.nome NOT LIKE '%Retirada%Proprietário%'

        UNION ALL
        
        SELECT 'Cielo' as origem, 'Saida' as fluxo, cr.taxa_importacao as valor, 'Taxas de Cartão' as categoria, 'Deduções e Impostos' as grupo
        FROM contas_receber cr
        WHERE cr.id_usuario = ? AND cr.status = 'Recebido' AND COALESCE(cr.taxa_importacao, 0) > 0 AND cr.data_pagamento BETWEEN ? AND ?
    ";

    $params = [$id_usuario, $data_inicio, $data_fim, $id_usuario, $data_inicio, $data_fim];
    $stmt = $db_financeiro->prepare($sql_dre);
    $stmt->execute($params);
    $movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estrutura hierárquica [Grupo][Categoria] = Valor
    $receitas = [];
    $deducoes = [];
    $custos = [];
    $despesas = [];

    $tot_receita = 0; $tot_deducao = 0; $tot_custo = 0; $tot_despesa = 0;

    foreach ($movimentos as $m) {
        $val = (float) $m['valor'];
        $cat = $m['categoria'];
        $grupo = !empty($m['grupo']) ? $m['grupo'] : 'Outros';
        
        $cat_low = mb_strtolower($cat, 'UTF-8');
        $grupo_low = mb_strtolower($grupo, 'UTF-8');

        // Filtro de segurança extra no PHP para distribuições de lucro
        if (strpos($cat_low, 'retirada') !== false && strpos($cat_low, 'propriet') !== false) {
            continue; 
        }

        if ($m['fluxo'] === 'Entrada') {
            $receitas[$grupo][$cat] = ($receitas[$grupo][$cat] ?? 0) + $val;
            $tot_receita += $val;
        } else {
            // REGRA 1: Taxas da Cacau Show -> Deduções
            // REGRA 2: 'Taxas' (genérico) -> Custos Variáveis
            if (strpos($cat_low, 'cacau show') !== false) {
                $deducoes['Deduções da Receita'][$cat] = ($deducoes['Deduções da Receita'][$cat] ?? 0) + $val;
                $tot_deducao += $val;
            } 
            elseif (strpos($cat_low, 'taxa') !== false && strpos($cat_low, 'cacau show') === false) {
                $custos['Custos Variáveis'][$cat] = ($custos['Custos Variáveis'][$cat] ?? 0) + $val;
                $tot_custo += $val;
            } 
            elseif (strpos($grupo_low, 'deduç') !== false || strpos($grupo_low, 'imposto') !== false || strpos($cat_low, 'desconto') !== false) {
                $deducoes[$grupo][$cat] = ($deducoes[$grupo][$cat] ?? 0) + $val;
                $tot_deducao += $val;
            } 
            elseif (strpos($grupo_low, 'custo') !== false || strpos($grupo_low, 'fornecedor') !== false || strpos($grupo_low, 'variá') !== false || strpos($cat_low, 'mercadoria') !== false) {
                $custos[$grupo][$cat] = ($custos[$grupo][$cat] ?? 0) + $val;
                $tot_custo += $val;
            } else {
                $despesas[$grupo][$cat] = ($despesas[$grupo][$cat] ?? 0) + $val;
                $tot_despesa += $val;
            }
        }
    }
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}

// Função para desenhar a árvore já com a lógica de abrir/fechar
function desenharArvoreDRE($agrupado, $is_subtracao = true, $prefixo = 'grp') {
    $sinal = $is_subtracao ? '- R$ ' : 'R$ ';
    $cor = $is_subtracao ? '#dc3545' : '#333';
    $contador = 0;

    foreach ($agrupado as $grupo => $itens) {
        $contador++;
        $id_grupo = $prefixo . '_' . $contador; 
        
        // Linha Mãe (Clicável)
        echo "<tr style='background: #f8f9fa; cursor: pointer; transition: 0.2s;' onclick=\"toggleDREGroup('{$id_grupo}')\" onmouseover=\"this.style.background='#f1f3f5'\" onmouseout=\"this.style.background='#f8f9fa'\">
                <td style='padding: 10px 20px; font-weight: bold; font-size: 13px; color: #444; user-select: none;'>
                    <span id='icon_{$id_grupo}' style='display: inline-block; width: 15px; color: #007bff;'>▶</span> 📂 " . htmlspecialchars($grupo) . "
                </td>
                <td style='text-align: right; font-weight: bold; color: $cor; padding: 10px 20px;'>" . $sinal . number_format(array_sum($itens), 2, ',', '.') . "</td>
              </tr>";
              
        // Linhas Filhas (Ocultas por padrão)
        foreach ($itens as $nome => $valor) {
            echo "<tr class='child-of-{$id_grupo}' style='display: none;'>
                    <td style='padding: 6px 20px 6px 45px; font-size: 12px; color: #666; border-left: 3px solid #e9ecef;'>↳ " . htmlspecialchars($nome) . "</td>
                    <td style='text-align: right; font-size: 12px; color: #777; padding: 6px 20px;'>" . $sinal . number_format($valor, 2, ',', '.') . "</td>
                  </tr>";
        }
    }
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

<div class="financeiro-wrapper" style="max-width: 900px; margin: 20px auto;">
    <form method="GET" class="composicao-box" style="padding: 20px; display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-end;">
        <div>
            <label style="font-size: 12px; font-weight: bold; margin-bottom: 5px; display: block;">Data Início</label>
            <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control">
        </div>
        <div>
            <label style="font-size: 12px; font-weight: bold; margin-bottom: 5px; display: block;">Data Fim</label>
            <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">GERAR DRE</button>
    </form>

    <div class="composicao-box" style="padding: 0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background: #343a40; color: white;">
                <td colspan="2" style="padding: 15px; text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
                    DRE - DEMONSTRATIVO DE RESULTADO DO EXERCÍCIO
                    <div style="font-size: 12px; font-weight: normal; color: #adb5bd; margin-top: 5px;">Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></div>
                </td>
            </tr>

            <tr style="background: #e9ecef;">
                <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">1. RECEITA BRUTA DE VENDAS</td>
                <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #28a745;">R$ <?= number_format($tot_receita, 2, ',', '.') ?></td>
            </tr>
            <?php desenharArvoreDRE($receitas, false, 'rec'); ?>
            
            <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">2. (-) DEDUÇÕES DA RECEITA</td>
                <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_deducao, 2, ',', '.') ?></td>
            </tr>
            <?php desenharArvoreDRE($deducoes, true, 'ded'); ?>
            
            <tr style="background: #d4edda; border-top: 2px solid #c3e6cb;">
                <td style="padding: 15px 20px; font-weight: bold; font-size: 16px; color: #155724;">3. (=) RECEITA LÍQUIDA (1 - 2)</td>
                <td style="padding: 15px 20px; text-align: right; font-weight: bold; font-size: 16px; color: #155724;">R$ <?= number_format($tot_receita - $tot_deducao, 2, ',', '.') ?></td>
            </tr>

            <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">4. (-) CUSTOS VARIÁVEIS / FORNECEDORES</td>
                <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_custo, 2, ',', '.') ?></td>
            </tr>
            <?php desenharArvoreDRE($custos, true, 'cus'); ?>
            
            <?php $margem = ($tot_receita - $tot_deducao) - $tot_custo; ?>
            <tr style="background: #cce5ff; border-top: 2px solid #b8daff;">
                <td style="padding: 15px 20px; font-weight: bold; font-size: 16px; color: #004085;">5. (=) MARGEM DE CONTRIBUIÇÃO (3 - 4)</td>
                <td style="padding: 15px 20px; text-align: right; font-weight: bold; font-size: 16px; color: #004085;">R$ <?= number_format($margem, 2, ',', '.') ?></td>
            </tr>

            <tr style="background: #e9ecef; border-top: 2px solid #fff;">
                <td style="padding: 12px 20px; font-weight: bold; font-size: 15px; color: #000;">6. (-) DESPESAS OPERACIONAIS E FIXAS</td>
                <td style="padding: 12px 20px; text-align: right; font-weight: bold; font-size: 15px; color: #dc3545;">- R$ <?= number_format($tot_despesa, 2, ',', '.') ?></td>
            </tr>
            <?php desenharArvoreDRE($despesas, true, 'des'); ?>
            
            <?php 
                $lucro = $margem - $tot_despesa; 
                $bg_resultado = $lucro >= 0 ? '#28a745' : '#dc3545';
            ?>
            <tr style="background: <?= $bg_resultado ?>; color: #fff; border-top: 2px solid #fff;">
                <td style="padding: 20px; font-weight: bold; font-size: 18px;">7. (=) RESULTADO DO EXERCÍCIO</td>
                <td style="padding: 20px; text-align: right; font-weight: bold; font-size: 20px;">R$ <?= number_format($lucro, 2, ',', '.') ?></td>
            </tr>
        </table>
    </div>
</div>

<script>
// Script para abrir e fechar as categorias (Efeito Sanfona)
function toggleDREGroup(groupId) {
    const rows = document.querySelectorAll('.child-of-' + groupId);
    const icon = document.getElementById('icon_' + groupId);
    
    let isHidden = true;
    if (rows.length > 0) {
        isHidden = rows[0].style.display === 'none';
    }

    rows.forEach(row => {
        row.style.display = isHidden ? 'table-row' : 'none';
    });

    if (icon) {
        icon.innerHTML = isHidden ? '▼' : '▶';
    }
}
</script>

<?php require '../includes/footer.php'; ?>