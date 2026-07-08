<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
require '../config.php';
require '../auth/auth_check.php';

// 2. Identifica o usuário
$user_id = $_SESSION['user_id'];
$nome_loja = $_SESSION['username'] ?? 'Loja';
$nome_operador = $_SESSION['nome'] ?? 'Operador';

// 3. Verifica se o caixa deste usuário já está aberto hoje
$stmtCaixa = $db_financeiro->prepare("SELECT id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
$stmtCaixa->execute([$user_id]);
$caixa_aberto = $stmtCaixa->fetch(PDO::FETCH_ASSOC);

$is_caixa_aberto = $caixa_aberto ? true : false;

// 4. Busca o ID do dono (Franqueado) para listar os eventos dele
// Se a sessão não tiver id_sessao, faz a busca no banco para garantir
if (!isset($_SESSION['id_sessao'])) {
    $stmtDono = $db_users->prepare("SELECT id_dono FROM user WHERE id = ?");
    $stmtDono->execute([$user_id]);
    $dadoDono = $stmtDono->fetch(PDO::FETCH_ASSOC);
    $id_dono = $dadoDono['id_dono'] ?? $user_id;
} else {
    $id_dono = $_SESSION['id_sessao'];
}

// 5. Busca os eventos ativos do franqueado dono
$stmtEventos = $db_financeiro->prepare("
    SELECT id, nome_evento 
    FROM pdv_eventos 
    WHERE user_id = ? 
    AND status = 'ativo' 
    ORDER BY id DESC
");
$stmtEventos->execute([$id_dono]);
$eventosAtivos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - Frente de Caixa</title>
    <link rel="stylesheet" href="../static/css/pdv.css">
</head>

<body>

    <header class="pdv-header">
        <h2>🍫 PDV - <?php echo htmlspecialchars(strtoupper($nome_loja)); ?></h2>
        <div>Operador: <strong><?php echo htmlspecialchars($nome_operador); ?></strong></div>

        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="pdv-status">CAIXA ABERTO</div>
            <button class="btn-action" style="background: #ffc107; color: #000; padding: 8px 15px; font-size: 0.9rem;" onclick="abrirFechamento()">Fechar Caixa</button>
            <a href="../selecao_ferramentas.php" class="btn-action" style="background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; font-size: 0.9rem;">Sair do PDV</a>
        </div>
    </header>

    <main class="pdv-main">
        <section class="pdv-left">
            <div class="search-bar" style="position: relative;">
                <input type="text" id="input-busca" placeholder="🔍 Bipar código de barras ou pesquisar produto (F2)" autofocus style="width: 100%; padding: 15px; font-size: 1.1rem; border: 2px solid #0d6efd; border-radius: 5px; outline: none;">

                <div id="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ccc; border-radius: 0 0 5px 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; max-height: 300px; overflow-y: auto;">
                </div>
            </div>
            <div class="cart-area">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qtd</th>
                            <th>Unid (R$)</th>
                            <th>Total (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="totals-area">
                <div class="total-label">TOTAL A PAGAR:</div>
                <div class="total-value">R$ 0,00</div>
            </div>
        </section>

        <section class="pdv-right">
          <div class="teclado-grid">
    <?php
    // 1. Descobrir qual caixa está aberto e o evento atual
    $stmtTurno = $db_financeiro->prepare("SELECT evento_id FROM pdv_turnos WHERE user_id = ? AND status = 'aberto' ORDER BY id DESC LIMIT 1");
    $stmtTurno->execute([$_SESSION['user_id']]);
    $turno_atual = $stmtTurno->fetch(PDO::FETCH_ASSOC);
    $evento_id = $turno_atual ? (int)$turno_atual['evento_id'] : 0;
    
    // 2. Verificar se esse evento controla estoque
    $controla_estoque = false;
    if ($evento_id > 0) {
        $stmtEv = $db_financeiro->prepare("SELECT controla_estoque FROM pdv_eventos WHERE id = ?");
        $stmtEv->execute([$evento_id]);
        $ev = $stmtEv->fetch(PDO::FETCH_ASSOC);
        $controla_estoque = ($ev && $ev['controla_estoque'] == 1);
    }

    // 3. Buscar os botões e cruzar com o estoque
    $caminho_produtos = str_replace('\\', '/', dirname(__DIR__)) . '/db/produtos.db';
    $db_financeiro->exec("ATTACH DATABASE '$caminho_produtos' AS p_db");

    $stmtTeclado = $db_financeiro->prepare("
        SELECT t.produto_id, p.nome_produto, p.preco2 as preco 
        FROM pdv_teclado_rapido t
        JOIN p_db.produtos_unificados p ON t.produto_id = p.id
        WHERE t.status = 'ativo'
        ORDER BY t.posicao ASC
    ");
    $stmtTeclado->execute();
    $botoes = $stmtTeclado->fetchAll(PDO::FETCH_ASSOC);

    foreach ($botoes as $b) {
        $estoque_limite = "'ilimitado'"; // Atenção: com aspas simples para o JS ler como string
        
        // Se controla estoque, vai no banco e pega o saldo
        if ($controla_estoque) {
            $stmtEst = $db_financeiro->prepare("SELECT quantidade_atual FROM pdv_estoque_evento WHERE evento_id = ? AND produto_id = ?");
            $stmtEst->execute([$evento_id, $b['produto_id']]);
            $est = $stmtEst->fetch(PDO::FETCH_ASSOC);
            $estoque_limite = $est ? (float)$est['quantidade_atual'] : 0;
        }

        $nome_escapado = addslashes($b['nome_produto']);
        $preco = (float)$b['preco'];
        
        // O botão agora manda a variável $estoque_limite correta (0, 1, 2... ou 'ilimitado')
        echo "<button type='button' class='btn-teclado' onclick=\"adicionarItem({$b['produto_id']}, '{$nome_escapado}', {$preco}, {$estoque_limite})\">";
        echo htmlspecialchars($b['nome_produto']);
        echo "</button>";
    }
    ?>
</div>

            <div class="action-area">
                <button class="btn-action btn-cancel" onclick="abrirCancelarItem()">Cancelar Item</button>
                <button class="btn-action btn-cancel" onclick="cancelarVenda()" style="background: #6c757d;">Cancelar Venda</button>
                <button class="btn-action btn-pay" onclick="abrirPagamento()">PAGAR (F12)</button>
            </div>
        </section>
    </main>

    <div id="recibo-print"></div>
    <!-- MODAL 1: ABERTURA DE CAIXA -->
    <?php if (!$is_caixa_aberto): ?>
        <div id="modalAberturaCaixa" class="modal-overlay" style="display: flex; background: #343a40; z-index: 99999;">
            <div class="modal-content text-center" style="border-top: 5px solid #28a745; width: 100%; max-width: 450px;">
                <h3>🔒 Abertura de Caixa</h3>
                <p style="margin-bottom: 20px; color: #666;">Informe seus dados para iniciar o turno.</p>

                <div style="text-align: left; margin-bottom: 15px;">
                    <label style="font-weight: bold; color: #555;">Operador:</label>
                    <input type="text" id="nome_operador_input" placeholder="Ex: João Silva" style="width:100%; padding: 12px; font-size: 1.1rem; border: 1px solid #ccc; border-radius: 5px;">
                </div>

                <div style="text-align: left; margin-bottom: 15px;">
                    <label style="font-weight: bold; color: #555;">Evento:</label>
                    <select id="evento_id_input" style="width:100%; padding: 12px; font-size: 1.1rem; border: 1px solid #0d6efd; border-radius: 5px; background: white; margin-bottom: 15px;">
                        <option value="0" selected>-- Selecione o Evento --</option>
                        <?php
                        if (!empty($eventosAtivos)) {
                            foreach ($eventosAtivos as $ev): ?>
                                <option value="<?php echo $ev['id']; ?>">
                                    <?php echo htmlspecialchars($ev['nome_evento']); ?>
                                </option>
                        <?php endforeach;
                        } ?>
                    </select>
                </div>

                <div style="text-align: left; margin-bottom: 25px;">
                    <label style="font-weight: bold; color: #555;">Fundo de Caixa:</label>
                    <input type="text" inputmode="numeric" oninput="mascaraMoeda(event)" id="fundo_caixa_input" placeholder="0,00" style="width:100%; padding: 15px; font-size: 1.5rem; text-align: center; border: 2px solid #0d6efd; border-radius: 8px;">
                </div>

                <button class="btn-action btn-pay" style="width: 100%; padding: 15px;" onclick="abrirCaixa()">ABRIR CAIXA</button>
                <a href="../selecao_ferramentas.php" style="display: block; margin-top: 15px; color: #6c757d; text-decoration: none;">Voltar ao Painel Principal</a>
            </div>
        </div>
    <?php endif; ?>

    <div id="modalCancelarItem" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 class="text-center">🗑️ Remover Item do Cupom</h3>
            <p class="text-center" style="margin-bottom: 15px;">Clique no botão remover ao lado do item:</p>

            <div id="lista-cancelar-item" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
            </div>

            <div class="modal-actions">
                <button class="btn-action" style="background: #6c757d; width: 100%;" onclick="fecharModal('modalCancelarItem')">Voltar ao Caixa (Esc)</button>
            </div>
        </div>
    </div>

    <div id="modalFechamento" class="modal-overlay" style="display: none; z-index: 99999; align-items: center; justify-content: center;">
        <div class="modal-content text-center" style="width: 95%; max-width: 650px; padding: 25px; box-sizing: border-box;">
            <h3>Fechamento de caixa</h3>

            <div style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 1.2rem; border: 1px solid #badbcc;">
                Total Vendido: <strong id="resumo-vendido-hoje">Carregando...</strong>
            </div>

            <p style="margin-bottom: 20px;">Informe os valores apurados:</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; margin-bottom: 20px;">
                <div><label style="font-weight: bold; color: #555;">💵 Dinheiro (Gaveta)</label><input type="text" id="f_dinheiro" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
                <div><label style="font-weight: bold; color: #555;">💳 Débito</label><input type="text" id="f_debito" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
                <div><label style="font-weight: bold; color: #555;">💳 Crédito</label><input type="text" id="f_credito" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
                <div><label style="font-weight: bold; color: #555;">💠 PIX</label><input type="text" id="f_pix" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
                <div><label style="font-weight: bold; color: #555;">🍲 Alimentação</label><input type="text" id="f_alimentacao" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
                <div><label style="font-weight: bold; color: #555;">➕ Outros</label><input type="text" id="f_outros" class="fechamento-input" inputmode="numeric" oninput="mascaraMoeda(event); calcularTotalFechamento()" placeholder="0,00" style="width:100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem;"></div>
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <span style="font-size: 1.2rem; color: #333;">Total Apurado:</span><br>
                <strong id="total-fechamento-calc" style="font-size: 2.2rem; color: #0d6efd;">R$ 0,00</strong>
            </div>
            <div class="modal-actions" style="display: flex; gap: 10px; margin-top: 0;">
                <button class="btn-action" style="background: #dc3545; flex: 2; padding: 15px;" onclick="confirmarFechamento()">Fechar Caixa</button>
                <button class="btn-action" style="background: #6c757d; flex: 1; padding: 15px;" onclick="fecharModal('modalFechamento')">Voltar</button>
            </div>
        </div>
    </div>

    <div id="modalCancelar" class="modal-overlay" style="display: none;">
        <div class="modal-content text-center">
            <h3>⚠️ Cancelar Venda</h3>
            <p>Tem certeza que deseja cancelar todos os itens e limpar o caixa?</p>
            <div class="modal-actions">
                <button class="btn-action btn-cancel" onclick="confirmarCancelamento()">Sim, Cancelar</button>
                <button class="btn-action" style="background: #6c757d;" onclick="fecharModal('modalCancelar')">Não, Voltar</button>
            </div>
        </div>
    </div>

    <div id="modalPagamento" class="modal-overlay" style="display: none; z-index: 99998; align-items: center; justify-content: center;">
        <div class="modal-content" style="width: 95%; max-width: 500px; padding: 25px;">
            <h3 class="text-center">💰 Finalizar Venda</h3>

            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.1rem;">
                    <span>Subtotal:</span>
                    <strong id="pag-subtotal">R$ 0,00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: #198754;">Desconto (R$):</span>
                    <input type="text" id="pag-desconto" inputmode="numeric" oninput="mascaraMoeda(event); recalcularTotalPagamento()" placeholder="0,00" style="width: 100px; padding: 8px; text-align: right; border: 1px solid #ccc; border-radius: 5px;">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="color: #dc3545;">Acréscimo (R$):</span>
                    <input type="text" id="pag-acrescimo" inputmode="numeric" oninput="mascaraMoeda(event); recalcularTotalPagamento()" placeholder="0,00" style="width: 100px; padding: 8px; text-align: right; border: 1px solid #ccc; border-radius: 5px;">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px dashed #ccc; padding-top: 15px;">
                    <span style="font-size: 1.3rem; font-weight: bold;">TOTAL:</span>
                    <strong id="pag-total" style="font-size: 2rem; color: #0d6efd;">R$ 0,00</strong>
                </div>
            </div>

            <p style="text-align: center; margin-bottom: 10px; color: #555;">Selecione a forma de pagamento:</p>
            <div class="pagamento-metodos" style="grid-template-columns: 1fr 1fr 1fr;">
                <button class="btn-pagamento" onclick="processarPagamento('Dinheiro')"><span>💵</span> Dinheiro</button>
                <button class="btn-pagamento" onclick="processarPagamento('PIX')"><span>💠</span> PIX</button>
                <button class="btn-pagamento" onclick="processarPagamento('Debito')"><span>💳</span> Débito</button>
                <button class="btn-pagamento" onclick="processarPagamento('Credito')"><span>💳</span> Crédito</button>
                <button class="btn-pagamento" onclick="processarPagamento('Alimentacao')"><span>🍲</span> Alimentação</button>
                <button class="btn-pagamento" onclick="processarPagamento('Cheque Empresa')"><span>🏢</span> Ch. Empresa</button>
                <button class="btn-pagamento" onclick="processarPagamento('Outros')"><span>➕</span> Outros</button>
            </div>
            <div class="modal-actions" style="margin-top: 15px;">
                <button class="btn-action" style="background: #6c757d; width: 100%; padding: 15px;" onclick="fecharModal('modalPagamento')">Voltar ao Caixa (Esc)</button>
            </div>
        </div>
    </div>

    <div id="modalTroco" class="modal-overlay" style="display: none;">
        <div class="modal-content text-center">
            <h3>💵 Receber em Dinheiro</h3>
            <div style="font-size: 1.2rem; margin-bottom: 15px;">
                Total da Venda: <strong id="troco-total-venda" style="color: #dc3545;">R$ 0,00</strong>
            </div>
            <p>Valor entregue pelo cliente (R$):</p>
            <input type="text" inputmode="numeric" id="valor-recebido" placeholder="0,00" oninput="mascaraMoeda(event)" onkeyup="calcularTroco()" style="width:100%; padding: 15px; font-size: 2rem; text-align: center; margin-bottom: 15px; border: 2px solid #ccc; border-radius: 8px;" autofocus>

            <div style="font-size: 1.2rem; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                Troco a devolver:<br>
                <strong id="valor-troco" style="color: #28a745; font-size: 2.5rem;">R$ 0,00</strong>
            </div>

            <div class="modal-actions">
                <button class="btn-action btn-pay" onclick="finalizarDinheiro()">Confirmar Venda</button>
                <button class="btn-action" style="background: #6c757d;" onclick="fecharModal('modalTroco'); abrirModal('modalPagamento');">Voltar</button>
            </div>
        </div>
    </div>

    <div id="modalAlerta" class="modal-overlay" style="display: none;">
        <div class="modal-content text-center">
            <h3 id="alertaTitulo">Aviso</h3>
            <p id="alertaMensagem">Mensagem do sistema.</p>
            <div class="modal-actions">
                <button class="btn-action btn-pay" onclick="fecharModal('modalAlerta')">OK</button>
            </div>
        </div>
    </div>

<?php
    $hoje = date('Y-m-d');
    $stmtPromos = $db_financeiro->prepare("
        SELECT id, nome_campanha, tipo_mecanica, qtd_gatilho, valor_beneficio 
        FROM motor_promocoes 
        WHERE status = 'ativo' 
        AND data_inicio <= ? AND data_fim >= ?
    ");
    $stmtPromos->execute([$hoje, $hoje]);
    $promocoes_ativas = $stmtPromos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($promocoes_ativas as $key => $promo) {
        $stmtProd = $db_financeiro->prepare("SELECT produto_id FROM motor_promocoes_produtos WHERE promocao_id = ?");
        $stmtProd->execute([$promo['id']]);
        $produtos = $stmtProd->fetchAll(PDO::FETCH_COLUMN);
        $promocoes_ativas[$key]['produtos'] = $produtos;
    }
    ?>
    <script>
        const PROMOCOES_ATIVAS = <?php echo json_encode($promocoes_ativas); ?>;
    </script>
    <script src="../static/js/pdv.js?v=2"></script>
</body>
</html>