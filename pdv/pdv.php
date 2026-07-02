<?php
session_start();
// Se não estiver logado, manda pro login. Ajuste conforme sua estrutura.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Define os nomes. Ajuste as chaves 'username' e 'nome' conforme está no seu banco/login
$nome_loja = $_SESSION['username'] ?? 'Loja Padrão';
$nome_operador = $_SESSION['nome'] ?? 'Operador';
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
        <div class="pdv-status">CAIXA ABERTO</div>
    </header>>

    <main class="pdv-main">

        <section class="pdv-left">
            <div class="search-bar">
                <input type="text" placeholder="🔍 Bipar código de barras ou pesquisar produto (F2)" autofocus>
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
                        <tr>
                            <td>Trufa Tradicional 30g</td>
                            <td>3</td>
                            <td>3,50</td>
                            <td>10,50</td>
                        </tr>
                        <tr>
                            <td>Tablete Cacau 70%</td>
                            <td>1</td>
                            <td>12,90</td>
                            <td>12,90</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="totals-area">
                <div class="total-label">TOTAL A PAGAR:</div>
                <div class="total-value">R$ 23,40</div>
            </div>
        </section>

        <section class="pdv-right">

            <div class="teclado-area">
                <div class="teclado-header">🖱️ Teclado Rápido (Mais Vendidos)</div>
                <div class="teclado-grid">
                    <button class="teclado-btn" onclick="adicionarItem(1, 'Trufa Trad. 30g', 3.50)">
                        <span>Trufa Trad.</span>
                        <span class="preco">R$ 3,50</span>
                    </button>

                    <button class="teclado-btn" onclick="adicionarItem(2, 'Tablete 70%', 12.90)">
                        <span>Tablete 70%</span>
                        <span class="preco">R$ 12,90</span>
                    </button>
                    <button class="teclado-btn">
                        <span>Café Expresso</span>
                        <span class="preco">R$ 6,00</span>
                    </button>
                    <button class="teclado-btn">
                        <span>Tablete 70%</span>
                        <span class="preco">R$ 12,90</span>
                    </button>
                    <button class="teclado-btn">
                        <span>MonteBello</span>
                        <span class="preco">R$ 11,90</span>
                    </button>
                    <button class="teclado-btn">
                        <span>Sacola Presente</span>
                        <span class="preco">R$ 2,50</span>
                    </button>
                </div>
            </div>

            <div class="action-area">
                <button class="btn-action btn-cancel">Cancelar Item</button>
                <button class="btn-action btn-cancel" onclick="cancelarVenda()" style="background: #6c757d;">Cancelar Venda</button>
                <button class="btn-action btn-pay" onclick="abrirPagamento()">PAGAR (F12)</button>
            </div>

        </section>
    </main>

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

    <div id="modalPagamento" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3 class="text-center">💰 Finalizar Venda</h3>
            <div class="pagamento-totais">
                Total: <span id="pag-total" style="color: #0d6efd; font-weight: bold;">R$ 0,00</span>
            </div>
            <div class="pagamento-metodos" style="grid-template-columns: 1fr 1fr 1fr;">
                <button class="btn-pagamento" onclick="processarPagamento('Dinheiro')"><span>💵</span> Dinheiro</button>
                <button class="btn-pagamento" onclick="processarPagamento('PIX')"><span>💠</span> PIX</button>
                <button class="btn-pagamento" onclick="processarPagamento('Debito')"><span>💳</span> Débito</button>
                <button class="btn-pagamento" onclick="processarPagamento('Credito')"><span>💳</span> Crédito</button>
                <button class="btn-pagamento" onclick="processarPagamento('Alimentacao')"><span>🍲</span> Alimentação</button>
                <button class="btn-pagamento" onclick="processarPagamento('Cheque Empresa')"><span>🏢</span> Cheq. Empresa</button>
                <button class="btn-pagamento" onclick="processarPagamento('Outros')"><span>➕</span> Outros</button>
            </div>
            <div class="modal-actions mt-15">
                <button class="btn-action" style="background: #6c757d; width: 100%;" onclick="fecharModal('modalPagamento')">Voltar ao Caixa (Esc)</button>
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

    <script src="../static/js/pdv.js"></script>
</body>

</html>