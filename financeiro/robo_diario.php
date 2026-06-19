<?php
require '../config.php'; 

$token_seguranca = "cacau2026"; 

if (!isset($_GET['token']) || $_GET['token'] !== $token_seguranca) {
    if (php_sapi_name() !== 'cli') { die("Acesso negado."); }
}

$hoje = date('Y-m-d');
$hoje_br = date('d/m/Y');

try {
    $stmt_usuarios = $db_financeiro->query("SELECT id, nome, email FROM usuarios");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
    $emails_enviados = 0;

    foreach ($usuarios as $user) {
        $id_usuario = $user['id'];
        $email_destino = $user['email'];
        $nome_usuario = htmlspecialchars($user['nome'] ?? 'Usuário');

        if (empty($email_destino)) continue;

        // Busca contas de HOJE
        $stmt_hoje = $db_financeiro->prepare("SELECT descricao, valor, fornecedor FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento = ?");
        $stmt_hoje->execute([$id_usuario, $hoje]);
        $contas_hoje = $stmt_hoje->fetchAll(PDO::FETCH_ASSOC);

        // Busca contas ATRASADAS
        $stmt_atraso = $db_financeiro->prepare("SELECT descricao, valor, fornecedor, vencimento FROM contas_pagar WHERE id_usuario = ? AND status != 'Pago' AND vencimento < ? ORDER BY vencimento ASC");
        $stmt_atraso->execute([$id_usuario, $hoje]);
        $contas_atrasadas = $stmt_atraso->fetchAll(PDO::FETCH_ASSOC);

        $total_hoje = count($contas_hoje);
        $total_atrasadas = count($contas_atrasadas);

        // Se tiver contas hoje ou em atraso, monta o e-mail
        if ($total_hoje > 0 || $total_atrasadas > 0) {
            
            $stmt_saldo = $db_financeiro->prepare("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE id_usuario = ? AND (status = 'Ativa' OR status IS NULL)");
            $stmt_saldo->execute([$id_usuario]);
            $saldo_inicial = (float) $stmt_saldo->fetchColumn();

            $stmt_mov = $db_financeiro->prepare("SELECT tipo, SUM(valor) FROM movimentacoes_caixa WHERE id_usuario = ? AND data_movimento <= ? GROUP BY tipo");
            $stmt_mov->execute([$id_usuario, $hoje]);
            $movs = $stmt_mov->fetchAll(PDO::FETCH_KEY_PAIR);
            $saldo_disponivel = $saldo_inicial + ($movs['Entrada'] ?? 0) - ($movs['Saida'] ?? 0);
            
            $saldo_str = number_format($saldo_disponivel, 2, ',', '.');
            $cor_saldo = $saldo_disponivel >= 0 ? '#28a745' : '#dc3545';
            
            $html_corpo = "";

            // BLOCO: CONTAS ATRASADAS (Vermelho Escuro)
            if ($total_atrasadas > 0) {
                $html_corpo .= "<h3 style='color: #721c24; margin-top: 20px; border-bottom: 2px solid #f5c6cb; padding-bottom: 5px;'>⚠️ Contas em Atraso ({$total_atrasadas})</h3>";
                $html_corpo .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
                $tot_vencido = 0;
                foreach ($contas_atrasadas as $c) {
                    $nome = htmlspecialchars($c['fornecedor'] ?: $c['descricao']);
                    $val = number_format($c['valor'], 2, ',', '.');
                    $venc = date('d/m/Y', strtotime($c['vencimento']));
                    $tot_vencido += $c['valor'];
                    $html_corpo .= "<tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #444;'>{$nome}<br><small style='color: #999;'>Venceu em: {$venc}</small></td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right; color: #721c24; font-weight: bold;'>R$ {$val}</td>
                    </tr>";
                }
                $html_corpo .= "<tr style='background: #f8d7da;'><td style='padding: 10px; font-weight: bold; text-align: right; color: #721c24;'>Total em Atraso:</td><td style='padding: 10px; text-align: right; font-weight: bold; color: #721c24;'>R$ " . number_format($tot_vencido, 2, ',', '.') . "</td></tr>";
                $html_corpo .= "</table>";
            }

            // BLOCO: CONTAS DE HOJE (Laranja/Amarelo)
            if ($total_hoje > 0) {
                $html_corpo .= "<h3 style='color: #856404; margin-top: 20px; border-bottom: 2px solid #ffeeba; padding-bottom: 5px;'>📅 Vencem Hoje ({$total_hoje})</h3>";
                $html_corpo .= "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
                $tot_hoje_val = 0;
                foreach ($contas_hoje as $c) {
                    $nome = htmlspecialchars($c['fornecedor'] ?: $c['descricao']);
                    $val = number_format($c['valor'], 2, ',', '.');
                    $tot_hoje_val += $c['valor'];
                    $html_corpo .= "<tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #444;'>{$nome}</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right; color: #856404; font-weight: bold;'>R$ {$val}</td>
                    </tr>";
                }
                $html_corpo .= "<tr style='background: #fff3cd;'><td style='padding: 10px; font-weight: bold; text-align: right; color: #856404;'>Total de Hoje:</td><td style='padding: 10px; text-align: right; font-weight: bold; color: #856404;'>R$ " . number_format($tot_hoje_val, 2, ',', '.') . "</td></tr>";
                $html_corpo .= "</table>";
            }

            $assunto = "🔔 Alerta Financeiro: {$total_hoje} para hoje, {$total_atrasadas} em atraso";
            
            $mensagem = "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; margin: 0;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;'>
                    <div style='background: #343a40; color: #ffffff; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Resumo de Contas a Pagar</h2>
                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #ccc;'>{$hoje_br}</p>
                    </div>
                    <div style='padding: 30px;'>
                        <p style='font-size: 16px; color: #333;'>Olá, {$nome_usuario}!</p>
                        
                        {$html_corpo}

                        <div style='background: #f8f9fa; padding: 20px; border-radius: 6px; text-align: center; border: 1px solid #e9ecef; margin-top: 30px;'>
                            <span style='font-size: 13px; text-transform: uppercase; font-weight: bold; color: #666; display: block; margin-bottom: 5px;'>Seu Saldo Atual em Caixa</span>
                            <span style='font-size: 24px; font-weight: 900; color: {$cor_saldo};'>R$ {$saldo_str}</span>
                        </div>
                        <div style='text-align: center; margin-top: 30px;'>
                            <a href='https://caixadeferramentascs.online/financeiro/dashboard.php' style='background: #28a745; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 4px; font-weight: bold; display: inline-block;'>Acessar Painel</a>
                        </div>
                    </div>
                </div>
            </body>
            </html>";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Robô Financeiro <nao-responda@caixadeferramentascs.online>\r\n";

            if(mail($email_destino, $assunto, $mensagem, $headers)){ $emails_enviados++; }
        }
    }
    echo "<h3>✅ Fim de turno. Foram enviados {$emails_enviados} e-mails de alerta.</h3>";

} catch (Exception $e) { die("Erro: " . $e->getMessage()); }
?>