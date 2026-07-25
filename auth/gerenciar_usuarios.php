<?php
require '../config.php';
require 'auth_franqueado_check.php';

$page_title = "Gestão de Equipas";
$sessao_nome = "Acessos";

require '../includes/header.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_usuario') {
    $id_alvo = $_POST['id_usuario'];
    $nova_senha = $_POST['nova_senha'];
    $novo_perfil = $_POST['perfil'];

    try {
        if (!empty($nova_senha)) {
            // Atualiza SENHA e PERFIL
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $db_users->prepare("UPDATE user SET password_hash = ?, perfil = ? WHERE id = ?");
            $stmt->execute([$senha_hash, $novo_perfil, $id_alvo]);
        } else {
            // Atualiza APENAS O PERFIL (mantém a senha intacta se o campo ficar em branco)
            $stmt = $db_users->prepare("UPDATE user SET perfil = ? WHERE id = ?");
            $stmt->execute([$novo_perfil, $id_alvo]);
        }
        $mensagem = "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;'>✅ Acessos do utilizador atualizados com sucesso!</div>";
    } catch (Exception $e) {
        $mensagem = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;'>❌ Erro ao atualizar: " . $e->getMessage() . "</div>";
    }
}

// Busca todos os utilizadores registados e seus perfis
try {
    $stmt = $db_users->query("SELECT id, username, perfil FROM user ORDER BY username ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<div class="financeiro-wrapper" style="max-width: 800px; margin: 40px auto;">
    <div class="header-actions">
        <h1>Gestão de Equipas & Acessos</h1>
        <a href="area_franqueado.php" class="btn btn-secondary" style="text-decoration: none;">← VOLTAR</a>
    </div>

    <p style="color: #666; margin-bottom: 20px;">Como franqueado master, você pode redefinir senhas e mudar os níveis de acesso (Perfis).</p>

    <?= $mensagem ?>

    <table class="table-financeiro">
        <thead>
            <tr style="background: #f4f4f4;">
                <th>ID</th>
                <th>Nome de Utilizador</th>
                <th>Perfil Atual</th>
                <th style="text-align: center;">Ações de Segurança</th>
                <th style="text-align: center;">Transferir</th>
                <th style="text-align: center;">Excluir</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="color: #888;">#<?= $u['id'] ?></td>
                    <td style="font-weight: bold; color: #333; font-size: 16px;"><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <?php if (isset($u['perfil']) && $u['perfil'] === 'franqueado'): ?>
                            <span style="background: #cce5ff; color: #004085; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Franqueado</span>
                        <?php else: ?>
                            <span style="background: #e2e3e5; color: #383d41; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Colaborador</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px; background: #ffc107; color: #000; border: none;" onclick="abrirModalEdicao(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['perfil'] ?? 'colaborador', ENT_QUOTES) ?>')">
                            ⚙️ Editar Acessos
                        </button>
                    </td>
                    <td>
                        <button onclick="transferirUsuario(<?= $u['id'] ?>, '<?= $u['loja'] ?>')">Transferir Loja</button>
                    </td>
                    <td>
                        <button onclick="excluirUsuario(<?= $u['id'] ?>)" style="color: red;">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalEdicao" class="modal-financeiro" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #fff; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <div class="modal-header" style="background: #ffc107; color: #000; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; border-bottom: none;">
            <h3 style="margin:0;">Editar Acessos</h3>
            <button onclick="fecharModalEdicao()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="action" value="editar_usuario">
            <input type="hidden" name="id_usuario" id="edit_id_usuario">

            <p style="margin-bottom: 15px; font-size: 14px;">Editando o utilizador: <strong id="edit_nome_usuario" style="color: #007bff;"></strong></p>

            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Perfil de Acesso</label>
                <select name="perfil" id="edit_perfil" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="colaborador">Colaborador (Quadro e Validades)</option>
                    <option value="franqueado">Franqueado (Acesso Total)</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nova Senha (Deixe em branco para não alterar)</label>
                <input type="text" name="nova_senha" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Ex: cacau123">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="fecharModalEdicao()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" style="padding:10px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Guardar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    function excluirUsuario(id) {
        if (confirm("Tem certeza que deseja excluir este usuário definitivamente?")) {
            fetch('../db/deletar_usuario.php?id=' + id, {
                    method: 'GET'
                })
                .then(response => {
                    alert("Usuário excluído com sucesso!");
                    window.location.reload(); // Recarrega a página para atualizar a tabela
                });
        }
    }
    // Função para Editar/Transferir
    function transferirUsuario(id, lojaAtual) {
        // Um prompt simples para digitar a nova loja
        let novaLoja = prompt("O usuário atualmente está na loja: " + lojaAtual + "\nDigite a nova loja para transferência (ex: Arena Esportes ou CS Revenda Show):");

        if (novaLoja && novaLoja.trim() !== "" && novaLoja !== lojaAtual) {
            let formData = new FormData();
            formData.append('id', id);
            formData.append('nova_loja', novaLoja);

            fetch('../db/atualizar_users.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                alert("Usuário transferido para a loja " + novaLoja + " com sucesso!");
                window.location.reload();
            });
        }
    }

    function abrirModalEdicao(id, username, perfil) {
        document.getElementById('edit_id_usuario').value = id;
        document.getElementById('edit_nome_usuario').innerText = username;

        // Seleciona o perfil correto no dropdown
        document.getElementById('edit_perfil').value = perfil;

        document.getElementById('modalEdicao').style.display = 'flex';
    }

    function fecharModalEdicao() {
        document.getElementById('modalEdicao').style.display = 'none';
    }
</script>

<?php require '../includes/footer.php'; ?>