<?php
// auth/minha_equipe.php
require '../config.php';
require 'auth_franqueado_check.php';

$page_title = "Minhas lojas";
$sessao_nome = "Lojas";

require '../includes/header.php';

$id_dono = $_SESSION['user_id'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ==========================================
    // 1. CRIAR LOJA E COLABORADOR
    // ==========================================
    if ($_POST['action'] === 'criar_usuario') {
        $nome_loja = trim($_POST['nome_loja']);
        $novo_username = trim($_POST['novo_username']);
        $nova_senha = $_POST['nova_senha'];
        $perfil = 'colaborador';

        if (!empty($novo_username) && !empty($nova_senha) && !empty($nome_loja)) {
            try {
                $db_users->beginTransaction();

                $stmt = $db_users->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$novo_username]);

                if ($stmt->fetch()) {
                    $mensagem = "<div style='color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 20px;'>❌ Este nome de utilizador já existe.</div>";
                    $db_users->rollBack();
                } else {
                    $stmtLoja = $db_users->prepare("INSERT INTO lojas (nome, id_franqueado, ativo) VALUES (?, ?, 1)");
                    $stmtLoja->execute([$nome_loja, $id_dono]);

                    $novo_id_loja = $db_users->lastInsertId();

                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmtUser = $db_users->prepare("INSERT INTO user (username, password_hash, perfil, id_dono, id_loja) VALUES (?, ?, ?, ?, ?)");
                    $stmtUser->execute([$novo_username, $senha_hash, $perfil, $id_dono, $novo_id_loja]);

                    $db_users->commit();
                    $mensagem = "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 6px; margin-bottom: 20px;'>✅ Loja e login cadastrados com sucesso!</div>";
                }
            } catch (Exception $e) {
                $db_users->rollBack();
                $mensagem = "<div style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px;'>Erro: " . $e->getMessage() . "</div>";
            }
        }
    }

    // ==========================================
    // 2. EDITAR LOJA E COLABORADOR
    // ==========================================
    if ($_POST['action'] === 'editar_usuario') {
        $id_usuario = $_POST['id_usuario'];
        $id_loja = $_POST['id_loja'];
        $nome_loja = trim($_POST['nome_loja']);
        $username = trim($_POST['username']);
        $nova_senha = $_POST['nova_senha'];

        if (!empty($id_usuario) && !empty($username) && !empty($nome_loja)) {
            try {
                $db_users->beginTransaction();

                $stmtCheck = $db_users->prepare("SELECT id FROM user WHERE username = ? AND id != ?");
                $stmtCheck->execute([$username, $id_usuario]);

                if ($stmtCheck->fetch()) {
                    $mensagem = "<div style='color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 20px;'>❌ Este nome de utilizador já está em uso.</div>";
                    $db_users->rollBack();
                } else {
                    if (!empty($id_loja)) {
                        $stmtLoja = $db_users->prepare("UPDATE lojas SET nome = ? WHERE id = ? AND id_franqueado = ?");
                        $stmtLoja->execute([$nome_loja, $id_loja, $id_dono]);
                    }

                    if (!empty($nova_senha)) {
                        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $stmtUser = $db_users->prepare("UPDATE user SET username = ?, password_hash = ? WHERE id = ? AND id_dono = ?");
                        $stmtUser->execute([$username, $senha_hash, $id_usuario, $id_dono]);
                    } else {
                        $stmtUser = $db_users->prepare("UPDATE user SET username = ? WHERE id = ? AND id_dono = ?");
                        $stmtUser->execute([$username, $id_usuario, $id_dono]);
                    }

                    $db_users->commit();
                    $mensagem = "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 6px; font-size: 14px; margin-bottom: 20px;'>✅ Dados atualizados com sucesso!</div>";
                }
            } catch (Exception $e) {
                $db_users->rollBack();
                $mensagem = "<div style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px;'>Erro: " . $e->getMessage() . "</div>";
            }
        }
    }

    // ==========================================
    // 3. ATIVAR / INATIVAR LOJA
    // ==========================================
    if ($_POST['action'] === 'toggle_status') {
        $id_loja = $_POST['id_loja'];
        $novo_status = $_POST['novo_status'];

        if (!empty($id_loja)) {
            try {
                $stmtLoja = $db_users->prepare("UPDATE lojas SET ativo = ? WHERE id = ? AND id_franqueado = ?");
                $stmtLoja->execute([$novo_status, $id_loja, $id_dono]);

                $status_texto = $novo_status == 1 ? 'ativada' : 'inativada';
                $mensagem = "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 6px; font-size: 14px; margin-bottom: 20px;'>✅ Loja {$status_texto} com sucesso!</div>";
            } catch (Exception $e) {
                $mensagem = "<div style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px;'>Erro: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Busca as lojas e os funcionários que pertencem a este franqueado
$stmt = $db_users->prepare("SELECT
    u.id,
    u.username,
    u.id_loja,
    l.nome AS loja,
    l.ativo
FROM user u
LEFT JOIN lojas l
    ON l.id = u.id_loja
WHERE u.id_dono = ?
ORDER BY u.username ASC");

$stmt->execute([$id_dono]);
$equipe = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<div class="financeiro-wrapper" style="max-width: 900px; margin: 40px auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">

    <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
        <h1 style="margin: 0; color: #333; font-size: 26px;">Gestão da Minha Equipe</h1>
        <a href="area_franqueado.php" class="btn btn-secondary" style="background: #007bff; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">← VOLTAR</a>
    </div>

    <?= $mensagem ?>

    <p style="color: #555; font-size: 16px; margin-bottom: 25px;">Cadastre o acesso para os colaboradores da sua loja. Eles terão acesso APENAS ao Quadro de Gestão e Controle de Validades.</p>

    <!-- Bloco de Cadastro -->
    <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 35px;">
        <h3 style="margin-top: 0; color: #333; font-size: 18px;">➕ Cadastrar Nova Loja</h3>

        <form method="POST" action="" style="display: flex; gap: 15px; margin-top: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="action" value="criar_usuario">

            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444; font-size: 14px;">Nome da Loja</label>
                <input type="text" name="nome_loja" required placeholder="Ex: Arena Esportes" style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444; font-size: 14px;">Nome de Acesso (Login)</label>
                <input type="text" name="novo_username" required placeholder="Ex: cs_revenda" style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444; font-size: 14px;">Senha</label>
                <input type="text" name="nova_senha" required placeholder="Ex: senha123" style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>

            <button type="submit" style="background: #28a745; color: #fff; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; height: 46px;">Salvar</button>
        </form>
    </div>

    <h3 style="color: #333; font-size: 20px; margin-bottom: 15px;">Equipe Atual</h3>

    <!-- Tabela Estilizada -->
    <div style="overflow-x: auto; border: 1px solid #dee2e6; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; background: #fff; font-size: 15px;">
            <thead>
                <tr style="background: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 15px; text-align: left; color: #495057; width: 30%;">Nome da Loja</th>
                    <th style="padding: 15px; text-align: left; color: #495057; width: 25%;">Nome de Acesso</th>
                    <th style="padding: 15px; text-align: center; color: #495057; width: 15%;">Status</th>
                    <th style="padding: 15px; text-align: center; color: #495057; width: 30%;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($equipe) === 0): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #6c757d;">Nenhuma loja cadastrada ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipe as $u): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px; font-size: 14px; color: #212529;">
                                <?= htmlspecialchars($u['loja'] ?? 'Loja sem nome (Antiga)') ?>
                            </td>
                            <td style="padding: 15px; font-weight: bold; color: #212529; font-size: 16px;">
                                <?= htmlspecialchars($u['username']) ?>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <?php if (!isset($u['ativo']) || $u['ativo'] == 1): ?>
                                    <span style="background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">Ativa</span>
                                <?php else: ?>
                                    <span style="background: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; text-align: center; display: flex; gap: 8px; justify-content: center;">
                                <button type="button" class="btn btn-warning" style="padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer;"
                                    onclick="abrirModalEdicaoEquipe(
                                        <?= $u['id'] ?>, 
                                        '<?= $u['id_loja'] ?? '' ?>', 
                                        '<?= htmlspecialchars($u['loja'] ?? '', ENT_QUOTES) ?>', 
                                        '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>'
                                    )">
                                    ✏️ Editar
                                </button>

                                <?php if (!empty($u['id_loja'])): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id_loja" value="<?= $u['id_loja'] ?>">
                                        <input type="hidden" name="novo_status" value="<?= (isset($u['ativo']) && $u['ativo'] == 1) ? 0 : 1 ?>">

                                        <?php if (!isset($u['ativo']) || $u['ativo'] == 1): ?>
                                             <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer; background: #6c757d; color: white;" onclick="return confirm('Tem certeza que deseja INATIVAR esta loja?')">
                                                🚫 Inativar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px; cursor: pointer; background: #28a745; color: white;" onclick="return confirm('Tem certeza que deseja ATIVAR esta loja?')">
                                                ✅ Ativar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edição -->
<div id="modalEdicaoEquipe" class="modal-financeiro" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #fff; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <div class="modal-header" style="background: #ffc107; color: #000; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0; font-size: 18px;">Editar Loja/Acesso</h3>
            <button type="button" onclick="fecharModalEdicaoEquipe()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="action" value="editar_usuario">
            <input type="hidden" name="id_usuario" id="edit_eq_id_usuario">
            <input type="hidden" name="id_loja" id="edit_eq_id_loja">

            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nome da Loja</label>
                <input type="text" name="nome_loja" id="edit_eq_nome_loja" required class="form-control" style="width: 100%; padding: 10px; font-size: 13px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nome de Acesso (Login)</label>
                <input type="text" name="username" id="edit_eq_username" required class="form-control" style="width: 100%; padding: 10px; font-size: 13px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nova Senha</label>
                <input type="text" name="nova_senha" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;" placeholder="Deixe em branco para não alterar">
                <small style="color: #666; font-size: 11px;">Só preencha se quiser mudar a senha atual.</small>
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="fecharModalEdicaoEquipe()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" style="padding:10px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalEdicaoEquipe(idUsuario, idLoja, nomeLoja, username) {
        document.getElementById('edit_eq_id_usuario').value = idUsuario;
        document.getElementById('edit_eq_id_loja').value = idLoja;
        document.getElementById('edit_eq_nome_loja').value = nomeLoja;
        document.getElementById('edit_eq_username').value = username;

        document.getElementById('modalEdicaoEquipe').style.display = 'flex';
    }

    function fecharModalEdicaoEquipe() {
        document.getElementById('modalEdicaoEquipe').style.display = 'none';
    }
</script>

<?php require '../includes/footer.php'; ?>