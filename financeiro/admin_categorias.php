<?php
require '../config.php';
require '../auth/auth_check.php';

$sessao_nome = "Plano de Contas"; 
$page_title = "Gestão de Categorias";
require '../includes/header.php';

// Busca todas as categorias
$categorias = $db_financeiro->query("SELECT * FROM categorias_financeiras ORDER BY tipo, grupo, nome")->fetchAll();

// Busca apenas os nomes das Categorias Mãe (Grupos) já existentes para sugerir no formulário
$grupos = $db_financeiro->query("SELECT DISTINCT grupo FROM categorias_financeiras WHERE grupo IS NOT NULL AND grupo != '' ORDER BY grupo")->fetchAll();
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<style>
    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .status-badge.despesa { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .status-badge.receita { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .btn-acao { cursor: pointer; border: none; background: none; font-size: 18px; margin: 0 4px; transition: transform 0.2s; }
    .btn-acao:hover { transform: scale(1.2); }
</style>

<div class="financeiro-container" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    <h2>Gestão de Categorias</h2>
    <p>Organize suas finanças criando Categorias Mãe e suas respetivas Sub-categorias.</p>

    <form id="formCategoria" onsubmit="salvarCategoria(event)" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
        <input type="hidden" name="action" value="salvar_categoria">
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group" style="display: flex; flex-direction: column;">
                <label style="font-weight:bold; font-size: 13px; margin-bottom:5px;">Tipo de Movimento</label>
                <select name="tipo" required style="padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="Despesa">Despesa (Saída)</option>
                    <option value="Receita">Receita (Entrada)</option>
                </select>
            </div>
            
            <div class="form-group" style="display: flex; flex-direction: column;">
                <label style="font-weight:bold; font-size: 13px; margin-bottom:5px;">Categoria Mãe</label>
                <input type="text" name="grupo" list="listaGrupos" required placeholder="Ex: Custos Operacionais, Despesas com Pessoal..." style="padding:10px; border:1px solid #ccc; border-radius:4px;">
                <datalist id="listaGrupos">
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= htmlspecialchars($g['grupo']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 15px;">
            <label style="font-weight:bold; font-size: 13px; margin-bottom:5px;">Nome da Sub-categoria</label>
            <input type="text" name="nome" required placeholder="Ex: Royalties, Salários, Energia Elétrica..." style="padding:10px; border:1px solid #ccc; border-radius:4px;">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Cadastrar na Árvore</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f4f4f4; border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left;">Categoria Mãe</th>
                <th style="padding: 10px; text-align: left;">Sub-categoria</th>
                <th style="padding: 10px; text-align: left;">Tipo</th>
                <th style="padding: 10px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categorias as $cat): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: bold; color: #444;">📂 <?= htmlspecialchars($cat['grupo']) ?></td>
                <td style="padding: 10px; color: #555;">↳ <?= htmlspecialchars($cat['nome']) ?></td>
                <td style="padding: 10px;"><span class="status-badge <?= strtolower($cat['tipo']) ?>"><?= $cat['tipo'] ?></span></td>
                <td style="padding: 10px; text-align: center; white-space: nowrap;">
                    <button class="btn-acao edit" onclick="abrirModalEditar(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['tipo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['grupo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['nome'], ENT_QUOTES) ?>')">✏️</button>
                    <button class="btn-acao delete" onclick="excluirCategoria(<?= $cat['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalEditarCategoria" class="modal-financeiro" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #fff; border-radius: 8px; width: 90%; max-width: 450px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <div class="modal-header" style="background: #ffc107; color: #000; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; border-bottom: none;">
            <h3 style="margin:0;">Editar Categoria</h3>
            <button onclick="fecharModalEditar()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        
        <form id="formEditarCategoria" onsubmit="salvarEdicao(event)" style="padding: 20px;">
            <input type="hidden" name="action" value="editar_categoria">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Tipo de Movimento</label>
                <select name="tipo" id="edit_tipo" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="Despesa">Despesa (Saída)</option>
                    <option value="Receita">Receita (Entrada)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Categoria Mãe</label>
                <input type="text" name="grupo" id="edit_grupo" list="listaGruposEdit" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                <datalist id="listaGruposEdit">
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= htmlspecialchars($g['grupo']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nome da Sub-categoria</label>
                <input type="text" name="nome" id="edit_nome" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="fecharModalEditar()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" style="padding:10px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Guardar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
// Agora os scripts apontam corretamente para o admin_categorias_actions.php
function salvarCategoria(e) {
    e.preventDefault();
    fetch('admin_categorias_actions.php', { method: 'POST', body: new FormData(e.target) })
    .then(res => res.json())
    .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
}

function excluirCategoria(id) {
    if(!confirm('Atenção: Eliminar esta categoria pode afetar o histórico financeiro se já estiver em uso. Continuar?')) return;
    const fd = new FormData();
    fd.append('action', 'excluir_categoria');
    fd.append('id', id);
    fetch('admin_categorias_actions.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
}

function abrirModalEditar(id, tipo, grupo, nome) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tipo').value = tipo;
    document.getElementById('edit_grupo').value = grupo;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('modalEditarCategoria').style.display = 'flex';
}

function fecharModalEditar() {
    document.getElementById('modalEditarCategoria').style.display = 'none';
}

function salvarEdicao(e) {
    e.preventDefault();
    fetch('admin_categorias_actions.php', { method: 'POST', body: new FormData(e.target) })
    .then(res => res.json())
    .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
}
</script>

<?php require '../includes/footer.php'; ?>