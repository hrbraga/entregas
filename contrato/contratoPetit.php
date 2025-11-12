<?php
// Variável para guardar o HTML do contrato gerado
$contractHTML = null;

// ==================================================
// === PARTE 1: LÓGICA PHP (BACKEND) ===
// ==================================================
// Verifica se o formulário foi enviado (usando o método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coletar dados do formulário
    // Usamos htmlspecialchars para evitar ataques XSS
    $contratanteNome = htmlspecialchars($_POST['contratanteNome']);
    $contratanteDoc = htmlspecialchars($_POST['contratanteDoc']);
    $contratanteEnd = htmlspecialchars($_POST['contratanteEnd']);

    $contratadoNome = htmlspecialchars($_POST['contratadoNome']);
    $contratadoDoc = htmlspecialchars($_POST['contratadoDoc']);
    $contratadoEnd = htmlspecialchars($_POST['contratadoEnd']);
    
    $dataEntrega = htmlspecialchars($_POST['dataEntrega']);

    // Quantidades (vamos pegar todas)
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

    // 2. Coletar e Calcular Valores
    // Convertemos para float para garantir que são números
    $valorTotal = (float)$_POST['valorTotal'];
    $valorEntrada = (float)$_POST['valorEntrada'];
    
    // CÁLCULO AUTOMÁTICO (Saldo Restante)
    $saldoRestante = $valorTotal - $valorEntrada;

    // 3. Gerar Dados Automáticos
    // DATA AUTOMÁTICA (Data do Contrato)
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese'); // Define o idioma
    date_default_timezone_set('America/Sao_Paulo'); // Define o fuso
    $dataContrato = strftime('%d de %B de %Y'); // Formato: 05 de agosto de 2025

    // ==================================================
    // === O LOG NO BACKEND (AGORA É DE VERDADE) ===
    // ==================================================
    $logFilePath = 'logs/contratos.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Contrato gerado. CONCORDOU COM OS TERMOS. | Contratante: $contratanteNome | Valor: R$ $valorTotal\n";
    
    // Escreve no arquivo de log, anexando a nova entrada
    file_put_contents($logFilePath, $logMessage, FILE_APPEND);
    // ==================================================

    // 4. Montar o HTML do Contrato
    // Usamos a sintaxe HEREDOC (<<<HTML ... HTML;) para facilitar
    $contractHTML = <<<HTML
        <h2>CONTRATO DE FORNECIMENTO DE PETIT DELI [fonte: 1]</h2>
        
        <p>Pelo presente instrumento particular, de um lado, CONTRATANTE: <strong>$contratanteNome</strong>, inscrito(a) no CPF/CNPJ sob o nº <strong>$contratanteDoc</strong>, residente e domiciliado(a) à <strong>$contratanteEnd</strong>, e, de outro lado, CONTRATADO: <strong>$contratadoNome</strong> inscrita no CPF/CNPJ sob o nº <strong>$contratadoDoc</strong>, alocada na <strong>$contratadoEnd</strong>, têm entre si, justas e contratadas, as cláusulas e condições seguintes: [fonte: 2]</p>

        <h3>CLÁUSULA 1 – OBJETO [fonte: 3]</h3>
        <p>O presente contrato tem por objeto o fornecimento de doces (bombons) Petit Deli Cacau Show a serem entregues no até o dia <strong>$dataEntrega</strong> conforme especificações abaixo. [fonte: 4]</p>

        <h3>CLÁUSULA 2 – QUANTIDADE E SABORES DOS BOMBONS [fonte: 5]</h3>
        <p>Serão fornecidos bombons nos sabores abaixo discriminados, conforme disponibilidade informada pelo CONTRATADO: [fonte: 6]</p>
        
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
        <h3>CLÁUSULA 3 – VALOR E FORMA DE PAGAMENTO [fonte: 8]</h3>
        <p>O valor total acordado para o fornecimento dos Petit Deli é de <strong>R$ {number_format($valorTotal, 2, ',', '.')}</strong>. [baseado na fonte 9]</p>
        <p>O pagamento será realizado da seguinte forma: [fonte: 10]</p>
        <ul>
            <li>Entrada de <strong>R$ {number_format($valorEntrada, 2, ',', '.')}</strong> do valor total no ato da assinatura deste contrato. [baseado na fonte 11]</li>
            <li>Saldo restante no valor de <strong>R$ {number_format($saldoRestante, 2, ',', '.')}</strong>, a ser pago no momento da retirada dos produtos, podendo ser parcelado em até 3 (quatro) vezes no cartão de crédito. [baseado na fonte 12]</li>
        </ul>

        <h3>CLÁUSULA 4 – RETIRADA DOS PRODUTOS [fonte: 13]</h3>
        <p>Os doces serão retirados pelo CONTRATANTE no dia <strong>$dataEntrega</strong>, na loja do CONTRATADO no endereço à <strong>$contratadoEnd</strong> em horário comercial. [fonte: 14]</p>

        <h3>CLÁUSULA 5 – CANCELAMENTO [fonte: 15]</h3>
        <p>Em caso de cancelamento por parte do CONTRATANTE, será retido 50% (cinquenta por cento) do valor pago como entrada, a título de multa rescisória e despesas operacionais. [fonte: 16]</p>
        <p>O restante do valor pago será devolvido ao CONTRATANTE em até 7 (sete) dias úteis. [fonte: 17]</p>

        <h3>CLÁSULA 6 – DISPOSIÇÕES GERAIS [fonte: 18]</h3>
        <p>Parágrafo único: As partes elegem o foro da comarca de Cornélio Procópio-PR para dirimir quaisquer dúvidas ou conflitos oriundos deste contrato. [fonte: 19]</p>

        <br>
        <p>E por estarem assim justas e contratadas, firmam o presente instrumento em duas vias de igual teor e forma, na presença de duas testemunhas. [fonte: 20]</p>
        
        <p>Local: Cornélio Procópio-PR [fonte: 21]</p>
        <p>Data: <strong>$dataContrato</strong>. [baseado na fonte 22]</p>
        <br>
        <p>CONTRATANTE: [fonte: 23]</p>
        <p>_______________________________________</p>
        <p>$contratanteNome</p>
        <br>
        <p>CONTRATADO: [fonte: 25]</p>
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
    <link rel="stylesheet" href="..\contrato\contrato.css">
</head>
<body>

    <h1>Gerador de Contrato (Petit Deli)</h1>
    <p>Preencha os campos abaixo para gerar o contrato com base no modelo.</p>

    <div class="form-container">
        <form action="index.php" method="POST" id="contractForm">
            <fieldset>
                <legend>Dados do CONTRATANTE [fonte: 2]</legend>
                <label for="contratanteNome">Nome:</label>
                <input type="text" id="contratanteNome" name="contratanteNome" value="Sarah Rayane Barbosa Elias Senefonte" required>
                <label for="contratanteDoc">CPF/CNPJ:</label>
                <input type="text" id="contratanteDoc" name="contratanteDoc" value="052.527.529-07" required>
                <label for="contratanteEnd">Endereço:</label>
                <input type="text" id="contratanteEnd" name="contratanteEnd" value="Rua Piauí, 277, Cornélio Procópio-PR" required>
            </fieldset>

            <fieldset>
                <legend>Dados do CONTRATADO [fonte: 2]</legend>
                <label for="contratadoNome">Nome/Razão Social:</label>
                <input type="text" id="contratadoNome" name="contratadoNome" value="R I CHAGAS BRAGA ME" required>
                <label for="contratadoDoc">CPF/CNPJ:</label>
                <input type="text" id="contratadoDoc" name="contratadoDoc" value="10.466.204/0001-76" required>
                <label for="contratadoEnd">Endereço (será usado como local de retirada [fonte: 14]):</label>
                <input type="text" id="contratadoEnd" name="contratadoEnd" value="Avenida XV de Novembro, 448, Cornélio Procópio-PR" required>
            </fieldset>

            <fieldset>
                <legend>Detalhes do Pedido e Pagamento</legend>
                <label for="dataEntrega">Data da Entrega/Retirada [fonte: 4, 14]:</label>
                <input type="text" id="dataEntrega" name="dataEntrega" value="14/08/2025" required>
                
                <label for="valorTotal">Valor Total (R$) [fonte: 9]:</label>
                <input type="number" id="valorTotal" name="valorTotal" value="440.00" step="0.01" required>
                
                <label for="valorEntrada">Valor da Entrada (R$) [fonte: 11]:</label>
                <input type="number" id="valorEntrada" name="valorEntrada" value="100.00" step="0.01" required>
            </fieldset>

            <fieldset>
                <legend>Quantidades de Bombons (CLÁUSULA 2) [fonte: 7]</legend>
                <div class="sabor-grid">
                    <div><label for="qntBrigadeiro">BRIGADEIRO:</label><input type="number" id="qntBrigadeiro" name="qntBrigadeiro" value="110"></div>
                    <div><label for="qntBeijinho">BEIJINHO:</label><input type="number" id="qntBeijinho" name="qntBeijinho" value="0"></div>
                    <div><label for="qntCookies">COOKIES AND CREAM:</label><input type="number" id="qntCookies" name="qntCookies" value="0"></div>
                    <div><label for="qntPaçoca">PAÇOCA:</label><input type="number" id="qntPaçoca" name="qntPaçoca" value="110"></div>
                    <div><label for="qntTortaH">TORTA HOLANDESA:</label><input type="number" id="qntTortaH" name="qntTortaH" value="0"></div>
                    <div><label for="qntTortaM">TORTA DE MARACUJÁ:</label><input type="number" id="qntTortaM" name="qntTortaM" value="0"></div>
                    <div><label for="qntBemCasado">BEM CASADO:</label><input type="number" id="qntBemCasado" name="qntBemCasado" value="0"></div>
                    <div><label for="qntMilFolhas">MIL-FOLHAS:</label><input type="number" id="qntMilFolhas" name="qntMilFolhas" value="0"></div>
                </div>
            </fieldset>
            
            <button type="submit" id="gerarContrato">Gerar Contrato</button>
        </form>
    </div>

    <hr>

    <?php
    // Se a variável $contractHTML foi preenchida lá em cima...
    if ($contractHTML):
    ?>
        <div id="contratoOutput" class="contrato-preview">
            <?php echo $contractHTML; // Imprime o contrato gerado ?>
        </div>
        
        <button id="imprimirContrato" onclick="window.print();">Imprimir / Salvar PDF</button>

    <?php 
    endif; // Fim do 'if' que exibe o contrato
    ?>

    <script src="../static/js/contrato.js"></script>
</body>
</html>