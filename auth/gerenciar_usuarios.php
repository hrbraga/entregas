<?php
require '../config.php';
require 'auth_check.php'; // Garante que está logado
require 'auth_franqueado_check.php';

// Só permite o utilizador master
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'hugo_admin') {
    // Caminho relativo limpo e direto
    header("Location: ../selecao_ferramentas.php?erro=acesso_negado");
    exit;
}

require '../includes/header.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_senha') {
    $id_alvo = $_POST['id_usuario'];
    $nova_senha = $_POST['nova_senha'];
    
    if (!empty($nova_senha)) {
        // Criptografa a nova senha com segurança máxima
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db_users->prepare("UPDATE user SET password_hash = ? WHERE id = ?");
            $stmt->execute([$senha_hash, $id_alvo]);
            $mensagem = "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;'>✅ Senha atualizada com sucesso! O utilizador já pode fazer login.</div>";
        } catch (Exception $e) {
            $mensagem = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;'>❌ Erro ao atualizar: " . $e->getMessage() . "</div>";
        }
    }
}

// Busca todos os utilizadores registados
try {
    $stmt = $db_users->query("SELECT id, username FROM user ORDER BY username ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<div class="financeiro-wrapper" style="max-width: 800px; margin: 40px auto;">
    <div class="header-actions">
        <h1>Gestão de Equipas & Acessos</h1>
        <a href="area_franqueado.php" class="btn btn-secondary" style="text-decoration: none;">← VOLTAR</a>
    </div>

    <p style="color: #666; margin-bottom: 20px;">Como franqueado, pode redefinir a senha de qualquer funcionário caso este se esqueça.</p>

    <?= $mensagem ?>

    <table class="table-financeiro">
        <thead>
            <tr style="background: #f4f4f4;">
                <th>ID</th>
                <th>Nome de Utilizador (Login)</th>
                <th style="text-align: center;">Ações de Segurança</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="color: #888;">#<?= $u['id'] ?></td>
                    <td style="font-weight: bold; color: #333; font-size: 16px;"><?= htmlspecialchars($u['username']) ?></td>
                    <td style="text-align: center;">
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px; background: #ffc107; color: #000; border: none;" onclick="abrirModalSenha(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                            🔑 Redefinir Senha
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="form-group">
    <label>Perfil de Acesso</label>
    <select name="perfil" class="form-control">
        <option value="colaborador">Colaborador</option>
        <option value="franqueado">Franqueado</option>
    </select>
</div>

<div id="modalSenha" class="modal-financeiro" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #fff; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <div class="modal-header" style="background: #ffc107; color: #000; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; border-bottom: none;">
            <h3 style="margin:0;">Alterar Senha</h3>
            <button onclick="fecharModalSenha()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="action" value="reset_senha">
            <input type="hidden" name="id_usuario" id="reset_id_usuario">
            
            <p style="margin-bottom: 15px; font-size: 14px;">Defina uma nova senha para o utilizador: <strong id="reset_nome_usuario" style="color: #007bff;"></strong></p>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nova Senha</label>
                <input type="text" name="nova_senha" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Ex: cacau123">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="fecharModalSenha()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" style="padding:10px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Guardar Nova Senha</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalSenha(id, username) {
    document.getElementById('reset_id_usuario').value = id;
    document.getElementById('reset_nome_usuario').innerText = username;
    document.getElementById('modalSenha').style.display = 'flex';
}

function fecharModalSenha() {
    document.getElementById('modalSenha').style.display = 'none';
}
</script>

<?php require '../includes/footer.php'; ?>