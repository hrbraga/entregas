<?php
// Variável para guardar o HTML do contrato gerado
$contractHTML = null;

// ==================================================
// === PARTE 1: LÓGICA PHP (BACKEND) ===
// ==================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coletar dados do formulário
    $contratanteNome = htmlspecialchars($_POST['contratanteNome']);
    $contratanteDoc = htmlspecialchars($_POST['contratanteDoc']);
    $contratanteEnd = htmlspecialchars($_POST['contratanteEnd']);

    $contratadoNome = htmlspecialchars($_POST['contratadoNome']);
    $contratadoDoc = htmlspecialchars($_POST['contratadoDoc']);
    $contratadoEnd = htmlspecialchars($_POST['contratadoEnd']);
    
    $dataEntregaInput = htmlspecialchars($_POST['dataEntrega']);
    $dataEntrega = date('d/m/Y', strtotime($dataEntregaInput));

    // Quantidades
    $qnt = [
        'brigadeiro' => htmlspecialchars($_POST['qntBrigadeiro']),
        'beijinho' => htmlspecialchars($_POST['qntBeijinho']),
        'cookies' => htmlspecialchars($_POST['qntCookies']),
        'pacoca' => htmlspecialchars($_POST['qntPaçoca']),
        'tortaH' => htmlspecialchars($_POST['qntTortaH']),
        'tortaM' => htmlspecialchars($_POST['qntTortaM']),
        'bemCasado' => htmlspecialchars($_POST['qntBemCasado']),
        'milFolhas' => htmlspecialchars($_POST['qntMilFolhas'])
    ];

    // 2. Calcular Valores
    $valorTotal = (float)$_POST['valorTotal'];
    $valorEntrada = (float)$_POST['valorEntrada'];
    $saldoRestante = $valorTotal - $valorEntrada;

    // ==================================================
    // === CORREÇÃO DO BUG DO PDF ===
    // Formatamos os valores ANTES do bloco HTML
    // ==================================================
    $valorTotalFormatado = number_format($valorTotal, 2, ',', '.');
    $valorEntradaFormatado = number_format($valorEntrada, 2, ',', '.');
    $saldoRestanteFormatado = number_format($saldoRestante, 2, ',', '.');
    // ==================================================

    // 3. Gerar Dados Automáticos
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
    date_default_timezone_set('America/Sao_Paulo');
    $dataContrato = strftime('%d de %B de %Y');

    // ==================================================
    // === O LOG NO BACKEND (CORRIGIDO) ===
    // ==================================================
    $logFilePath = '../logs/contratos.log'; 
    
    if (!is_dir(dirname($logFilePath))) {
        mkdir(dirname($logFilePath), 0755, true);
    }
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Contrato gerado. CONCORDOU. | Contratante: $contratanteNome | Valor: R$ $valorTotal\n";
    @file_put_contents($logFilePath, $logMessage, FILE_APPEND);
    // ==================================================

    // 4. Montar o HTML do Contrato
    // (Este é o bloco que você colou, que está correto)
    $contractHTML = <<<HTML
        <h2>CONTRATO DE FORNECIMENTO DE PETIT DELI</h2>
        
        <p>Pelo presente instrumento particular, de um lado, CONTRATANTE: <strong>$contratanteNome</strong>, inscrito(a) no CPF/CNPJ sob o nº <strong>$contratanteDoc</strong>, residente e domiciliado(a) à <strong>$contratanteEnd</strong>, e, de outro lado, CONTRATADO: <strong>$contratadoNome</strong> inscrita no CPF/CNPJ sob o nº <strong>$contratadoDoc</strong>, alocada na <strong>$contratadoEnd</strong>, têm entre si, justas e contratadas, as cláusulas e condições seguintes:</p>

        <h3>CLÁUSULA 1 – OBJETO</h3>
        <p>O presente contrato tem por objeto o fornecimento de doces (bombons) Petit Deli Cacau Show a serem entregues no até o dia <strong>$dataEntrega</strong> conforme especificações abaixo.</p>

        <h3>CLÁUSULA 2 – QUANTIDADE E SABORES DOS BOMBONS</h3>
        <p>Serão fornecidos bombons nos sabores abaixo discriminados, conforme disponibilidade informada pelo CONTRATADO:</p>
        
        <table>
            <thead>
                <tr><th>QNT</th><th>SABOR</th><th>QNT</th><th>SABOR</th></tr>
            </thead>
            <tbody>
                <tr><td>{$qnt['brigadeiro']}</td><td>BRIGADEIRO</td><td>{$qnt['beijinho']}</td><td>BEIJINHO</td></tr>
                <tr><td>{$qnt['cookies']}</td><td>COOKIES AND CREAM</td><td>{$qnt['pacoca']}</td><td>PAÇOCA</td></tr>
                <tr><td>{$qnt['tortaH']}</td><td>TORTA HOLANDESA</td><td>{$qnt['tortaM']}</td><td>TORTA DE MARACUJÁ</td></tr>
                <tr><td>{$qnt['bemCasado']}</td><td>BEM CASADO</td><td>{$qnt['milFolhas']}</td><td>MIL-FOLHAS</td></tr>
            </tbody>
        </table>

        <h3>CLÁUSULA 3 – VALOR E FORMA DE PAGAMENTO</h3>
        <p>O valor total acordado para o fornecimento dos Petit Deli é de <strong>R$ $valorTotalFormatado</strong>.</p>
        <p>O pagamento será realizado da seguinte forma:</p>
        <ul>
            <li>Entrada de <strong>R$ $valorEntradaFormatado</strong> do valor total no ato da assinatura deste contrato.</li>
            <li>Saldo restante no valor de <strong>R$ $saldoRestanteFormatado</strong>, a ser pago no momento da retirada dos produtos, podendo ser parcelado em até 3 (quatro) vezes no cartão de crédito.</li>
        </ul>

        <h3>CLÁUSULA 4 – RETIRADA DOS PRODUTOS</h3>
        <p>Os doces serão retirados pelo CONTRATANTE no dia <strong>$dataEntrega</strong>, na loja do CONTRATADO no endereço à <strong>$contratadoEnd</strong> em horário comercial.</p>

        <h3>CLÁUSULA 5 – CANCELAMENTO</h3>
        <p>Em caso de cancelamento por parte do CONTRATANTE, será retido 50% (cinquenta por cento) do valor pago como entrada, a título de multa rescisória e despesas operacionais.</p>
        <p>O restante do valor pago será devolvido ao CONTRATANTE em até 7 (sete) dias úteis.</p>

        <h3>CLÁSULA 6 – DISPOSIÇÕES GERAIS</h3>
        <p>Parágrafo único: As partes elegem o foro da comarca de Cornélio Procópio-PR para dirimir quaisquer dúvidas ou conflitos oriundos deste contrato.</p>

        <br>
        <p>E por estarem assim justas e contratadas, firmam o presente instrumento em duas vias de igual teor e forma, na presença de duas testemunhas.</p>
        
        <p>Local: Cornélio Procópio-PR</p>
        <p>Data: <strong>$dataContrato</strong>.</p>
        <br>
        <p>CONTRATANTE:</p>
        <p>_______________________________________</p>
        <p>$contratanteNome</p>
        <br>
        <p>CONTRATADO:</p>
        <p>_______________________________________</p>
        <p>$contratadoNome</p>
HTML;

} // Fim do bloco "if POST"
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Contrato</title>
    <link rel="stylesheet" href="../static/css/contrato.css">
</head>
<body>

    <h1>Gerador de Contrato (Petit Deli)</h1>
    <p>Preencha os campos abaixo para gerar o contrato com base no modelo.</p>

    <div class="form-container">
        <form action="contratoPetit.php" method="POST" id="contractForm">
            
            <fieldset>
                <legend>Dados do CONTRATANTE</legend>
                <label for="contratanteNome">Nome:</label>
                <input type="text" id="contratanteNome" name="contratanteNome" value="<?php echo htmlspecialchars($_POST['contratanteNome'] ?? 'Sarah Rayane Barbosa Elias Senefonte'); ?>" required>
                
                <label for="contratanteDoc">CPF/CNPJ:</label>
                <input type="text" id="contratanteDoc" name="contratanteDoc" value="<?php echo htmlspecialchars($_POST['contratanteDoc'] ?? '052.527.529-07'); ?>" required>
                
                <label for="contratanteEnd">Endereço:</label>
                <input type="text" id="contratanteEnd" name="contratanteEnd" value="<?php echo htmlspecialchars($_POST['contratanteEnd'] ?? 'Rua Piauí, 277, Cornélio Procópio-PR'); ?>" required>
            </fieldset>

            <fieldset>
                <legend>Dados do CONTRATADO</legend>
                <label for="contratadoNome">Nome/Razão Social:</label>
                <input type="text" id="contratadoNome" name="contratadoNome" value="<?php echo htmlspecialchars($_POST['contratadoNome'] ?? 'R I CHAGAS BRAGA ME'); ?>" required>
                
                <label for="contratadoDoc">CPF/CNPJ:</label>
                <input type="text" id="contratadoDoc" name="contratadoDoc" value="<?php echo htmlspecialchars($_POST['contratadoDoc'] ?? '10.466.204/0001-76'); ?>" required>
                
                <label for="contratadoEnd">Endereço (Local de retirada):</label>
                <input type="text" id="contratadoEnd" name="contratadoEnd" value="<?php echo htmlspecialchars($_POST['contratadoEnd'] ?? 'Avenida XV de Novembro, 448, Cornélio Procópio-PR'); ?>" required>
            </fieldset>

            <fieldset>
                <legend>Detalhes do Pedido e Pagamento</legend>
                
                <label for="dataEntrega">Data da Entrega/Retirada:</label>
                <input type="date" id="dataEntrega" name="dataEntrega" value="<?php echo htmlspecialchars($_POST['dataEntrega'] ?? '2025-08-14'); ?>" required>
                
                <label for="valorTotal">Valor Total (R$):</label>
                <input type="number" id="valorTotal" name="valorTotal" value="<?php echo htmlspecialchars($_POST['valorTotal'] ?? '440.00'); ?>" step="0.01" required>
                
                <label for="valorEntrada">Valor da Entrada (R$):</label>
                <input type="number" id="valorEntrada" name="valorEntrada" value="<?php echo htmlspecialchars($_POST['valorEntrada'] ?? '100.00'); ?>" step="0.01" required>
            </fieldset>

            <fieldset>
                <legend>Quantidades de Bombons</legend>
                <div class="sabor-grid">
                    <div><label for="qntBrigadeiro">BRIGADEIRO:</label><input type="number" id="qntBrigadeiro" name="qntBrigadeiro" value="<?php echo htmlspecialchars($_POST['qntBrigadeiro'] ?? '110'); ?>"></div>
                    <div><label for="qntBeijinho">BEIJINHO:</label><input type="number" id="qntBeijinho" name="qntBeijinho" value="<?php echo htmlspecialchars($_POST['qntBeijinho'] ?? '0'); ?>"></div>
                    <div><label for="qntCookies">COOKIES:</label><input type="number" id="qntCookies" name="qntCookies" value="<?php echo htmlspecialchars($_POST['qntCookies'] ?? '0'); ?>"></div>
                    <div><label for="qntPaçoca">PAÇOCA:</label><input type="number" id="qntPaçoca" name="qntPaçoca" value="<?php echo htmlspecialchars($_POST['qntPaçoca'] ?? '110'); ?>"></div>
                    <div><label for="qntTortaH">TORTA HOLANDESA:</label><input type="number" id="qntTortaH" name="qntTortaH" value="<?php echo htmlspecialchars($_POST['qntTortaH'] ?? '0'); ?>"></div>
                    <div><label for="qntTortaM">TORTA MARACUJÁ:</label><input type="number" id="qntTortaM" name="qntTortaM" value="<?php echo htmlspecialchars($_POST['qntTortaM'] ?? '0'); ?>"></div>
                    <div><label for="qntBemCasado">BEM CASADO:</label><input type="number" id="qntBemCasado" name="qntBemCasado" value="<?php echo htmlspecialchars($_POST['qntBemCasado'] ?? '0'); ?>"></div>
                    <div><label for="qntMilFolhas">MIL-FOLHAS:</label><input type="number" id="qntMilFolhas" name="qntMilFolhas" value="<?php echo htmlspecialchars($_POST['qntMilFolhas'] ?? '0'); ?>"></div>
                </div>
            </fieldset>
            
            <button type="submit" id="gerarContrato">Gerar Contrato (PDF)</button>
            <button type="button" id="limparContrato">Limpar Formulário</button>
        </form>
    </div>

    <?php
    // Dispara a impressão (PDF) se o contrato foi gerado
    if ($contractHTML):
    ?>
        <div id="contratoOutput" class="contrato-preview">
            <?php echo $contractHTML; ?>
        </div>
        
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>

    <?php 
    endif;
    ?>

    <script src="../static/js/contrato.js"></script>
</body>
</html>