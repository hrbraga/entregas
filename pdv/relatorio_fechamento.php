<?php
session_start();
require '../config.php';
require '../auth/auth_check.php';

$turno_id = $_GET['turno'] ?? 0;

$stmtTurno = $db_financeiro->prepare("SELECT * FROM pdv_turnos WHERE id = ? AND user_id = ?");
$stmtTurno->execute([$turno_id, $_SESSION['user_id']]);
$turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

if (!$turno) { die("Relatório não encontrado ou acesso negado."); }

// Busca vendas agrupadas
$stmtVendas = $db_financeiro->prepare("SELECT forma_pagamento, SUM(total) as total_metodo FROM pdv_vendas WHERE turno_id = ? AND status = 'concluida' GROUP BY forma_pagamento");
$stmtVendas->execute([$turno_id]);
$vendas = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);

// Normaliza os nomes para cruzar os dados
$sistema = ['Dinheiro' => 0, 'Debito' => 0, 'Credito' => 0, 'PIX' => 0, 'Alimentacao' => 0, 'Outros' => 0];
$total_sistema = 0;

foreach ($vendas as $v) {
    $metodo = $v['forma_pagamento'];
    // Ajuste de nomes para garantir a formatação
    if ($metodo == 'Débito') $metodo = 'Debito';
    if ($metodo == 'Crédito') $metodo = 'Credito';
    if ($metodo == 'Cheque Empresa') $metodo = 'Outros';
    if ($metodo == 'Alimentação') $metodo = 'Alimentacao';
    
    $sistema[$metodo] = ($sistema[$metodo] ?? 0) + $v['total_metodo'];
    $total_sistema += $v['total_metodo'];
}

// Em dinheiro, o sistema considera as Vendas + Fundo Inicial
$sistema['Dinheiro'] += $turno['fundo_caixa'];

// O que o operador informou
$informado = [
    'Dinheiro' => $turno['f_dinheiro'],
    'Debito' => $turno['f_debito'],
    'Credito' => $turno['f_credito'],
    'PIX' => $turno['f_pix'],
    'Alimentacao' => $turno['f_alimentacao'],
    'Outros' => $turno['f_outros']
];

function formataBR($valor) { return "R$ " . number_format((float)$valor, 2, ',', '.'); }
function calcDif($informado, $sistema) {
    $dif = $informado - $sistema;
    if ($dif < -0.01) return '<span style="color:#dc3545; font-weight:bold;">' . formataBR($dif) . ' (Falta)</span>';
    if ($dif > 0.01) return '<span style="color:#198754; font-weight:bold;">+' . formataBR($dif) . ' (Sobra)</span>';
    return '<span style="color:#6c757d;">OK</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Auditoria de Fechamento</title>
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; padding: 20px; }
        .relatorio-container { background: white; padding: 30px; border-radius: 10px; width: 100%; max-width: 650px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 15px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: right; }
        th { background: #f8f9fa; text-align: right; }
        th:first-child, td:first-child { text-align: left; font-weight: bold; }
        .total-row { font-size: 1.1rem; font-weight: bold; background: #e9ecef; }
        .acoes { display: flex; gap: 10px; margin-top: 30px; }
        button, a { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: bold; text-align: center; text-decoration: none; }
        .btn-print { background: #0d6efd; color: white; }
        .btn-home { background: #6c757d; color: white; }
        @media print { body { background: white; padding: 0; } .relatorio-container { box-shadow: none; max-width: 100%; } .acoes { display: none; } }
    </style>
</head>
<body>

<div class="relatorio-container">
    <div class="header">
        <h2>🧾 AUDITORIA DE CAIXA</h2>
        <p>Turno: #<?php echo str_pad($turno_id, 5, '0', STR_PAD_LEFT); ?></p>
        <p style="font-size: 0.9rem; color: #666;">
            Abertura: <?php echo date('d/m/Y H:i', strtotime($turno['data_abertura'])); ?> | 
            Fechamento: <?php echo date('d/m/Y H:i', strtotime($turno['data_fechamento'])); ?><br>
            Fundo de Troco Inicial: <strong><?php echo formataBR($turno['fundo_caixa']); ?></strong>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Método</th>
                <th>Sistema</th>
                <th>Operador Informou</th>
                <th>Diferença</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sistema as $metodo => $valorSis): ?>
            <tr>
                <td><?php echo $metodo; ?></td>
                <td><?php echo formataBR($valorSis); ?></td>
                <td><?php echo formataBR($informado[$metodo]); ?></td>
                <td><?php echo calcDif($informado[$metodo], $valorSis); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAIS</td>
                <td><?php echo formataBR($total_sistema + $turno['fundo_caixa']); ?></td>
                <td><?php echo formataBR($turno['valor_fechamento']); ?></td>
                <td><?php echo calcDif($turno['valor_fechamento'], $total_sistema + $turno['fundo_caixa']); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="acoes">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <a href="../selecao_ferramentas.php" class="btn-home">Voltar ao Início</a>
    </div>
</div>

</body>
</html>