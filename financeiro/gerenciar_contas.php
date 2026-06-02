<?php
require_once '../config.php';
require_once '../auth/auth_check.php';
$page_title = "Gerenciar contas";
$sessao_nome = "Caixa e Bancos";
$id_usuario = $_SESSION['user_id'];

// Atualiza o banco de dados silenciosamente
try { $db_financeiro->exec("ALTER TABLE contas_bancarias ADD COLUMN numero_conta TEXT"); } catch (Exception $e) { }
try { $db_financeiro->exec("ALTER TABLE contas_bancarias ADD COLUMN status TEXT DEFAULT 'Ativa'"); } catch (Exception $e) { } 

// =================================================================
// PROCESSAMENTO DE AÇÕES
// =================================================================

// --- MUDAR STATUS DA CONTA (ATIVAR/INATIVAR) ---
if (isset($_GET['acao']) && isset($_GET['id']) && in_array($_GET['acao'], ['ativar', 'inativar'])) {
    $id_alvo = (int)$_GET['id'];
    $novo_status = ($_GET['acao'] === 'inativar') ? 'Inativa' : 'Ativa';
    
    $stmt = $db_financeiro->prepare("UPDATE contas_bancarias SET status = ? WHERE id = ? AND id_usuario = ?");
    $stmt->execute([$novo_status, $id_alvo, $id_usuario]);
    
    header("Location: gerenciar_contas.php?msg=status_ok");
    exit;
}

// --- EXCLUIR CONTA BANCÁRIA ---
if (isset($_GET['acao']) && isset($_GET['id']) && $_GET['acao'] === 'excluir') {
    $id_alvo = (int)$_GET['id'];
    try {
        $stmt = $db_financeiro->prepare("DELETE FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$id_alvo, $id_usuario]);
        header("Location: gerenciar_contas.php?msg=excluida");
        exit;
    } catch (PDOException $e) {
        header("Location: gerenciar_contas.php?msg=erro_excluir");
        exit;
    }
}

// --- SALVAR (NOVA CONTA OU ATUALIZAÇÃO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id_conta = $_POST['id_conta'] ?? null;
    $nome_conta = $_POST['nome_conta'];
    $banco = $_POST['banco'];
    $numero_conta = $_POST['numero_conta'];
    
    // Máscara
    $saldo_bruto = $_POST['saldo_inicial'] ?? '0';
    $saldo_limpo = str_replace('.', '', $saldo_bruto);
    $saldo_limpo = str_replace(',', '.', $saldo_limpo);
    $saldo_inicial = (float)$saldo_limpo;

    if ($acao === 'salvar') {
        $stmt = $db_financeiro->prepare("INSERT INTO contas_bancarias (id_usuario, nome_conta, banco, numero_conta, saldo_inicial, data_saldo_inicial, status) VALUES (?, ?, ?, ?, ?, ?, 'Ativa')");
        $stmt->execute([$id_usuario, $nome_conta, $banco, $numero_conta, $saldo_inicial, date('Y-m-d')]);
        header("Location: gerenciar_contas.php?msg=sucesso");
        exit;
    } 
    elseif ($acao === 'atualizar' && $id_conta) {
        $stmt = $db_financeiro->prepare("UPDATE contas_bancarias SET nome_conta = ?, banco = ?, numero_conta = ?, saldo_inicial = ? WHERE id = ? AND id_usuario = ?");
        $stmt->execute([$nome_conta, $banco, $numero_conta, $saldo_inicial, $id_conta, $id_usuario]);
        header("Location: gerenciar_contas.php?msg=atualizada");
        exit;
    }
}

require_once '../includes/header.php';

$stmt_contas = $db_financeiro->prepare("SELECT * FROM contas_bancarias WHERE id_usuario = ? ORDER BY status ASC, nome_conta ASC");
$stmt_contas->execute([$id_usuario]);
$contas = $stmt_contas->fetchAll(PDO::FETCH_ASSOC);

$mensagem = ""; $tipo_alerta = "success";
if(isset($_GET['msg'])) {
    if($_GET['msg'] === 'sucesso') $mensagem = "Nova conta cadastrada com sucesso!";
    if($_GET['msg'] === 'atualizada') $mensagem = "Conta atualizada com sucesso!";
    if($_GET['msg'] === 'status_ok') $mensagem = "Status da conta alterado com sucesso!";
    if($_GET['msg'] === 'excluida') $mensagem = "Conta apagada permanentemente!";
    if($_GET['msg'] === 'erro_excluir') {
        $mensagem = "Ação negada: Esta conta possui movimentações financeiras. Por motivos de segurança, você deve apenas INATIVÁ-LA.";
        $tipo_alerta = "danger";
    }
}
?>

<link rel="stylesheet" href="../static/css/financeiro.css">
<link rel="stylesheet" href="../static/css/caixa_bancos.css">


    <div class="financeiro-nav">
        <div class="nav-dropdown">
            <button class="nav-dropbtn">Cadastros ▾</button>
            <div class="nav-dropdown-content">
                <a href="gerenciar_contas.php">Contas Correntes</a>
                <a href="#">Fornecedores</a>
                <a href="#">Clientes</a>
            </div>
        </div>
        <a href="caixa_bancos.php">Caixa e Bancos</a>
        <a href="contas_pagar.php">Contas a Pagar</a>
        <a href="#">Contas a Receber</a>
        <div class="nav-dropdown">
            <button class="nav-dropbtn">Relatórios ▾</button>
            <div class="nav-dropdown-content">
                <a href="relatorio_contas.php">Pagamentos</a>
                <a href="#">Recebimentos</a>
            </div>
        </div>
    </div>

<div class="container-dashboard">
    <div class="cabecalho-pagina">
        <h2>Gerenciar Contas Bancárias</h2>
    </div>

    <?php if(!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipo_alerta ?>" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; <?php echo ($tipo_alerta == 'danger') ? 'background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545;' : 'background: #d4edda; color: #155724; border-left: 5px solid #28a745;'; ?>">
            <?= ($tipo_alerta == 'danger') ? '❌' : '✅' ?> <?= $mensagem ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div class="painel-branco" style="flex: 1; min-width: 320px;">
            <h4 id="tituloForm" style="margin-top: 0; color: #495057; border-bottom: 2px solid #f1f3f5; padding-bottom: 10px; margin-bottom: 20px;">+ Nova Conta</h4>
            
            <form method="POST" action="gerenciar_contas.php" id="formConta">
                <input type="hidden" name="acao" id="acaoForm" value="salvar">
                <input type="hidden" name="id_conta" id="id_conta" value="">
                
                <div class="grupo-filtro" style="margin-bottom: 15px;">
                    <label>Nome da Conta (Ex: Caixa Loja...)</label>
                    <input type="text" id="nome_conta" name="nome_conta" class="form-control" required placeholder="Digite o nome...">
                </div>

                <div class="grupo-filtro" style="margin-bottom: 15px;">
                    <label>Instituição Bancária (Ex: Itaú, Sicredi)</label>
                    <input type="text" id="banco" name="banco" class="form-control" required placeholder="Qual o banco?">
                </div>

                <div class="grupo-filtro" style="margin-bottom: 15px;">
                    <label>Agência / Número (Opcional)</label>
                    <input type="text" id="numero_conta" name="numero_conta" class="form-control" placeholder="0000-0">
                </div>

                <div class="grupo-filtro" style="margin-bottom: 25px;">
                    <label>Saldo Inicial Atual (R$)</label>
                    <input type="text" id="saldo_inicial" name="saldo_inicial" class="form-control" value="0,00" oninput="mascaraMoeda(this)" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" id="btnForm" class="btn btn-success" style="flex: 1; font-weight: bold; padding: 10px;">💾 Cadastrar Conta</button>
                    <button type="button" id="btnCancelar" class="btn btn-secondary" style="display: none; padding: 10px;" onclick="cancelarEdicao()">Cancelar</button>
                </div>
            </form>
        </div>

        <div class="painel-branco" style="flex: 2; min-width: 320px; width: 100%; overflow: hidden;"> 
            <h4 style="margin-top: 0; color: #495057; border-bottom: 2px solid #f1f3f5; padding-bottom: 10px; margin-bottom: 20px;">Minhas Contas</h4>
            
            <div style="width: 100%; overflow-x: auto; padding-bottom: 180px;">
                <table class="table table-striped table-hover" style="width: 100%; min-width: 550px;">
                    <thead>
                        <tr>
                            <th>Nome da Conta</th>
                            <th>Banco</th>
                            <th>Saldo Inicial</th>
                            <th>Status</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($contas as $c): 
                            $inativa = ($c['status'] === 'Inativa');
                            // Em vez de opacity, mudamos a cor do texto para dar o efeito apagado sem estragar o menu!
                            $cor_texto = $inativa ? 'color: #a0a0a0;' : '';
                            $cor_saldo = $inativa ? '#8ba2b5' : '#1565c0'; 
                        ?>
                        <tr style="<?= $inativa ? 'background-color: #f8f9fa;' : '' ?>">
                            <td style="vertical-align: middle; <?= $cor_texto ?>"><strong><?= htmlspecialchars($c['nome_conta']) ?></strong></td>
                            <td style="vertical-align: middle; <?= $cor_texto ?>"><?= htmlspecialchars($c['banco']) ?></td>
                            <td style="color: <?= $cor_saldo ?>; font-weight: bold; vertical-align: middle; white-space: nowrap;">R$ <?= number_format($c['saldo_inicial'], 2, ',', '.') ?></td>
                            <td style="vertical-align: middle;">
                                <?php if($inativa): ?>
                                    <span style="background: #dc3545; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Inativa</span>
                                <?php else: ?>
                                    <span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Ativa</span>
                                <?php endif; ?>
                            </td>
                            
                            <td style="text-align: center; vertical-align: middle;">
                                <div class="dropdown-acoes">
                                    <button onclick="toggleMenuContas(event, <?= $c['id'] ?>)" class="btn-dots">⋮</button>
                                    <div id="menu-conta-<?= $c['id'] ?>" class="dropdown-content">
                                        <a href="#" onclick="editarConta(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nome_conta'])) ?>', '<?= htmlspecialchars(addslashes($c['banco'])) ?>', '<?= htmlspecialchars(addslashes($c['numero_conta'])) ?>', '<?= $c['saldo_inicial'] ?>'); return false;">✏️ Editar</a>
                                        
                                        <?php if($inativa): ?>
                                            <a href="gerenciar_contas.php?acao=ativar&id=<?= $c['id'] ?>" style="color: #28a745;">✅ Ativar Conta</a>
                                        <?php else: ?>
                                            <a href="gerenciar_contas.php?acao=inativar&id=<?= $c['id'] ?>" onclick="return confirm('Inativar esta conta? Ela sumirá das opções de lançamento.')">⏸️ Inativar</a>
                                        <?php endif; ?>

                                        <a href="gerenciar_contas.php?acao=excluir&id=<?= $c['id'] ?>" class="text-danger" onclick="return confirm('ATENÇÃO: Deseja apagar esta conta permanentemente? (Contas com lançamentos ativos não poderão ser apagadas)')">🗑️ Excluir</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($contas)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: #666;">Nenhuma conta cadastrada ainda. Use o painel ao lado!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>

<script>
// Máscara 
function mascaraMoeda(input) {
    let valor = input.value.replace(/\D/g, ''); 
    if (valor === '') valor = '0';
    valor = (parseInt(valor) / 100).toFixed(2) + ''; 
    valor = valor.replace(".", ","); 
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."); 
    input.value = valor;
}

// Menus Kebab
function toggleMenuContas(event, idMenu) {
    event.stopPropagation();
    let menus = document.getElementsByClassName("dropdown-content");
    for (let i = 0; i < menus.length; i++) {
        if (menus[i].id !== `menu-conta-${idMenu}`) {
            menus[i].classList.remove('dropdown-show');
        }
    }
    document.getElementById(`menu-conta-${idMenu}`).classList.toggle("dropdown-show");
}

window.onclick = function(event) {
    if (!event.target.matches('.btn-dots')) {
        let dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('dropdown-show')) {
                dropdowns[i].classList.remove('dropdown-show');
            }
        }
    }
}

// Edição
function editarConta(id, nome, banco, numero, saldoBd) {
    let dropdowns = document.getElementsByClassName("dropdown-content");
    for (let i = 0; i < dropdowns.length; i++) dropdowns[i].classList.remove('dropdown-show');

    document.getElementById('acaoForm').value = 'atualizar';
    document.getElementById('id_conta').value = id;
    document.getElementById('nome_conta').value = nome;
    document.getElementById('banco').value = banco;
    document.getElementById('numero_conta').value = numero;
    
    let valor = parseFloat(saldoBd).toFixed(2);
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    document.getElementById('saldo_inicial').value = valor;
    
    document.getElementById('tituloForm').innerText = '✏️ Editar Conta Bancária';
    document.getElementById('btnForm').innerText = '🔄 Atualizar Conta';
    document.getElementById('btnForm').classList.remove('btn-success');
    document.getElementById('btnForm').classList.add('btn-primary');
    document.getElementById('btnCancelar').style.display = 'block';
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancelarEdicao() {
    document.getElementById('formConta').reset();
    document.getElementById('acaoForm').value = 'salvar';
    document.getElementById('id_conta').value = '';
    document.getElementById('saldo_inicial').value = '0,00';
    
    document.getElementById('tituloForm').innerText = '+ Nova Conta';
    document.getElementById('btnForm').innerText = '💾 Cadastrar Conta';
    document.getElementById('btnForm').classList.add('btn-success');
    document.getElementById('btnForm').classList.remove('btn-primary');
    document.getElementById('btnCancelar').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>