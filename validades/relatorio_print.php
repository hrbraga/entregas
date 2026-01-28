<?php
require '../config.php';
require '../auth/auth_check.php';

$mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
$ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$mes || !$ano) {
    die("Mês ou Ano inválidos.");
}

// Formata mês para 2 dígitos (ex: 1 virar 01)
$mesStr = str_pad($mes, 2, '0', STR_PAD_LEFT);
$periodo = "$ano-$mesStr"; // Formato YYYY-MM para busca no SQLite

try {
    // Busca produtos onde a data começa com o Ano-Mês selecionado
    $sql = "SELECT * FROM itens_validade 
            WHERE user_id = ? 
            AND strftime('%Y-%m', data_validade) = ? 
            ORDER BY data_validade ASC";
    
    $stmt = $db_validades->prepare($sql);
    $stmt->execute([$user_id, $periodo]);
    $itens = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

// Nomes dos meses para o título
$meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Validades - <?php echo $meses[$mes] . '/' . $ano; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #000; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 18pt; }
        p.sub { text-align: center; margin-top: 0; color: #555; font-size: 10pt; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 10pt; }
        th { background-color: #f0f0f0; }
        
        /* Oculta botão de imprimir na hora da impressão */
        @media print {
            .no-print { display: none; }
        }
        
        .btn-print {
            display: block; margin: 0 auto 20px auto; padding: 10px 20px;
            background: #333; color: #fff; text-decoration: none;
            width: 100px; text-align: center; border-radius: 4px;
        }
    </style>
</head>
<body>

    <a href="#" onclick="window.print()" class="btn-print no-print">🖨️ Imprimir</a>

    <h1>Relatório de Validades</h1>
    <p class="sub">Período: <strong><?php echo $meses[$mes] . ' / ' . $ano; ?></strong></p>
    <p class="sub">Gerado em: <?php echo date('d/m/Y H:i'); ?></p>

    <?php if (count($itens) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th width="15%">Código</th>
                    <th width="45%">Produto</th>
                    <th width="15%">Validade</th>
                    <th width="10%">Qtd.</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): 
                    $dataVal = new DateTime($item['data_validade']);
                    $hoje = new DateTime();
                    $hoje->setTime(0,0); // Zera hora para comparar apenas data
                    
                    $status = "No Prazo";
                    if ($dataVal < $hoje) {
                        $status = "VENCIDO";
                        $style = "font-weight:bold; color:red;"; // Visual na tela (na impressora pb fica cinza escuro)
                    } elseif ($dataVal == $hoje) {
                        $status = "Vence Hoje";
                        $style = "font-weight:bold;";
                    } else {
                        $style = "";
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['codigo_produto']); ?></td>
                    <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                    <td><?php echo $dataVal->format('d/m/Y'); ?></td>
                    <td><?php echo $item['quantidade']; ?></td>
                    <td style="<?php echo $style; ?>"><?php echo $status; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; margin-top:50px;">Nenhum produto encontrado com vencimento neste mês.</p>
    <?php endif; ?>

    <script>
        // Abre a janela de impressão automaticamente ao carregar
        window.onload = function() { window.print(); }
    </script>
</body>
</html>