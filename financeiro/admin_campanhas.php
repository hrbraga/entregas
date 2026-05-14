<?php
require '../config.php';
require '../auth/auth_check.php';
require '../includes/header.php';

try {
    // Buscamos os IDs também para o JavaScript conseguir identificar quem vai editar/excluir
    $sql = "SELECT c.id as id_campanha, c.nome_campanha, cn.id as id_nivel, cn.nivel, cn.vencimento_nf, cn.vencimento_royalties 
            FROM campanhas c 
            JOIN campanhas_niveis cn ON c.id = cn.id_campanha 
            ORDER BY c.id DESC, cn.vencimento_nf ASC";
    $stmt = $db_financeiro->query($sql);
    $campanhas_salvas = $stmt->fetchAll();
} catch (Exception $e) {
    $campanhas_salvas = [];
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<style>
    .btn-acao { cursor: pointer; border: none; background: none; font-size: 18px; margin: 0 4px; transition: transform 0.2s; }
    .btn-acao:hover { transform: scale(1.2); }
</style>

<div class="financeiro-container" style="max-width: 900px;">
    <h2>Cadastro de Campanhas e Royalties</h2>
    <p>Defina as datas para cruzamento automático. O sistema buscará preferencialmente a <b>2ª Parcela</b> do XML para definir o nível.</p>
    <br>

    <form id="formCampanha" onsubmit="salvarCampanha(event)" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
        <input type="hidden" name="action" value="salvar">
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Nome da Campanha (Ex: MAES2026)</label>
            <input type="text" name="nome_campanha" required placeholder="Digite exatamente como vem na tag infCpl do XML">
        </div>

        <div id="container-niveis">
            <div class="linha-nivel" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; align-items: flex-end;">
                <div class="form-group">
                    <label>Nível</label>
                    <select name="nivel[]" required>
                        <option value="">Selecione...</option>
                        <option value="Ouro">Ouro</option>
                        <option value="Prata">Prata</option>
                        <option value="Bronze">Bronze</option>
                        <option value="Cobre">Cobre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Venc. Parcela Base</label>
                    <input type="date" name="venc_nf[]" required>
                </div>
                <div class="form-group">
                    <label>Vencimento Royalties</label>
                    <input type="date" name="venc_roy[]" required>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="adicionarLinha()" style="margin-top: 15px; font-size: 13px;">+ Adicionar Outro Nível</button>
        <hr style="margin: 20px 0; border: 1px solid #ccc;">
        <button type="submit" class="btn btn-primary" style="width: 100%; height: 45px; font-weight: bold;">Salvar Campanha e Níveis</button>
    </form>

    <h3>Níveis e Datas Cadastradas</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f4f4f4; border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left;">Campanha</th>
                <th style="padding: 10px; text-align: left;">Nível</th>
                <th style="padding: 10px; text-align: left;">Data Parcela Base</th>
                <th style="padding: 10px; text-align: left;">Data Royalty</th>
                <th style="padding: 10px; text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campanhas_salvas as $c): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px; font-weight: bold;">🎯 <?= htmlspecialchars($c['nome_campanha']) ?></td>
                <td style="padding: 10px;"><?= htmlspecialchars($c['nivel']) ?></td>
                <td style="padding: 10px;"><?= date('d/m/Y', strtotime($c['vencimento_nf'])) ?></td>
                <td style="padding: 10px;"><?= date('d/m/Y', strtotime($c['vencimento_royalties'])) ?></td>
                <td style="padding: 10px; text-align: center;">
                    <button class="btn-acao edit" onclick="abrirModalEditar(<?= $c['id_campanha'] ?>, <?= $c['id_nivel'] ?>, '<?= htmlspecialchars($c['nome_campanha'], ENT_QUOTES) ?>', '<?= htmlspecialchars($c['nivel'], ENT_QUOTES) ?>', '<?= $c['vencimento_nf'] ?>', '<?= $c['vencimento_royalties'] ?>')">✏️</button>
                    <button class="btn-acao delete" onclick="excluirNivel(<?= $c['id_nivel'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalEditarCampanha" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <div style="background: #ffc107; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between;">
            <h3 style="margin:0;">Editar Nível e Datas</h3>
            <button onclick="fecharModalEditar()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        
        <form id="formEditarCampanha" onsubmit="salvarEdicao(event)" style="padding: 20px;">
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="id_campanha" id="edit_id_campanha">
            <input type="hidden" name="id_nivel" id="edit_id_nivel">

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nome da Campanha</label>
                <input type="text" name="nome_campanha" id="edit_nome" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Nível</label>
                <select name="nivel_edit" id="edit_nivel" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="Ouro">Ouro</option>
                    <option value="Prata">Prata</option>
                    <option value="Bronze">Bronze</option>
                    <option value="Cobre">Cobre</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Data Parcela Base</label>
                    <input type="date" name="venc_nf_edit" id="edit_venc_nf" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="font-weight:bold; font-size: 13px; display:block; margin-bottom:5px;">Data Royalty</label>
                    <input type="date" name="venc_roy_edit" id="edit_venc_roy" required style="width: 100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="fecharModalEditar()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" style="padding:10px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Manutenção do formulário múltiplo original
    function adicionarLinha() {
        const container = document.getElementById('container-niveis');
        const novaLinha = document.createElement('div');
        novaLinha.className = 'linha-nivel';
        novaLinha.style = "display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end; margin-top: 10px;";
        novaLinha.innerHTML = `
            <div class="form-group">
                <select name="nivel[]" required>
                    <option value="">Selecione...</option>
                    <option value="Ouro">Ouro</option>
                    <option value="Prata">Prata</option>
                    <option value="Bronze">Bronze</option>
                    <option value="Cobre">Cobre</option>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="venc_nf[]" required>
            </div>
            <div class="form-group">
                <input type="date" name="venc_roy[]" required>
            </div>
            <button type="button" class="btn btn-danger" style="height: 38px; padding: 0 15px;" onclick="this.parentElement.remove()">X</button>
        `;
        container.appendChild(novaLinha);
    }

    function salvarCampanha(event) {
        event.preventDefault();
        fetch('admin_campanhas_actions.php', { method: 'POST', body: new FormData(event.target) })
        .then(res => res.json())
        .then(data => { alert(data.message); if (data.status === 'success') location.reload(); });
    }

    // --- NOVAS FUNÇÕES: EDITAR E EXCLUIR ---
    function abrirModalEditar(idCampanha, idNivel, nomeCampanha, nivel, vencNf, vencRoy) {
        document.getElementById('edit_id_campanha').value = idCampanha;
        document.getElementById('edit_id_nivel').value = idNivel;
        document.getElementById('edit_nome').value = nomeCampanha;
        document.getElementById('edit_nivel').value = nivel;
        document.getElementById('edit_venc_nf').value = vencNf;
        document.getElementById('edit_venc_roy').value = vencRoy;
        document.getElementById('modalEditarCampanha').style.display = 'flex';
    }

    function fecharModalEditar() {
        document.getElementById('modalEditarCampanha').style.display = 'none';
    }

    function salvarEdicao(event) {
        event.preventDefault();
        fetch('admin_campanhas_actions.php', { method: 'POST', body: new FormData(event.target) })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
    }

    function excluirNivel(idNivel) {
        if(!confirm('Deseja realmente apagar as configurações deste Nível?')) return;
        const fd = new FormData();
        fd.append('action', 'excluir');
        fd.append('id_nivel', idNivel);
        
        fetch('admin_campanhas_actions.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') location.reload(); });
    }
</script>

<?php require '../includes/footer.php'; ?>