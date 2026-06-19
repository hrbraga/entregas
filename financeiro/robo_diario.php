<?php
require '../config.php'; 

// ==========================================
// CONFIGURAÇÕES DO ROBÔ
// ==========================================
$token_seguranca = "cacau2026"; // Senha para rodar o script
$email_destino   = "cacaushowcp@gmail.com"; // <-- SUBSTITUA PELO SEU E-MAIL
$id_usuario      = 1; // <-- SEU ID NO BANCO DE DADOS (Geralmente 1)

// Trava de Segurança: Só roda se o token estiver correto ou via painel do servidor
if (!isset($_GET['token']) || $_GET['token'] !== $token_seguranca) {
    if (php_sapi_name() !== 'cli') {
        die("Acesso negado. Token inválido.");
    }
}

$hoje = date('Y-m-d');
$hoje_br = date('d/m/Y');

try {
    // 1. Busca contas a pagar HOJE
    $stmt_pagar = $db_financeiro->prepare("SELECT descricao, valor, fornecedor FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento = ?");
    $stmt_pagar->execute([$id_usuario, $hoje]);
    $contas_hoje = $stmt_pagar->fetchAll(PDO::FETCH_ASSOC);

    // 2. Busca o Saldo Atual para colocar no e-mail
    $stmt_saldo = $db_financeiro->prepare("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
    $stmt_saldo->execute([$id_usuario]);
    $saldo_inicial = (float) $stmt_saldo->fetchColumn();

    $stmt_mov = $db_financeiro->prepare("SELECT tipo, SUM(valor) FROM movimentacoes_caixa WHERE id_usuario = ? AND data_movimento <= ? GROUP BY tipo");
    $stmt_mov->execute([$id_usuario, $hoje]);
    $movs = $stmt_mov->fetchAll(PDO::FETCH_KEY_PAIR);
    $saldo_disponivel = $saldo_inicial + ($movs['Entrada'] ?? 0) - ($movs['Saida'] ?? 0);

    // 3. Monta e dispara o E-mail SE houver contas
    if (count($contas_hoje) > 0) {
        $total_pagar = 0;
        $lista_html = "";
        
        foreach ($contas_hoje as $conta) {
            $nome = htmlspecialchars($conta['fornecedor'] ?: $conta['descricao']);
            $valor = number_format($conta['valor'], 2, ',', '.');
            $total_pagar += $conta['valor'];
            
            $lista_html .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #444;'>{$nome}</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right; color: #dc3545; font-weight: bold;'>R$ {$valor}</td>
            </tr>";
        }
        
        $total_str = number_format($total_pagar, 2, ',', '.');
        $saldo_str = number_format($saldo_disponivel, 2, ',', '.');
        $cor_saldo = $saldo_disponivel >= 0 ? '#28a745' : '#dc3545';

        // Estrutura do E-mail HTML
        $assunto = "🔔 Alerta Financeiro: " . count($contas_hoje) . " conta(s) vencendo hoje ({$hoje_br})";
        
        $mensagem = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; margin: 0;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                <div style='background: #343a40; color: #ffffff; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Resumo Diário - Cacau Show</h2>
                    <p style='margin: 5px 0 0 0; font-size: 14px; color: #ccc;'>{$hoje_br}</p>
                </div>
                
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #333;'>Bom dia, Hugo!</p>
                    <p style='color: #555;'>Você tem <strong>" . count($contas_hoje) . " compromisso(s)</strong> a vencer hoje. Aqui está o resumo:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        {$lista_html}
                        <tr style='background: #f8f9fa;'>
                            <td style='padding: 15px; font-weight: bold; text-align: right; color: #333;'>TOTAL A PAGAR HOJE:</td>
                            <td style='padding: 15px; text-align: right; font-weight: 900; color: #dc3545; font-size: 18px;'>R$ {$total_str}</td>
                        </tr>
                    </table>

                    <div style='background: #f8f9fa; padding: 20px; border-radius: 6px; text-align: center; border: 1px solid #e9ecef;'>
                        <span style='font-size: 13px; text-transform: uppercase; font-weight: bold; color: #666; display: block; margin-bottom: 5px;'>Saldo Atual em Caixa</span>
                        <span style='font-size: 24px; font-weight: 900; color: {$cor_saldo};'>R$ {$saldo_str}</span>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='https://caixadeferramentascs.online/financeiro/dashboard.php' style='background: #28a745; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 4px; font-weight: bold; display: inline-block;'>Acessar Painel</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Headers para forçar o envio em HTML e não cair no spam facilmente
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Robô Financeiro <nao-responda@caixadeferramentascs.online>\r\n";

        // Tenta enviar
        if(mail($email_destino, $assunto, $mensagem, $headers)){
            echo "<h3 style='color: green;'>✅ Sucesso! O E-mail foi enviado para {$email_destino}.</h3>";
        } else {
            echo "<h3 style='color: red;'>❌ Falha no envio. O servidor não tem serviço de e-mail ativo nativamente.</h3>";
        }

    } else {
        echo "<h3>✅ Tudo tranquilo! Nenhuma conta para pagar hoje. O robô vai descansar.</h3>";
    }

} catch (Exception $e) {
    die("Erro no robô: " . $e->getMessage());
}
?>