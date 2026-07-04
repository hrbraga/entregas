<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';

$evento_id = $_GET['evento_id'] ?? 0;

// Busca dados do Evento
$stmt = $db_financeiro->prepare("SELECT * FROM pdv_eventos WHERE id = ?");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    die("<h2 style='font-family: sans-serif;'>Erro: Evento não encontrado.</h2><a href='gestao_eventos.php'>Voltar</a>");
}

$caminho_produtos = str_replace('\\', '/', dirname(__DIR__)) . '/db/produtos.db';
$db_financeiro->exec("ATTACH DATABASE '$caminho_produtos' AS p_db");

// 1. CAPA DAS VENDAS
$stmtVendas = $db_financeiro->prepare("SELECT v.*, t.nome_operador FROM pdv_vendas v LEFT JOIN pdv_turnos t ON v.turno_id = t.id WHERE v.evento_id = ? ORDER BY v.id DESC");
$stmtVendas->execute([$evento_id]);
$vendas = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);

// 2. BUSCA TODOS OS ITENS DE TODAS AS VENDAS DESSE EVENTO
$stmtItens = $db_financeiro->prepare("
    SELECT i.venda_id, p.codigo_interno, p.nome_produto, i.quantidade, i.preco_unitario, i.subtotal
    FROM pdv_itens i
    LEFT JOIN p_db.produtos_unificados p ON i.produto_id = p.id
    JOIN pdv_vendas v ON i.venda_id = v.id
    WHERE v.evento_id = ?
");
$stmtItens->execute([$evento_id]);
$itens_raw = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

// Agrupa os itens pelo ID da venda para imprimir certinho
$itens_por_venda = [];
foreach($itens_raw as $it) {
    $itens_por_venda[$it['venda_id']][] = $it;
}

// 3. RESUMO DE PRODUTOS VENDIDOS
$stmtResumo = $db_financeiro->prepare("
    SELECT p.nome_produto, SUM(i.quantidade) as total_qtd, SUM(i.subtotal) as total_valor 
    FROM pdv_itens i
    JOIN pdv_vendas v ON i.venda_id = v.id
    JOIN p_db.produtos_unificados p ON i.produto_id = p.id
    WHERE v.evento_id = ?
    GROUP BY i.produto_id
");
$stmtResumo->execute([$evento_id]);
$resumo = $stmtResumo->fetchAll(PDO::FETCH_ASSOC);

// 4. SALDO DE ESTOQUE
$stmtEstoque = $db_financeiro->prepare("
    SELECT p.nome_produto, e.quantidade_inicial, e.quantidade_atual 
    FROM pdv_estoque_evento e
    JOIN p_db.produtos_unificados p ON e.produto_id = p.id
    WHERE e.evento_id = ?
");
$stmtEstoque->execute([$evento_id]);
$estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Evento - <?php echo htmlspecialchars($evento['nome_evento']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; font-size: 14px; }
        h1 { color: #0d6efd; font-size: 24px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px;}
        h2 { color: #333; font-size: 18px; margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 5px;}
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Estilo do "Recibo" na Venda a Venda */
        .recibo-box { border: 1px solid #000; padding: 15px; margin-bottom: 15px; background: #fff; page-break-inside: avoid; }
        .recibo-header { display: flex; justify-content: space-between; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; font-weight: bold; }
        .recibo-table { width: 100%; border: none; margin-top: 0; }
        .recibo-table td { border: none; padding: 4px; }
        .recibo-total { text-align: right; font-size: 16px; font-weight: bold; border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; }

        @media print { 
            .btn-imprimir, .btn-voltar { display: none !important; } 
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Relatório de Evento: <?php echo htmlspecialchars($evento['nome_evento']); ?></h1>
        <div>
            <button class="btn-imprimir" onclick="window.print()" style="padding: 10px 20px; background: #0d6efd; color: white; border: none; cursor: pointer; border-radius: 5px;">🖨️ Imprimir (A4)</button>
            <a href="gestao_eventos.php" class="btn-voltar" style="margin-left: 10px; text-decoration: none; padding: 10px 20px; background: #6c757d; color: white; border-radius: 5px;">Voltar</a>
        </div>
    </div>
    
    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?> | <strong>Emissão:</strong> <?php echo date('d/m/Y H:i'); ?></p>

    <h2>1. Venda a Venda (Detalhamento)</h2>
    <?php if(count($vendas) > 0): ?>
        <?php foreach($vendas as $v): ?>
            <div class="recibo-box">
                <div class="recibo-header">
                    <span>Venda #<?php echo $v['id']; ?></span>
                    <span>Op: <?php echo $v['nome_operador'] ? $v['nome_operador'] : 'Loja'; ?></span>
                    <span>Pgto: <?php echo $v['forma_pagamento']; ?></span>
                </div>
                <table class="recibo-table">
                    <?php if(isset($itens_por_venda[$v['id']])): ?>
                        <?php foreach($itens_por_venda[$v['id']] as $it): ?>
                            <tr>
                                <td width="10%"><?php echo $it['quantidade']; ?>x</td>
                                <td width="20%"><?php echo $it['codigo_interno']; ?></td>
                                <td width="50%"><?php echo htmlspecialchars($it['nome_produto']); ?></td>
                                <td width="20%" class="text-right">R$ <?php echo number_format($it['subtotal'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="color: red;"><em>Itens não encontrados ou com erro de ID.</em></td></tr>
                    <?php endif; ?>
                </table>
                <div class="recibo-total">TOTAL: R$ <?php echo number_format($v['total'], 2, ',', '.'); ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nenhuma venda registrada para este evento ainda.</p>
    <?php endif; ?>

    <h2>2. Resumo de Produtos Vendidos</h2>
    <table>
        <tr>
            <th>Produto</th>
            <th class="text-center">Qtd Vendida</th>
            <th class="text-right">Total Faturado</th>
        </tr>
        <?php foreach($resumo as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['nome_produto']); ?></td>
            <td class="text-center"><?php echo $r['total_qtd']; ?></td>
            <td class="text-right">R$ <?php echo number_format($r['total_valor'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>3. Saldo de Estoque</h2>
    <table>
        <tr>
            <th>Produto</th>
            <th class="text-center">Qtd Inicial</th>
            <th class="text-center">Qtd Atual</th>
        </tr>
        <?php foreach($estoque as $e): ?>
        <tr>
            <td><?php echo htmlspecialchars($e['nome_produto']); ?></td>
            <td class="text-center"><?php echo $e['quantidade_inicial']; ?></td>
            <td class="text-center" style="font-weight: bold; <?php echo ($e['quantidade_atual'] <= 0) ? 'color: red;' : 'color: green;'; ?>">
                <?php echo $e['quantidade_atual']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>