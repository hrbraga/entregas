<?php
require '../config.php';
require '../auth/auth_check.php';
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
    <h2>Gestão de Categorias (Plano de Contas)</h2>
    <p>Organize suas finanças criando Categorias Mãe e suas respectivas Sub-categorias.</p>

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
                <th style="padding: 10px; text-align: center;">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categorias as $cat): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: bold; color: #444;">📂 <?= htmlspecialchars($cat['grupo']) ?></td>
                <td style="padding: 10px; color: #555;">↳ <?= htmlspecialchars($cat['nome']) ?></td>
                <td style="padding: 10px;"><span class="status-badge <?= strtolower($cat['tipo']) ?>"><?= $cat['tipo'] ?></span></td>
                <td style="padding: 10px; text-align: center;">
                    <button class="btn-acao delete" onclick="excluirCategoria(<?= $cat['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function salvarCategoria(e) {
    e.preventDefault();
    fetch('contas_pagar_actions.php', { method: 'POST', body: new FormData(e.target) })
    .then(res => res.json())
    .then(data => { alert(data.message); location.reload(); });
}

function excluirCategoria(id) {
    if(!confirm('Atenção: Eliminar esta categoria pode afetar o histórico financeiro. Continuar?')) return;
    const fd = new FormData();
    fd.append('action', 'excluir_categoria');
    fd.append('id', id);
    fetch('contas_pagar_actions.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => { alert(data.message); location.reload(); });
}
</script>

<?php require '../includes/footer.php'; ?>