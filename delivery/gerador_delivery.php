<?php 

    require '../config.php'; 
    require '../auth/custos_auth_check.php'; // Proteção de nível 1 (Igual ao contrato/etiquetas)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Etiquetas</title>
    
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/selecao.css">
    <link rel="shortcut icon" href="../static/img/chocolatinho.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/css/delivery.css">

</head>

    <header>
        <h1>Etiquetas Delivery</h1>
    </header>
<body>

<div class="delivery-container">
    <div class="form-box">
        <form id="form-delivery">
            
            <div class="form-group">
                <label for="data_pedido">Data do Pedido:</label>
                <input type="date" id="data_pedido" name="data_pedido" value="<?php echo date('Y-m-d'); ?>"c>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="de_quem">De:</label>
                    <input type="text" id="de_quem" name="de_quem" placeholder="Remetente">
                </div>
                <div class="form-group half">
                    <label for="para_quem">Para:*</label>
                    <input type="text" id="para_quem" name="para_quem" placeholder="Destinatário" required>
                </div>
            </div>

            <div class="form-group">
                <label for="fone">Fone de Contato:*</label>
                <input type="text" id="fone" name="fone" placeholder="(00) 00000-0000" required>
            </div>

            <div class="form-group">
                <label for="endereco">Endereço de Entrega:*</label>
                <textarea id="endereco" name="endereco" rows="3" placeholder="Rua, Número, Bairro, Complemento..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="data_entrega">Agendar Data:</label>
                    <input type="date" id="data_entrega" name="data_entrega">
                </div>
                <div class="form-group half">
                    <label for="hora_entrega">Agendar Hora:</label>
                    <input type="time" id="hora_entrega" name="hora_entrega">
                </div>
            </div>

            <div class="form-group">
                <label for="obs">Observação:</label>
                <textarea id="obs" name="obs" rows="2" placeholder="Ex: Tocar interfone, deixar na portaria..."></textarea>
            </div>

            <div class="buttons-area">
                <button type="button" id="btn-limpar" class="btn-secondary">Limpar</button>
                <button type="submit" class="btn-primary">Imprimir Etiqueta</button>
            </div>
        </form>
    </div>
</div>

<div id="print-area">
    <div class="etiqueta-termica">
        <div class="header-etiqueta">
            <img src="../static/img/chocolatinho.png" alt="Logo" class="logo-print">
            <h3>DELIVERY</h3>
        </div>
        
        <hr class="dashed">
        
        <p><strong>DATA PEDIDO:</strong> <span id="print-data-pedido"></span></p>
        
        <div class="row-print">
            <p><strong>DE:</strong> <span id="print-de"></span></p>
            <p><strong>PARA:</strong> <span id="print-para"></span></p>
        </div>

        <p><strong>CONTATO:</strong> <span id="print-fone"></span></p>
        
        <hr class="solid">
        
        <p class="titulo-secao">ENDEREÇO DE ENTREGA</p>
        <p id="print-endereco" class="texto-grande"></p>
        
        <hr class="solid">
        
        <p><strong>AGENDAMENTO:</strong></p>
        <p><span id="print-data-entrega"></span> às <span id="print-hora-entrega"></span></p>
        
        <div id="box-obs">
            <hr class="dashed">
            <p><strong>OBSERVAÇÃO:</strong></p>
            <p id="print-obs"></p>
        </div>

        <hr class="dashed">
        <p class="footer-print">Obrigado pela preferência!</p>
        <p class="footer-print">Entregue com carinho.</p>
    </div>
</div>

 <footer>
        <a href="../selecao_ferramentas.php">
            <p>Voltar ao Início</p>
        </a>
</footer>

<script src="../static/js/delivery.js"></script>

</body>
</html>